<?php

namespace App\Core\System\Checks;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Fetches Cloudron's OIDC discovery document and checks the issuer matches.
 */
class OidcCheck implements AddonCheck
{
    public function key(): string
    {
        return 'oidc';
    }

    public function configured(): bool
    {
        return filled(config('intranet.oidc.issuer'))
            && filled(config('intranet.oidc.client_id'))
            && filled(config('intranet.oidc.client_secret'));
    }

    public function probe(): void
    {
        $issuer = rtrim((string) config('intranet.oidc.issuer'), '/');
        $parts = parse_url($issuer);

        if (! is_array($parts) || ($parts['scheme'] ?? null) !== 'https' || ! isset($parts['host'])) {
            throw new RuntimeException('OIDC issuer is not an https URL.');
        }

        $origin = 'https://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
        $metadata = Http::acceptJson()->connectTimeout(5)->timeout(10)
            ->get($origin.'/.well-known/openid-configuration')
            ->throw()
            ->json();

        if (! is_array($metadata) || ($metadata['issuer'] ?? null) !== $issuer) {
            throw new RuntimeException('OIDC discovery does not match the issuer.');
        }
    }

    public function details(): array
    {
        $host = parse_url((string) config('intranet.oidc.issuer'), PHP_URL_HOST);

        return ['host' => is_string($host) ? $host : null, 'port' => null, 'database' => null];
    }
}
