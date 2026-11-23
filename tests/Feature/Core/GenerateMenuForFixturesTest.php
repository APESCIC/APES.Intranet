<?php

use App\Core\Access\RoleName;
use App\Core\Navigation\MenuMode;
use App\Models\User;
use Database\Seeders\CommunicationsPermissionsSeeder;
use Database\Seeders\Microsoft365BootstrapSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(CommunicationsPermissionsSeeder::class);
    $this->seed(Microsoft365BootstrapSeeder::class);
});

it('writes Menu::for snapshot fixtures when MENU_FIXTURES=1', function () {
    if (getenv('MENU_FIXTURES') !== '1') {
        expect(true)->toBeTrue();

        return;
    }

    $dir = base_path('tests/Fixtures/menu-for');
    if (! is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    foreach (RoleName::cases() as $role) {
        $user = User::factory()->create()->assignRole($role->value);
        $snapshot = menuSnapshotFor($user, MenuMode::Staff);
        file_put_contents(
            $dir.'/'.$role->value.'.json',
            json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n",
        );
    }

    $admin = User::factory()->create()->assignRole(RoleName::Admin->value);
    $snapshot = menuSnapshotFor($admin, MenuMode::Admin);
    file_put_contents(
        $dir.'/admin-panel.json',
        json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n",
    );

    expect(is_file($dir.'/staff.json'))->toBeTrue();
});
