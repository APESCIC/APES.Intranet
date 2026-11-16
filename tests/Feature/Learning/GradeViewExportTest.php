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
use Modules\Learning\Services\CourseAuthoringService;
use Modules\Learning\Services\GradeReleaseService;
use Modules\Learning\Services\GradeViewExportService;
use Modules\Learning\Services\GradingSchemeService;
use Modules\Learning\Services\LearningAssignmentService;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(LearningPermissionsSeeder::class);
});

it('shows only released grades to the learner and scopes CSV export', function () {
    $admin = User::factory()->create([
        'name' => '=AdminCmd',
        'email' => 'admin-export@example.test',
    ]);
    $admin->assignRole(RoleName::Admin->value);
    $admin->givePermissionTo([
        LearningPermissions::ACCESS,
        LearningPermissions::COURSE_AUTHOR,
        LearningPermissions::ASSIGNMENTS_MANAGE,
        LearningPermissions::GRADE_VIEW,
    ]);

    $instructor = User::factory()->create(['name' => 'Tutor', 'email' => 'tutor@example.test']);
    $instructor->assignRole(RoleName::Staff->value);
    $instructor->givePermissionTo([LearningPermissions::ACCESS, LearningPermissions::GRADE_VIEW]);

    $learner = User::factory()->create([
        'name' => '+LearnerFormula',
        'email' => 'learner@example.test',
    ]);
    $learner->assignRole(RoleName::Staff->value);
    $learner->givePermissionTo(LearningPermissions::ACCESS);

    $outsider = User::factory()->create();
    $outsider->assignRole(RoleName::Staff->value);
    $outsider->givePermissionTo([LearningPermissions::ACCESS, LearningPermissions::GRADE_VIEW]);

    $course = app(CourseAuthoringService::class)->createCourse(['title' => 'Export course'], $admin);
    $schemes = app(GradingSchemeService::class);
    $item = $schemes->createGradeItem($course, 'Final');
    $mark = $schemes->recordMark($item, $learner, 18.0, $admin, true);

    $assign = app(LearningAssignmentService::class);
    $assign->assign($instructor, LearningRole::Instructor, LearningScope::Course, $course->id, $admin);
    $assign->assign($instructor, LearningRole::Assessor, LearningScope::Course, $course->id, $admin);
    $approver = User::factory()->create();
    $approver->assignRole(RoleName::Staff->value);
    $approver->givePermissionTo([LearningPermissions::ACCESS, LearningPermissions::GRADE_VIEW]);
    $assign->assign($approver, LearningRole::GradeApprover, LearningScope::Course, $course->id, $admin);

    $views = app(GradeViewExportService::class);
    expect($views->learnerReleasedGrades($learner))->toHaveCount(0);

    $this->actingAs($learner)->get(route('learning.grades.mine'))
        ->assertOk()
        ->assertSee('No released grades yet')
        ->assertDontSee('Export course')
        ->assertDontSee('>Final<', false);

    $release = app(GradeReleaseService::class);
    $release->mark($mark->fresh(), $instructor);
    $release->moderate($mark->fresh(), $instructor);
    $release->release($mark->fresh(), $approver, notify: false);

    expect($views->learnerReleasedGrades($learner))->toHaveCount(1)
        ->and($views->learnerReleasedGrades($learner)->first()['score'])->toBe(18.0);

    $this->actingAs($learner)->get(route('learning.grades.mine'))
        ->assertOk()
        ->assertSee('Export course')
        ->assertSee('Final')
        ->assertSee('18');

    // Outsider without course scope cannot export.
    $this->actingAs($outsider)
        ->get(route('learning.courses.grades.export', $course))
        ->assertForbidden();

    $csv = $this->actingAs($instructor)
        ->get(route('learning.courses.grades.export', $course))
        ->assertOk()
        ->streamedContent();

    expect($csv)->toContain("'+LearnerFormula")
        ->toContain('learner@example.test')
        ->toContain('Final')
        ->toContain(GradeReleaseState::Released->value)
        ->not->toContain("\n+LearnerFormula"); // formula-escaped
});

it('escapes spreadsheet formula prefixes in CSV fields', function () {
    $service = app(GradeViewExportService::class);

    expect($service->escapeCsvField('=1+1'))->toBe("'=1+1")
        ->and($service->escapeCsvField('+cmd'))->toBe("'+cmd")
        ->and($service->escapeCsvField('-1'))->toBe("'-1")
        ->and($service->escapeCsvField('@sum'))->toBe("'@sum")
        ->and($service->escapeCsvField('safe'))->toBe('safe');
});
