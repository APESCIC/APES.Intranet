<?php

use App\Core\Access\RoleName;
use App\Models\User;
use Database\Seeders\LearningPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Learning\GradeReleaseState;
use Modules\Learning\LearningPermissions;
use Modules\Learning\LearningRole;
use Modules\Learning\LearningScope;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\GradeItem;
use Modules\Learning\Models\GradeMark;
use Modules\Learning\Services\CourseAuthoringService;
use Modules\Learning\Services\EnrolmentService;
use Modules\Learning\Services\LearningAssignmentService;
use Modules\Learning\Services\LearningReportService;
use Modules\Learning\Services\ProgressService;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(LearningPermissionsSeeder::class);
});

function reportAdmin(): User
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

function reportInstructor(User $admin, Course $course): User
{
    $user = User::factory()->create();
    $user->assignRole(RoleName::Staff->value);
    $user->givePermissionTo([LearningPermissions::ACCESS, LearningPermissions::GRADE_VIEW]);
    app(LearningAssignmentService::class)->assign(
        $user,
        LearningRole::Instructor,
        LearningScope::Course,
        $course->id,
        $admin,
    );

    return $user;
}

/**
 * @return array{0: Course, 1: User, 2: User}
 */
function reportCourseWithLearners(User $admin): array
{
    $authoring = app(CourseAuthoringService::class);
    $course = $authoring->createCourse(['title' => 'Report course'], $admin);
    $course->forceFill(['status' => Course::STATUS_PUBLISHED])->save();
    $section = $authoring->addSection($course, 'Core');
    $lesson = $authoring->addLesson($section, 'One', '<p>Hi</p>');
    $authoring->publishLesson($lesson, $admin);

    $done = User::factory()->create(['name' => 'Done Learner']);
    $done->assignRole(RoleName::Staff->value);
    $done->givePermissionTo(LearningPermissions::ACCESS);

    $overdue = User::factory()->create(['name' => 'Overdue Learner']);
    $overdue->assignRole(RoleName::Staff->value);
    $overdue->givePermissionTo(LearningPermissions::ACCESS);

    $enrol = app(EnrolmentService::class);
    $enrol->enrolManual($course, $done, $admin);
    $enrol->enrolManual($course, $overdue, $admin);
    app(ProgressService::class)->completeLesson($done, $lesson->refresh());

    return [$course->refresh(), $done, $overdue];
}

it('shows scoped completion and overdue reports and CSV for instructors', function () {
    $admin = reportAdmin();
    [$course, $done, $overdue] = reportCourseWithLearners($admin);
    $instructor = reportInstructor($admin, $course);

    $this->actingAs($instructor)
        ->get(route('learning.reports.show', $course))
        ->assertOk()
        ->assertSee('Done Learner')
        ->assertSee('Overdue Learner')
        ->assertSee('Overdue')
        ->assertSee('Complete');

    $csv = $this->actingAs($instructor)
        ->get(route('learning.reports.completions.export', $course));

    $csv->assertOk();
    expect($csv->headers->get('content-type'))->toContain('text/csv')
        ->and($csv->streamedContent())->toContain('Done Learner')
        ->and($csv->streamedContent())->toContain('Overdue Learner');
});

it('suppresses grade distributions for small groups', function () {
    $admin = reportAdmin();
    [$course] = reportCourseWithLearners($admin);
    $instructor = reportInstructor($admin, $course);

    $item = GradeItem::query()->create([
        'course_id' => $course->id,
        'title' => 'Quiz 1',
        'source_type' => 'manual',
        'weight' => 1,
    ]);

    GradeMark::query()->create([
        'grade_item_id' => $item->id,
        'user_id' => User::factory()->create()->id,
        'raw_score' => 80,
        'release_state' => GradeReleaseState::Released,
    ]);

    $distribution = app(LearningReportService::class)->gradeDistribution($instructor, $course);

    expect($distribution['suppressed'])->toBeTrue()
        ->and($distribution['buckets'])->toBeEmpty();

    $this->actingAs($instructor)
        ->get(route('learning.reports.show', $course))
        ->assertOk()
        ->assertSee('Grade distributions are hidden');
});

it('refuses reports to learners without a scoped view role', function () {
    $admin = reportAdmin();
    [$course] = reportCourseWithLearners($admin);
    $learner = User::factory()->create();
    $learner->assignRole(RoleName::Staff->value);
    $learner->givePermissionTo([LearningPermissions::ACCESS, LearningPermissions::GRADE_VIEW]);

    $this->actingAs($learner)
        ->get(route('learning.reports.show', $course))
        ->assertForbidden();
});
