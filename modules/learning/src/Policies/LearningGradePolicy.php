<?php

namespace Modules\Learning\Policies;

use App\Models\User;
use Modules\Learning\Services\LearningAssignmentService;

/**
 * Grade edit and release never follow Admin or HR Spatie roles. A scoped
 * LearningRoleAssignment is required. No gradebook UI ships in v0.6.6; this
 * policy is the gate for later versions and for matrix tests now.
 */
class LearningGradePolicy
{
    public function __construct(private readonly LearningAssignmentService $assignments) {}

    public function view(User $user, ?int $courseId = null, ?int $cohortId = null): bool
    {
        return $this->assignments->canViewOthersGrades($user, $courseId, $cohortId);
    }

    public function edit(User $user, ?int $courseId = null, ?int $cohortId = null): bool
    {
        return $this->assignments->canEditGrades($user, $courseId, $cohortId);
    }

    public function release(User $user, ?int $courseId = null, ?int $cohortId = null): bool
    {
        return $this->assignments->canReleaseGrades($user, $courseId, $cohortId);
    }
}
