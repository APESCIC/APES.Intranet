<?php

use App\Core\Access\RoleName;
use App\Core\Navigation\MenuMode;
use App\Models\User;
use Database\Seeders\CommunicationsPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(CommunicationsPermissionsSeeder::class);
});

it('lets admins switch menu mode without changing permissions', function () {
    $admin = User::factory()->create()->assignRole(RoleName::Admin->value);

    $this->actingAs($admin)
        ->post(route('communications.menu-mode'), ['mode' => 'admin'])
        ->assertRedirect();

    expect(session(MenuMode::SESSION_KEY))->toBe('admin');

    $this->actingAs($admin)
        ->withSession([MenuMode::SESSION_KEY => 'admin'])
        ->get(route('dashboard'))
        ->assertOk();
});

it('forbids the menu mode switcher for non-admins', function () {
    $staff = User::factory()->create()->assignRole(RoleName::Staff->value);

    $this->actingAs($staff)
        ->post(route('communications.menu-mode'), ['mode' => 'admin'])
        ->assertForbidden();
});

it('still forbids admin routes for non-admins regardless of session mode', function () {
    $staff = User::factory()->create()->assignRole(RoleName::Staff->value);

    $this->actingAs($staff)
        ->withSession([MenuMode::SESSION_KEY => 'admin'])
        ->get(route('admin.roles.index'))
        ->assertForbidden();
});
