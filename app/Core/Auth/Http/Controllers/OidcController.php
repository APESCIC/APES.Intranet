<?php

namespace App\Core\Auth\Http\Controllers;

use App\Core\Auth\Contracts\DirectoryGroups;
use App\Core\Auth\Contracts\IdentityProvider;
use App\Core\Auth\Exceptions\DirectoryUnavailable;
use App\Core\Auth\Exceptions\IdentityProviderUnavailable;
use App\Core\Auth\GroupRoleMapper;
use App\Core\Qa\QaLogin;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OidcController
{
    public function show(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('core.auth.login', ['qaEnabled' => QaLogin::enabled()]);
    }

    public function redirect(IdentityProvider $identityProvider): RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        try {
            return $identityProvider->authorizationRedirect();
        } catch (IdentityProviderUnavailable $exception) {
            report($exception);
            abort(503, 'Cloudron sign-in is unavailable. Please try again in a few minutes.');
        }
    }

    public function callback(
        Request $request,
        IdentityProvider $identityProvider,
        DirectoryGroups $directory,
        GroupRoleMapper $mapper,
    ): RedirectResponse|Response {
        try {
            $identity = $identityProvider->callbackIdentity();
        } catch (IdentityProviderUnavailable $exception) {
            report($exception);
            abort(503, 'Cloudron sign-in is unavailable. Please try again in a few minutes.');
        }

        if ($identity->subject === null || $identity->email === null) {
            return $this->denied('Your Cloudron account did not share an email address with the intranet.');
        }

        $email = Str::lower($identity->email);

        try {
            $groups = $directory->groupsForEmail($email);
        } catch (DirectoryUnavailable $exception) {
            report($exception);
            abort(503, 'The APES directory is unavailable. Please try again in a few minutes.');
        }

        $roles = $mapper->rolesFor($groups);
        $user = User::query()->where('oidc_sub', $identity->subject)->first()
            ?? User::query()->where('email', $email)->whereNull('oidc_sub')->first();

        if ($roles === []) {
            $user?->syncRoles([]);

            return $this->denied();
        }

        if ($user === null && User::query()->where('email', $email)->exists()) {
            return $this->denied('Another intranet account already uses your email address. Ask the APES IT Administrator for help.');
        }

        $user ??= new User(['password' => Str::random(64)]);
        $user->forceFill([
            'oidc_sub' => $identity->subject,
            'email' => $email,
            'name' => $identity->name ?? $email,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();
        $user->syncRoles($roles);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'You have signed out of the intranet.');
    }

    private function denied(?string $reason = null): Response
    {
        return response()->view('core.auth.denied', ['reason' => $reason], 403);
    }
}
