<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Learning\LearningPermissions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class LearningPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (array_keys(LearningPermissions::permissions()) as $name) {
            Permission::findOrCreate($name, 'web');
        }

        foreach (LearningPermissions::grants() as $roleName => $permissions) {
            Role::findByName($roleName)->givePermissionTo($permissions);
        }

        // Never leave grade edit/release on Cloudron roles, even if an older
        // deploy granted them by mistake.
        foreach (LearningPermissions::assignmentOnly() as $permission) {
            foreach (Role::query()->get() as $role) {
                if ($role->hasPermissionTo($permission)) {
                    $role->revokePermissionTo($permission);
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
