<?php

namespace App\Core\Qa\Http\Controllers;

use App\Core\Access\RoleName;
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

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', Rule::enum(RoleName::class)],
        ]);

        Auth::login(QaLogin::userFor(RoleName::from($validated['role'])));
        $request->session()->regenerate();

        return redirect('/');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('qa.index');
    }
}
