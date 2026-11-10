<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Operations\OperationsPermissions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class OperationsPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        foreach (array_keys(OperationsPermissions::permissions()) as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $registrar->forgetCachedPermissions();

        foreach (OperationsPermissions::grants() as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, 'web');
            foreach ($permissions as $permission) {
                if (! $role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }
        }

        $registrar->forgetCachedPermissions();
    }
}
