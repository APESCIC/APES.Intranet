<?php

use App\Core\Access\RoleName;
use App\Core\Navigation\Menu;
use App\Core\Navigation\NavGroup;
use App\Core\Navigation\NavItem;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function userWithRole(RoleName $role): User
{
    return User::factory()->create(['name' => 'Sam Example'])->assignRole($role->value);
}

/**
 * @return list<string>
 */
function menuKeysFor(User $user): array
{
    return collect(Menu::for($user))
        ->flatMap(fn (NavGroup $group) => array_map(fn (NavItem $item) => $item->key, $group->items))
        ->all();
}

it('sends guests from the dashboard to the sign-in page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

it('sends signed-in people from the home page to the dashboard', function () {
    $this->actingAs(userWithRole(RoleName::Staff))->get('/')->assertRedirect(route('dashboard'));
});

it('refuses the dashboard to someone with no intranet role', function () {
    $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertForbidden();
});

it('shows the dashboard to every intranet role', function (RoleName $role) {
    $this->actingAs(userWithRole($role))->get(route('dashboard'))->assertOk();
})->with(RoleName::cases());

it('shows menu items only to roles with their permission', function (RoleName $role, array $visible, array $hidden) {
    $keys = menuKeysFor(userWithRole($role));

    expect($keys)->toContain('dashboard', 'teams', 'updates', 'directory', 'rota', 'help-desk');
    foreach ($visible as $key) {
        expect($keys)->toContain($key);
    }
    foreach ($hidden as $key) {
        expect($keys)->not->toContain($key);
    }
})->with([
    'staff' => [RoleName::Staff, [], ['hr', 'agreements', 'roles', 'audit']],
    'volunteer' => [RoleName::Volunteer, [], ['hr', 'agreements', 'roles', 'audit']],
    'student' => [RoleName::Student, [], ['hr', 'agreements', 'roles', 'audit']],
    'manager' => [RoleName::Manager, [], ['hr', 'agreements', 'roles', 'audit']],
    'hr' => [RoleName::Hr, ['hr'], ['agreements', 'roles', 'audit']],
    'legal' => [RoleName::Legal, ['agreements'], ['hr', 'roles', 'audit']],
    'admin' => [RoleName::Admin, ['hr', 'agreements', 'roles', 'audit'], []],
]);

it('leaves out a menu group when none of its items are allowed', function () {
    $headings = collect(Menu::for(userWithRole(RoleName::Staff)))->pluck('heading');

    expect($headings)->not->toContain('Admin', 'HR and legal');
});

it('renders the shell with a skip link, labelled menu and the current page marked', function () {
    $response = $this->actingAs(userWithRole(RoleName::Admin))->get(route('admin.roles.index'))->assertOk();

    $response->assertSee('href="#main-content"', false)
        ->assertSee('id="main-content"', false)
        ->assertSee('aria-label="Main menu"', false)
        ->assertSee('aria-controls="workspace-drawer"', false)
        ->assertSee('Sam Example')
        ->assertSee('SE');

    expect($response->getContent())->toMatch('/data-nav-key="roles"\s+aria-current="page"/');
});

it('does not render hidden menu items in the page', function () {
    $this->actingAs(userWithRole(RoleName::Volunteer))->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('data-nav-key="roles"', false)
        ->assertDontSee('data-nav-key="hr"', false);
});

it('opens Teams in a new tab and marks planned features as not available yet', function () {
    $this->actingAs(userWithRole(RoleName::Staff))->get(route('dashboard'))
        ->assertSee(Menu::TEAMS_URL, false)
        ->assertSee('(opens in a new tab)')
        ->assertSee('not available yet');
});
