<?php

namespace Modules\People\Services;

use App\Core\Audit\AuditLogger;
use App\Core\Auth\Contracts\DirectoryRoster;
use App\Core\Auth\DirectoryPerson;
use App\Core\Auth\GroupRoleMapper;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Create, update and deactivate intranet members from a read-only directory.
 */
final class MemberDirectorySync
{
    public function __construct(
        private readonly DirectoryRoster $roster,
        private readonly GroupRoleMapper $mapper,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return array{created: int, updated: int, deactivated: int, unchanged: int, seen: int}
     */
    public function run(string $source = 'ldap'): array
    {
        $people = $this->roster->listPeople();
        $summary = ['created' => 0, 'updated' => 0, 'deactivated' => 0, 'unchanged' => 0, 'seen' => count($people)];
        $seenIds = [];

        DB::transaction(function () use ($people, $source, &$summary, &$seenIds) {
            foreach ($people as $person) {
                $result = $this->syncPerson($person, $source);
                $summary[$result['status']]++;
                if ($result['user'] !== null) {
                    $seenIds[] = (int) $result['user']->getKey();
                }
            }

            $query = User::query()->where(function ($q) {
                $q->whereNotNull('oidc_sub')->orWhereNotNull('cloudron_username');
            });
            if ($seenIds !== []) {
                $query->whereNotIn('id', $seenIds);
            }

            foreach ($query->get() as $stale) {
                if ($this->deactivate($stale, $source)) {
                    $summary['deactivated']++;
                } else {
                    $summary['unchanged']++;
                }
            }
        });

        $this->audit->record('people.directory_synced', null, $summary);

        return $summary;
    }

    /**
     * @return array{status: string, user: ?User}
     */
    private function syncPerson(DirectoryPerson $person, string $source): array
    {
        $roles = $this->mapper->rolesFor($person->groups);
        $email = Str::lower($person->email);

        $user = User::query()->where('email', $email)->first();

        if ($roles === []) {
            if ($user === null) {
                return ['status' => 'unchanged', 'user' => null];
            }

            return [
                'status' => $this->deactivate($user, $source) ? 'deactivated' : 'unchanged',
                'user' => $user,
            ];
        }

        $created = $user === null;
        $user ??= new User(['password' => Str::random(64)]);

        $beforeRoles = $created ? [] : $user->getRoleNames()->all();
        $dirty = $created
            || $user->name !== $person->name
            || $user->email !== $email
            || $user->cloudron_username !== $person->username
            || ! $user->is_active
            || $beforeRoles !== $roles;

        $user->forceFill([
            'email' => $email,
            'name' => $person->name,
            'cloudron_username' => $person->username,
            'is_active' => true,
            'deactivated_at' => null,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        $user->syncRoles($roles);
        $this->audit->recordRoleChange($user, $beforeRoles, $roles, $source);

        return [
            'status' => $created ? 'created' : ($dirty ? 'updated' : 'unchanged'),
            'user' => $user,
        ];
    }

    private function deactivate(User $user, string $source): bool
    {
        $changed = false;

        if ($user->is_active) {
            $user->forceFill([
                'is_active' => false,
                'deactivated_at' => now(),
            ])->save();
            $changed = true;
        }

        $before = $user->getRoleNames()->all();
        if ($before !== []) {
            $user->syncRoles([]);
            $this->audit->recordRoleChange($user, $before, [], $source);
            $changed = true;
        }

        if ($changed) {
            $this->endSessions($user);
            $this->audit->record('people.member_deactivated', $user, ['source' => $source]);
        }

        return $changed;
    }

    private function endSessions(User $user): void
    {
        DB::table('sessions')->where('user_id', $user->getKey())->delete();
    }
}
