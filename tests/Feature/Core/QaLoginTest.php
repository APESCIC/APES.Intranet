<?php

use App\Core\Access\RoleName;
use App\Core\Access\RoleStacks;
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

it('signs out', function () {
    enableQa();
    $this->post(route('qa.login'), ['profile' => 'admin']);

    $this->post(route('qa.logout'))->assertRedirect(route('qa.index'));
    $this->assertGuest();
});
