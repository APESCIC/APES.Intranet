<?php

use App\Models\User;
use Database\Seeders\LegalPagesSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([RolesAndPermissionsSeeder::class, LegalPagesSeeder::class]);
});

it('redirects guests to login on the hosting and security page', function () {
    $this->get(route('legal.hosting-and-security'))
        ->assertRedirect(route('login'));
});

it('shows approved hosting and security wording to signed-in users', function () {
    $user = User::factory()->create()->assignRole('staff');

    $this->actingAs($user)
        ->get(route('legal.hosting-and-security'))
        ->assertOk()
        ->assertSee('Hosting and security')
        ->assertSee('London')
        ->assertSee('Akamai Connected Cloud')
        ->assertSee('Linode')
        ->assertSee('trust.akamai.com')
        ->assertSee('Disk encryption of this Linode')
        ->assertSee('12 November 2026')
        ->assertSee('SOC 2 Type II')
        ->assertSee('What we do not claim')
        ->assertSee('Cyber Essentials covers Akamai')
        ->assertSee('not the servers that run APES applications');
});
