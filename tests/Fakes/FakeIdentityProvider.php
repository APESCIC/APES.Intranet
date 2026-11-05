<?php

namespace Tests\Fakes;

use App\Core\Auth\Contracts\IdentityProvider;
use App\Core\Auth\Exceptions\IdentityProviderUnavailable;
use App\Core\Auth\OidcIdentity;
use Illuminate\Http\RedirectResponse;

final class FakeIdentityProvider implements IdentityProvider
{
    public ?OidcIdentity $identity = null;

    public bool $unavailable = false;

    public ?bool $lastForceLogin = null;

    public function authorizationRedirect(bool $forceLogin = false): RedirectResponse
    {
        if ($this->unavailable) {
            throw new IdentityProviderUnavailable('down');
        }

        $this->lastForceLogin = $forceLogin;

        return redirect()->away('https://my.cloudron.example.test/openid/auth'.($forceLogin ? '?prompt=login&max_age=0' : ''));
    }

    public function callbackIdentity(): OidcIdentity
    {
        if ($this->unavailable || $this->identity === null) {
            throw new IdentityProviderUnavailable('down');
        }

        return $this->identity;
    }
}
