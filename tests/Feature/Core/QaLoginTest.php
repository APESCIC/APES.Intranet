<?php

use App\Core\Access\RoleName;
use App\Core\Access\RoleStacks;
use App\Core\Audit\AuditLog;
use App\Core\Qa\QaLogin;
use App\Models\User;
use Database\Seeders\QaUserSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    // CSRF checks switch back on once the environment is no longer "testing".
    $this->withoutMiddleware(PreventRequestForgery::class);
});

function enableQa(string $environment = 'local', bool $flag = true): void
{
    config(['intranet.qa_login_enabled' => $flag]);
    app()->detectEnvironment(fn () => $environment);
}

it('is not found when the flag is off', function () {
    enableQa(flag: false);

    $this->get(route('qa.index'))->assertNotFound();
    $this->post(route('qa.login'), ['profile' => 'admin'])->assertNotFound();
    $this->post(route('qa.login'), ['profile' => 'staff-admin'])->assertNotFound();
    $this->assertGuest();
});

it('cannot be enabled outside local, even with the flag on', function (string $environment) {
    enableQa($environment);

    expect(QaLogin::enabled())->toBeFalse();
    $this->get(route('qa.index'))->assertNotFound();
    $this->post(route('qa.login'), ['profile' => 'admin'])->assertNotFound();
    $this->assertGuest();
})->with(['production', 'staging', 'testing']);

it('lists the 8 approved groups and the common stacks when enabled on local', function () {
    enableQa();

    $response = $this->get(route('qa.index'))->assertOk();

    foreach (RoleName::cases() as $role) {
        $response->assertSee($role->label());
    }
    foreach (RoleStacks::common() as $roles) {
        $response->assertSee(RoleStacks::label($roles));
    }
    $response->assertSee('intranet.supervisor')->assertSee('intranet.management');
});

it('shows the Developer sign-in panel on the login page when enabled on local', function () {
    enableQa();

    $response = $this->get(route('login'))->assertOk()
        ->assertSee('id="developer-signin"', false)
        ->assertSee('Developer sign-in')
        ->assertSee('Local only')
        ->assertSee('Sign in with Cloudron')
        ->assertDontSee('use the QA role switcher');

    foreach (RoleName::cases() as $role) {
        $response->assertSee('value="'.$role->value.'"', false);
    }
    foreach (array_keys(RoleStacks::common()) as $stack) {
        $response->assertSee('value="'.$stack.'"', false);
    }
});

it('leaves the Developer sign-in panel off the login page when the flag is off', function () {
    enableQa(flag: false);

    $this->get(route('login'))->assertOk()
        ->assertSee('Sign in with Cloudron')
        ->assertDontSee('developer-signin')
        ->assertDontSee(route('qa.login', absolute: false));
});

it('leaves the Developer sign-in panel off the login page outside local, even with the flag on', function (string $environment) {
    enableQa($environment);

    $this->get(route('login'))->assertOk()
        ->assertDontSee('developer-signin')
        ->assertDontSee('Local only');
})->with(['production', 'staging', 'testing']);

it('keeps /qa as a fallback with the same panel', function () {
    enableQa();

    $this->get(route('qa.index'))->assertOk()
        ->assertSee('id="developer-signin"', false)
        ->assertSee('Local only');
});

it('signs in from the login page panel and returns to the page that was asked for', function () {
    enableQa();

    $this->get(route('admin.audit.index'))->assertRedirect(route('login'));
    $this->get(route('login'))->assertSee('developer-signin');
    $this->post(route('qa.login'), ['profile' => 'admin'])->assertRedirect(route('admin.audit.index'));
});

it('signs in as a fake account with exactly one group role', function (RoleName $role) {
    enableQa();

    $this->post(route('qa.login'), ['profile' => $role->value])->assertRedirect(route('dashboard'));

    $user = auth()->user();
    expect($user->email)->toBe("qa-{$role->value}@example.test")
        ->and($user->getRoleNames()->all())->toBe([$role->value]);
})->with(RoleName::cases());

it('signs in as a fake account with a stack of roles', function (string $stack) {
    enableQa();

    $this->post(route('qa.login'), ['profile' => $stack])->assertRedirect(route('dashboard'));

    $expected = array_map(fn (RoleName $role) => $role->value, RoleStacks::common()[$stack]);
    expect(auth()->user()->getRoleNames()->sort()->values()->all())->toBe(collect($expected)->sort()->values()->all());
})->with(array_keys(RoleStacks::common()));

it('reuses the same fake account each time', function () {
    enableQa();

    $this->post(route('qa.login'), ['profile' => 'staff']);
    $this->post(route('qa.logout'));
    $this->post(route('qa.login'), ['profile' => 'staff']);

    expect(User::where('email', 'qa-staff@example.test')->count())->toBe(1);
});

it('rejects an unknown profile', function () {
    enableQa();

    $this->post(route('qa.login'), ['profile' => 'superuser'])->assertSessionHasErrors('profile');
    $this->assertGuest();
});

it('seeds one fake account per group and stack', function () {
    $this->seed(QaUserSeeder::class);

    expect(User::where('email', 'like', 'qa-%@example.test')->count())->toBe(count(RoleName::cases()) + count(RoleStacks::common()));
});

