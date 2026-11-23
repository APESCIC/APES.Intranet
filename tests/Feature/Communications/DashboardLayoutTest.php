<?php

use App\Core\Access\RoleName;
use App\Models\User;
use Database\Seeders\CommunicationsBootstrapSeeder;
use Database\Seeders\Microsoft365BootstrapSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(CommunicationsBootstrapSeeder::class);
    $this->seed(Microsoft365BootstrapSeeder::class);
});

it('groups the dashboard into titled sections and drops the empty Teams chat card', function () {
    $staff = User::factory()->create()->assignRole(RoleName::Staff->value);

    $html = $this->actingAs($staff)->get(route('dashboard'))->assertOk()->getContent();
    $main = (string) str($html)->after('<main id="main-content"')->before('</main>');

    expect($main)
        ->toContain('id="dashboard-section-m365"')
        ->toContain('sg-app-tile')
        ->toContain('>Teams</span>')
        ->not->toContain('data-card="teams"')
        ->not->toContain('Chat in Teams');
});
