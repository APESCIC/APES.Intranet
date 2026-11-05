<?php

use App\Core\Access\AccessMatrix;
use App\Core\Access\RoleName;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('creates the eight intranet roles with their permissions', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    expect(Role::pluck('name')->sort()->values()->all())
        ->toBe(collect(RoleName::cases())->pluck('value')->sort()->values()->all());

    foreach (AccessMatrix::grants() as $roleName => $permissions) {
        expect(Role::findByName($roleName)->permissions->pluck('name')->sort()->values()->all())
            ->toBe(collect($permissions)->sort()->values()->all());
    }
});

it('is idempotent', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(RolesAndPermissionsSeeder::class);

    expect(Role::count())->toBe(count(RoleName::cases()))
        ->and(Permission::count())->toBe(count(AccessMatrix::permissions()));
});

it('removes a role that left the matrix once nobody holds it', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Role::findOrCreate('manager', 'web');
    $held = Role::findOrCreate('retired', 'web');
    User::factory()->create()->assignRole($held);

    $this->seed(RolesAndPermissionsSeeder::class);

    expect(Role::where('name', 'manager')->exists())->toBeFalse()
        ->and(Role::where('name', 'retired')->exists())->toBeTrue();
});

it('removes a permission that was granted to a role by hand', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Role::findByName(RoleName::Volunteer->value)->givePermissionTo(AccessMatrix::ADMIN_ROLES_VIEW);

    $this->seed(RolesAndPermissionsSeeder::class);

    expect(Role::findByName(RoleName::Volunteer->value)->hasPermissionTo(AccessMatrix::ADMIN_ROLES_VIEW))->toBeFalse();
});
