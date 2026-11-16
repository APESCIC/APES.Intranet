<?php

use App\Core\Access\RoleName;
use App\Models\User;
use Database\Seeders\LearningPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\Learning\LearningPermissions;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\Lesson;
use Modules\Learning\Models\LessonProgress;
use Modules\Learning\Services\CourseAuthoringService;
use Modules\Learning\Services\EnrolmentService;
use Modules\Learning\Services\ProgressService;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(LearningPermissionsSeeder::class);
});

function progressAuthor(): User
{
    $user = User::factory()->create();
    $user->assignRole(RoleName::Admin->value);
    $user->givePermissionTo([
        LearningPermissions::ACCESS,
        LearningPermissions::COURSE_AUTHOR,
        LearningPermissions::ENROL_MANAGE,
    ]);

    return $user;
}

function progressLearner(): User
{
    $user = User::factory()->create();
    $user->assignRole(RoleName::Staff->value);
    $user->givePermissionTo(LearningPermissions::ACCESS);

    return $user;
}

/**
 * @return array{0: Course, 1: list<Lesson>}
 */
function twoLessonCourse(User $author): array
{
    $authoring = app(CourseAuthoringService::class);
    $course = $authoring->createCourse(['title' => 'Progress course'], $author);
    $section = $authoring->addSection($course, 'Core');
    $l1 = $authoring->addLesson($section, 'One', '<p>First</p>');
    $l2 = $authoring->addLesson($section, 'Two', '<p>Second</p>');
    $authoring->publishLesson($l1, $author);
    $authoring->publishLesson($l2, $author);

    return [$course->refresh(), [$l1->refresh(), $l2->refresh()]];
}

it('computes lesson and course completion from progress without any grade fields', function () {
    $author = progressAuthor();
    $learner = progressLearner();
    [$course, $lessons] = twoLessonCourse($author);

    app(EnrolmentService::class)->enrolManual($course, $learner, $author);
    $progress = app(ProgressService::class);

    expect(Schema::getColumnListing('learning_lesson_progress'))
        ->not->toContain('grade')
        ->not->toContain('score')
        ->not->toContain('pass_mark')
        ->and(Schema::getColumnListing('learning_course_completions'))
        ->not->toContain('grade')
        ->not->toContain('score');

    $summary = $progress->courseProgressSummary($learner, $course);
    expect($summary)->toMatchArray(['total' => 2, 'completed' => 0, 'percent' => 0])
        ->and($progress->isCourseComplete($learner, $course))->toBeFalse();

    $progress->completeLesson($learner, $lessons[0]);

    expect($progress->isLessonComplete($learner, $lessons[0]))->toBeTrue()
        ->and($progress->isLessonComplete($learner, $lessons[1]))->toBeFalse()
        ->and($progress->courseProgressSummary($learner, $course)['percent'])->toBe(50)
        ->and($progress->isCourseComplete($learner, $course))->toBeFalse();

    $progress->completeLesson($learner, $lessons[1]);

    expect($progress->isCourseComplete($learner, $course))->toBeTrue()
        ->and($progress->courseProgressSummary($learner, $course))->toMatchArray([
            'total' => 2,
            'completed' => 2,
            'percent' => 100,
        ]);
});

it('keeps progress computation independent of grade permissions', function () {
    $author = progressAuthor();
    $learner = progressLearner();
    [$course, $lessons] = twoLessonCourse($author);

    app(EnrolmentService::class)->enrolManual($course, $learner, $author);
    $progress = app(ProgressService::class);

    // Learner has no grade view/edit/release — progress must still work.
    expect($learner->can(LearningPermissions::GRADE_VIEW))->toBeFalse()
        ->and($learner->can(LearningPermissions::GRADE_EDIT))->toBeFalse()
        ->and($learner->can(LearningPermissions::GRADE_RELEASE))->toBeFalse();

    foreach ($lessons as $lesson) {
        $progress->completeLesson($learner, $lesson);
    }

    expect($progress->isCourseComplete($learner, $course))->toBeTrue()
        ->and(LessonProgress::query()->where('user_id', $learner->id)->whereNotNull('completed_at')->count())->toBe(2);
});

it('starts a lesson and locks the learner to the published version', function () {
    $author = progressAuthor();
    $learner = progressLearner();
    [$course, $lessons] = twoLessonCourse($author);
    app(EnrolmentService::class)->enrolManual($course, $learner, $author);

    $row = app(ProgressService::class)->startLesson($learner, $lessons[0]);

    expect($row->isStarted())->toBeTrue()
        ->and($row->isCompleted())->toBeFalse()
        ->and($row->lesson_version_id)->not->toBeNull();
});
