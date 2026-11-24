<?php

namespace App\Core\System\Checks;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Confirms the read-only Cloudron API token is set and answers.
 * Never exposes the token value.
 */
class CloudronApiCheck implements AddonCheck
{
    public function key(): string
    {
        return 'cloudron_api';
    }

    public function configured(): bool
    {
        return filled(config('intranet.cloudron.api_token'))
            && filled(config('intranet.cloudron.api_base'));
    }

    public function probe(): void
    {
        $base = rtrim((string) config('intranet.cloudron.api_base'), '/');
        $token = (string) config('intranet.cloudron.api_token');

        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(10)
            ->get($base.'/api/v1/users', ['per_page' => 1]);

        if (! $response->successful()) {
            throw new RuntimeException('Cloudron API refused the read-only request.');
        }
    }

    public function details(): array
    {
        $host = parse_url((string) config('intranet.cloudron.api_base'), PHP_URL_HOST);

        return [
            'host' => is_string($host) ? $host : null,
            'port' => null,
            'database' => $this->configured() ? '•••••• (set)' : null,
        ];
    }
}
