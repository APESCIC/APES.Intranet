<?php

use App\Core\Access\RoleName;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\HelpdeskBootstrapSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Helpdesk\HelpdeskPermissions;
use Modules\Helpdesk\Models\Category;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function helpdeskDeploySeed(): void
{
    test()->seed(RolesAndPermissionsSeeder::class);
    test()->seed(HelpdeskBootstrapSeeder::class);
}

it('runs the help desk seeder after the core seeder on every Cloudron deploy', function () {
    $script = file_get_contents(base_path('deploy/cloudron/activate-release.sh'));

    $core = strpos($script, "db:seed --class='Database\\Seeders\\RolesAndPermissionsSeeder'");
    $helpdesk = strpos($script, "db:seed --class='Database\\Seeders\\HelpdeskBootstrapSeeder'");

    expect($core)->not->toBeFalse()
        ->and($helpdesk)->not->toBeFalse()
        ->and($helpdesk)->toBeGreaterThan($core);
});

it('grants the help desk permissions in the fake-data seeder that CI uses', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Role::findByName(RoleName::Admin->value)->hasPermissionTo(HelpdeskPermissions::QUEUE_IT))->toBeTrue()
        ->and(Role::findByName(RoleName::Management->value)->hasPermissionTo(HelpdeskPermissions::QUEUE_MANAGEMENT))->toBeTrue();
});

it('keeps the help desk grants and categories after a deploy re-runs the seeders', function () {
    helpdeskDeploySeed();
    Category::query()->where('slug', 'devices')->update(['name' => 'Renamed by an admin']);
    helpdeskDeploySeed();

    foreach (HelpdeskPermissions::grants() as $roleName => $permissions) {
        foreach ($permissions as $permission) {
            expect(Role::findByName($roleName)->hasPermissionTo($permission))->toBeTrue();
        }
    }

    expect(Role::findByName(RoleName::Management->value)->hasPermissionTo(HelpdeskPermissions::QUEUE_IT))->toBeFalse()
        ->and(Role::findByName(RoleName::Staff->value)->hasPermissionTo(HelpdeskPermissions::QUEUE_MANAGEMENT))->toBeFalse()
        ->and(Category::query()->where('slug', 'devices')->value('name'))->toBe('Renamed by an admin')
        ->and(Category::query()->count())->toBe(9)
        ->and(Category::query()->where('slug', 'lost-device-or-2fa-reset')->value('requires_identity_check'))->toBeTruthy();
});
