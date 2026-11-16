<?php

namespace Modules\Learning\Services;

use App\Core\Audit\AuditLogger;
use App\Core\Support\HtmlSanitizer;
use App\Models\User;
use InvalidArgumentException;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\Question;
use Modules\Learning\Models\Quiz;
use Modules\Learning\QuestionType;

class QuestionBankService
{
    public function __construct(
        private readonly HtmlSanitizer $sanitizer,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array{
     *   type: string|QuestionType,
     *   stem: string,
     *   options?: ?array<int, string>,
     *   correct_answer?: mixed,
     *   points?: int,
     *   needs_manual_mark?: bool
     * }  $data
     */
    public function createQuestion(Course $course, array $data, User $author): Question
    {
        $type = $data['type'] instanceof QuestionType
            ? $data['type']
            : QuestionType::from((string) $data['type']);

        $needsManual = array_key_exists('needs_manual_mark', $data)
            ? (bool) $data['needs_manual_mark']
            : $type->needsManualMarkByDefault();

        $this->assertQuestionPayload($type, $data['options'] ?? null, $data['correct_answer'] ?? null, $needsManual);

        $question = Question::query()->create([
            'course_id' => $course->id,
            'type' => $type,
            'stem' => $this->sanitizer->clean($data['stem']),
            'options' => $data['options'] ?? null,
            'correct_answer' => $this->normaliseCorrectAnswer($type, $data['correct_answer'] ?? null),
            'points' => max(1, (int) ($data['points'] ?? 1)),
            'needs_manual_mark' => $needsManual,
            'created_by' => $author->id,
        ]);

        $this->audit->record('learning.question.created', $question, [
            'course_id' => $course->id,
            'type' => $type->value,
        ], $author);

        return $question;
    }

    /**
     * @param  array{
     *   title: string,
     *   instructions?: ?string,
     *   attempt_limit?: ?int,
     *   time_limit_seconds?: ?int,
     *   random_draw_count?: ?int,
     *   lesson_id?: ?int
     * }  $data
     */
    public function createQuiz(Course $course, array $data, User $author): Quiz
    {
        $quiz = Quiz::query()->create([
            'course_id' => $course->id,
            'lesson_id' => $data['lesson_id'] ?? null,
            'title' => trim($data['title']),
            'instructions' => $this->sanitizer->clean($data['instructions'] ?? null),
            'attempt_limit' => $data['attempt_limit'] ?? null,
            'time_limit_seconds' => $data['time_limit_seconds'] ?? null,
            'random_draw_count' => $data['random_draw_count'] ?? null,
            'status' => Quiz::STATUS_DRAFT,
            'created_by' => $author->id,
        ]);

        $this->audit->record('learning.quiz.created', $quiz, [
            'course_id' => $course->id,
        ], $author);

        return $quiz;
    }

    /**
     * @param  list<int>  $questionIds
     */
    public function attachQuestions(Quiz $quiz, array $questionIds): Quiz
    {
        $questions = Question::query()
            ->where('course_id', $quiz->course_id)
            ->whereIn('id', $questionIds)
            ->get()
            ->keyBy('id');

        if ($questions->count() !== count(array_unique($questionIds))) {
            throw new InvalidArgumentException('Every question must belong to the quiz course.');
        }

        $sync = [];
        foreach (array_values(array_unique($questionIds)) as $index => $id) {
            $sync[$id] = ['sort_order' => $index + 1];
        }

        $quiz->questions()->sync($sync);

        return $quiz->refresh()->load('questions');
    }

    public function publishQuiz(Quiz $quiz, User $actor): Quiz
    {
        if ($quiz->questions()->count() === 0) {
            throw new InvalidArgumentException('A quiz needs at least one question before publishing.');
        }

        if ($quiz->random_draw_count !== null && $quiz->random_draw_count > $quiz->questions()->count()) {
            throw new InvalidArgumentException('Random draw count cannot exceed the attached question pool.');
        }

        $quiz->update(['status' => Quiz::STATUS_PUBLISHED]);

        $this->audit->record('learning.quiz.published', $quiz, [
            'course_id' => $quiz->course_id,
        ], $actor);

        return $quiz->refresh();
    }

    public function grantExtraTime(Quiz $quiz, User $learner, int $extraSeconds, User $actor): void
    {
        if ($extraSeconds < 1) {
            throw new InvalidArgumentException('Extra time must be at least one second.');
        }

        $quiz->extraTimes()->updateOrCreate(
            ['user_id' => $learner->id],
            [
                'extra_seconds' => $extraSeconds,
                'granted_by' => $actor->id,
            ],
        );

        $this->audit->record('learning.quiz.extra_time', $quiz, [
            'user_id' => $learner->id,
            'extra_seconds' => $extraSeconds,
        ], $actor);
    }

    /**
     * @param  ?array<int, string>  $options
     */
    private function assertQuestionPayload(QuestionType $type, ?array $options, mixed $correct, bool $needsManual): void
    {
        match ($type) {
            QuestionType::Mcq => $this->assertMcq($options, $correct),
            QuestionType::TrueFalse => $this->assertTrueFalse($correct),
            QuestionType::ShortAnswer => $this->assertShortAnswer($correct),
            QuestionType::Essay => $this->assertEssay($needsManual),
        };
    }

    /**
     * @param  ?array<int, string>  $options
     */
    private function assertMcq(?array $options, mixed $correct): void
    {
        if ($options === null || count($options) < 2) {
            throw new InvalidArgumentException('Multiple choice needs at least two options.');
        }

        if (! is_string($correct) && ! is_int($correct)) {
            throw new InvalidArgumentException('Multiple choice needs a correct option key.');
        }

        $key = (string) $correct;
        if (! array_key_exists($key, $options) && ! in_array($key, array_map('strval', array_keys($options)), true)) {
            // Allow numeric index or associative key.
            if (! isset($options[$correct])) {
                throw new InvalidArgumentException('Correct answer must match an option key.');
            }
        }
    }

    private function assertTrueFalse(mixed $correct): void
    {
        if (! is_bool($correct) && ! in_array($correct, [0, 1, '0', '1', 'true', 'false'], true)) {
            throw new InvalidArgumentException('True/false needs a boolean correct answer.');
        }
    }

    private function assertShortAnswer(mixed $correct): void
    {
        if (! is_string($correct) || trim($correct) === '') {
            throw new InvalidArgumentException('Short answer needs a non-empty correct answer.');
        }
    }

    private function assertEssay(bool $needsManual): void
    {
        if (! $needsManual) {
            throw new InvalidArgumentException('Essay questions must be marked manually.');
        }
    }

    private function normaliseCorrectAnswer(QuestionType $type, mixed $correct): ?array
    {
        return match ($type) {
            QuestionType::Mcq => ['value' => (string) $correct],
            QuestionType::TrueFalse => ['value' => filter_var($correct, FILTER_VALIDATE_BOOLEAN)],
            QuestionType::ShortAnswer => ['value' => mb_strtolower(trim((string) $correct))],
            QuestionType::Essay => null,
        };
    }
}
