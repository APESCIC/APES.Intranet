<?php

namespace App\Core\Qa;

use App\Core\Access\RoleName;
use App\Core\Access\RoleStacks;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * Fake accounts for previewing the intranet locally as each approved group
 * and each common stack of groups.
 */
final class QaLogin
{
    /** Flashed for the request straight after a role switch. */
    public const SWITCHED = 'qa.switched';

    /** Flashed when a page needs a fresh sign-in, to ask the person to confirm their role. */
    public const CONFIRM = 'qa.confirm';

    public static function enabled(): bool
    {
        return config('intranet.qa_login_enabled') === true && app()->environment('local');
    }

    /**
     * The profile key of a QA account, or null for any other account.
     */
    public static function profileFor(?User $user): ?string
    {
        if ($user === null || preg_match('/^qa-(.+)@example\.test$/', $user->email, $matches) !== 1) {
            return null;
        }

        return array_key_exists($matches[1], self::profiles()) ? $matches[1] : null;
    }

    /**
     * A path on this site to return to, or null if it could lead elsewhere.
     */
    public static function safeReturnPath(mixed $path): ?string
    {
        if (! is_string($path) || $path === '' || strlen($path) > 2000) {
            return null;
        }

        if (! str_starts_with($path, '/') || str_starts_with($path, '//') || preg_match('/[\\\\\s]/', $path) === 1) {
            return null;
        }

        return $path;
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

    /**
     * Profiles for the picker, split into single groups and stacks.
     *
     * @return array{groups: list<array{key: string, label: string, groups: list<string>}>, stacks: list<array{key: string, label: string, groups: list<string>}>}
     */
    public static function picker(): array
    {
        $groups = [];
        $stacks = [];

        foreach (self::profiles() as $key => $roles) {
            $profile = [
                'key' => $key,
                'label' => RoleStacks::label($roles),
                'groups' => array_map(fn (RoleName $role) => self::groupFor($role), $roles),
            ];

            if (count($roles) === 1) {
                $groups[] = $profile;
            } else {
                $stacks[] = $profile;
            }
        }

        return ['groups' => $groups, 'stacks' => $stacks];
    }

    public static function label(string $profile): string
    {
        return RoleStacks::label(self::profiles()[$profile] ?? []);
    }

    /**
     * Sends the request straight after a role switch to the dashboard when the
     * new role may not open the page it returned to.
     */
    public static function fallbackAfterSwitch(Request $request, int $status): ?RedirectResponse
    {
        if (! in_array($status, [403, 404], true) || ! $request->isMethod('GET') || ! $request->hasSession() || ! self::enabled()) {
            return null;
        }

        if ($request->session()->pull(self::SWITCHED) !== true) {
            return null;
        }

        return redirect()->route('dashboard')
            ->with('qa.notice', 'Your new QA role cannot open that page, so you are on the dashboard instead.');
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

    private static function groupFor(RoleName $role): string
    {
        return (string) array_search($role->value, config('intranet.group_roles', []), true);
    }
}
