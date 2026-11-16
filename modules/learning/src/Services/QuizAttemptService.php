<?php

namespace Modules\Learning\Services;

use App\Core\Audit\AuditLogger;
use App\Core\Support\HtmlSanitizer;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Learning\Models\Question;
use Modules\Learning\Models\Quiz;
use Modules\Learning\Models\QuizAttempt;
use Modules\Learning\Models\QuizAttemptAnswer;
use Modules\Learning\QuestionType;
use Modules\Learning\QuizAttemptStatus;
use RuntimeException;

class QuizAttemptService
{
    public function __construct(
        private readonly EnrolmentService $enrolments,
        private readonly LearningAssignmentService $assignments,
        private readonly HtmlSanitizer $sanitizer,
        private readonly AuditLogger $audit,
    ) {}

    public function effectiveTimeLimitSeconds(Quiz $quiz, User $learner): ?int
    {
        if ($quiz->time_limit_seconds === null) {
            return null;
        }

        $extra = (int) $quiz->extraTimes()
            ->where('user_id', $learner->id)
            ->value('extra_seconds');

        return $quiz->time_limit_seconds + $extra;
    }

    public function remainingAttempts(Quiz $quiz, User $learner): ?int
    {
        if ($quiz->attempt_limit === null) {
            return null;
        }

        $used = $quiz->attempts()
            ->where('user_id', $learner->id)
            ->whereIn('status', [
                QuizAttemptStatus::Submitted->value,
                QuizAttemptStatus::Expired->value,
                QuizAttemptStatus::InProgress->value,
            ])
            ->count();

        return max(0, $quiz->attempt_limit - $used);
    }

    public function startAttempt(Quiz $quiz, User $learner): QuizAttempt
    {
        if (! $quiz->isPublished()) {
            throw new RuntimeException('Only published quizzes can be attempted.');
        }

        if (! $this->enrolments->canAccess($learner, $quiz->course)) {
            throw new RuntimeException('You are not enrolled on this course.');
        }

        $open = $quiz->attempts()
            ->where('user_id', $learner->id)
            ->where('status', QuizAttemptStatus::InProgress)
            ->first();

        if ($open !== null) {
            if ($open->due_at !== null && $open->due_at->isPast()) {
                $this->expireAttempt($open);

                return $this->startAttempt($quiz->refresh(), $learner);
            }

            return $open;
        }

        $remaining = $this->remainingAttempts($quiz, $learner);
        if ($remaining === 0) {
            throw new RuntimeException('No attempts remaining for this quiz.');
        }

        $drawn = $this->drawQuestions($quiz);
        if ($drawn->isEmpty()) {
            throw new RuntimeException('This quiz has no questions.');
        }

        $limit = $this->effectiveTimeLimitSeconds($quiz, $learner);
        $startedAt = now();

        $attempt = QuizAttempt::query()->create([
            'quiz_id' => $quiz->id,
            'user_id' => $learner->id,
            'attempt_number' => (int) $quiz->attempts()->where('user_id', $learner->id)->max('attempt_number') + 1,
            'status' => QuizAttemptStatus::InProgress,
            'started_at' => $startedAt,
            'due_at' => $limit === null ? null : $startedAt->copy()->addSeconds($limit),
            'drawn_question_ids' => $drawn->pluck('id')->all(),
        ]);

        $this->audit->record('learning.quiz.attempt.started', $attempt, [
            'quiz_id' => $quiz->id,
            'drawn' => $drawn->pluck('id')->all(),
            'due_at' => $attempt->due_at?->toIso8601String(),
        ], $learner);

        return $attempt;
    }

    /**
     * @param  array<int|string, string|null>  $responses  question_id => response
     */
    public function submitAttempt(QuizAttempt $attempt, array $responses, User $learner): QuizAttempt
    {
        if ($attempt->user_id !== $learner->id) {
            throw new RuntimeException('This attempt belongs to another learner.');
        }

        if ($attempt->status !== QuizAttemptStatus::InProgress) {
            throw new RuntimeException('This attempt is already closed.');
        }

        if ($attempt->due_at !== null && $attempt->due_at->isPast()) {
            return $this->expireAttempt($attempt);
        }

        return DB::transaction(function () use ($attempt, $responses, $learner): QuizAttempt {
            $questions = Question::query()
                ->whereIn('id', $attempt->drawn_question_ids ?? [])
                ->get()
                ->keyBy('id');

            $autoScore = 0.0;

            foreach ($questions as $question) {
                $raw = $responses[$question->id] ?? $responses[(string) $question->id] ?? null;
                $response = is_string($raw) ? $this->sanitizer->clean($raw) : null;
                [$isCorrect, $score, $needsManual] = $this->scoreResponse($question, $response);

                QuizAttemptAnswer::query()->updateOrCreate(
                    [
                        'attempt_id' => $attempt->id,
                        'question_id' => $question->id,
                    ],
                    [
                        'response' => $response,
                        'needs_manual_mark' => $needsManual,
                        'is_correct' => $needsManual ? null : $isCorrect,
                        'score' => $needsManual ? null : $score,
                        'marked_at' => $needsManual ? null : now(),
                        'marked_by' => $needsManual ? null : null,
                    ],
                );

                if (! $needsManual && $score !== null) {
                    $autoScore += (float) $score;
                }
            }

            $attempt->update([
                'status' => QuizAttemptStatus::Submitted,
                'submitted_at' => now(),
                'auto_score' => $autoScore,
            ]);

            $this->audit->record('learning.quiz.attempt.submitted', $attempt, [
                'quiz_id' => $attempt->quiz_id,
                'auto_score' => $autoScore,
            ], $learner);

            return $attempt->refresh()->load('answers');
        });
    }

