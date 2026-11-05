<?php

namespace App\Core\Access\Http\Controllers;

use App\Core\Access\RoleName;
use Illuminate\View\View;

/**
 * "Who can see what": the handbook page that explains the approved groups.
 */
class AccessGuideController
{
    public function __invoke(): View
    {
        /** @var array<string, string> $groupRoles */
        $groupRoles = config('intranet.group_roles', []);

        $groups = collect($groupRoles)->map(fn (string $role, string $group) => [
            'group' => $group,
            'role' => RoleName::from($role),
        ])->values();

        return view('core.access-guide', [
            'accountTypes' => $groups->filter(fn (array $row) => $row['role']->isAccountType())->values(),
            'permissionRoles' => $groups->reject(fn (array $row) => $row['role']->isAccountType())->values(),
            'freshSignInMinutes' => (int) config('intranet.fresh_signin_minutes', 15),
        ]);
    }
}
