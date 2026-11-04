<?php

namespace App\Core\Qa;

use App\Core\Access\RoleName;
use App\Models\User;

/**
 * Fake accounts for previewing the intranet as each role on Laragon.
 */
final class QaLogin
{
    public static function enabled(): bool
    {
        return config('intranet.qa_login_enabled') === true && app()->environment('local');
    }

    public static function emailFor(RoleName $role): string
    {
        return 'qa-'.$role->value.'@example.test';
    }

    public static function userFor(RoleName $role): User
    {
        $user = User::query()->firstOrCreate(
            ['email' => self::emailFor($role)],
            ['name' => 'QA '.$role->label(), 'password' => str()->random(40)],
        );

        $user->syncRoles([$role->value]);

        return $user;
    }
}
