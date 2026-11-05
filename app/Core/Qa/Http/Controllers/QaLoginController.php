<?php

namespace App\Core\Qa\Http\Controllers;

use App\Core\Access\RoleName;
use App\Core\Access\RoleStacks;
use App\Core\Audit\AuditLogger;
use App\Core\Auth\FreshSignIn;
use App\Core\Qa\QaLogin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class QaLoginController
{
    public function index(Request $request): View
    {
        $profiles = collect(QaLogin::profiles())->map(fn (array $roles, string $key) => [
            'key' => $key,
            'label' => RoleStacks::label($roles),
            'groups' => array_map(fn (RoleName $role) => self::groupFor($role), $roles),
        ]);

        return view('core.qa.index', [
            'groups' => $profiles->filter(fn (array $profile) => count($profile['groups']) === 1)->values(),
            'stacks' => $profiles->filter(fn (array $profile) => count($profile['groups']) > 1)->values(),
            'current' => $request->user(),
        ]);
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $validated = $request->validate([
            'profile' => ['required', 'string', Rule::in(array_keys(QaLogin::profiles()))],
        ]);

        $user = QaLogin::userFor($validated['profile']);
        Auth::login($user);
        $request->session()->regenerate();
        (new FreshSignIn($request->session()))->record(now()->getTimestamp());
        $audit->record('auth.signed_in', $user, ['method' => 'qa', 'roles' => $user->getRoleNames()->all()], $user);

        return redirect()->route('dashboard');
    }

    public function destroy(Request $request, AuditLogger $audit): RedirectResponse
    {
        $audit->record('auth.signed_out', $request->user());
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('qa.index');
    }

    private static function groupFor(RoleName $role): string
    {
        return (string) array_search($role->value, config('intranet.group_roles', []), true);
    }
}
