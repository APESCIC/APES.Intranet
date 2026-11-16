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
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        foreach (array_keys(LearningPermissions::permissions()) as $name) {
            Permission::findOrCreate($name, 'web');
        }

        // Spatie caches the permission map; refresh before attaching to roles
        // (same pattern as HelpdeskPermissionsSeeder / OperationsPermissionsSeeder).
        $registrar->forgetCachedPermissions();

        foreach (LearningPermissions::grants() as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, 'web');
            foreach ($permissions as $permission) {
                if (! $role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }
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

        $registrar->forgetCachedPermissions();
    }
}
