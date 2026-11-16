<?php

use App\Core\Access\RoleName;
use App\Models\User;
use Database\Seeders\LearningPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Modules\Learning\GradeReleaseState;
use Modules\Learning\LearningPermissions;
use Modules\Learning\LearningRole;
use Modules\Learning\LearningScope;
use Modules\Learning\Notifications\GradeResultReadyNotification;
use Modules\Learning\Services\CourseAuthoringService;
use Modules\Learning\Services\GradeReleaseService;
use Modules\Learning\Services\GradingSchemeService;
use Modules\Learning\Services\LearningAssignmentService;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(LearningPermissionsSeeder::class);
});

it('hides unreleased grades from the learner and notifies without a grade value', function () {
    Notification::fake();

    $author = User::factory()->create();
    $author->assignRole(RoleName::Admin->value);
    $author->givePermissionTo([
        LearningPermissions::ACCESS,
        LearningPermissions::COURSE_AUTHOR,
        LearningPermissions::ASSIGNMENTS_MANAGE,
        LearningPermissions::GRADE_VIEW,
    ]);

    $learner = User::factory()->create();
    $learner->assignRole(RoleName::Staff->value);
    $learner->givePermissionTo(LearningPermissions::ACCESS);

    $assessor = User::factory()->create();
    $assessor->assignRole(RoleName::Staff->value);
    $assessor->givePermissionTo([LearningPermissions::ACCESS, LearningPermissions::GRADE_VIEW]);

    $approver = User::factory()->create();
    $approver->assignRole(RoleName::Staff->value);
    $approver->givePermissionTo([LearningPermissions::ACCESS, LearningPermissions::GRADE_VIEW]);

    $course = app(CourseAuthoringService::class)->createCourse(['title' => 'Release course'], $author);
    $schemes = app(GradingSchemeService::class);
    $item = $schemes->createGradeItem($course, 'Midterm');
    $mark = $schemes->recordMark($item, $learner, 17.5, $assessor, true);
    expect($mark->release_state)->toBe(GradeReleaseState::Draft);

    $assignments = app(LearningAssignmentService::class);
    $assignments->assign($assessor, LearningRole::Assessor, LearningScope::Course, $course->id, $author);
    $assignments->assign($approver, LearningRole::GradeApprover, LearningScope::Course, $course->id, $author);

    $release = app(GradeReleaseService::class);
    expect($release->visibleToLearner($mark, $learner))->toBeNull();

    // Export / API style: unreleased payload must stay empty.
    $exportRow = $release->visibleToLearner($mark->fresh(), $learner);
    expect($exportRow)->toBeNull();

    $release->mark($mark->fresh(), $assessor);
    $release->moderate($mark->fresh(), $assessor);
    expect($release->visibleToLearner($mark->fresh(), $learner))->toBeNull();

    // Admin alone cannot release.
    expect(fn () => $release->release($mark->fresh(), $author))->toThrow(RuntimeException::class);

    $released = $release->release($mark->fresh(), $approver);
    expect($released->release_state)->toBe(GradeReleaseState::Released)
        ->and($release->visibleToLearner($released, $learner))->toMatchArray([
            'raw_score' => 17.5,
            'passed' => true,
        ]);

    Notification::assertSentTo($learner, GradeResultReadyNotification::class, function (GradeResultReadyNotification $n) use ($learner) {
        $payload = $n->toArray($learner);
        $mail = $n->toMail($learner);

        $lines = implode(' ', $mail->introLines);
        expect($payload['body'])->not->toContain('17.5')
            ->and($payload)->not->toHaveKey('raw_score')
            ->and($lines)->not->toContain('17.5')
            ->and($lines)->toContain('does not include the grade');

        return true;
    });
});
