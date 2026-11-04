<?php

namespace App\Core\Auth\Contracts;

use App\Core\Auth\Exceptions\IdentityProviderUnavailable;
use App\Core\Auth\OidcIdentity;
use Illuminate\Http\RedirectResponse;

interface IdentityProvider
{
    /**
     * @throws IdentityProviderUnavailable
     */
    public function authorizationRedirect(): RedirectResponse;

    /**
     * @throws IdentityProviderUnavailable
     */
    public function callbackIdentity(): OidcIdentity;
}
