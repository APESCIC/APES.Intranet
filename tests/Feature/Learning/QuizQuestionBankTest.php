<?php

use App\Core\Access\RoleName;
use App\Models\User;
use Database\Seeders\LearningPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Learning\LearningPermissions;
use Modules\Learning\LearningRole;
use Modules\Learning\LearningScope;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\Question;
use Modules\Learning\Models\Quiz;
use Modules\Learning\Models\QuizAttempt;
use Modules\Learning\QuestionType;
use Modules\Learning\QuizAttemptStatus;
use Modules\Learning\Services\CourseAuthoringService;
use Modules\Learning\Services\EnrolmentService;
use Modules\Learning\Services\LearningAssignmentService;
use Modules\Learning\Services\QuestionBankService;
use Modules\Learning\Services\QuizAttemptService;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(LearningPermissionsSeeder::class);
});

function quizAuthor(): User
{
    $user = User::factory()->create();
    $user->assignRole(RoleName::Admin->value);
    $user->givePermissionTo([
        LearningPermissions::ACCESS,
        LearningPermissions::COURSE_AUTHOR,
        LearningPermissions::ENROL_MANAGE,
        LearningPermissions::ASSIGNMENTS_MANAGE,
        LearningPermissions::GRADE_VIEW,
    ]);

    return $user;
}

function quizLearner(): User
{
    $user = User::factory()->create();
    $user->assignRole(RoleName::Staff->value);
    $user->givePermissionTo(LearningPermissions::ACCESS);

    return $user;
}

/**
 * @return array{0: Course, 1: Quiz, 2: QuestionBankService, 3: QuizAttemptService}
 */
function publishedQuizWithPool(User $author, int $attemptLimit = 2, ?int $timeLimit = 60, ?int $draw = null): array
{
    $authoring = app(CourseAuthoringService::class);
    $bank = app(QuestionBankService::class);
    $attempts = app(QuizAttemptService::class);

    $course = $authoring->createCourse(['title' => 'Quiz course'], $author);
    $course->update(['status' => Course::STATUS_PUBLISHED]);

    $mcq = $bank->createQuestion($course, [
        'type' => QuestionType::Mcq,
        'stem' => '<p>Capital of Wales?</p>',
        'options' => ['a' => 'Cardiff', 'b' => 'Swansea'],
        'correct_answer' => 'a',
        'points' => 2,
    ], $author);

    $tf = $bank->createQuestion($course, [
        'type' => QuestionType::TrueFalse,
        'stem' => '<p>APES is a CIC?</p>',
        'correct_answer' => true,
        'points' => 1,
    ], $author);

    $short = $bank->createQuestion($course, [
        'type' => QuestionType::ShortAnswer,
        'stem' => '<p>Short code for learning?</p>',
        'correct_answer' => 'lms',
        'points' => 1,
    ], $author);

    $essay = $bank->createQuestion($course, [
        'type' => QuestionType::Essay,
        'stem' => '<p>Reflect on safeguarding.</p>',
        'points' => 5,
    ], $author);

    $quiz = $bank->createQuiz($course, [
        'title' => 'Unit quiz',
        'attempt_limit' => $attemptLimit,
        'time_limit_seconds' => $timeLimit,
        'random_draw_count' => $draw,
    ], $author);

    $bank->attachQuestions($quiz, [$mcq->id, $tf->id, $short->id, $essay->id]);
    $bank->publishQuiz($quiz, $author);

    return [$course->refresh(), $quiz->refresh(), $bank, $attempts];
}

it('enforces attempt limits and time limits on start and submit', function () {
    $author = quizAuthor();
    $learner = quizLearner();
    [$course, $quiz, , $attempts] = publishedQuizWithPool($author, attemptLimit: 1, timeLimit: 30);

    app(EnrolmentService::class)->enrolManual($course, $learner, $author);

    $first = $attempts->startAttempt($quiz, $learner);
    expect($first->status)->toBe(QuizAttemptStatus::InProgress)
        ->and($first->due_at)->not->toBeNull()
        ->and($first->due_at->equalTo($first->started_at->copy()->addSeconds(30)))->toBeTrue()
        ->and($attempts->remainingAttempts($quiz, $learner))->toBe(0)
        ->and($attempts->startAttempt($quiz, $learner)->id)->toBe($first->id);

    $submitted = $attempts->submitAttempt($first, [
        $first->drawn_question_ids[0] => 'a',
    ], $learner);

    expect($submitted->status)->toBe(QuizAttemptStatus::Submitted)
        ->and($attempts->remainingAttempts($quiz, $learner))->toBe(0);

    expect(fn () => $attempts->startAttempt($quiz, $learner))->toThrow(RuntimeException::class);
});

