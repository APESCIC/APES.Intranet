<?php

use App\Core\Access\RoleName;
use App\Core\Navigation\Menu;
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

/**
 * @return list<string>
 */
function registeredLucideIcons(): array
{
    $source = file_get_contents(base_path('resources/js/app.js'));
    preg_match('/const icons = \{([^}]+)\}/s', $source, $match);
    expect($match)->not->toBeEmpty();

    preg_match_all('/^\s+([A-Za-z]\w*),/m', $match[1], $symbols);

    return array_values(array_unique(array_map(
        static fn (string $symbol): string => strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $symbol)),
        $symbols[1],
    )));
}

/**
 * @return list<string>
 */
function menuLucideIconsFor(User $user, ?MenuMode $mode = null): array
{
    $icons = [];

    foreach (Menu::for($user, $mode) as $group) {
        $icons[] = $group->sectionIcon();

        foreach ($group->items as $item) {
            $icons[] = $item->icon;
        }
    }

    return array_values(array_unique($icons));
}

it('registers every menu and section icon from app.js', function () {
    $user = User::factory()->create(['name' => 'Menu Inspector'])->assignRole(RoleName::Admin->value);
    $registered = registeredLucideIcons();
    $used = [];

    foreach (RoleName::cases() as $role) {
        $roleUser = User::factory()->create()->assignRole($role->value);
        $used = array_merge($used, menuLucideIconsFor($roleUser, MenuMode::Staff));
        $used = array_merge($used, menuLucideIconsFor($roleUser, MenuMode::Admin));
    }

    $used[] = 'external-link';

    $missing = array_values(array_diff(array_unique($used), $registered));

    expect($missing)->toBe([]);
});

it('keeps section icons distinct from chevrons in the shell markup', function () {
    $html = $this->actingAs(User::factory()->create()->assignRole(RoleName::Staff->value))
        ->get(route('dashboard'))
        ->assertOk()
        ->getContent();

    expect($html)
        ->toContain('class="sg-top-nav__icon"')
        ->toContain('class="sg-phone-menu__icon"')
        ->toContain('data-lucide="layout-dashboard"')
        ->toContain('data-lucide="megaphone"')
        ->toContain('data-lucide="life-buoy"')
        ->toContain('data-workspace-phone-disclosure-trigger');
});