it('asks a stale QA session to confirm its role on the page it came from, then opens the page', function () {
    enableQa();
    $this->post(route('qa.login'), ['profile' => 'admin']);
    $this->travel(16)->minutes();

    $this->get(route('admin.system.index'))->assertRedirect(route('auth.reauth'));
    $this->get(route('auth.reauth'), ['referer' => route('admin.overview')])->assertRedirect(route('admin.overview'));
    $this->get(route('admin.overview'))->assertOk()
        ->assertSee('data-qa-confirm', false)
        ->assertSee('Confirm QA Admin')
        ->assertSee('data-qa-autoopen', false);
    $this->post(route('qa.login'), ['profile' => 'admin'])->assertRedirect(route('admin.system.index'));
    $this->get(route('admin.system.index'))->assertOk();
});

it('asks a stale QA session to confirm on the dashboard when it did not come from a page on this site', function (?string $referer) {
    enableQa();
    $this->post(route('qa.login'), ['profile' => 'admin']);
    $this->travel(16)->minutes();

    $this->get(route('admin.system.index'));
    $this->get(route('auth.reauth'), $referer === null ? [] : ['referer' => $referer])->assertRedirect(route('dashboard'));
})->with([
    'no referer' => [fn () => null],
    'another site' => [fn () => 'https://evil.example/admin'],
    'the page itself' => [fn () => route('admin.system.index')],
]);

it('still sends a non-QA account to the switcher when QA is on', function () {
    enableQa();
    $user = User::factory()->create();
    $user->assignRole(RoleName::Admin->value);

    $this->actingAs($user)->get(route('auth.reauth'))->assertRedirect(route('qa.index'));
});

it('signs out of QA to the Developer sign-in panel on the login page', function () {
    enableQa();
    $this->post(route('qa.login'), ['profile' => 'admin']);

    $this->post(route('qa.logout'))->assertRedirect(route('login').'#developer-signin');
    $this->assertGuest();
    $this->get(route('login'))->assertSee('You have signed out of QA.')->assertSee('id="developer-signin"', false);
});

it('shows the amber QA pill with the current profile, and Sign out of QA, to a QA account', function () {
    enableQa();
    $this->post(route('qa.login'), ['profile' => 'staff-admin']);

    $this->get(route('dashboard'))->assertOk()
        ->assertSee('data-qa-pill', false)
        ->assertSee('QA: Staff + Admin')
        ->assertSee('popovertarget="qa-switcher"', false)
        ->assertSee('name="return_to" value="/dashboard"', false)
        ->assertSee('aria-current="true"', false)
        ->assertSee('action="'.route('qa.logout').'"', false)
        ->assertSee('Sign out of QA')
        ->assertDontSee('Switch role')
        ->assertDontSee('action="'.route('logout').'"', false);
});

it('keeps the Staff/Admin panel switch apart from the QA pill', function () {
    enableQa();
    $this->post(route('qa.login'), ['profile' => 'staff-admin']);

    $this->get(route('dashboard'))->assertSee('data-panel-switch', false)->assertSee('data-qa-pill', false);
});

it('gives ordinary accounts the normal Sign out and no QA pill, even with QA on', function () {
    enableQa();
    $user = User::factory()->create();
    $user->assignRole(RoleName::Staff->value);

    $this->actingAs($user)->get(route('dashboard'))->assertOk()
        ->assertSee('action="'.route('logout').'"', false)
        ->assertDontSee('data-qa-pill', false)
        ->assertDontSee('Switch role');
});

it('hides the QA pill from a QA account when the flag is off or outside local', function (string $environment, bool $flag) {
    enableQa($environment, $flag);
    $user = QaLogin::userFor('admin');

    $this->actingAs($user)->get(route('dashboard'))->assertOk()
        ->assertDontSee('data-qa-pill', false)
        ->assertDontSee('qa-switcher')
        ->assertDontSee(route('qa.logout'));
})->with([
    'flag off' => ['local', false],
    'production' => ['production', true],
    'staging' => ['staging', true],
    'testing' => ['testing', true],
]);

it('stays on the page after a switch when the new role may open it', function () {
    enableQa();
    $this->post(route('qa.login'), ['profile' => 'staff']);

    $this->post(route('qa.login'), ['profile' => 'admin', 'return_to' => '/admin/audit?page=1'])
        ->assertRedirect('/admin/audit?page=1');
    $this->get('/admin/audit?page=1')->assertOk();
    expect(auth()->user()->email)->toBe('qa-admin@example.test');
});

it('lands on the dashboard after a switch when the new role may not open the page', function () {
    enableQa();
    $this->post(route('qa.login'), ['profile' => 'admin']);

    $this->post(route('qa.login'), ['profile' => 'volunteer', 'return_to' => '/admin/audit'])
        ->assertRedirect('/admin/audit');
    $this->get('/admin/audit')->assertRedirect(route('dashboard'));
    $this->get(route('dashboard'))->assertOk()->assertSee('Your new QA role cannot open that page');
    $this->get('/admin/audit')->assertForbidden();
});

it('ignores a return path that could lead to another site', function (string $returnTo) {
    enableQa();

    $this->post(route('qa.login'), ['profile' => 'staff', 'return_to' => $returnTo])
        ->assertRedirect(route('dashboard'));
})->with(['//evil.example', 'https://evil.example/', '/\\evil.example', 'dashboard', ' /dashboard']);

it('audits a role switch as a QA sign-in, with the profile it switched from', function () {
    enableQa();
    $this->post(route('qa.login'), ['profile' => 'staff']);
    $this->post(route('qa.login'), ['profile' => 'admin', 'return_to' => '/dashboard']);

    $entry = AuditLog::query()->where('event', 'auth.signed_in')->latest('id')->first();
    expect($entry->context)->toMatchArray(['method' => 'qa', 'roles' => ['admin'], 'switched_from' => 'staff']);
});
