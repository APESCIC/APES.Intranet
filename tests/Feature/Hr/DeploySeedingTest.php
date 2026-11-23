<?php

use App\Core\Access\RoleName;
use Database\Seeders\HrBootstrapSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Hr\HrPermissions;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function hrDeploySeed(): void
{
    test()->seed(RolesAndPermissionsSeeder::class);
    test()->seed(HrBootstrapSeeder::class);
}

it('runs the HR seeder after the core seeder on every Cloudron deploy', function () {
    $script = file_get_contents(base_path('deploy/cloudron/activate-release.sh'));

    $core = strpos($script, "db:seed --class='Database\\Seeders\\RolesAndPermissionsSeeder'");
    $hr = strpos($script, "db:seed --class='Database\\Seeders\\HrBootstrapSeeder'");

    expect($core)->not->toBeFalse()
        ->and($hr)->not->toBeFalse()
        ->and($hr)->toBeGreaterThan($core);
});

it('keeps the HR grants after a deploy re-runs the seeders', function () {
    hrDeploySeed();
    hrDeploySeed();

    foreach (HrPermissions::grants() as $roleName => $permissions) {
        foreach ($permissions as $permission) {
            expect(Role::findByName($roleName)->hasPermissionTo($permission))->toBeTrue();
        }
    }

    expect(Role::findByName(RoleName::Staff->value)->hasPermissionTo(HrPermissions::ACCESS))->toBeFalse();
});
