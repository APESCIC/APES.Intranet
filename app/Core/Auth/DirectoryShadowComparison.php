<?php

namespace App\Core\Auth;

use App\Core\Audit\AuditLogger;
use App\Core\Auth\Cloudron\LdapDirectoryGroups;
use App\Core\Auth\Contracts\DirectoryRoster;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

/**
 * Read-only compare of Cloudron API roster vs LDAP when both are available.
 */
final class DirectoryShadowComparison
{
    public const CACHE_KEY = 'intranet.directory_shadow_comparison';

    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return array{
     *     status: string,
     *     compared_at: string,
     *     cloudron_count: int,
     *     ldap_count: int,
     *     difference_count: int,
     *     differences: list<array{type: string, email: string}>,
     *     usernames: list<string>,
     *     message?: string
     * }
     */
    public function run(DirectoryRoster $cloudronRoster): array
    {
        $comparedAt = now()->toIso8601String();

        try {
            $cloudron = $this->indexByEmail($cloudronRoster->listPeople());
            $ldap = $this->indexByEmail(app(LdapDirectoryGroups::class)->listPeople());
        } catch (Throwable $exception) {
            report($exception);
            $result = [
                'status' => 'failed',
                'compared_at' => $comparedAt,
                'cloudron_count' => 0,
                'ldap_count' => 0,
                'difference_count' => 0,
                'differences' => [],
                'usernames' => [],
                'message' => 'LDAP or Cloudron comparison could not run. Sign-in and sync were not blocked.',
            ];
            $this->store($result);

            return $result;
        }

        $differences = [];
        $emails = array_unique([...array_keys($cloudron), ...array_keys($ldap)]);
        sort($emails);

        foreach ($emails as $email) {
            $left = $cloudron[$email] ?? null;
            $right = $ldap[$email] ?? null;

            if ($left === null) {
                $differences[] = ['type' => 'missing_on_cloudron', 'email' => $email];

                continue;
            }
            if ($right === null) {
                $differences[] = ['type' => 'missing_on_ldap', 'email' => $email];

                continue;
            }

            $leftGroups = $left->groups;
            $rightGroups = $right->groups;
            sort($leftGroups);
            sort($rightGroups);

            if ($leftGroups !== $rightGroups) {
                $differences[] = ['type' => 'groups_differ', 'email' => $email];
            }
            if ($left->active !== $right->active) {
                $differences[] = ['type' => 'active_differ', 'email' => $email];
            }
        }

        $usernames = [];
        foreach ($differences as $difference) {
            $person = $cloudron[$difference['email']] ?? $ldap[$difference['email']] ?? null;
            if ($person?->username) {
                $usernames[] = $person->username;
            }
        }

        $result = [
            'status' => $differences === [] ? 'match' : 'differences',
            'compared_at' => $comparedAt,
            'cloudron_count' => count($cloudron),
            'ldap_count' => count($ldap),
            'difference_count' => count($differences),
            'differences' => $differences,
            'usernames' => array_values(array_unique($usernames)),
        ];

        $this->store($result);

        return $result;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function latest(): ?array
    {
        $cached = Cache::get(self::CACHE_KEY);

        return is_array($cached) ? $cached : null;
    }

    /**
     * @param  list<DirectoryPerson>  $people
     * @return array<string, DirectoryPerson>
     */
    private function indexByEmail(array $people): array
    {
        $indexed = [];
        foreach ($people as $person) {
            $indexed[Str::lower($person->email)] = $person;
        }

        return $indexed;
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function store(array $result): void
    {
        Cache::forever(self::CACHE_KEY, $result);
        $this->audit->record('people.directory_shadow_compared', null, [
            'status' => $result['status'],
            'difference_count' => $result['difference_count'],
            'cloudron_count' => $result['cloudron_count'],
            'ldap_count' => $result['ldap_count'],
            'usernames' => $result['usernames'],
            'difference_types' => array_count_values(array_column($result['differences'], 'type')),
        ]);
    }
}
