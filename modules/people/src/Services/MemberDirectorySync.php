<?php

namespace Modules\People\Services;

use App\Core\Access\RoleName;
use App\Core\Audit\AuditLogger;
use App\Core\Auth\Contracts\DirectoryRoster;
use App\Core\Auth\DirectoryPerson;
use App\Core\Auth\Exceptions\DirectoryUnavailable;
use App\Core\Auth\GroupRoleMapper;
use App\Core\Notifications\AlertDispatcher;
use App\Core\Notifications\NotificationCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Create, update and deactivate intranet members from a read-only directory.
 */
final class MemberDirectorySync
{
    /** Stop and change nothing when a run would deactivate more than this share of active directory members. */
    public const MAX_DEACTIVATION_RATIO = 0.20;

    public function __construct(
        private readonly DirectoryRoster $roster,
        private readonly GroupRoleMapper $mapper,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return array{created: int, updated: int, deactivated: int, unchanged: int, seen: int, stopped?: string}
     */
    public function run(string $source = 'ldap'): array
    {
        try {
            $people = $this->roster->listPeople();
        } catch (DirectoryUnavailable $exception) {
            return $this->stop($source, 'directory_unavailable', $exception);
        } catch (Throwable $exception) {
            report($exception);

            return $this->stop($source, 'directory_unavailable', $exception);
        }

        if ($people === []) {
            return $this->stop($source, 'empty_roster');
        }

        $plannedDeactivations = $this->countPlannedDeactivations($people);
        $activeDirectoryMembers = User::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNotNull('oidc_sub')->orWhereNotNull('cloudron_username');
            })
            ->count();

        if (
            $activeDirectoryMembers > 0
            && ($plannedDeactivations / $activeDirectoryMembers) > self::MAX_DEACTIVATION_RATIO
        ) {
            return $this->stop($source, 'deactivation_cap', null, [
                'planned_deactivations' => $plannedDeactivations,
                'active_directory_members' => $activeDirectoryMembers,
            ]);
        }

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
     * @param  list<DirectoryPerson>  $people
     */
    private function countPlannedDeactivations(array $people): int
    {
        $keepEmails = [];
        foreach ($people as $person) {
            $roles = $this->mapper->rolesFor($person->groups);
            if ($person->active && $roles !== []) {
                $keepEmails[Str::lower($person->email)] = true;
            }
        }

        return User::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNotNull('oidc_sub')->orWhereNotNull('cloudron_username');
            })
            ->get()
            ->filter(fn (User $user) => ! isset($keepEmails[Str::lower($user->email)]))
            ->count();
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array{created: int, updated: int, deactivated: int, unchanged: int, seen: int, stopped: string}
     */
    private function stop(string $source, string $reason, ?Throwable $exception = null, array $extra = []): array
    {
        $summary = [
            'created' => 0,
            'updated' => 0,
            'deactivated' => 0,
            'unchanged' => 0,
            'seen' => 0,
            'stopped' => $reason,
            'source' => $source,
            ...$extra,
        ];

        $this->audit->record('people.directory_sync_stopped', null, $summary);

        $admins = User::role(RoleName::Admin->value)->get();
        AlertDispatcher::send(
            $admins,
            NotificationCategory::AccountSecurity,
            'Directory sync stopped on the APES Intranet.',
            'Open System status for details. No member records were changed.',
            route('admin.system.index'),
        );

        if ($exception !== null) {
            report($exception);
        }

        return [
            'created' => 0,
            'updated' => 0,
            'deactivated' => 0,
            'unchanged' => 0,
            'seen' => 0,
            'stopped' => $reason,
        ];
    }

    /**
     * @return array{status: string, user: ?User}
     */
    private function syncPerson(DirectoryPerson $person, string $source): array
    {
        $roles = $this->mapper->rolesFor($person->groups);
        $email = Str::lower($person->email);

        $user = User::query()->where('email', $email)->first();

        if (! $person->active || $roles === []) {
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
