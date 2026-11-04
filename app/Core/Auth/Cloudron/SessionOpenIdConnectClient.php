<?php

namespace App\Core\Auth\Cloudron;

use Jumbojett\OpenIDConnectClient;

/**
 * Keeps OIDC state in Laravel's session and captures the redirect instead of
 * sending headers itself.
 */
class SessionOpenIdConnectClient extends OpenIDConnectClient
{
    private ?string $capturedRedirect = null;

    public function redirect(string $url): void
    {
        $this->capturedRedirect = $url;
    }

    public function capturedRedirect(): ?string
    {
        return $this->capturedRedirect;
    }

    protected function startSession(): void {}

    protected function commitSession(): void {}

    protected function getSessionKey(string $key): mixed
    {
        return session()->get('intranet.oidc.'.$key, false);
    }

    protected function setSessionKey(string $key, mixed $value): void
    {
        session()->put('intranet.oidc.'.$key, $value);
    }

    protected function unsetSessionKey(string $key): void
    {
        session()->forget('intranet.oidc.'.$key);
    }
}
