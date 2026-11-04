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

    public function authorizationRedirect(): RedirectResponse
    {
        if ($this->unavailable) {
            throw new IdentityProviderUnavailable('down');
        }

        return redirect()->away('https://my.cloudron.example.test/openid/auth');
    }

    public function callbackIdentity(): OidcIdentity
    {
        if ($this->unavailable || $this->identity === null) {
            throw new IdentityProviderUnavailable('down');
        }

        return $this->identity;
    }
}
