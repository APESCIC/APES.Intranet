<?php

use App\Core\Access\RoleName;
use App\Core\Qa\QaLogin;
use App\Models\User;
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
    $this->post(route('qa.login'), ['role' => 'admin'])->assertNotFound();
});

it('cannot be enabled outside local, even with the flag on', function (string $environment) {
    enableQa($environment);

    expect(QaLogin::enabled())->toBeFalse();
    $this->get(route('qa.index'))->assertNotFound();
    $this->post(route('qa.login'), ['role' => 'admin'])->assertNotFound();
    $this->assertGuest();
})->with(['production', 'staging', 'testing']);

it('lists every role when enabled on local', function () {
    enableQa();

    $response = $this->get(route('qa.index'))->assertOk();

    foreach (RoleName::cases() as $role) {
        $response->assertSee($role->label());
    }
});

it('signs in as a fake account with exactly one role', function (RoleName $role) {
    enableQa();

    $this->post(route('qa.login'), ['role' => $role->value])->assertRedirect('/');

    $user = auth()->user();
    expect($user->email)->toBe("qa-{$role->value}@example.test")
        ->and($user->getRoleNames()->all())->toBe([$role->value]);
})->with(RoleName::cases());

it('reuses the same fake account each time', function () {
    enableQa();

    $this->post(route('qa.login'), ['role' => 'staff']);
    $this->post(route('qa.logout'));
    $this->post(route('qa.login'), ['role' => 'staff']);

    expect(User::where('email', 'qa-staff@example.test')->count())->toBe(1);
});

it('rejects an unknown role', function () {
    enableQa();

    $this->post(route('qa.login'), ['role' => 'superuser'])->assertSessionHasErrors('role');
    $this->assertGuest();
});

it('signs out', function () {
    enableQa();
    $this->post(route('qa.login'), ['role' => 'admin']);

    $this->post(route('qa.logout'))->assertRedirect(route('qa.index'));
    $this->assertGuest();
});
