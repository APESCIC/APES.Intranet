<?php

use App\Core\Auth\Contracts\DirectoryGroups;
use App\Core\Auth\Contracts\IdentityProvider;
use App\Core\Auth\Exceptions\DirectoryUnavailable;
use App\Core\Auth\Exceptions\IdentityProviderUnavailable;
use App\Core\Auth\OidcIdentity;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

final class FakeIdentityProvider implements IdentityProvider
{
    public ?OidcIdentity $identity = null;

    public bool $unavailable = false;

    public function authorizationRedirect(): RedirectResponse
    {
        if ($this->unavailable) {
            throw new IdentityProviderUnavailable('down');
        }

        return redirect()->away('https://my.cloudron.example.test/openid/auth');
    }

    public function callbackIdentity(): OidcIdentity
    {
        if ($this->unavailable || $this->identity === null) {
            throw new IdentityProviderUnavailable('down');
        }

        return $this->identity;
    }
}

final class FakeDirectoryGroups implements DirectoryGroups
{
    /** @var list<string> */
    public array $groups = [];

    public bool $unavailable = false;

    public function groupsForEmail(string $email): array
    {
        if ($this->unavailable) {
            throw new DirectoryUnavailable('down');
        }

        return $this->groups;
    }
}

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->idp = new FakeIdentityProvider;
    $this->directory = new FakeDirectoryGroups;
    $this->app->instance(IdentityProvider::class, $this->idp);
    $this->app->instance(DirectoryGroups::class, $this->directory);
});

function signInAs(object $test, array $groups, string $sub = 'sub-alex', string $email = 'alex@example.test'): TestResponse
{
    $test->idp->identity = new OidcIdentity($sub, $email, 'Alex Example');
    $test->directory->groups = $groups;

    return $test->get(route('auth.callback'));
}

it('sends guests to the sign-in page', function () {
    $this->get('/')->assertRedirect(route('login'));
    $this->get(route('dashboard'))->assertRedirect(route('login'));
    $this->get(route('login'))->assertOk()->assertSee('Sign in with Cloudron');
});

it('redirects to Cloudron to sign in', function () {
    $this->get(route('auth.redirect'))->assertRedirect('https://my.cloudron.example.test/openid/auth');
});

it('gives a staff group member the staff role', function () {
    signInAs($this, ['intranet.staff'])->assertRedirect(route('dashboard'));

    $user = User::where('email', 'alex@example.test')->firstOrFail();
    $this->assertAuthenticatedAs($user);
    expect($user->oidc_sub)->toBe('sub-alex')
        ->and($user->getRoleNames()->all())->toBe(['staff']);
});

it('maps several intranet groups and ignores other groups', function () {
    signInAs($this, ['intranet.staff', 'intranet.manager', 'lms.teacher', 'Intranet.HR']);

    expect(User::where('email', 'alex@example.test')->firstOrFail()->getRoleNames()->sort()->values()->all())
        ->toBe(['hr', 'manager', 'staff']);
});

it('removes a role at the next sign-in when the group is removed', function () {
    signInAs($this, ['intranet.staff', 'intranet.manager']);
    $this->post(route('logout'));

    signInAs($this, ['intranet.staff']);

    expect(User::where('oidc_sub', 'sub-alex')->firstOrFail()->getRoleNames()->all())->toBe(['staff']);
});

it('denies sign-in with a clear page when the person has no intranet group', function () {
    signInAs($this, ['lms.student'])
        ->assertForbidden()
        ->assertSee('not in an intranet group');

    $this->assertGuest();
    expect(User::where('email', 'alex@example.test')->exists())->toBeFalse();
});

it('strips all roles from a known person who has lost every intranet group', function () {
    signInAs($this, ['intranet.admin']);
    $this->post(route('logout'));

    signInAs($this, [])->assertForbidden();

    $this->assertGuest();
    expect(User::where('oidc_sub', 'sub-alex')->firstOrFail()->getRoleNames()->all())->toBe([]);
});

it('keeps the same account when the email changes in Cloudron', function () {
    signInAs($this, ['intranet.staff']);
    $this->post(route('logout'));

    signInAs($this, ['intranet.staff'], email: 'alex.new@example.test');

    expect(User::count())->toBe(1)
        ->and(User::firstOrFail()->email)->toBe('alex.new@example.test');
});

it('refuses an email already used by a different Cloudron account', function () {
    User::factory()->create(['email' => 'alex@example.test', 'oidc_sub' => 'sub-someone-else']);

    signInAs($this, ['intranet.staff'])->assertForbidden()->assertSee('already uses your email');
    $this->assertGuest();
});

it('denies an identity without an email', function () {
    $this->idp->identity = new OidcIdentity('sub-alex', null, 'Alex');
    $this->directory->groups = ['intranet.staff'];

    $this->get(route('auth.callback'))->assertForbidden();
    $this->assertGuest();
});

it('shows a temporary error when Cloudron is unavailable', function () {
    $this->idp->unavailable = true;

    $this->get(route('auth.redirect'))->assertStatus(503);
    $this->get(route('auth.callback'))->assertStatus(503);
    $this->assertGuest();
});

it('shows a temporary error when the directory is unavailable', function () {
    $this->directory->unavailable = true;

    signInAs($this, ['intranet.staff'])->assertStatus(503);
    $this->assertGuest();
});

it('signs out', function () {
    signInAs($this, ['intranet.staff']);

    $this->post(route('logout'))->assertRedirect(route('login'));
    $this->assertGuest();
});

it('keeps OIDC and LDAP secrets blank in the committed env examples', function () {
    foreach (['.env.example', '.env.laragon.example'] as $file) {
        $lines = file(base_path($file), FILE_IGNORE_NEW_LINES);

        foreach (['OIDC_CLIENT_ID', 'OIDC_CLIENT_SECRET', 'LDAP_BIND_DN', 'LDAP_BIND_PASSWORD'] as $key) {
            expect($lines)->toContain($key.'=');
        }
    }
});