    public function expireAttempt(QuizAttempt $attempt): QuizAttempt
    {
        if ($attempt->status !== QuizAttemptStatus::InProgress) {
            return $attempt;
        }

        $attempt->update([
            'status' => QuizAttemptStatus::Expired,
            'submitted_at' => now(),
            'auto_score' => $attempt->auto_score ?? 0,
        ]);

        $this->audit->record('learning.quiz.attempt.expired', $attempt, [
            'quiz_id' => $attempt->quiz_id,
        ], $attempt->user);

        return $attempt->refresh();
    }

    /**
     * Answers awaiting a human marker for a course (essay / manual).
     *
     * @return Collection<int, QuizAttemptAnswer>
     */
    public function manualMarkQueue(?int $courseId = null): Collection
    {
        $query = QuizAttemptAnswer::query()
            ->with(['question', 'attempt.user', 'attempt.quiz'])
            ->where('needs_manual_mark', true)
            ->whereNull('marked_at')
            ->whereHas('attempt', fn ($q) => $q->where('status', QuizAttemptStatus::Submitted));

        if ($courseId !== null) {
            $query->whereHas('attempt.quiz', fn ($q) => $q->where('course_id', $courseId));
        }

        return $query->orderBy('id')->get();
    }

    public function markAnswer(
        QuizAttemptAnswer $answer,
        User $marker,
        float $score,
        ?string $feedback = null,
        ?bool $isCorrect = null,
    ): QuizAttemptAnswer {
        if (! $answer->awaitsManualMark()) {
            throw new RuntimeException('This answer is not waiting for a manual mark.');
        }

        $courseId = $answer->attempt->quiz->course_id;
        if (! $this->assignments->canEditGrades($marker, $courseId)) {
            throw new RuntimeException('You do not have a scoped learning role that can mark this answer.');
        }

        $max = (float) $answer->question->points;
        if ($score < 0 || $score > $max) {
            throw new InvalidArgumentException("Score must be between 0 and {$max}.");
        }

        $answer->update([
            'score' => $score,
            'is_correct' => $isCorrect ?? ($score >= $max),
            'marked_by' => $marker->id,
            'marked_at' => now(),
            'marker_feedback' => $feedback !== null ? $this->sanitizer->clean($feedback) : null,
        ]);

        $attempt = $answer->attempt()->with('answers')->firstOrFail();
        $manual = (float) $attempt->answers()->whereNotNull('score')->where('needs_manual_mark', true)->sum('score');
        $attempt->update(['manual_score' => $manual]);

        $this->audit->record('learning.quiz.answer.marked', $answer, [
            'attempt_id' => $attempt->id,
            'score' => $score,
        ], $marker);

        return $answer->refresh();
    }

    /**
     * @return Collection<int, Question>
     */
    private function drawQuestions(Quiz $quiz): Collection
    {
        $pool = $quiz->questions()->get();
        if ($quiz->random_draw_count === null) {
            return $pool->values();
        }

        $count = min($quiz->random_draw_count, $pool->count());

        return $pool->shuffle()->take($count)->values();
    }

    /**
     * @return array{0: ?bool, 1: ?float, 2: bool} is_correct, score, needs_manual
     */
    private function scoreResponse(Question $question, ?string $response): array
    {
        if ($question->needs_manual_mark || $question->type === QuestionType::Essay) {
            return [null, null, true];
        }

        $expected = $question->correct_answer['value'] ?? null;
        $given = $response === null ? '' : trim($response);
        $type = $question->type;

        if ($type === QuestionType::Mcq) {
            $correct = (string) $expected === (string) $given;
        } elseif ($type === QuestionType::TrueFalse) {
            $correct = filter_var($given, FILTER_VALIDATE_BOOLEAN) === (bool) $expected;
        } elseif ($type === QuestionType::ShortAnswer) {
            $correct = mb_strtolower($given) === mb_strtolower((string) $expected);
        } else {
            throw new RuntimeException('Unsupported auto-mark question type.');
        }

        return [$correct, $correct ? (float) $question->points : 0.0, false];
    }
}
