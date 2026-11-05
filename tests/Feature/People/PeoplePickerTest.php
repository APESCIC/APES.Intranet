<?php

use App\Core\Access\RoleName;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('finds people by name or email quickly and respects visibility', function () {
    $viewer = User::factory()->create()->assignRole(RoleName::Staff->value);

    foreach (range(1, 50) as $i) {
        User::factory()->create([
            'name' => sprintf('Member %02d', $i),
            'email' => sprintf('member%02d@example.test', $i),
            'emergency_contact_name' => 'Hidden '.$i,
        ])->assignRole(RoleName::Staff->value);
    }

    User::factory()->create([
        'name' => 'Zed Target',
        'email' => 'zed.target@example.test',
        'emergency_contact_name' => 'Should Not Appear',
    ])->assignRole(RoleName::Volunteer->value);

    $started = hrtime(true);
    $response = $this->actingAs($viewer)->getJson(route('people.picker', ['q' => 'Zed']));
    $elapsedMs = (hrtime(true) - $started) / 1_000_000;

    $response->assertOk()
        ->assertJsonFragment(['name' => 'Zed Target', 'email' => 'zed.target@example.test'])
        ->assertJsonMissing(['emergency_contact_name' => 'Should Not Appear']);

    expect($elapsedMs)->toBeLessThan(300);
});
