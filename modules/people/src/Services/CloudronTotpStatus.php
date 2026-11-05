<?php

namespace Modules\People\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Read-only pull of Cloudron totpEnabled flags. Token never logged.
 */
final class CloudronTotpStatus
{
    /**
     * @return array{checked: int, matched: int, enabled: int, missing: int}
     */
    public function refresh(): array
    {
        $base = rtrim((string) config('intranet.cloudron.api_base'), '/');
        $token = (string) config('intranet.cloudron.api_token');

        if ($base === '' || $token === '') {
            throw new RuntimeException('Cloudron API is not configured for the 2FA status view.');
        }

        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(20)
            ->get($base.'/api/v1/users');

        if (! $response->successful()) {
            throw new RuntimeException('Cloudron users API refused the read-only request.');
        }

        /** @var list<array<string, mixed>> $remote */
        $remote = $response->json('users') ?? [];
        $byEmail = [];

        foreach ($remote as $row) {
            $email = Str::lower((string) ($row['email'] ?? ''));
            if ($email !== '' && array_key_exists('totpEnabled', $row)) {
                $byEmail[$email] = (bool) $row['totpEnabled'];
            }
        }

        $summary = ['checked' => count($byEmail), 'matched' => 0, 'enabled' => 0, 'missing' => 0];

        foreach (User::query()->active()->get() as $user) {
            $email = Str::lower($user->email);
            if (! array_key_exists($email, $byEmail)) {
                $summary['missing']++;

                continue;
            }

            $user->forceFill([
                'totp_enabled' => $byEmail[$email],
                'totp_checked_at' => now(),
            ])->save();

            $summary['matched']++;
            if ($byEmail[$email]) {
                $summary['enabled']++;
            }
        }

        return $summary;
    }
}