it('applies per-learner extra time to the attempt due time', function () {
    $author = quizAuthor();
    $learner = quizLearner();
    $other = quizLearner();
    [$course, $quiz, $bank, $attempts] = publishedQuizWithPool($author, attemptLimit: 2, timeLimit: 60);

    app(EnrolmentService::class)->enrolManual($course, $learner, $author);
    app(EnrolmentService::class)->enrolManual($course, $other, $author);

    $bank->grantExtraTime($quiz, $learner, 120, $author);

    expect($attempts->effectiveTimeLimitSeconds($quiz, $learner))->toBe(180)
        ->and($attempts->effectiveTimeLimitSeconds($quiz, $other))->toBe(60);

    $learnerAttempt = $attempts->startAttempt($quiz, $learner);
    $otherAttempt = $attempts->startAttempt($quiz, $other);

    expect($learnerAttempt->due_at->equalTo($learnerAttempt->started_at->copy()->addSeconds(180)))->toBeTrue()
        ->and($otherAttempt->due_at->equalTo($otherAttempt->started_at->copy()->addSeconds(60)))->toBeTrue();
});

it('queues essay answers for manual marking and accepts a scoped marker', function () {
    $author = quizAuthor();
    $learner = quizLearner();
    $marker = User::factory()->create();
    $marker->assignRole(RoleName::Staff->value);
    $marker->givePermissionTo([LearningPermissions::ACCESS, LearningPermissions::GRADE_VIEW]);

    [$course, $quiz, , $attempts] = publishedQuizWithPool($author, attemptLimit: 3, timeLimit: null, draw: null);

    app(EnrolmentService::class)->enrolManual($course, $learner, $author);
    app(LearningAssignmentService::class)->assign(
        $marker,
        LearningRole::Assessor,
        LearningScope::Course,
        $course->id,
        $author,
    );

    $attempt = $attempts->startAttempt($quiz, $learner);
    $questions = Question::query()
        ->whereIn('id', $attempt->drawn_question_ids)
        ->get()
        ->keyBy('id');

    $responses = [];
    foreach ($questions as $id => $question) {
        $responses[$id] = match ($question->type) {
            QuestionType::Mcq => 'a',
            QuestionType::TrueFalse => '1',
            QuestionType::ShortAnswer => 'lms',
            QuestionType::Essay => '<p>My reflection on safeguarding practice.</p>',
        };
    }

    $submitted = $attempts->submitAttempt($attempt, $responses, $learner);
    expect($submitted->status)->toBe(QuizAttemptStatus::Submitted)
        ->and((float) $submitted->auto_score)->toBe(4.0);

    $queue = $attempts->manualMarkQueue($course->id);
    expect($queue)->toHaveCount(1)
        ->and($queue->first()->awaitsManualMark())->toBeTrue()
        ->and($queue->first()->question->type)->toBe(QuestionType::Essay);

    $pending = $queue->first();
    $adminOnly = quizAuthor();
    expect(fn () => $attempts->markAnswer($pending, $adminOnly, 1.0))
        ->toThrow(RuntimeException::class);

    $marked = $attempts->markAnswer($pending->fresh(), $marker, 4.0, '<p>Good</p>');
    expect($marked->awaitsManualMark())->toBeFalse()
        ->and((float) $marked->score)->toBe(4.0)
        ->and($attempts->manualMarkQueue($course->id))->toHaveCount(0)
        ->and((float) $submitted->refresh()->manual_score)->toBe(4.0);
});

it('draws a random subset when random_draw_count is set', function () {
    $author = quizAuthor();
    $learner = quizLearner();
    [$course, $quiz, , $attempts] = publishedQuizWithPool($author, attemptLimit: 5, timeLimit: null, draw: 2);

    app(EnrolmentService::class)->enrolManual($course, $learner, $author);

    $attempt = $attempts->startAttempt($quiz, $learner);
    expect($attempt->drawn_question_ids)->toHaveCount(2)
        ->and(QuizAttempt::query()->whereKey($attempt->id)->exists())->toBeTrue();
});

it('lets an enrolled learner open the quiz play page', function () {
    $author = quizAuthor();
    $learner = quizLearner();
    [$course, $quiz] = publishedQuizWithPool($author);

    app(EnrolmentService::class)->enrolManual($course, $learner, $author);

    $this->actingAs($learner)
        ->get(route('learning.play.quiz', [$course, $quiz]))
        ->assertOk()
        ->assertSee('Unit quiz')
        ->assertSee('Start attempt');
});
