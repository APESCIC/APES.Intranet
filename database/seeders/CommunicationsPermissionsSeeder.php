<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Communications\CommunicationsPermissions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CommunicationsPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        foreach (array_keys(CommunicationsPermissions::permissions()) as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $registrar->forgetCachedPermissions();

        foreach (CommunicationsPermissions::grants() as $roleName => $permissions) {
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
