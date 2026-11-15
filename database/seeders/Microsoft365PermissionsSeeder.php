<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Microsoft365\Microsoft365Permissions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class Microsoft365PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (array_keys(Microsoft365Permissions::permissions()) as $name) {
            Permission::findOrCreate($name, 'web');
        }

        foreach (Microsoft365Permissions::grants() as $roleName => $permissions) {
            Role::findByName($roleName)->givePermissionTo($permissions);
        }
    }
}
