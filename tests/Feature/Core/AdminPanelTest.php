<?php

use App\Core\Access\AccessMatrix;
use App\Core\Access\RoleName;
use App\Core\Auth\OidcIdentity;
use App\Core\System\SystemStatus;
use App\Models\User;
use Database\Seeders\CommunicationsPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

const LEGACY_MENU_MODE_KEY = 'intranet.menu_mode';

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(CommunicationsPermissionsSeeder::class);
    $this->app->instance(SystemStatus::class, new SystemStatus([]));
    Http::fake(['*' => Http::response('down', 500)]);
});

function panelUser(RoleName ...$roles): User
{
    $user = User::factory()->create(['name' => 'Sam Example']);
    foreach ($roles as $role) {
        $user->assignRole($role->value);
    }

    return $user;
}

/**
 * @return array<string, mixed>
 */
function freshSignIn(): array
{
    return ['intranet.auth_time' => now()->getTimestamp()];
}

function assertAdminPanel(TestResponse $response): void
{
    $response->assertOk()
        ->assertSee('data-nav-key="roles"', false)
        ->assertDontSee('data-nav-key="updates"', false);
}

function assertStaffPanel(TestResponse $response): void
{
    $response->assertOk()
        ->assertSee('data-nav-key="updates"', false)
        ->assertDontSee('data-nav-key="roles"', false);
}

dataset('admin menu pages', [
    'overview' => 'admin.overview',
    'roles' => 'admin.roles.index',
    'audit log' => 'admin.audit.index',
    'access review' => 'admin.access-review.index',
    'system status' => 'admin.system.index',
    '2FA status' => 'admin.totp.index',
    'Microsoft 365 links' => 'admin.m365-links.index',
]);

it('keeps Admin selected on every admin page, on any visit, without storing a mode', function (string $route) {
    $admin = panelUser(RoleName::Admin);

    // First visit, a refresh, Back, a direct link and a new tab are all a plain GET of the same address.
    foreach (range(1, 2) as $visit) {
        assertAdminPanel($this->actingAs($admin)->withSession(freshSignIn())->get(route($route)));
    }

    expect(session()->has(LEGACY_MENU_MODE_KEY))->toBeFalse();
})->with('admin menu pages');

it('keeps Admin selected after the fresh sign-in redirect returns to an admin page', function () {
    fakeCloudron($this);
    config(['intranet.fresh_signin_minutes' => 15]);

    signInWithCloudron($this, ['intranet.staff', 'intranet.admin']);
    $this->travel(16)->minutes();

    $this->get(route('admin.system.index'))->assertRedirect(route('auth.reauth'));
    $this->idp->identity = new OidcIdentity('sub-alex', 'alex@example.test', 'Alex Example', now()->getTimestamp());
    $this->get(route('auth.callback'))->assertRedirect(route('admin.system.index'));

    assertAdminPanel($this->get(route('admin.system.index')));
});

it('shows the Staff panel on the dashboard, even straight after an admin page', function () {
    $admin = panelUser(RoleName::Admin);

    assertAdminPanel($this->actingAs($admin)->get(route('admin.roles.index')));
    assertStaffPanel($this->actingAs($admin)->get(route('dashboard')));
});

it('ignores a menu mode left in the session by an earlier release', function () {
    $admin = panelUser(RoleName::Admin);

    assertStaffPanel($this->actingAs($admin)->withSession([LEGACY_MENU_MODE_KEY => 'admin'])->get(route('dashboard')));
});

it('lists every admin section on the overview for a full admin', function () {
    $response = $this->actingAs(panelUser(RoleName::Admin))->get(route('admin.overview'))->assertOk();

    foreach (['roles', 'audit', 'access-review', 'system', 'totp', 'm365-links'] as $key) {
        $response->assertSee('data-admin-section="'.$key.'"', false);
    }
    $response->assertDontSee('data-admin-section="admin-overview"', false);
});

it('leaves a section off the overview when the admin lacks its permission', function () {
    Role::findByName(RoleName::Admin->value)->revokePermissionTo(AccessMatrix::ADMIN_AUDIT_VIEW);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->actingAs(panelUser(RoleName::Admin))->get(route('admin.overview'))
        ->assertOk()
        ->assertSee('data-admin-section="roles"', false)
        ->assertDontSee('data-admin-section="audit"', false);
});

