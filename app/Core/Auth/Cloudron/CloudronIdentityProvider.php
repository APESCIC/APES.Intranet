<?php

namespace App\Core\Auth\Cloudron;

use App\Core\Auth\Contracts\IdentityProvider;
use App\Core\Auth\Exceptions\IdentityProviderUnavailable;
use App\Core\Auth\OidcIdentity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Throwable;

class CloudronIdentityProvider implements IdentityProvider
{
    public function authorizationRedirect(): RedirectResponse
    {
        try {
            $client = $this->client();
            $authenticated = $client->authenticate();
            $redirect = $client->capturedRedirect();
        } catch (Throwable $exception) {
            throw new IdentityProviderUnavailable('Cloudron sign-in is unavailable.', previous: $exception);
        }

        if ($authenticated || $redirect === null || trim($redirect) === '') {
            throw new IdentityProviderUnavailable('Cloudron sign-in did not produce a redirect.');
        }

        return redirect()->away($redirect);
    }

    public function callbackIdentity(): OidcIdentity
    {
        try {
            $client = $this->client();

            if (! $client->authenticate()) {
                throw new IdentityProviderUnavailable('Cloudron sign-in did not complete.');
            }

            $subject = $this->claim($client->getVerifiedClaims('sub'));
            $userInfo = $client->requestUserInfo();
        } catch (IdentityProviderUnavailable $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new IdentityProviderUnavailable('Cloudron sign-in is unavailable.', previous: $exception);
        }

        if (! is_object($userInfo)) {
            throw new IdentityProviderUnavailable('Cloudron returned an invalid profile.');
        }

        $userInfoSubject = $this->claim($userInfo->sub ?? null);

        if ($subject !== null && $userInfoSubject !== null && ! hash_equals($subject, $userInfoSubject)) {
            throw new IdentityProviderUnavailable('Cloudron subject claims do not match.');
        }

        return new OidcIdentity(
            subject: $subject,
            email: $this->claim($userInfo->email ?? null),
            name: $this->claim($userInfo->name ?? null),
        );
    }

    private function client(): SessionOpenIdConnectClient
    {
        $issuer = rtrim($this->required('issuer'), '/');
        $scopes = config('intranet.oidc.scopes');

        $client = new SessionOpenIdConnectClient($issuer, $this->required('client_id'), $this->required('client_secret'));
        $client->providerConfigParam($this->discovery($issuer));
        $client->setRedirectURL($this->required('redirect_uri'));
        $client->setCodeChallengeMethod('S256');
        $client->addScope(array_values(array_diff(is_array($scopes) ? $scopes : [], ['openid'])));

        return $client;
    }

    /**
     * Cloudron publishes discovery at the dashboard origin, while the issuer
     * and its endpoints live under /openid.
     *
     * @return array<string, mixed>
     */
    private function discovery(string $issuer): array
    {
        $parts = parse_url($issuer);

        if (! is_array($parts) || ($parts['scheme'] ?? null) !== 'https' || ! isset($parts['host'])) {
            throw new IdentityProviderUnavailable('The OIDC issuer must be an https URL.');
        }

        $origin = 'https://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
        $metadata = Http::acceptJson()->connectTimeout(5)->timeout(10)
            ->get($origin.'/.well-known/openid-configuration')
            ->throw()
            ->json();

        if (! is_array($metadata) || ($metadata['issuer'] ?? null) !== $issuer) {
            throw new IdentityProviderUnavailable('OIDC discovery does not match the issuer.');
        }

        return $metadata;
    }

    private function required(string $key): string
    {
        $value = config('intranet.oidc.'.$key);

        if (! is_string($value) || trim($value) === '') {
            throw new IdentityProviderUnavailable("OIDC {$key} is not configured.");
        }

        return trim($value);
    }

    private function claim(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
