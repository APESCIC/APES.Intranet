<?php

namespace Database\Seeders;

use App\Core\Access\AccessMatrix;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Real roles and permissions, not fake data. Safe to run on every deploy:
 * php artisan db:seed --class="Database\Seeders\RolesAndPermissionsSeeder" --force
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        foreach (array_keys(AccessMatrix::permissions()) as $name) {
            Permission::findOrCreate($name, 'web');
        }

        // Model events may be muted (WithoutModelEvents), so refresh the cache by hand.
        $registrar->forgetCachedPermissions();

        foreach (AccessMatrix::grants() as $roleName => $permissions) {
            Role::findOrCreate($roleName, 'web')->syncPermissions($permissions);
        }

        // A role dropped from the matrix goes once nobody holds it. One that is
        // still assigned stays until those people sign in again.
        Role::query()->whereNotIn('name', array_keys(AccessMatrix::grants()))->withCount('users')->get()
            ->each(function (Role $role) {
                if ($role->users_count === 0) {
                    $role->delete();
                } else {
                    $this->command?->warn("Role {$role->name} is not in the matrix but {$role->users_count} people still hold it.");
                }
            });

        $registrar->forgetCachedPermissions();
    }
}
