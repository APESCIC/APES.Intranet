<?php

use App\Core\Access\RoleName;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('lets a member edit their own allowed fields', function () {
    $user = User::factory()->create()->assignRole(RoleName::Staff->value);

    $this->actingAs($user)
        ->put(route('people.profile.update'), [
            'job_title' => 'Keeper',
            'team' => 'Care',
            'skills' => 'First aid, Driving',
            'bio' => 'I look after the animals.',
            'phone' => '01234 567890',
            'emergency_contact_name' => 'Sam Example',
            'emergency_contact_phone' => '07123 456789',
        ])
        ->assertRedirect(route('people.profile.show', $user));

    $user->refresh();
    expect($user->job_title)->toBe('Keeper')
        ->and($user->team)->toBe('Care')
        ->and($user->skills)->toBe(['First aid', 'Driving']);
});

it('hides HR-only fields from staff viewing someone else', function () {
    $viewer = User::factory()->create()->assignRole(RoleName::Staff->value);
    $subject = User::factory()->create([
        'emergency_contact_name' => 'Secret Contact',
        'employee_number' => 'E9999',
    ])->assignRole(RoleName::Volunteer->value);

    $this->actingAs($viewer)
        ->get(route('people.directory.show', $subject))
        ->assertOk()
        ->assertDontSee('Secret Contact')
        ->assertDontSee('E9999')
        ->assertDontSee('data-field="emergency_contact_name"', false)
        ->assertDontSee('data-field="employee_number"', false);
});

it('shows HR-only fields to HR', function () {
    $viewer = User::factory()->create()->assignRole(RoleName::Hr->value);
    $subject = User::factory()->create([
        'emergency_contact_name' => 'Secret Contact',
        'employee_number' => 'E9999',
    ])->assignRole(RoleName::Staff->value);

    $this->actingAs($viewer)
        ->get(route('people.directory.show', $subject))
        ->assertOk()
        ->assertSee('Secret Contact')
        ->assertSee('E9999');
});

it('rejects photos larger than 5 MB', function () {
    Storage::fake('public');
    $user = User::factory()->create()->assignRole(RoleName::Staff->value);

    $this->actingAs($user)
        ->put(route('people.profile.update'), [
            'photo' => UploadedFile::fake()->image('huge.jpg', 100, 100)->size(5121),
        ])
        ->assertSessionHasErrors('photo');
});

it('accepts photos under 5 MB', function () {
    Storage::fake('public');
    $user = User::factory()->create()->assignRole(RoleName::Staff->value);

    $this->actingAs($user)
        ->put(route('people.profile.update'), [
            'photo' => UploadedFile::fake()->image('ok.jpg', 100, 100)->size(1024),
        ])
        ->assertRedirect();

    expect($user->refresh()->photo_path)->not->toBeNull();
});
