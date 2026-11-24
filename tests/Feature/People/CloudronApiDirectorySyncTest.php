<?php

use App\Core\Access\RoleName;
use App\Core\Audit\AuditLog;
use App\Core\Auth\Cloudron\CloudronApiDirectoryRoster;
use App\Core\Auth\Contracts\DirectoryRoster;
use App\Core\Auth\Exceptions\DirectoryUnavailable;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Modules\People\Services\MemberDirectorySync;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Config::set('intranet.cloudron.api_base', 'https://my.cloudron.example.test');
    Config::set('intranet.cloudron.api_token', 'test-cloudron-token-secret');
    app()->bind(DirectoryRoster::class, CloudronApiDirectoryRoster::class);
    Notification::fake();
});

function cloudronDirectoryHttp(array $users, array $groups = []): void
{
    Http::fake([
        'https://my.cloudron.example.test/api/v1/groups*' => Http::response(['groups' => $groups], 200),
        'https://my.cloudron.example.test/api/v1/users*' => Http::response(['users' => $users], 200),
    ]);
}

it('creates updates and deactivates members from fake Cloudron API responses', function () {
    $existing = User::factory()->create([
        'email' => 'leavers@example.test',
        'oidc_sub' => 'sub-leaver',
        'cloudron_username' => 'leaver',
        'is_active' => true,
    ])->assignRole(RoleName::Staff->value);

    $keepers = [];
    foreach (range(1, 4) as $i) {
        User::factory()->create([
            'email' => "keep{$i}@example.test",
            'oidc_sub' => "sub-keep-{$i}",
            'cloudron_username' => "keep{$i}",
            'is_active' => true,
        ])->assignRole(RoleName::Staff->value);
        $keepers[] = [
            'id' => "uid-keep-{$i}",
            'email' => "keep{$i}@example.test",
            'username' => "keep{$i}",
            'displayName' => "Keep {$i}",
            'groupIds' => ['gid-staff'],
            'active' => true,
        ];
    }

    DB::table('sessions')->insert([
        'id' => 'session-leaver',
        'user_id' => $existing->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'test',
        'payload' => 'x',
        'last_activity' => time(),
    ]);

    cloudronDirectoryHttp(
        users: [
            [
                'id' => 'uid-alex',
                'email' => 'alex@example.test',
                'username' => 'alex',
                'displayName' => 'Alex Example',
                'groupIds' => ['gid-staff'],
                'active' => true,
            ],
            [
                'id' => 'uid-blake',
                'email' => 'blake@example.test',
                'username' => 'blake',
                'displayName' => 'Blake Example',
                'groupIds' => ['gid-vol'],
                'active' => true,
            ],
            ...$keepers,
        ],
        groups: [
            ['id' => 'gid-staff', 'name' => 'intranet.staff', 'userIds' => ['uid-alex', 'uid-keep-1', 'uid-keep-2', 'uid-keep-3', 'uid-keep-4']],
            ['id' => 'gid-vol', 'name' => 'intranet.volunteer', 'userIds' => ['uid-blake']],
        ],
    );

    $summary = app(MemberDirectorySync::class)->run('cloudron_api');

    expect($summary['created'])->toBe(2)
        ->and($summary)->not->toHaveKey('stopped')
        ->and(User::where('email', 'alex@example.test')->firstOrFail()->hasRole('staff'))->toBeTrue();

    $existing->refresh();
    expect($existing->is_active)->toBeFalse()
        ->and(DB::table('sessions')->where('user_id', $existing->id)->exists())->toBeFalse();

    expect(AuditLog::query()->where('event', 'people.directory_synced')->exists())->toBeTrue();
});

