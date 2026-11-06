<?php

use App\Core\Access\RoleName;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('shows the security page with the Cloudron profile link', function () {
    config(['intranet.cloudron.profile_url' => 'https://my.cloudron.apes.org.uk/#/profile']);
    $user = User::factory()->create()->assignRole(RoleName::Staff->value);

    $this->actingAs($user)
        ->get(route('people.security'))
        ->assertOk()
        ->assertSee('https://my.cloudron.apes.org.uk/#/profile', false)
        ->assertSee('target="_blank"', false)
        ->assertDontSee('otpauth://')
        ->assertDontSee('BEGIN PRIVATE KEY');
});

it('refreshes totp flags for admin from the Cloudron users API', function () {
    config([
        'intranet.cloudron.api_base' => 'https://cloudron.test',
        'intranet.cloudron.api_token' => 'test-token',
    ]);

    $admin = User::factory()->create(['email' => 'admin@example.test'])->assignRole(RoleName::Admin->value);
    $member = User::factory()->create(['email' => 'member@example.test'])->assignRole(RoleName::Staff->value);

    Http::fake([
        'https://cloudron.test/api/v1/users' => Http::response([
            'users' => [
                ['email' => 'admin@example.test', 'totpEnabled' => true],
                ['email' => 'member@example.test', 'totpEnabled' => false],
            ],
        ], 200),
    ]);

    $this->actingAs($admin)
        ->post(route('admin.totp.refresh'))
        ->assertRedirect();

    expect($admin->refresh()->totp_enabled)->toBeTrue()
        ->and($member->refresh()->totp_enabled)->toBeFalse();

    $this->actingAs(User::factory()->create()->assignRole(RoleName::Staff->value))
        ->get(route('admin.totp.index'))
        ->assertForbidden();
});
