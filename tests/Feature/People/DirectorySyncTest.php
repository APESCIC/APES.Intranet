<?php

use App\Core\Access\RoleName;
use App\Core\Audit\AuditLog;
use App\Core\Auth\Contracts\DirectoryRoster;
use App\Core\Auth\DirectoryPerson;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\People\Services\MemberDirectorySync;
use Tests\Fakes\FakeDirectoryRoster;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->roster = new FakeDirectoryRoster;
    app()->instance(DirectoryRoster::class, $this->roster);
});

it('creates updates and deactivates members from a fake LDAP roster', function () {
    $existing = User::factory()->create([
        'email' => 'leavers@example.test',
        'oidc_sub' => 'sub-leaver',
        'cloudron_username' => 'leaver',
        'is_active' => true,
    ])->assignRole(RoleName::Staff->value);

    DB::table('sessions')->insert([
        'id' => 'session-leaver',
        'user_id' => $existing->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'test',
        'payload' => 'x',
        'last_activity' => time(),
    ]);

    $this->roster->people = [
        new DirectoryPerson('alex@example.test', 'Alex Example', 'alex', ['intranet.staff'], true),
        new DirectoryPerson('blake@example.test', 'Blake Example', 'blake', ['intranet.volunteer'], true),
    ];

    $summary = app(MemberDirectorySync::class)->run('ldap');

    expect($summary['created'])->toBe(2)
        ->and(User::where('email', 'alex@example.test')->firstOrFail()->is_active)->toBeTrue()
        ->and(User::where('email', 'alex@example.test')->firstOrFail()->hasRole('staff'))->toBeTrue();

    $existing->refresh();
    expect($existing->is_active)->toBeFalse()
        ->and($existing->getRoleNames()->all())->toBe([])
        ->and(DB::table('sessions')->where('user_id', $existing->id)->exists())->toBeFalse();

    expect(AuditLog::query()->where('event', 'people.directory_synced')->exists())->toBeTrue();

    $second = app(MemberDirectorySync::class)->run('ldap');
    expect($second['created'])->toBe(0)
        ->and($second['updated'])->toBe(0)
        ->and($second['deactivated'])->toBe(0);
});

it('keeps a second run unchanged when the roster is the same', function () {
    $this->roster->people = [
        new DirectoryPerson('alex@example.test', 'Alex Example', 'alex', ['intranet.staff'], true),
    ];

    app(MemberDirectorySync::class)->run('ldap');
    $again = app(MemberDirectorySync::class)->run('ldap');

    expect($again['unchanged'])->toBeGreaterThan(0)
        ->and($again['created'])->toBe(0)
        ->and($again['deactivated'])->toBe(0);
});
