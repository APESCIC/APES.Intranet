<?php

namespace App\Core\Qa\Http\Controllers;

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
        return view('core.qa.index', [
            'qaProfiles' => QaLogin::picker(),
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

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request, AuditLogger $audit): RedirectResponse
    {
        $audit->record('auth.signed_out', $request->user());
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('qa.index');
    }
}
