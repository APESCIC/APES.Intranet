<?php

use App\Core\Access\RoleName;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('searches and filters the directory', function () {
    $viewer = User::factory()->create()->assignRole(RoleName::Staff->value);
    User::factory()->create(['name' => 'Alex Care', 'team' => 'Care', 'skills' => ['First aid']])->assignRole(RoleName::Staff->value);
    User::factory()->create(['name' => 'Blake Office', 'team' => 'Office', 'skills' => ['Admin']])->assignRole(RoleName::Volunteer->value);

    $this->actingAs($viewer)
        ->get(route('people.directory.index', ['q' => 'Alex', 'type' => 'staff', 'team' => 'Care', 'skill' => 'First aid']))
        ->assertOk()
        ->assertSee('Alex Care')
        ->assertDontSee('Blake Office');
});

it('hides inactive people from the directory', function () {
    $viewer = User::factory()->create()->assignRole(RoleName::Staff->value);
    User::factory()->inactive()->create(['name' => 'Gone Person'])->assignRole(RoleName::Staff->value);

    $this->actingAs($viewer)
        ->get(route('people.directory.index'))
        ->assertOk()
        ->assertDontSee('Gone Person');
});
