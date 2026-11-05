<?php

namespace App\Core\Auth\Http\Controllers;

use App\Core\Audit\AuditLogger;
use App\Core\Auth\Contracts\DirectoryGroups;
use App\Core\Auth\Contracts\IdentityProvider;
use App\Core\Auth\Exceptions\DirectoryUnavailable;
use App\Core\Auth\Exceptions\IdentityProviderUnavailable;
use App\Core\Auth\FreshSignIn;
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
    public function __construct(private readonly AuditLogger $audit) {}

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

    /**
     * Ask Cloudron for a new sign-in, for pages behind the fresh-signin middleware.
     */
    public function reauthenticate(Request $request, IdentityProvider $identityProvider): RedirectResponse
    {
        if (QaLogin::enabled()) {
            return redirect()->route('qa.index')->with('status', 'Sign in again to open that page.');
        }

        (new FreshSignIn($request->session()))->markRequested();

        try {
            return $identityProvider->authorizationRedirect(forceLogin: true);
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
            return $this->denied(null, 'missing_email', 'Your Cloudron account did not share an email address with the intranet.');
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
            if ($user !== null) {
                $before = $user->getRoleNames()->all();
                $user->syncRoles([]);
                $this->audit->recordRoleChange($user, $before, [], 'cloudron');
                if ($user->is_active) {
                    $user->forceFill([
                        'is_active' => false,
                        'deactivated_at' => now(),
                    ])->save();
                    $this->audit->record('people.member_deactivated', $user, ['source' => 'cloudron_sign_in']);
                }
            }

            return $this->denied($user, 'no_intranet_group');
        }

        if ($user === null && User::query()->where('email', $email)->exists()) {
            return $this->denied(null, 'email_conflict', 'Another intranet account already uses your email address. Ask the APES IT Administrator for help.');
        }

        $user ??= new User(['password' => Str::random(64)]);
        $user->forceFill([
            'oidc_sub' => $identity->subject,
            'email' => $email,
            'name' => $identity->name ?? $email,
            'email_verified_at' => $user->email_verified_at ?? now(),
            'is_active' => true,
            'deactivated_at' => null,
        ])->save();

        $before = $user->getRoleNames()->all();
        $user->syncRoles($roles);
        $this->audit->recordRoleChange($user, $before, $roles, 'cloudron');

        $fresh = new FreshSignIn($request->session());
        $requestedAt = $fresh->pullRequestedAt();

        Auth::login($user);
        $request->session()->regenerate();
        $this->audit->record('auth.signed_in', $user, ['method' => 'cloudron', 'roles' => $roles], $user);

        if ($requestedAt !== null && ! FreshSignIn::honoured($requestedAt, $identity->authTime)) {
            $this->audit->record('auth.fresh_signin_not_honoured', $user);

            return response()->view('core.auth.denied', [
                'title' => 'Please sign in to Cloudron again',
                'reason' => 'This page needs a recent Cloudron sign-in, but Cloudron did not ask you to sign in again. Sign out of Cloudron, sign in again, then reopen the page.',
            ], 403);
        }

        $fresh->record($identity->authTime);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->audit->record('auth.signed_out', $request->user());

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'You have signed out of the intranet.');
    }

    private function denied(?User $user, string $reason, ?string $message = null): Response
    {
        $this->audit->record('auth.sign_in_denied', $user, ['reason' => $reason]);

        return response()->view('core.auth.denied', ['reason' => $message], 403);
    }
}
