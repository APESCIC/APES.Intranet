<?php

use App\Core\Access\AccessMatrix;
use App\Core\Auth\OidcIdentity;
use App\Models\User;
use Database\Seeders\HrPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Hr\HrPermissions;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(HrPermissionsSeeder::class);
    fakeCloudron($this);
});

it('sends guests to the sign-in page', function () {
    $this->get('/')->assertRedirect(route('login'));
    $this->get(route('dashboard'))->assertRedirect(route('login'));
    $this->get(route('login'))->assertOk()->assertSee('Sign in with Cloudron');
});

it('redirects to Cloudron to sign in', function () {
    $this->get(route('auth.redirect'))->assertRedirect('https://my.cloudron.example.test/openid/auth');
});

it('gives a staff group member the staff role', function () {
    signInWithCloudron($this, ['intranet.staff'])->assertRedirect(route('dashboard'));

    $user = User::where('email', 'alex@example.test')->firstOrFail();
    $this->assertAuthenticatedAs($user);
    expect($user->oidc_sub)->toBe('sub-alex')
        ->and($user->getRoleNames()->all())->toBe(['staff']);
});

it('maps several intranet groups and ignores other groups', function () {
    signInWithCloudron($this, ['intranet.staff', 'intranet.management', 'lms.teacher', 'Intranet.HR']);

    expect(User::where('email', 'alex@example.test')->firstOrFail()->getRoleNames()->sort()->values()->all())
        ->toBe(['hr', 'management', 'staff']);
});

it('gives a supervisor group member team.supervise but not team.manage', function () {
    signInWithCloudron($this, ['intranet.volunteer', 'intranet.supervisor']);

    $user = User::where('email', 'alex@example.test')->firstOrFail();
    expect($user->getRoleNames()->sort()->values()->all())->toBe(['supervisor', 'volunteer'])
        ->and($user->can(AccessMatrix::TEAM_SUPERVISE))->toBeTrue()
        ->and($user->can(AccessMatrix::TEAM_MANAGE))->toBeFalse()
        ->and($user->can(HrPermissions::ACCESS))->toBeFalse();
});

it('gives a management group member team.supervise, team.manage and HR access', function () {
    signInWithCloudron($this, ['intranet.staff', 'intranet.management']);

    $user = User::where('email', 'alex@example.test')->firstOrFail();
    expect($user->getRoleNames()->sort()->values()->all())->toBe(['management', 'staff'])
        ->and($user->can(AccessMatrix::TEAM_SUPERVISE))->toBeTrue()
        ->and($user->can(AccessMatrix::TEAM_MANAGE))->toBeTrue()
        ->and($user->can(HrPermissions::ACCESS))->toBeTrue();
});

it('removes a role at the next sign-in when the group is removed', function () {
    signInWithCloudron($this, ['intranet.staff', 'intranet.management']);
    $this->post(route('logout'));

    signInWithCloudron($this, ['intranet.staff']);

    expect(User::where('oidc_sub', 'sub-alex')->firstOrFail()->getRoleNames()->all())->toBe(['staff']);
});

it('denies sign-in with a clear page when the person has no intranet group', function () {
    signInWithCloudron($this, ['lms.student'])
        ->assertForbidden()
        ->assertSee('not in an intranet group');

    $this->assertGuest();
    expect(User::where('email', 'alex@example.test')->exists())->toBeFalse();
});

it('strips all roles from a known person who has lost every intranet group', function () {
    signInWithCloudron($this, ['intranet.admin']);
    $this->post(route('logout'));

    signInWithCloudron($this, [])->assertForbidden();

    $this->assertGuest();
    expect(User::where('oidc_sub', 'sub-alex')->firstOrFail()->getRoleNames()->all())->toBe([]);
});

it('keeps the same account when the email changes in Cloudron', function () {
    signInWithCloudron($this, ['intranet.staff']);
    $this->post(route('logout'));

    signInWithCloudron($this, ['intranet.staff'], email: 'alex.new@example.test');

    expect(User::count())->toBe(1)
        ->and(User::firstOrFail()->email)->toBe('alex.new@example.test');
});

it('refuses an email already used by a different Cloudron account', function () {
    User::factory()->create(['email' => 'alex@example.test', 'oidc_sub' => 'sub-someone-else']);

    signInWithCloudron($this, ['intranet.staff'])->assertForbidden()->assertSee('already uses your email');
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

it('shows a temporary error when the LDAP directory is unavailable and source is ldap', function () {
    config(['intranet.directory_source' => 'ldap']);
    $this->directory->unavailable = true;

    signInWithCloudron($this, ['intranet.staff'])->assertStatus(503);
    $this->assertGuest();
});

it('maps OIDC groups claim roles and ignores other groups when source is cloudron', function () {
    config(['intranet.directory_source' => 'cloudron']);
    $this->directory->groups = ['intranet.admin']; // must not be used

    signInWithCloudron($this, ['intranet.staff', 'intranet.hr', 'other']);

    expect(User::where('email', 'alex@example.test')->firstOrFail()->getRoleNames()->sort()->values()->all())
        ->toBe(['hr', 'staff'])
        ->and(User::where('email', 'alex@example.test')->firstOrFail()->cloudron_username)->toBe('alex');
});

it('returns 503 and leaves roles unchanged when the groups claim is missing', function () {
    config(['intranet.directory_source' => 'cloudron']);
    signInWithCloudron($this, ['intranet.staff']);
    $user = User::where('oidc_sub', 'sub-alex')->firstOrFail();
    $this->post(route('logout'));

    $this->idp->identity = new OidcIdentity('sub-alex', 'alex@example.test', 'Alex Example', null, null, 'alex');
    $this->get(route('auth.callback'))->assertStatus(503);

    $user->refresh();
    expect($user->getRoleNames()->all())->toBe(['staff'])
        ->and($user->is_active)->toBeTrue();
});

it('denies access for an empty groups claim or only non-intranet groups', function () {
    config(['intranet.directory_source' => 'cloudron']);

    signInWithCloudron($this, [])->assertForbidden();
    $this->assertGuest();

    signInWithCloudron($this, ['students.only'])->assertForbidden();
    $this->assertGuest();
});

it('does not call LDAP DirectoryGroups when source is cloudron', function () {
    config(['intranet.directory_source' => 'cloudron']);
    $this->directory->unavailable = true;

    signInWithCloudron($this, ['intranet.staff'])->assertRedirect(route('dashboard'));
    $this->assertAuthenticated();
});

it('signs out', function () {
    signInWithCloudron($this, ['intranet.staff']);

    $this->post(route('logout'))->assertRedirect(route('login'));
    $this->assertGuest();
});

it('keeps OIDC and LDAP secrets blank in the committed env examples', function () {
    foreach (['.env.example', '.env.herd.example'] as $file) {
        $lines = file(base_path($file), FILE_IGNORE_NEW_LINES);

        foreach (['OIDC_CLIENT_ID', 'OIDC_CLIENT_SECRET', 'LDAP_BIND_DN', 'LDAP_BIND_PASSWORD'] as $key) {
            expect($lines)->toContain($key.'=');
        }
    }
});