it('lands a normal sign-in on the dashboard with the Staff panel', function () {
    fakeCloudron($this);

    signInWithCloudron($this, ['intranet.staff', 'intranet.admin'])->assertRedirect(route('dashboard'));

    assertStaffPanel($this->get(route('dashboard')));
    expect(session()->has(LEGACY_MENU_MODE_KEY))->toBeFalse();
});

it('returns an admin to a protected admin deep link after sign-in', function () {
    fakeCloudron($this);

    $this->get(route('admin.roles.index'))->assertRedirect(route('login'));
    signInWithCloudron($this, ['intranet.staff', 'intranet.admin'])->assertRedirect(route('admin.roles.index'));

    assertAdminPanel($this->get(route('admin.roles.index')));
});

it('stores no menu mode across sign-out and sign-in', function () {
    fakeCloudron($this);

    signInWithCloudron($this, ['intranet.staff', 'intranet.admin']);
    $this->get(route('admin.overview'))->assertOk();
    $this->post(route('logout'))->assertRedirect();
    signInWithCloudron($this, ['intranet.staff', 'intranet.admin']);

    expect(session()->has(LEGACY_MENU_MODE_KEY))->toBeFalse();
});

it('refuses the overview to everyone without the admin role, whatever the session says', function (RoleName $role) {
    $this->actingAs(panelUser(RoleName::Staff, $role))
        ->withSession([LEGACY_MENU_MODE_KEY => 'admin'] + freshSignIn())
        ->get(route('admin.overview'))
        ->assertForbidden();
})->with(array_values(array_filter(RoleName::cases(), fn (RoleName $role) => $role !== RoleName::Admin)));

it('refuses every admin page to staff, whatever the session says', function (string $route) {
    $this->actingAs(panelUser(RoleName::Staff))
        ->withSession([LEGACY_MENU_MODE_KEY => 'admin'] + freshSignIn())
        ->get(route($route))
        ->assertForbidden();
})->with('admin menu pages');

it('shows HR its permitted admin pages with the Staff panel and no switch', function () {
    $response = $this->actingAs(panelUser(RoleName::Staff, RoleName::Hr))
        ->withSession(freshSignIn())
        ->get(route('admin.access-review.index'))
        ->assertOk()
        ->assertDontSee('data-panel-switch', false);

    assertStaffPanel($response);
});

it('removes the switch and the Admin panel on the next request when the admin role goes', function () {
    $admin = panelUser(RoleName::Staff, RoleName::Admin);
    $this->actingAs($admin)->get(route('dashboard'))->assertSee('data-panel-switch', false);

    $admin->removeRole(RoleName::Admin->value);

    $this->actingAs($admin)->get(route('dashboard'))->assertOk()->assertDontSee('data-panel-switch', false);
    $this->actingAs($admin)->get(route('admin.overview'))->assertForbidden();
    $this->actingAs($admin)->get(route('admin.roles.index'))->assertForbidden();
});

it('still asks admins for a fresh sign-in on access review and system status', function (string $route) {
    $this->actingAs(panelUser(RoleName::Admin))
        ->get(route($route))
        ->assertRedirect(route('auth.reauth'));
})->with(['admin.access-review.index', 'admin.system.index']);

it('sends the old menu mode form to the matching panel without storing anything', function () {
    $admin = panelUser(RoleName::Admin);
    $staff = panelUser(RoleName::Staff);

    $this->actingAs($admin)->post(route('communications.menu-mode'), ['mode' => 'admin'])
        ->assertRedirect(route('admin.overview'));
    $this->actingAs($admin)->post(route('communications.menu-mode'), ['mode' => 'normal'])
        ->assertRedirect(route('dashboard'));
    $this->actingAs($staff)->post(route('communications.menu-mode'), ['mode' => 'admin'])
        ->assertRedirect(route('dashboard'));

    expect(session()->has(LEGACY_MENU_MODE_KEY))->toBeFalse();
});
