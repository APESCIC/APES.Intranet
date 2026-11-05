<?php

use App\Core\Access\RoleName;
use App\Core\Audit\AuditLog;
use App\Core\Auth\OidcIdentity;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    fakeCloudron($this);
    config(['intranet.fresh_signin_minutes' => 15]);

    Route::middleware(['web', 'auth', 'fresh-signin'])->get('/_test/hr-record', fn () => 'HR record')->name('test.hr');
});

/**
 * Signs in through the fake Cloudron with the given auth_time.
 */
function signInAt(object $test, string $group, int $authTime): void
{
    $test->idp->identity = new OidcIdentity('sub-alex', 'alex@example.test', 'Alex Example', $authTime);
    $test->directory->groups = ['intranet.staff', $group];
    $test->get(route('auth.callback'))->assertRedirect();
}

it('lets a recent sign-in open a fresh-signin page', function () {
    signInAt($this, 'intranet.hr', now()->getTimestamp());

    $this->get('/_test/hr-record')->assertOk()->assertSee('HR record');
});

it('sends a sign-in older than the window back to Cloudron, then returns to the page', function () {
    signInAt($this, 'intranet.hr', now()->getTimestamp());
    $this->travel(16)->minutes();

    $this->get('/_test/hr-record')->assertRedirect(route('auth.reauth'));
    $this->get(route('auth.reauth'))->assertRedirect('https://my.cloudron.example.test/openid/auth?prompt=login&max_age=0');
    expect($this->idp->lastForceLogin)->toBeTrue()
        ->and(AuditLog::where('event', 'auth.fresh_signin_required')->first()?->context)->toBe(['path' => '/_test/hr-record']);

    $this->idp->identity = new OidcIdentity('sub-alex', 'alex@example.test', 'Alex Example', now()->getTimestamp());
    $this->get(route('auth.callback'))->assertRedirect(url('/_test/hr-record'));
    $this->get('/_test/hr-record')->assertOk();
});

it('uses the Cloudron auth_time, not the time of the intranet sign-in', function () {
    signInAt($this, 'intranet.hr', now()->subHour()->getTimestamp());

    $this->get('/_test/hr-record')->assertRedirect(route('auth.reauth'));
});

it('asks admin and management to sign in again too', function (string $group) {
    signInAt($this, $group, now()->subMinutes(20)->getTimestamp());

    $this->get('/_test/hr-record')->assertRedirect(route('auth.reauth'));
})->with(['intranet.admin', 'intranet.management']);

it('refuses when Cloudron did not really ask for a new sign-in', function () {
    $stale = now()->subHour()->getTimestamp();
    signInAt($this, 'intranet.hr', $stale);
    $this->get('/_test/hr-record');
    $this->get(route('auth.reauth'));

    $this->idp->identity = new OidcIdentity('sub-alex', 'alex@example.test', 'Alex Example', $stale);
    $this->get(route('auth.callback'))->assertForbidden()->assertSee('Cloudron did not ask you to sign in again');

    expect(AuditLog::where('event', 'auth.fresh_signin_not_honoured')->exists())->toBeTrue();
    $this->get('/_test/hr-record')->assertRedirect(route('auth.reauth'));
});

it('takes the window from config', function () {
    config(['intranet.fresh_signin_minutes' => 5]);
    signInAt($this, 'intranet.hr', now()->getTimestamp());
    $this->travel(6)->minutes();

    $this->get('/_test/hr-record')->assertRedirect(route('auth.reauth'));
});

it('treats a QA sign-in as fresh', function () {
    $user = User::factory()->create()->assignRole(RoleName::Hr->value);
    $this->actingAs($user)->withSession(['intranet.auth_time' => now()->getTimestamp()]);

    $this->get('/_test/hr-record')->assertOk();
});

it('sends guests to sign in rather than re-authenticate', function () {
    $this->get('/_test/hr-record')->assertRedirect(route('login'));
    $this->get(route('auth.reauth'))->assertRedirect(route('login'));
});
