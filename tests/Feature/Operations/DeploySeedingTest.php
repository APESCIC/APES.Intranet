<?php

use App\Core\Access\RoleName;
use Database\Seeders\CommunicationsBootstrapSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\OperationsBootstrapSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Operations\OperationsPermissions;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function operationsDeploySeed(): void
{
    test()->seed(RolesAndPermissionsSeeder::class);
    test()->seed(CommunicationsBootstrapSeeder::class);
    test()->seed(OperationsBootstrapSeeder::class);
}

it('runs the operations seeder after the core seeder on every Cloudron deploy', function () {
    $script = file_get_contents(base_path('deploy/cloudron/activate-release.sh'));

    $core = strpos($script, "db:seed --class='Database\\Seeders\\RolesAndPermissionsSeeder'");
    $operations = strpos($script, "db:seed --class='Database\\Seeders\\OperationsBootstrapSeeder'");

    expect($core)->not->toBeFalse()
        ->and($operations)->not->toBeFalse()
        ->and($operations)->toBeGreaterThan($core);
});

it('grants the operations permissions in the fake-data seeder that CI uses', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Role::findByName(RoleName::Admin->value)->hasPermissionTo(OperationsPermissions::SITES_MANAGE))->toBeTrue()
        ->and(Role::findByName(RoleName::Supervisor->value)->hasPermissionTo(OperationsPermissions::ROTA_MANAGE))->toBeTrue();
});

it('keeps the operations grants after a deploy re-runs the core seeder', function () {
    operationsDeploySeed();
    operationsDeploySeed();

    foreach (OperationsPermissions::grants() as $roleName => $permissions) {
        foreach ($permissions as $permission) {
            expect(Role::findByName($roleName)->hasPermissionTo($permission))->toBeTrue();
        }
    }

    expect(Role::findByName(RoleName::Staff->value)->hasPermissionTo(OperationsPermissions::ROTA_MANAGE))->toBeFalse()
        ->and(Role::findByName(RoleName::Volunteer->value)->hasPermissionTo(OperationsPermissions::FORMS_MANAGE))->toBeFalse();
});
