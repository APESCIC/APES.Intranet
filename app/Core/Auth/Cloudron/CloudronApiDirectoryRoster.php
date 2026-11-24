<?php

namespace App\Core\Auth\Cloudron;

use App\Core\Auth\Contracts\DirectoryRoster;
use App\Core\Auth\DirectoryPerson;
use App\Core\Auth\Exceptions\DirectoryUnavailable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Read-only directory roster from the Cloudron dashboard API.
 * Token never logged; GET only.
 */
final class CloudronApiDirectoryRoster implements DirectoryRoster
{
    /**
     * @return list<DirectoryPerson>
     *
     * @throws DirectoryUnavailable
     */
    public function listPeople(): array
    {
        $base = rtrim((string) config('intranet.cloudron.api_base'), '/');
        $token = (string) config('intranet.cloudron.api_token');

        if ($base === '' || $token === '') {
            throw new DirectoryUnavailable('Cloudron API is not configured for directory sync.');
        }

        try {
            $groupsById = $this->fetchGroupNames($base, $token);
            $users = $this->fetchUsers($base, $token);
        } catch (DirectoryUnavailable $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($this->redact($exception));

            throw new DirectoryUnavailable('Cloudron directory API request failed.', 0, $this->redact($exception));
        }

        $people = [];

        foreach ($users as $row) {
            if (! is_array($row)) {
                continue;
            }

            $email = Str::lower(trim((string) ($row['email'] ?? '')));
            if ($email === '') {
                continue;
            }

            $groupIds = $row['groupIds'] ?? [];
            $groups = [];
            if (is_array($groupIds)) {
                foreach ($groupIds as $groupId) {
                    $id = (string) $groupId;
                    if ($id !== '' && isset($groupsById[$id])) {
                        $groups[] = $groupsById[$id];
                    }
                }
            }

            $username = isset($row['username']) ? trim((string) $row['username']) : '';
            $name = trim((string) ($row['displayName'] ?? ''));
            if ($name === '') {
                $name = $username !== '' ? $username : $email;
            }

            $people[] = new DirectoryPerson(
                email: $email,
                name: $name,
                username: $username !== '' ? $username : null,
                groups: array_values(array_unique($groups)),
                active: array_key_exists('active', $row) ? (bool) $row['active'] : true,
            );
        }

        return $people;
    }

    /**
     * @return array<string, string> group id → lower-case name
     */
    private function fetchGroupNames(string $base, string $token): array
    {
        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(20)
            ->get($base.'/api/v1/groups');

        if (! $response->successful()) {
            throw new DirectoryUnavailable('Cloudron groups API refused the read-only request.');
        }

        /** @var list<array<string, mixed>> $groups */
        $groups = $response->json('groups') ?? [];
        $byId = [];

        foreach ($groups as $group) {
            if (! is_array($group)) {
                continue;
            }
            $id = (string) ($group['id'] ?? '');
            $name = Str::lower(trim((string) ($group['name'] ?? '')));
            if ($id !== '' && $name !== '') {
                $byId[$id] = $name;
            }
        }

        return $byId;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchUsers(string $base, string $token): array
    {
        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(20)
            ->get($base.'/api/v1/users', ['per_page' => 1000]);

        if (! $response->successful()) {
            throw new DirectoryUnavailable('Cloudron users API refused the read-only request.');
        }

        /** @var list<array<string, mixed>> $users */
        $users = $response->json('users') ?? [];

        return is_array($users) ? $users : [];
    }

    private function redact(Throwable $exception): Throwable
    {
        $token = (string) config('intranet.cloudron.api_token');
        if ($token === '') {
            return $exception;
        }

        $message = str_replace($token, '[redacted]', $exception->getMessage());

        if ($exception instanceof RequestException) {
            return new DirectoryUnavailable($message, (int) $exception->getCode());
        }

        return new DirectoryUnavailable($message, (int) $exception->getCode());
    }
}
