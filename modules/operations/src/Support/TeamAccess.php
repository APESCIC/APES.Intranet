<?php

namespace Modules\Operations\Support;

use App\Core\Access\AccessMatrix;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Modules\Operations\Models\Site;

/**
 * Who oversees which site or member. Supervisors oversee the team named on
 * their profile; management and admin oversee every team.
 */
final class TeamAccess
{
    /**
     * Sites the user may manage with the given permission.
     *
     * @return Collection<int, Site>
     */
    public static function manageableSites(User $user, string $permission): Collection
    {
        if (! $user->can($permission)) {
            return collect();
        }

        return Site::query()->active()->orderBy('name')->get()
            ->filter(fn (Site $site) => Gate::forUser($user)->allows('viewTeamRota', $site))
            ->values();
    }

    public static function canManageSite(User $user, Site $site, string $permission): bool
    {
        return $user->can($permission) && Gate::forUser($user)->allows('viewTeamRota', $site);
    }

    /**
     * Whether the user oversees this member: same team as a supervisor, or
     * their line manager.
     */
    public static function supervises(User $supervisor, User $member): bool
    {
        if ($supervisor->can(AccessMatrix::TEAM_MANAGE)) {
            return true;
        }

        if (! $supervisor->can(AccessMatrix::TEAM_SUPERVISE)) {
            return false;
        }

        return (int) $member->manager_id === (int) $supervisor->getKey()
            || (filled($member->team) && $member->team === $supervisor->team);
    }

    /**
     * People to tell about a change on a team: its supervisors, else the
     * member's line manager.
     *
     * @return Collection<int, User>
     */
    public static function approversFor(?string $team, ?User $member = null): Collection
    {
        $approvers = filled($team)
            ? User::query()->active()->permission(AccessMatrix::TEAM_SUPERVISE)->where('team', $team)->get()
            : collect();

        if ($approvers->isEmpty() && $member?->manager_id !== null) {
            $manager = User::query()->active()->find($member->manager_id);
            if ($manager !== null) {
                $approvers = collect([$manager]);
            }
        }

        return $approvers->unique('id')->values();
    }
}
