<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    fakeCloudron($this);
});

it('creates an active profile on first Cloudron sign-in', function () {
    signInWithCloudron($this, ['intranet.staff']);

    $user = User::where('email', 'alex@example.test')->firstOrFail();
    expect($user->is_active)->toBeTrue()
        ->and($user->getAuthPassword())->not->toBe('');
});

it('marks a removed member inactive when they lose every intranet group', function () {
    signInWithCloudron($this, ['intranet.staff']);
    $this->post(route('logout'));

    signInWithCloudron($this, [])->assertForbidden();

    $user = User::where('oidc_sub', 'sub-alex')->firstOrFail();
    expect($user->is_active)->toBeFalse()
        ->and($user->getRoleNames()->all())->toBe([]);
});
