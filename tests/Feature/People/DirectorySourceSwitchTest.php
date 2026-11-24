<?php

use App\Core\Access\RoleName;
use App\Core\Audit\AuditLog;
use App\Core\Auth\Cloudron\CloudronApiDirectoryRoster;
use App\Core\Auth\Cloudron\LdapDirectoryGroups;
use App\Core\Auth\Contracts\DirectoryRoster;
use App\Core\Auth\DirectoryPerson;
use App\Core\Auth\DirectoryShadowComparison;
use App\Core\Auth\DirectorySource;
use App\Core\Auth\Exceptions\DirectoryUnavailable;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Tests\Fakes\FakeDirectoryRoster;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Cache::forget(DirectoryShadowComparison::CACHE_KEY);
});

it('binds the Cloudron API roster when the directory source is cloudron', function () {
    Config::set('intranet.directory_source', 'cloudron');

    expect(app(DirectoryRoster::class))->toBeInstanceOf(CloudronApiDirectoryRoster::class)
        ->and(DirectorySource::syncAuditSource())->toBe('cloudron_api');
});

it('binds the LDAP roster when the directory source is ldap', function () {
    Config::set('intranet.directory_source', 'ldap');

    expect(app(DirectoryRoster::class))->toBeInstanceOf(LdapDirectoryGroups::class)
        ->and(DirectorySource::syncAuditSource())->toBe('ldap');
});

it('records one difference when a group is missing on one side', function () {
    Config::set('intranet.directory_source', 'cloudron');
    Config::set('intranet.ldap.host', 'ldap.example.test');
    Config::set('intranet.ldap.bind_dn', 'cn=bind,dc=example,dc=test');

    $cloudron = new FakeDirectoryRoster;
    $cloudron->people = [
        new DirectoryPerson('alex@example.test', 'Alex', 'alex', ['intranet.staff'], true),
    ];

    $ldap = new FakeDirectoryRoster;
    $ldap->people = [
        new DirectoryPerson('alex@example.test', 'Alex', 'alex', ['intranet.staff', 'intranet.hr'], true),
    ];

    app()->instance(DirectoryRoster::class, $cloudron);
    app()->instance(LdapDirectoryGroups::class, $ldap);

    // DirectoryShadowComparison type-hints DirectoryRoster for cloudron and
    // resolves LdapDirectoryGroups from the container for LDAP.
    $result = app(DirectoryShadowComparison::class)->run($cloudron);

    expect($result['status'])->toBe('differences')
        ->and($result['difference_count'])->toBe(1)
        ->and($result['differences'][0]['type'])->toBe('groups_differ')
        ->and(DirectoryShadowComparison::latest()['difference_count'])->toBe(1)
        ->and(AuditLog::query()->where('event', 'people.directory_shadow_compared')->exists())->toBeTrue();
});

it('never blocks when the LDAP shadow comparison fails', function () {
    Config::set('intranet.directory_source', 'cloudron');
    Config::set('intranet.ldap.host', 'ldap.example.test');
    Config::set('intranet.ldap.bind_dn', 'cn=bind,dc=example,dc=test');

    $cloudron = new FakeDirectoryRoster;
    $cloudron->people = [
        new DirectoryPerson('alex@example.test', 'Alex', 'alex', ['intranet.staff'], true),
    ];

    $ldap = new class implements DirectoryRoster
    {
        public function listPeople(): array
        {
            throw new DirectoryUnavailable('LDAP down');
        }
    };

    app()->instance(LdapDirectoryGroups::class, $ldap);

    $result = app(DirectoryShadowComparison::class)->run($cloudron);

    expect($result['status'])->toBe('failed')
        ->and($result['difference_count'])->toBe(0);
});

it('shows the active directory source on system status', function () {
    Config::set('intranet.directory_source', 'cloudron');
    $admin = User::factory()->create()->assignRole(RoleName::Admin->value);

    $this->actingAs($admin)
        ->withSession(['intranet.auth_time' => now()->getTimestamp()])
        ->get(route('admin.system.index'))
        ->assertOk()
        ->assertSee('data-directory-source', false)
        ->assertSee('>cloudron<', false);
});