it('deactivates a user marked inactive in Cloudron and ends their sessions', function () {
    $user = User::factory()->create([
        'email' => 'inactive@example.test',
        'oidc_sub' => 'sub-inactive',
        'cloudron_username' => 'inactive',
        'is_active' => true,
    ])->assignRole(RoleName::Staff->value);

    $keepers = [];
    foreach (range(1, 4) as $i) {
        User::factory()->create([
            'email' => "stay{$i}@example.test",
            'oidc_sub' => "sub-stay-{$i}",
            'cloudron_username' => "stay{$i}",
            'is_active' => true,
        ])->assignRole(RoleName::Staff->value);
        $keepers[] = [
            'id' => "uid-stay-{$i}",
            'email' => "stay{$i}@example.test",
            'username' => "stay{$i}",
            'displayName' => "Stay {$i}",
            'groupIds' => ['gid-staff'],
            'active' => true,
        ];
    }

    DB::table('sessions')->insert([
        'id' => 'session-inactive',
        'user_id' => $user->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'test',
        'payload' => 'x',
        'last_activity' => time(),
    ]);

    cloudronDirectoryHttp(
        users: [
            [
                'id' => 'uid-inactive',
                'email' => 'inactive@example.test',
                'username' => 'inactive',
                'displayName' => 'Inactive Person',
                'groupIds' => ['gid-staff'],
                'active' => false,
            ],
            ...$keepers,
        ],
        groups: [
            ['id' => 'gid-staff', 'name' => 'intranet.staff', 'userIds' => ['uid-inactive', 'uid-stay-1', 'uid-stay-2', 'uid-stay-3', 'uid-stay-4']],
        ],
    );

    $summary = app(MemberDirectorySync::class)->run('cloudron_api');

    $user->refresh();
    expect($user->is_active)->toBeFalse()
        ->and($user->getRoleNames()->all())->toBe([])
        ->and(DB::table('sessions')->where('user_id', $user->id)->exists())->toBeFalse()
        ->and($summary['deactivated'])->toBeGreaterThanOrEqual(1);
});

it('stops with no changes when the Cloudron API errors', function () {
    User::factory()->create([
        'email' => 'admin@example.test',
        'is_active' => true,
    ])->assignRole(RoleName::Admin->value);

    Http::fake([
        'https://my.cloudron.example.test/api/v1/groups*' => Http::response(['message' => 'nope'], 500),
        'https://my.cloudron.example.test/api/v1/users*' => Http::response(['message' => 'nope'], 500),
    ]);

    $before = User::query()->count();
    $summary = app(MemberDirectorySync::class)->run('cloudron_api');

    expect($summary['stopped'])->toBe('directory_unavailable')
        ->and($summary['created'])->toBe(0)
        ->and($summary['deactivated'])->toBe(0)
        ->and(User::query()->count())->toBe($before)
        ->and(AuditLog::query()->where('event', 'people.directory_sync_stopped')->exists())->toBeTrue();
});

it('stops with no changes when the Cloudron API returns no users', function () {
    User::factory()->create()->assignRole(RoleName::Admin->value);

    cloudronDirectoryHttp(users: [], groups: []);

    $summary = app(MemberDirectorySync::class)->run('cloudron_api');

    expect($summary['stopped'])->toBe('empty_roster')
        ->and($summary['deactivated'])->toBe(0);
});

it('stops when a run would deactivate more than 20 percent of active members', function () {
    User::factory()->create()->assignRole(RoleName::Admin->value);

    foreach (range(1, 5) as $i) {
        User::factory()->create([
            'email' => "member{$i}@example.test",
            'oidc_sub' => "sub-{$i}",
            'cloudron_username' => "member{$i}",
            'is_active' => true,
        ])->assignRole(RoleName::Staff->value);
    }

    // Keep only one of five → 80% would deactivate.
    cloudronDirectoryHttp(
        users: [
            [
                'id' => 'uid-1',
                'email' => 'member1@example.test',
                'username' => 'member1',
                'displayName' => 'Member 1',
                'groupIds' => ['gid-staff'],
                'active' => true,
            ],
        ],
        groups: [
            ['id' => 'gid-staff', 'name' => 'intranet.staff', 'userIds' => ['uid-1']],
        ],
    );

    $summary = app(MemberDirectorySync::class)->run('cloudron_api');

    expect($summary['stopped'])->toBe('deactivation_cap')
        ->and(User::where('email', 'member2@example.test')->firstOrFail()->is_active)->toBeTrue()
        ->and(AuditLog::query()->where('event', 'people.directory_sync_stopped')->exists())->toBeTrue();
});

it('never puts the Cloudron API token in logs or exceptions', function () {
    Config::set('intranet.cloudron.api_token', 'super-secret-token-value');

    Http::fake([
        'https://my.cloudron.example.test/api/v1/groups*' => function (Request $request) {
            expect($request->header('Authorization')[0] ?? '')->toContain('super-secret-token-value');

            return Http::response(['message' => 'fail'], 401);
        },
    ]);

    try {
        app(CloudronApiDirectoryRoster::class)->listPeople();
        expect(false)->toBeTrue();
    } catch (DirectoryUnavailable $exception) {
        expect($exception->getMessage())->not->toContain('super-secret-token-value')
            ->and((string) $exception)->not->toContain('super-secret-token-value');
    }
});
