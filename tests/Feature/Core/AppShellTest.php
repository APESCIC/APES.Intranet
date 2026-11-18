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

    expect($headings)->not->toContain('Admin', 'HR and legal')
        ->and($headings)->not->toContain('More');
});

it('folds soon HR and Agreements into Support for people who can see them', function () {
    $menu = collect(Menu::for(userWithRole(RoleName::Hr), MenuMode::Staff));
    $support = $menu->firstWhere('key', 'support');

    expect($menu->pluck('heading'))->toContain('Support', 'Work')
        ->and($menu->pluck('heading'))->not->toContain('More', 'HR and legal', 'Support extras', 'Operations')
        ->and(collect($support?->items ?? [])->pluck('key')->all())->toContain('hr');
});

it('keeps account links out of People and in the user menu only', function () {
    $user = userWithRole(RoleName::Staff);
    $people = collect(Menu::for($user, MenuMode::Staff))->firstWhere('key', 'people');

    expect(collect($people->items)->pluck('key')->all())
        ->toContain('directory', 'org-chart', 'students')
        ->not->toContain('profile', 'notifications', 'security');

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-workspace-user-menu', false)
        ->assertSee('data-nav-key="profile"', false)
        ->assertSee('Notification settings')
        ->assertSee('Sign out');
});

it('renders the shell with a skip link, labelled menu and the current page marked', function () {
    $response = $this->actingAs(userWithRole(RoleName::Admin))
        ->get(route('admin.roles.index'))
        ->assertOk();

    $response->assertSee('href="#main-content"', false)
        ->assertSee('id="main-content"', false)
        ->assertSee('aria-label="Main menu"', false)
        ->assertDontSee('aria-controls="workspace-drawer"', false)
        ->assertDontSee('data-workspace-menu-open', false)
        ->assertSee('data-workspace-top-nav', false)
        ->assertSee('data-workspace-disclosure-trigger', false)
        ->assertSee('sg-mega-panel', false)
        ->assertSee('data-workspace-user-menu', false)
        ->assertSee('Sam Example')
        ->assertSee('SE')
        ->assertSee('data-panel-switch', false)
        ->assertSee('id="notification-bell-panel"', false)
        ->assertSee('data-notification-bell', false);

    expect($response->getContent())->toMatch('/data-nav-key="roles"\s+aria-current="page"/');
});

it('puts Intranet and Admin panel links in the account menu for admins, marking the active panel', function () {
    $admin = userWithRole(RoleName::Admin);

    $adminPage = $this->actingAs($admin)->get(route('admin.roles.index'))->assertOk()->getContent();
    expect($adminPage)
        ->toContain('aria-label="Switch panel"')
        ->toContain('data-panel-switch')
        ->toContain('sg-user-menu__panels')
        ->not->toContain('sg-switch--topbar')
        ->toMatch('/data-nav-key="admin-panel"[^>]*aria-current="true"/')
        ->toMatch('/data-nav-key="staff-panel"/')
        ->toContain('Intranet')
        ->toContain('Admin panel')
        ->not->toContain('data-brand-panel')
        ->not->toContain('Staff panel');

    $staffPage = $this->actingAs($admin)->get(route('dashboard'))->assertOk()->getContent();
    expect($staffPage)
        ->toMatch('/data-nav-key="staff-panel"[^>]*aria-current="true"/')
        ->toContain('Intranet')
        ->not->toContain('data-brand-panel')
        ->not->toContain('sg-switch--topbar')
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
        ->assertDontSee('Staff panel')
        ->assertDontSee('data-nav-key="staff-panel"', false);
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

it('exposes top-level dropdown hooks without a hamburger or drawer', function () {
    $html = $this->actingAs(userWithRole(RoleName::Staff))
        ->get(route('dashboard'))
        ->assertOk()
        ->getContent();

    expect($html)
        ->not->toContain('data-workspace-menu-open')
        ->not->toContain('aria-controls="workspace-drawer"')
        ->not->toContain('data-workspace-drawer')
        ->not->toContain('data-workspace-rail')
        ->toContain('sg-mega-panel')
        ->toContain('data-workspace-user-menu-trigger')
        ->toContain('data-notification-bell-toggle')
        ->toContain('data-workspace-disclosure-trigger')
        ->toContain('data-workspace-top-nav-more')
        ->not->toContain('More sections')
        ->and(substr_count($html, 'id="notification-bell-panel"'))->toBe(1);
});

it('renders Home as a direct dashboard link without a section dropdown', function () {
    $html = $this->actingAs(userWithRole(RoleName::Staff))
        ->get(route('dashboard'))
        ->assertOk()
        ->getContent();

    expect($html)
        ->toContain('class="sg-top-nav__link"')
        ->toMatch('/href="'.preg_quote(route('dashboard'), '/').'"\s+class="sg-top-nav__link"/')
        ->toMatch('/data-nav-key="dashboard"[^>]*aria-current="page"/')
        ->not->toContain('id="top-nav-panel-home"')
        ->not->toContain('aria-controls="top-nav-panel-home"')
        ->toContain('id="top-nav-panel-communications"')
        ->toContain('sg-mega-panel')
        ->toContain('data-top-nav-heading="Work"')
        ->not->toContain('data-top-nav-heading="Operations"')
        ->not->toContain('data-top-nav-heading="Learning"');
});

it('keeps the top nav on one row without a clipping horizontal scroll', function () {
    $css = file_get_contents(base_path('resources/css/shell.css'));

    expect($css)
        ->toContain('.sg-top-nav')
        ->toContain('flex-wrap: nowrap')
        ->toContain('overflow: visible')
        ->not->toContain('overflow-x: auto')
        ->toContain('.sg-mega-panel')
        ->toContain('minmax(14rem, 1fr)')
        ->toContain('.sg-mega-panel[data-columns=\'2\']')
        ->toContain('min-width: min(34rem, 94vw)')
        ->toContain('.sg-top-nav .sg-mega-panel')
        ->toContain('translateX(-50%)')
        ->toContain('.sg-top-nav__link')
        ->toContain('.sg-top-nav__more');
});

it('keeps shell icon and account targets at least 44 CSS pixels', function () {
    $css = file_get_contents(base_path('resources/css/shell.css'));

    expect($css)
        ->toContain('.sg-icon-btn')
        ->toContain('min-width: 2.75rem')
        ->toContain('min-height: 2.75rem')
        ->toContain('.sg-user-menu__trigger')
        ->toContain('@media (max-width: 22.5rem)')
        ->toContain('.sg-user-menu__panel')
        ->toContain('background: var(--sg-drawer-surface)');
});

it('sets the main content column to 95 percent of the viewport width', function () {
    $appCss = file_get_contents(base_path('resources/css/app.css'));
    $shellCss = file_get_contents(base_path('resources/css/shell.css'));

    expect($appCss)->toContain('--container-panel: 95%')
        ->and($shellCss)->toContain('width: var(--container-panel)')
        ->and($shellCss)->toContain('max-width: var(--container-panel)');
});
