<?php

use App\Core\Access\RoleName;
use App\Core\AdminDashboard\AdminDashboardLayout;
use App\Models\User;
use Database\Seeders\CommunicationsPermissionsSeeder;
use Database\Seeders\HelpdeskPermissionsSeeder;
use Database\Seeders\Microsoft365PermissionsSeeder;
use Database\Seeders\OperationsPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(CommunicationsPermissionsSeeder::class);
    $this->seed(Microsoft365PermissionsSeeder::class);
    $this->seed(OperationsPermissionsSeeder::class);
    $this->seed(HelpdeskPermissionsSeeder::class);
});

function layoutAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole(RoleName::Admin->value);

    return $user;
}

it('persists a personal layout across sessions', function () {
    $admin = layoutAdmin();

    $this->actingAs($admin)
        ->get(route('admin.overview', ['edit' => 1]))
        ->assertOk()
        ->assertSee('data-admin-layout-editor', false)
        ->assertSee('Move up', false);

    $first = $this->actingAs($admin)->get(route('admin.overview'))->assertOk()->getContent();
    preg_match_all('/data-admin-card="([^"]+)"/', $first, $matches);
    $keys = $matches[1];
    expect($keys)->not->toBeEmpty();

    $reversed = array_reverse($keys);
    $this->actingAs($admin)->post(route('admin.overview.layout.save'), [
        'ordered_keys' => $reversed,
        'hidden_keys' => [],
    ])->assertRedirect(route('admin.overview'));

    expect(AdminDashboardLayout::query()->where('user_id', $admin->id)->exists())->toBeTrue();

    $again = $this->actingAs($admin)->get(route('admin.overview'))->assertOk()->getContent();
    preg_match_all('/data-admin-card="([^"]+)"/', $again, $after);
    expect($after[1][0])->toBe($reversed[0]);
});

it('moves a card up without dragging', function () {
    $admin = layoutAdmin();
    $html = $this->actingAs($admin)->get(route('admin.overview'))->assertOk()->getContent();
    preg_match_all('/data-admin-card="([^"]+)"/', $html, $matches);
    $keys = $matches[1];
    expect(count($keys))->toBeGreaterThan(1);

    $this->actingAs($admin)->post(route('admin.overview.layout.move'), [
        'key' => $keys[1],
        'direction' => 'up',
    ])->assertRedirect(route('admin.overview', ['edit' => 1]));

    $after = $this->actingAs($admin)->get(route('admin.overview'))->assertOk()->getContent();
    preg_match_all('/data-admin-card="([^"]+)"/', $after, $moved);
    expect($moved[1][0])->toBe($keys[1]);
});

it('lists only permitted cards in the picker after hide', function () {
    $admin = layoutAdmin();
    $html = $this->actingAs($admin)->get(route('admin.overview'))->assertOk()->getContent();
    preg_match('/data-admin-card="([^"]+)"/', $html, $match);
    $key = $match[1];

    $this->actingAs($admin)->post(route('admin.overview.layout.hide'), ['key' => $key])
        ->assertRedirect(route('admin.overview', ['edit' => 1]));

    $this->actingAs($admin)->get(route('admin.overview', ['edit' => 1]))
        ->assertOk()
        ->assertSee('data-admin-card-picker', false)
        ->assertSee('data-add-card="'.$key.'"', false)
        ->assertDontSee('data-admin-card="'.$key.'"', false);
});
