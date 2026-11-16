<?php

namespace Modules\Learning\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\Quiz;
use Modules\Learning\QuestionType;
use Modules\Learning\Services\QuestionBankService;
use Modules\Learning\Services\QuizAttemptService;
use Throwable;

class QuizAuthoringController extends Controller
{
    public function __construct(
        private readonly QuestionBankService $bank,
        private readonly QuizAttemptService $attempts,
    ) {}

    public function index(Course $course): View
    {
        Gate::authorize('update', $course);

        $course->load(['questions', 'quizzes.questions']);

        return view('learning::courses.quizzes.index', [
            'course' => $course,
            'questionTypes' => QuestionType::cases(),
        ]);
    }

    public function storeQuestion(Request $request, Course $course): RedirectResponse
    {
        Gate::authorize('update', $course);

        $data = $request->validate([
            'type' => ['required', 'string'],
            'stem' => ['required', 'string'],
            'options' => ['nullable', 'array'],
            'options.*' => ['nullable', 'string'],
            'correct_answer' => ['nullable'],
            'points' => ['nullable', 'integer', 'min:1'],
        ]);

        if (($data['type'] ?? '') === QuestionType::TrueFalse->value) {
            $data['correct_answer'] = filter_var($data['correct_answer'] ?? false, FILTER_VALIDATE_BOOLEAN);
        }

        try {
            $this->bank->createQuestion($course, $data, $request->user());
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['question' => $e->getMessage()]);
        }

        return back()->with('status', 'Question added to the bank.');
    }

    public function storeQuiz(Request $request, Course $course): RedirectResponse
    {
        Gate::authorize('update', $course);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
            'attempt_limit' => ['nullable', 'integer', 'min:1'],
            'time_limit_seconds' => ['nullable', 'integer', 'min:1'],
            'random_draw_count' => ['nullable', 'integer', 'min:1'],
            'question_ids' => ['nullable', 'array'],
            'question_ids.*' => ['integer'],
        ]);

        try {
            $quiz = $this->bank->createQuiz($course, $data, $request->user());
            if (! empty($data['question_ids'])) {
                $this->bank->attachQuestions($quiz, array_map('intval', $data['question_ids']));
            }
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['quiz' => $e->getMessage()]);
        }

        return back()->with('status', 'Quiz created.');
    }

    public function publish(Request $request, Course $course, Quiz $quiz): RedirectResponse
    {
        Gate::authorize('update', $course);
        abort_unless($quiz->course_id === $course->id, 404);

        try {
            $this->bank->publishQuiz($quiz, $request->user());
        } catch (Throwable $e) {
            return back()->withErrors(['quiz' => $e->getMessage()]);
        }

        return back()->with('status', 'Quiz published.');
    }

    public function storeExtraTime(Request $request, Course $course, Quiz $quiz): RedirectResponse
    {
        Gate::authorize('update', $course);
        abort_unless($quiz->course_id === $course->id, 404);

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'extra_seconds' => ['required', 'integer', 'min:1'],
        ]);

        $learner = User::query()->findOrFail($data['user_id']);

        try {
            $this->bank->grantExtraTime($quiz, $learner, (int) $data['extra_seconds'], $request->user());
        } catch (Throwable $e) {
            return back()->withErrors(['extra_time' => $e->getMessage()]);
        }

        return back()->with('status', 'Extra time granted.');
    }

    public function markQueue(Course $course): View
    {
        Gate::authorize('update', $course);
        Gate::authorize('learning.grade.edit', $course->id);

        return view('learning::courses.quizzes.mark-queue', [
            'course' => $course,
            'queue' => $this->attempts->manualMarkQueue($course->id),
        ]);
    }

    public function markAnswer(Request $request, Course $course, Quiz $quiz, int $answer): RedirectResponse
    {
        Gate::authorize('learning.grade.edit', $course->id);
        abort_unless($quiz->course_id === $course->id, 404);

        $data = $request->validate([
            'score' => ['required', 'numeric', 'min:0'],
            'feedback' => ['nullable', 'string'],
        ]);

        $row = $quiz->attempts()
            ->with('answers')
            ->get()
            ->flatMap(fn ($a) => $a->answers)
            ->firstWhere('id', $answer);

        abort_if($row === null, 404);

        try {
            $this->attempts->markAnswer($row, $request->user(), (float) $data['score'], $data['feedback'] ?? null);
        } catch (Throwable $e) {
            return back()->withErrors(['mark' => $e->getMessage()]);
        }

        return back()->with('status', 'Answer marked.');
    }
}
