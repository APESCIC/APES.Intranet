<?php

use App\Core\Access\RoleName;
use App\Core\Navigation\Menu;
use App\Core\Navigation\MenuMode;
use App\Core\Navigation\NavGroup;
use App\Core\Navigation\NavItem;
use App\Models\User;
use Database\Seeders\CommunicationsPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(CommunicationsPermissionsSeeder::class);
});

function userWithRole(RoleName $role): User
{
    return User::factory()->create(['name' => 'Sam Example'])->assignRole($role->value);
}

/**
 * @return list<string>
 */
function menuKeysFor(User $user, ?MenuMode $mode = null): array
{
    return collect(Menu::for($user, $mode))
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
    $keys = menuKeysFor(userWithRole($role), MenuMode::Staff);

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
    'supervisor' => [RoleName::Supervisor, [], ['hr', 'agreements', 'roles', 'audit']],
    'management' => [RoleName::Management, ['hr'], ['agreements', 'roles', 'audit']],
    'hr' => [RoleName::Hr, ['hr'], ['agreements', 'roles', 'audit']],
    'legal' => [RoleName::Legal, ['agreements'], ['hr', 'roles', 'audit']],
    'admin' => [RoleName::Admin, ['hr', 'agreements'], ['roles', 'audit']],
]);

it('keeps system admin items out of the Staff menu for admins', function () {
    $keys = menuKeysFor(userWithRole(RoleName::Admin), MenuMode::Staff);

    expect($keys)->not->toContain('admin-overview', 'roles', 'audit', 'access-review', 'system', 'totp')
        ->and($keys)->toContain('hr', 'agreements', 'updates');
});

it('shows only the Admin inventory in the Admin panel, starting with the overview', function () {
    $keys = menuKeysFor(userWithRole(RoleName::Admin), MenuMode::Admin);

    expect($keys)->toContain('admin-overview', 'roles', 'audit', 'access-review', 'system', 'totp')
        ->and($keys[0])->toBe('admin-overview')
        ->and($keys)->not->toContain('dashboard', 'updates', 'directory');
});

it('gives non-admins the Staff menu even if asked for the Admin panel', function () {
    $keys = menuKeysFor(userWithRole(RoleName::Hr), MenuMode::Admin);

    expect($keys)->toContain('dashboard', 'updates')
        ->and($keys)->not->toContain('admin-overview', 'roles');
});

it('leaves out a menu group when none of its items are allowed', function () {
    $headings = collect(Menu::for(userWithRole(RoleName::Staff), MenuMode::Staff))->pluck('heading');

    expect($headings)->not->toContain('Admin', 'HR and legal');
});

it('renders the shell with a skip link, labelled menu and the current page marked', function () {
    $response = $this->actingAs(userWithRole(RoleName::Admin))
        ->get(route('admin.roles.index'))
        ->assertOk();

    $response->assertSee('href="#main-content"', false)
        ->assertSee('id="main-content"', false)
        ->assertSee('aria-label="Main menu"', false)
        ->assertSee('aria-controls="workspace-drawer"', false)
        ->assertSee('Sam Example')
        ->assertSee('SE')
        ->assertSee('data-panel-switch', false);

    expect($response->getContent())->toMatch('/data-nav-key="roles"\s+aria-current="page"/');
});

it('renders the Staff and Admin switch for admins, marking the active panel', function () {
    $admin = userWithRole(RoleName::Admin);

    $adminPage = $this->actingAs($admin)->get(route('admin.roles.index'))->assertOk()->getContent();
    expect($adminPage)
        ->toContain('aria-label="Switch panel"')
        ->toMatch('/href="'.preg_quote(route('admin.overview'), '/').'" class="sg-switch__link"\s+aria-current="true"\s*>Admin</')
        ->toMatch('/href="'.preg_quote(route('dashboard'), '/').'" class="sg-switch__link"\s*>Staff</')
        ->toContain('data-brand-panel>Admin panel<');

    $staffPage = $this->actingAs($admin)->get(route('dashboard'))->assertOk()->getContent();
    expect($staffPage)
        ->toMatch('/href="'.preg_quote(route('dashboard'), '/').'" class="sg-switch__link"\s+aria-current="true"\s*>Staff</')
        ->toContain('data-brand-panel>Staff panel<')
        ->not->toContain('Normal')
        ->not->toContain('Menu mode');
});

it('does not render hidden menu items or the Admin switcher for non-admins', function () {
    $this->actingAs(userWithRole(RoleName::Volunteer))->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('data-nav-key="roles"', false)
        ->assertDontSee('data-nav-key="hr"', false)
        ->assertDontSee('data-panel-switch', false)
        ->assertSee('data-brand-panel>APES CIC<', false)
        ->assertDontSee('Staff panel');
});

it('opens Teams in a new tab and marks planned features as not available yet', function () {
    $this->actingAs(userWithRole(RoleName::Staff))->get(route('dashboard'))
        ->assertSee(Menu::TEAMS_URL, false)
        ->assertSee('(opens in a new tab)');

    $this->actingAs(userWithRole(RoleName::Hr))->get(route('dashboard'))
        ->assertSee('not available yet');
});

it('shows the Admin panel to an admin on an admin route', function () {
    $this->actingAs(userWithRole(RoleName::Admin))
        ->get(route('admin.roles.index'))
        ->assertOk()
        ->assertSee('data-nav-key="roles"', false)
        ->assertDontSee('data-nav-key="updates"', false);
});
