<?php

namespace App\Core\Qa;

use App\Core\Access\RoleName;
use App\Core\Access\RoleStacks;
use App\Models\User;
use InvalidArgumentException;

/**
 * Fake accounts for previewing the intranet locally as each approved group
 * and each common stack of groups.
 */
final class QaLogin
{
    public static function enabled(): bool
    {
        return config('intranet.qa_login_enabled') === true && app()->environment('local');
    }

    /**
     * One profile per approved group, then the common stacks.
     *
     * @return array<string, list<RoleName>> Profile key => roles.
     */
    public static function profiles(): array
    {
        $profiles = [];

        foreach (RoleName::cases() as $role) {
            $profiles[$role->value] = [$role];
        }

        return $profiles + RoleStacks::common();
    }

    public static function emailFor(string $profile): string
    {
        return 'qa-'.$profile.'@example.test';
    }

    public static function userFor(string $profile): User
    {
        $roles = self::profiles()[$profile] ?? throw new InvalidArgumentException("Unknown QA profile [{$profile}].");

        $user = User::query()->firstOrCreate(
            ['email' => self::emailFor($profile)],
            ['name' => 'QA '.RoleStacks::label($roles), 'password' => str()->random(40)],
        );

        $user->syncRoles(array_map(fn (RoleName $role) => $role->value, $roles));

        return $user;
    }
}
