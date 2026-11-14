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
            'return_to' => ['nullable', 'string', 'max:2000'],
        ]);

        $previous = QaLogin::profileFor($request->user());
        $user = QaLogin::userFor($validated['profile']);
        Auth::login($user);
        $request->session()->regenerate();
        (new FreshSignIn($request->session()))->record(now()->getTimestamp());
        $audit->record('auth.signed_in', $user, array_filter([
            'method' => 'qa',
            'roles' => $user->getRoleNames()->all(),
            'switched_from' => $previous,
        ], fn ($value) => $value !== null), $user);

        $returnTo = QaLogin::safeReturnPath($validated['return_to'] ?? null);
        if ($returnTo !== null || $request->session()->has('url.intended')) {
            $request->session()->flash(QaLogin::SWITCHED, true);
        }

        return redirect()->intended($returnTo ?? route('dashboard'));
    }

    public function destroy(Request $request, AuditLogger $audit): RedirectResponse
    {
        $audit->record('auth.signed_out', $request->user());
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->to(route('login').'#developer-signin')
            ->with('status', 'You have signed out of QA.');
    }
}
