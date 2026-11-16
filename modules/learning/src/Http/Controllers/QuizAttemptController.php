<?php

namespace Modules\Learning\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\Question;
use Modules\Learning\Models\Quiz;
use Modules\Learning\Models\QuizAttempt;
use Modules\Learning\Services\EnrolmentService;
use Modules\Learning\Services\QuizAttemptService;
use Throwable;

class QuizAttemptController extends Controller
{
    public function __construct(
        private readonly QuizAttemptService $attempts,
        private readonly EnrolmentService $enrolments,
    ) {}

    public function show(Course $course, Quiz $quiz): View|RedirectResponse
    {
        abort_unless($quiz->course_id === $course->id, 404);
        abort_unless($quiz->isPublished(), 404);

        $user = request()->user();
        abort_unless($this->enrolments->canAccess($user, $course), 403);

        return view('learning::play.quiz', [
            'course' => $course,
            'quiz' => $quiz,
            'remaining' => $this->attempts->remainingAttempts($quiz, $user),
            'effectiveSeconds' => $this->attempts->effectiveTimeLimitSeconds($quiz, $user),
            'openAttempt' => $quiz->attempts()
                ->where('user_id', $user->id)
                ->where('status', 'in_progress')
                ->latest('id')
                ->first(),
        ]);
    }

    public function start(Request $request, Course $course, Quiz $quiz): RedirectResponse
    {
        abort_unless($quiz->course_id === $course->id, 404);

        try {
            $attempt = $this->attempts->startAttempt($quiz, $request->user());
        } catch (Throwable $e) {
            return back()->withErrors(['attempt' => $e->getMessage()]);
        }

        return redirect()->route('learning.play.quiz.attempt', [$course, $quiz, $attempt]);
    }

    public function attempt(Course $course, Quiz $quiz, QuizAttempt $attempt): View
    {
        abort_unless($quiz->course_id === $course->id, 404);
        abort_unless($attempt->quiz_id === $quiz->id, 404);
        abort_unless($attempt->user_id === request()->user()->id, 403);

        $questions = Question::query()
            ->whereIn('id', $attempt->drawn_question_ids ?? [])
            ->get()
            ->sortBy(fn (Question $q) => array_search($q->id, $attempt->drawn_question_ids ?? [], true))
            ->values();

        return view('learning::play.quiz-attempt', [
            'course' => $course,
            'quiz' => $quiz,
            'attempt' => $attempt,
            'questions' => $questions,
        ]);
    }

    public function submit(Request $request, Course $course, Quiz $quiz, QuizAttempt $attempt): RedirectResponse
    {
        abort_unless($quiz->course_id === $course->id, 404);
        abort_unless($attempt->quiz_id === $quiz->id, 404);

        $responses = $request->input('answers', []);
        if (! is_array($responses)) {
            $responses = [];
        }

        try {
            $this->attempts->submitAttempt($attempt, $responses, $request->user());
        } catch (Throwable $e) {
            return back()->withErrors(['attempt' => $e->getMessage()]);
        }

        return redirect()
            ->route('learning.play.quiz', [$course, $quiz])
            ->with('status', 'Attempt submitted.');
    }
}
