<?php

namespace Modules\Learning\Services;

use App\Core\Audit\AuditLogger;
use App\Models\User;
use InvalidArgumentException;
use Modules\Learning\LearningRole;
use Modules\Learning\LearningScope;
use Modules\Learning\Models\LearningRoleAssignment;

class LearningAssignmentService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function assign(
        User $user,
        LearningRole $role,
        LearningScope $scopeType,
        ?int $scopeId,
        ?User $actor = null,
    ): LearningRoleAssignment {
        $this->assertScope($role, $scopeType, $scopeId);
        $storedScopeId = $scopeId ?? 0;

        $assignment = LearningRoleAssignment::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'role' => $role->value,
                'scope_type' => $scopeType->value,
                'scope_id' => $storedScopeId,
            ],
            [
                'assigned_by' => $actor?->id,
            ],
        );

        $this->audit->record('learning.assignment.saved', $assignment, [
            'user_id' => $user->id,
            'role' => $role->value,
            'scope_type' => $scopeType->value,
            'scope_id' => $storedScopeId === 0 ? null : $storedScopeId,
        ], $actor);

        return $assignment->refresh();
    }

    public function revoke(LearningRoleAssignment $assignment, ?User $actor = null): void
    {
        $context = [
            'user_id' => $assignment->user_id,
            'role' => $assignment->role->value,
            'scope_type' => $assignment->scope_type->value,
            'scope_id' => ($assignment->scope_id === 0) ? null : $assignment->scope_id,
        ];

        $assignment->delete();

        $this->audit->record('learning.assignment.revoked', null, $context, $actor);
    }

    /**
     * @param  list<LearningRole>  $roles
     */
    public function hasRole(User $user, array $roles, ?int $courseId = null, ?int $cohortId = null): bool
    {
        if ($roles === []) {
            return false;
        }

        $roleValues = array_map(fn (LearningRole $role) => $role->value, $roles);

        return LearningRoleAssignment::query()
            ->where('user_id', $user->id)
            ->whereIn('role', $roleValues)
            ->get()
            ->contains(function (LearningRoleAssignment $assignment) use ($courseId, $cohortId): bool {
                if ($assignment->scope_type === LearningScope::Global) {
                    return true;
                }

                if ($courseId !== null && $assignment->coversCourse($courseId)) {
                    return true;
                }

                if ($cohortId !== null && $assignment->coversCohort($cohortId)) {
                    return true;
                }

                return false;
            });
    }

    public function canEditGrades(User $user, ?int $courseId = null, ?int $cohortId = null): bool
    {
        return $this->hasRole($user, LearningRole::canEditGrades(), $courseId, $cohortId);
    }

    public function canReleaseGrades(User $user, ?int $courseId = null, ?int $cohortId = null): bool
    {
        return $this->hasRole($user, LearningRole::canReleaseGrades(), $courseId, $cohortId);
    }

    public function canViewOthersGrades(User $user, ?int $courseId = null, ?int $cohortId = null): bool
    {
        return $this->hasRole($user, LearningRole::canViewOthersGrades(), $courseId, $cohortId);
    }

    private function assertScope(LearningRole $role, LearningScope $scopeType, ?int $scopeId): void
    {
        if ($role === LearningRole::LearningAdmin) {
            if ($scopeType !== LearningScope::Global || $scopeId !== null) {
                throw new InvalidArgumentException('Learning admin assignments must be global with no scope id.');
            }

            return;
        }

        if ($scopeType === LearningScope::Global) {
            throw new InvalidArgumentException("Role {$role->value} cannot be global; use course or cohort scope.");
        }

        if ($scopeId === null || $scopeId < 1) {
            throw new InvalidArgumentException("Role {$role->value} needs a positive {$scopeType->value} scope id.");
        }
    }
}
