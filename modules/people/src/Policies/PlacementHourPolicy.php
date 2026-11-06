<?php

namespace Modules\People\Policies;

use App\Core\Access\AccessMatrix;
use App\Models\User;
use Modules\People\Models\PlacementHour;

class PlacementHourPolicy
{
    public function create(User $user): bool
    {
        return $user->can(AccessMatrix::STUDENT_AREA);
    }

    public function review(User $user, PlacementHour $hour): bool
    {
        if ($user->can(AccessMatrix::TEAM_MANAGE) || $user->can(AccessMatrix::HR_ACCESS)) {
            return true;
        }

        $placement = $hour->placement;

        return $user->can(AccessMatrix::TEAM_SUPERVISE)
            && $placement !== null
            && (int) $placement->supervisor_id === (int) $user->getKey();
    }
}
