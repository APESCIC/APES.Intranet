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

it('matches the staff menu snapshot for each intranet role', function (RoleName $role) {
    $user = User::factory()->create()->assignRole($role->value);
    $snapshot = menuSnapshotFor($user, MenuMode::Staff);
    $fixture = json_decode(
        file_get_contents(base_path('tests/Fixtures/menu-for/'.$role->value.'.json')),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );

    expect($snapshot)->toBe($fixture);
})->with(RoleName::cases());

it('matches the admin panel menu snapshot for admins', function () {
    $admin = User::factory()->create()->assignRole(RoleName::Admin->value);
    $snapshot = menuSnapshotFor($admin, MenuMode::Admin);
    $fixture = json_decode(
        file_get_contents(base_path('tests/Fixtures/menu-for/admin-panel.json')),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );

    expect($snapshot)->toBe($fixture);
});
