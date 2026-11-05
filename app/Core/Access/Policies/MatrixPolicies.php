<?php

namespace App\Core\Access\Policies;

use App\Core\Access\AccessMatrix;
use App\Core\Access\Contracts\BelongsToTeam;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * Gates for each row of the approved permissions matrix. They check
 * permissions, never role or group names.
 */
final class MatrixPolicies
{
    public static function register(): void
    {
        foreach (['viewTeamRota', 'approveTeamSwap', 'approveTeamTimesheet', 'viewTeamChecklist', 'approveStudentHours'] as $ability) {
            Gate::define($ability, [self::class, 'ownTeamOrAll']);
        }

        Gate::define('useStudentArea', fn (User $user) => $user->can(AccessMatrix::STUDENT_AREA));
        Gate::define('viewHrRecord', fn (User $user) => $user->can(AccessMatrix::HR_ACCESS));
        Gate::define('viewSpecialCategory', fn (User $user) => $user->can(AccessMatrix::HR_SPECIAL_CATEGORY_VIEW));
        Gate::define('viewLegalRecord', fn (User $user) => $user->can(AccessMatrix::LEGAL_ACCESS));
    }

    /**
     * Management and admin see every team. Supervisors see only the teams
     * they supervise.
     */
    public static function ownTeamOrAll(User $user, BelongsToTeam $record): bool
    {
        if ($user->can(AccessMatrix::TEAM_MANAGE)) {
            return true;
        }

        return $user->can(AccessMatrix::TEAM_SUPERVISE) && $record->isSupervisedBy($user);
    }
}
