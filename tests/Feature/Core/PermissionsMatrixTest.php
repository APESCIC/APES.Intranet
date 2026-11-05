<?php

use App\Core\Access\AccessMatrix;
use App\Core\Access\RoleName;
use App\Core\Access\RoleStacks;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The approved matrix, written out by hand on purpose. Changing a grant in
 * AccessMatrix without changing this copy fails the test.
 */
const EXPECTED_GRANTS = [
    'staff' => ['intranet.access'],
    'volunteer' => ['intranet.access'],
    'student' => ['intranet.access', 'student.area'],
    'supervisor' => ['intranet.access', 'team.supervise'],
    'management' => ['hr.access', 'hr.special_category.view', 'intranet.access', 'team.manage', 'team.supervise'],
    'hr' => ['admin.access_review.view', 'hr.access', 'hr.special_category.view', 'intranet.access'],
    'legal' => ['intranet.access', 'legal.access'],
    'admin' => [
        'admin.access_review.view', 'admin.audit.view', 'admin.roles.view', 'admin.system.view', 'hr.access', 'hr.special_category.view',
        'intranet.access', 'legal.access', 'student.area', 'team.manage', 'team.supervise',
    ],
];

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    fakeCloudron($this);
});

/**
 * @return array<string, list<string>> Profile => Cloudron groups.
 */
function matrixProfiles(): array
{
    $groupFor = array_flip(config('intranet.group_roles'));
    $profiles = [];

    foreach (RoleName::cases() as $role) {
        $profiles[$role->value] = [$groupFor[$role->value]];
    }
    foreach (RoleStacks::common() as $key => $roles) {
        $profiles[$key] = array_map(fn (RoleName $role) => $groupFor[$role->value], $roles);
    }

    return $profiles;
}

it('matches the approved matrix exactly', function () {
    $actual = array_map(function (array $permissions) {
        sort($permissions);

        return $permissions;
    }, AccessMatrix::grants());

    expect($actual)->toBe(EXPECTED_GRANTS)
        ->and(array_keys(AccessMatrix::permissions()))->toEqualCanonicalizing(EXPECTED_GRANTS['admin']);
});

it('covers the 8 groups and the 4 common stacks', function () {
    expect(array_keys(matrixProfiles()))->toBe([
        'staff', 'volunteer', 'student', 'supervisor', 'management', 'hr', 'legal', 'admin',
        'staff-hr', 'staff-management', 'volunteer-supervisor', 'staff-admin',
    ]);
});

it('grants each group and stack exactly its permissions after signing in', function (string $profile) {
    $groups = matrixProfiles()[$profile];
    $expected = [];
    foreach ($groups as $group) {
        $expected = array_merge($expected, EXPECTED_GRANTS[config('intranet.group_roles')[$group]]);
    }

    signInWithCloudron($this, $groups)->assertRedirect(route('dashboard'));
    $user = User::where('email', 'alex@example.test')->firstOrFail();

    foreach (array_keys(AccessMatrix::permissions()) as $permission) {
        expect($user->can($permission))->toBe(in_array($permission, $expected, true), "{$profile} / {$permission}");
    }
})->with([
    'staff', 'volunteer', 'student', 'supervisor', 'management', 'hr', 'legal', 'admin',
    'staff-hr', 'staff-management', 'volunteer-supervisor', 'staff-admin',
]);
