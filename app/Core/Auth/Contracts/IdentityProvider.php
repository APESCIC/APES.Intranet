<?php

namespace App\Core\Auth\Contracts;

use App\Core\Auth\Exceptions\IdentityProviderUnavailable;
use App\Core\Auth\OidcIdentity;
use Illuminate\Http\RedirectResponse;

interface IdentityProvider
{
    /**
     * @param  bool  $forceLogin  Ask Cloudron for a new sign-in even if its own session is still open.
     *
     * @throws IdentityProviderUnavailable
     */
    public function authorizationRedirect(bool $forceLogin = false): RedirectResponse;

    /**
     * @throws IdentityProviderUnavailable
     */
    public function callbackIdentity(): OidcIdentity;
}
