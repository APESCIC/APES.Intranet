<?php

use App\Core\Access\RoleName;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('stores glass and system as defaults on a new user', function () {
    $user = User::factory()->create()->fresh();

    expect($user->appearance_theme)->toBe('glass')
        ->and($user->appearance_mode)->toBe('system')
        ->and($user->appearance_reduced_transparency)->toBeFalse();
});

it('saves theme and mode for the signed-in user', function () {
    $user = User::factory()->create()->assignRole(RoleName::Staff->value);

    $this->actingAs($user)
        ->postJson(route('appearance.preferences.update'), [
            'theme' => 'jungle',
            'mode' => 'dark',
            'reducedTransparency' => true,
        ])
        ->assertOk()
        ->assertJson([
            'theme' => 'jungle',
            'mode' => 'dark',
            'reducedTransparency' => true,
        ]);

    $user->refresh();

    expect($user->appearance_theme)->toBe('jungle')
        ->and($user->appearance_mode)->toBe('dark')
        ->and($user->appearance_reduced_transparency)->toBeTrue();
});

it('renders saved appearance attributes on the dashboard', function () {
    $user = User::factory()->create([
        'appearance_theme' => 'jungle',
        'appearance_mode' => 'dark',
        'appearance_reduced_transparency' => true,
    ])->assignRole(RoleName::Staff->value);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-theme="jungle"', false)
        ->assertSee('data-mode="dark"', false)
        ->assertSee('data-appearance-mode="dark"', false)
        ->assertSee('data-reduced-transparency', false)
        ->assertSee('data-appearance-mode-toggle', false)
        ->assertSee('data-appearance-panel', false);
});

it('refuses appearance saves from guests', function () {
    $this->postJson(route('appearance.preferences.update'), [
        'theme' => 'glass',
        'mode' => 'light',
    ])->assertUnauthorized();
});
