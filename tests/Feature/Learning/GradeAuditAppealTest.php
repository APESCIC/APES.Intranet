<?php

use App\Core\Access\RoleName;
use App\Models\User;
use Database\Seeders\LearningPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Learning\LearningPermissions;
use Modules\Learning\LearningRole;
use Modules\Learning\LearningScope;
use Modules\Learning\Models\GradeAppeal;
use Modules\Learning\Models\GradeEvent;
use Modules\Learning\Services\CourseAuthoringService;
use Modules\Learning\Services\GradeAuditService;
use Modules\Learning\Services\GradingSchemeService;
use Modules\Learning\Services\LearningAssignmentService;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(LearningPermissionsSeeder::class);
});

it('records append-only override events and refuses history edits', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RoleName::Admin->value);
    $admin->givePermissionTo([
        LearningPermissions::ACCESS,
        LearningPermissions::COURSE_AUTHOR,
        LearningPermissions::ASSIGNMENTS_MANAGE,
        LearningPermissions::GRADE_VIEW,
    ]);

    $assessor = User::factory()->create();
    $assessor->assignRole(RoleName::Staff->value);
    $assessor->givePermissionTo([LearningPermissions::ACCESS, LearningPermissions::GRADE_VIEW]);

    $learner = User::factory()->create();
    $learner->assignRole(RoleName::Staff->value);
    $learner->givePermissionTo(LearningPermissions::ACCESS);

    $course = app(CourseAuthoringService::class)->createCourse(['title' => 'Audit course'], $admin);
    $item = app(GradingSchemeService::class)->createGradeItem($course, 'Exam');
    app(GradingSchemeService::class)->recordMark($item, $learner, 10.0, $admin);

    app(LearningAssignmentService::class)->assign(
        $assessor,
        LearningRole::Assessor,
        LearningScope::Course,
        $course->id,
        $admin,
    );

    $audit = app(GradeAuditService::class);

    expect(fn () => $audit->override($item, $learner, 12.0, 'Bump', $admin))
        ->toThrow(RuntimeException::class);

    $mark = $audit->override($item, $learner, 12.0, 'Clerical correction', $assessor);
    expect((float) $mark->raw_score)->toBe(12.0);

    $history = $audit->history($item, $learner);
    expect($history)->toHaveCount(1)
        ->and($history->first()->event_type)->toBe('override')
        ->and($history->first()->actor_id)->toBe($assessor->id)
        ->and((float) $history->first()->old_score)->toBe(10.0)
        ->and((float) $history->first()->new_score)->toBe(12.0)
        ->and($history->first()->reason)->toBe('Clerical correction');

    $event = $history->first();
    expect(fn () => $event->update(['reason' => 'tamper']))->toThrow(RuntimeException::class)
        ->and(fn () => $event->delete())->toThrow(RuntimeException::class);

    expect(GradeEvent::query()->count())->toBe(1)
        ->and(GradeEvent::query()->first()->reason)->toBe('Clerical correction');
});

it('supports learner appeals with a recorded outcome', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RoleName::Admin->value);
    $admin->givePermissionTo([
        LearningPermissions::ACCESS,
        LearningPermissions::COURSE_AUTHOR,
        LearningPermissions::ASSIGNMENTS_MANAGE,
        LearningPermissions::GRADE_VIEW,
    ]);

    $assessor = User::factory()->create();
    $assessor->assignRole(RoleName::Staff->value);
    $assessor->givePermissionTo([LearningPermissions::ACCESS, LearningPermissions::GRADE_VIEW]);

    $learner = User::factory()->create();
    $learner->assignRole(RoleName::Staff->value);
    $learner->givePermissionTo(LearningPermissions::ACCESS);

    $course = app(CourseAuthoringService::class)->createCourse(['title' => 'Appeal course'], $admin);
    $item = app(GradingSchemeService::class)->createGradeItem($course, 'Essay');
    app(GradingSchemeService::class)->recordMark($item, $learner, 8.0, $admin);
    app(LearningAssignmentService::class)->assign(
        $assessor,
        LearningRole::Assessor,
        LearningScope::Course,
        $course->id,
        $admin,
    );

    $audit = app(GradeAuditService::class);
    $appeal = $audit->openAppeal($item, $learner, '<p>Marking error on criterion 2</p>');
    expect($appeal->status)->toBe(GradeAppeal::STATUS_OPEN);

    $resolved = $audit->resolveAppeal($appeal, $assessor, GradeAppeal::STATUS_UPHELD, 'Agreed — re-marked', 11.0);
    expect($resolved->status)->toBe(GradeAppeal::STATUS_UPHELD)
        ->and($resolved->resolved_by)->toBe($assessor->id)
        ->and($resolved->outcome_notes)->toContain('re-marked');

    $history = $audit->history($item, $learner);
    expect($history->pluck('event_type')->all())->toContain('appeal_opened', 'appeal_resolved')
        ->and((float) $history->last()->new_score)->toBe(11.0);
});
