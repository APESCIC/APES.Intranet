<?php

use App\Core\Access\AccessMatrix;
use App\Core\Access\RoleName;
use App\Core\Auth\GroupRoleMapper;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Finder\Finder;

uses(RefreshDatabase::class);

/**
 * Cloudron groups removed on 5 Nov 2026. They must never grant access.
 */
const LEGACY_GROUPS = [
    'intranet.users',
    'intranet.manager',
    'intranet.managers',
    'intranet.supervisors',
    'intranet.administrator',
    'intranet.students',
    'intranet.volunteers',
];

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    fakeCloudron($this);
});

it('lists exactly the 8 approved groups', function () {
    expect(app(GroupRoleMapper::class)->approvedGroups())->toBe([
        'intranet.staff',
        'intranet.volunteer',
        'intranet.student',
        'intranet.supervisor',
        'intranet.management',
        'intranet.hr',
        'intranet.legal',
        'intranet.admin',
    ]);
});

it('signs each approved group in with the right role', function (string $group, RoleName $role) {
    signInWithCloudron($this, [$group])->assertRedirect(route('dashboard'));

    expect(User::where('email', 'alex@example.test')->firstOrFail()->getRoleNames()->all())->toBe([$role->value]);
})->with([
    'staff' => ['intranet.staff', RoleName::Staff],
    'volunteer' => ['intranet.volunteer', RoleName::Volunteer],
    'student' => ['intranet.student', RoleName::Student],
    'supervisor' => ['intranet.supervisor', RoleName::Supervisor],
    'management' => ['intranet.management', RoleName::Management],
    'hr' => ['intranet.hr', RoleName::Hr],
    'legal' => ['intranet.legal', RoleName::Legal],
    'admin' => ['intranet.admin', RoleName::Admin],
]);

it('shows the no-access page to someone only in intranet.users', function () {
    signInWithCloudron($this, ['intranet.users'])
        ->assertForbidden()
        ->assertSee('No intranet access')
        ->assertSee('not in an intranet group');

    $this->assertGuest();
});

it('ignores every legacy and unapproved intranet group', function (string $group) {
    signInWithCloudron($this, [$group, 'intranet.unknown'])->assertForbidden();

    $this->assertGuest();
})->with(LEGACY_GROUPS);

it('gives admin every permission, including HR and legal', function () {
    signInWithCloudron($this, ['intranet.admin']);
    $admin = User::where('email', 'alex@example.test')->firstOrFail();

    foreach (array_keys(AccessMatrix::permissions()) as $permission) {
        expect($admin->can($permission))->toBeTrue("admin is missing {$permission}");
    }
});

it('keeps legacy group names out of the code and docs', function () {
    $files = Finder::create()->files()
        ->in(array_map(base_path(...), ['app', 'config', 'database', 'deploy', 'docs', 'resources', 'routes']))
        ->append([base_path('AGENTS.md'), base_path('README.md'), base_path('.env.example'), base_path('.env.wamp.example')]);

    $found = [];
    foreach ($files as $file) {
        $contents = file_get_contents($file->getRealPath());
        foreach (LEGACY_GROUPS as $group) {
            if (preg_match('/'.preg_quote($group, '/').'(?![a-z])/', $contents) === 1) {
                $found[] = $file->getRelativePathname().': '.$group;
            }
        }
    }

    expect($found)->toBe([]);
});
