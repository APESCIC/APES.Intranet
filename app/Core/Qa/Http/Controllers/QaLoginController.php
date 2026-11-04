<?php

namespace App\Core\Qa\Http\Controllers;

use App\Core\Access\RoleName;
use App\Core\Audit\AuditLogger;
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
            'roles' => RoleName::cases(),
            'current' => $request->user(),
        ]);
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', Rule::enum(RoleName::class)],
        ]);

        $user = QaLogin::userFor(RoleName::from($validated['role']));
        Auth::login($user);
        $request->session()->regenerate();
        $audit->record('auth.signed_in', $user, ['method' => 'qa', 'roles' => [$validated['role']]], $user);

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
}
