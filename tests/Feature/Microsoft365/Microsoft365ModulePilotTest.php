<?php

use App\Core\Access\RoleName;
use App\Core\Modules\ModuleRegistry;
use App\Core\Navigation\Menu;
use App\Core\Navigation\MenuMode;
use App\Core\Navigation\NavGroup;
use App\Core\Navigation\NavItem;
use App\Models\User;
use Database\Seeders\Microsoft365BootstrapSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Microsoft365\Models\MicrosoftLink;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(Microsoft365BootstrapSeeder::class);
});

it('hides Microsoft 365 nav and admin routes when the integration is disabled, and restores them on re-enable', function () {
    $admin = User::factory()->create()->assignRole(RoleName::Admin->value);
    $staff = User::factory()->create()->assignRole(RoleName::Staff->value);
    $registry = app(ModuleRegistry::class);

    expect($registry->enabled('microsoft-365'))->toBeTrue();
    expect(MicrosoftLink::query()->count())->toBe(8);

    $this->actingAs($admin)->get(route('admin.m365-links.index'))->assertOk();

    $enabledKeys = collect(Menu::for($staff, MenuMode::Staff))
        ->flatMap(fn (NavGroup $g) => array_map(fn (NavItem $i) => $i->key, $g->items))
        ->all();
    expect($enabledKeys)->toContain('m365-planner');

    $this->actingAs($staff)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Microsoft 365 apps');

    $registry->disable('microsoft-365', 'Pilot disable check', $admin);

    expect($registry->enabled('microsoft-365'))->toBeFalse()
        ->and(MicrosoftLink::query()->count())->toBe(8);

    $this->actingAs($admin)->get(route('admin.m365-links.index'))->assertNotFound();

    $disabledKeys = collect(Menu::for($staff, MenuMode::Staff))
        ->flatMap(fn (NavGroup $g) => array_map(fn (NavItem $i) => $i->key, $g->items))
        ->all();
    expect($disabledKeys)->not->toContain('m365-planner', 'm365-bookings', 'm365-loop');

    $this->actingAs($staff)->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Microsoft 365 apps');

    $registry->enable('microsoft-365', 'Pilot re-enable check', $admin);

    expect($registry->enabled('microsoft-365'))->toBeTrue()
        ->and(MicrosoftLink::query()->count())->toBe(8);

    $this->actingAs($admin)->get(route('admin.m365-links.index'))->assertOk();

    $restoredKeys = collect(Menu::for($staff, MenuMode::Staff))
        ->flatMap(fn (NavGroup $g) => array_map(fn (NavItem $i) => $i->key, $g->items))
        ->all();
    expect($restoredKeys)->toContain('m365-planner');

    $this->actingAs($staff)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Microsoft 365 apps');
});
