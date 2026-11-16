<?php

use App\Core\Access\RoleName;
use App\Core\Audit\AuditLog;
use App\Core\Modules\ModuleKind;
use App\Core\Modules\ModuleRegistry;
use App\Models\User;
use Database\Seeders\LearningPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Learning\LearningPermissions;
use Modules\Learning\LearningRole;
use Modules\Learning\LearningScope;
use Modules\Learning\Models\LearningRoleAssignment;
use Modules\Learning\Services\LearningAssignmentService;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(LearningPermissionsSeeder::class);
});

it('registers the learning module as enabled by default', function () {
    $registry = app(ModuleRegistry::class);

    expect($registry->manifest('learning'))->not->toBeNull()
        ->and($registry->enabled('learning'))->toBeTrue()
        ->and($registry->manifest('learning')->kind)->toBe(ModuleKind::Module);
});

it('never grants grade edit or release through Cloudron roles including Admin and HR', function () {
    foreach (RoleName::cases() as $role) {
        $spatie = Role::findByName($role->value);
        expect($spatie->hasPermissionTo(LearningPermissions::GRADE_EDIT))->toBeFalse("{$role->value} must not have grade.edit")
            ->and($spatie->hasPermissionTo(LearningPermissions::GRADE_RELEASE))->toBeFalse("{$role->value} must not have grade.release");
    }

    $admin = User::factory()->create();
    $admin->assignRole(RoleName::Admin->value);
    $hr = User::factory()->create();
    $hr->assignRole(RoleName::Hr->value);

    expect($admin->can(LearningPermissions::GRADE_EDIT))->toBeFalse()
        ->and($admin->can(LearningPermissions::GRADE_RELEASE))->toBeFalse()
        ->and($hr->can(LearningPermissions::GRADE_EDIT))->toBeFalse()
        ->and($hr->can(LearningPermissions::GRADE_RELEASE))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('learning.grade.edit', 10))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('learning.grade.release', 10))->toBeFalse()
        ->and(Gate::forUser($hr)->allows('learning.grade.edit', 10))->toBeFalse();
});

it('lets an assessor edit grades only inside their course scope', function () {
    $assessor = User::factory()->create();
    $assessor->assignRole(RoleName::Staff->value);
    $service = app(LearningAssignmentService::class);

    $service->assign($assessor, LearningRole::Assessor, LearningScope::Course, 42, $assessor);

    expect(Gate::forUser($assessor)->allows('learning.grade.edit', 42))->toBeTrue()
        ->and(Gate::forUser($assessor)->allows('learning.grade.edit', 99))->toBeFalse()
        ->and(Gate::forUser($assessor)->allows('learning.grade.release', 42))->toBeFalse()
        ->and(Gate::forUser($assessor)->allows('learning.grade.view', 42))->toBeTrue()
        ->and(Gate::forUser($assessor)->allows('learning.grade.view', 99))->toBeFalse();
});

it('lets a grade approver release grades inside scope and learning admin globally', function () {
    $approver = User::factory()->create();
    $approver->assignRole(RoleName::Staff->value);
    $adminLearner = User::factory()->create();
    $adminLearner->assignRole(RoleName::Admin->value);
    $service = app(LearningAssignmentService::class);

    $service->assign($approver, LearningRole::GradeApprover, LearningScope::Course, 7, $approver);
    $service->assign($adminLearner, LearningRole::LearningAdmin, LearningScope::Global, null, $adminLearner);

    expect(Gate::forUser($approver)->allows('learning.grade.release', 7))->toBeTrue()
        ->and(Gate::forUser($approver)->allows('learning.grade.edit', 7))->toBeTrue()
        ->and(Gate::forUser($approver)->allows('learning.grade.release', 8))->toBeFalse()
        ->and(Gate::forUser($adminLearner)->allows('learning.grade.edit', 1))->toBeTrue()
        ->and(Gate::forUser($adminLearner)->allows('learning.grade.release', 999))->toBeTrue();
});

it('audits learning role assignment creates and revokes', function () {
    $actor = User::factory()->create();
    $learner = User::factory()->create();
    $service = app(LearningAssignmentService::class);

    $assignment = $service->assign($learner, LearningRole::Instructor, LearningScope::Course, 3, $actor);

    expect(AuditLog::query()->where('event', 'learning.assignment.saved')->exists())->toBeTrue()
        ->and(LearningRoleAssignment::query()->whereKey($assignment->id)->exists())->toBeTrue();

    $service->revoke($assignment, $actor);

    expect(LearningRoleAssignment::query()->whereKey($assignment->id)->exists())->toBeFalse()
        ->and(AuditLog::query()->where('event', 'learning.assignment.revoked')->exists())->toBeTrue();
});

it('serves the My learning stub behind the module gate and keeps it off the HR training route', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleName::Staff->value);
    $user->givePermissionTo(LearningPermissions::ACCESS);

    $this->actingAs($user)
        ->get(route('learning.mine'))
        ->assertOk()
        ->assertSee('My learning')
        ->assertDontSee('gradebook', false);

    expect(route('learning.mine'))->not->toBe(route('hr.training.mine'));
});
