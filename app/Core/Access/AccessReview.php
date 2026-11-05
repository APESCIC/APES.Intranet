<?php

namespace App\Core\Access;

use App\Core\Audit\AuditLog;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The quarterly group membership and access review for admin and HR. It only
 * reports: groups are changed in Cloudron, never here.
 */
final class AccessReview
{
    /** No sign-in for this many days, while still holding a role, counts as a possible leaver. */
    public const LEAVER_DAYS = 90;

    /** How far back sensitive views are listed. */
    public const SENSITIVE_VIEW_DAYS = 92;

    /**
     * @return list<RoleName>
     */
    public static function seniorRoles(): array
    {
        return [RoleName::Admin, RoleName::Hr, RoleName::Legal, RoleName::Management];
    }

    /**
     * People holding more than one senior role.
     *
     * @return Collection<int, User>
     */
    public function stackedSeniorRoles(): Collection
    {
        $senior = array_map(fn (RoleName $role) => $role->value, self::seniorRoles());

        return User::query()->with('roles')->orderBy('name')->get()
            ->filter(fn (User $user) => $user->roles->pluck('name')->intersect($senior)->count() > 1)
            ->values();
    }

    /**
     * People who still hold a role but have not signed in for LEAVER_DAYS.
     *
     * @return Collection<int, array{user: User, lastSignIn: ?Carbon}>
     */
    public function leavers(): Collection
    {
        $lastSignIns = $this->lastSignIns();
        $cutoff = now()->subDays(self::LEAVER_DAYS);

        return User::query()->has('roles')->with('roles')->orderBy('name')->get()
            ->map(fn (User $user) => ['user' => $user, 'lastSignIn' => $lastSignIns->get($user->getKey())])
            ->filter(fn (array $row) => $row['lastSignIn'] === null || $row['lastSignIn']->lt($cutoff))
            ->values();
    }

    /**
     * Accounts in none of the approved groups: no intranet role at all.
     *
     * @return Collection<int, User>
     */
    public function peopleInNoGroup(): Collection
    {
        return User::query()->doesntHave('roles')->orderBy('name')->get();
    }

    /**
     * Admin and management views of DBS and health data.
     *
     * @return Collection<int, AuditLog>
     */
    public function seniorSpecialCategoryViews(): Collection
    {
        $viewers = User::role([RoleName::Admin->value, RoleName::Management->value])->pluck('id');

        return AuditLog::query()->with('actor')
            ->where('event', 'hr.special_category.viewed')
            ->whereIn('actor_id', $viewers)
            ->where('occurred_at', '>=', now()->subDays(self::SENSITIVE_VIEW_DAYS))
            ->latest('occurred_at')
            ->get();
    }

    /**
     * @return Collection<int, Carbon> User id => last sign-in.
     */
    private function lastSignIns(): Collection
    {
        return AuditLog::query()
            ->where('event', 'auth.signed_in')
            ->whereNotNull('actor_id')
            ->selectRaw('actor_id, max(occurred_at) as last_at')
            ->groupBy('actor_id')
            ->pluck('last_at', 'actor_id')
            ->map(fn ($value) => Carbon::parse($value));
    }
}
