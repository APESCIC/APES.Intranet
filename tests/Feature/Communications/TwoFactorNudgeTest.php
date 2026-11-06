<?php

use App\Core\Access\RoleName;
use App\Models\User;
use Database\Seeders\CommunicationsPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Communications\Models\DashboardDismissal;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(CommunicationsPermissionsSeeder::class);
});

it('shows a dismissible 2FA nudge that lasts the configured period', function () {
    config(['communications.two_factor_nudge.dismiss_days' => 7]);
    $user = User::factory()->create()->assignRole(RoleName::Staff->value);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Set up a passkey or authenticator')
        ->assertSee(route('people.security'), false);

    $this->actingAs($user)->post(route('dashboard.dismiss', 'two-factor-nudge'))->assertRedirect();

    $dismissal = DashboardDismissal::query()->where('user_id', $user->id)->first();
    expect($dismissal)->not->toBeNull()
        ->and($dismissal->dismissed_until->greaterThan(now()->addDays(6)))->toBeTrue();

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Set up a passkey or authenticator');
});
