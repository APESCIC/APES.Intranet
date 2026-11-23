<?php

namespace Modules\People\Policies;

use App\Core\Access\AccessMatrix;
use App\Models\User;
use Modules\Hr\HrPermissions;
use Modules\People\Models\Placement;

class PlacementPolicy
{
    public function view(User $user, Placement $placement): bool
    {
        if ($user->can(HrPermissions::ACCESS) || $user->can(AccessMatrix::TEAM_MANAGE)) {
            return true;
        }

        if ((int) $placement->student_id === (int) $user->getKey()) {
            return $user->can(AccessMatrix::STUDENT_AREA);
        }

        if ((int) $placement->supervisor_id === (int) $user->getKey()) {
            return $user->can(AccessMatrix::TEAM_SUPERVISE);
        }

        return false;
    }

    public function viewAny(User $user): bool
    {
        return $user->can(AccessMatrix::STUDENT_AREA)
            || $user->can(AccessMatrix::TEAM_SUPERVISE)
            || $user->can(HrPermissions::ACCESS);
    }
}
