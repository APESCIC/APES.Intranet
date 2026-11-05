<?php

use App\Core\Access\RoleName;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('sends guests to sign in', function () {
    $this->get(route('access-guide'))->assertRedirect(route('login'));
});

it('shows the page to every role', function (RoleName $role) {
    $user = User::factory()->create()->assignRole($role->value);

    $this->actingAs($user)->get(route('access-guide'))->assertOk()->assertSee('Who can see what');
})->with(RoleName::cases());

it('lists exactly the 8 approved groups with their roles', function () {
    $user = User::factory()->create()->assignRole(RoleName::Staff->value);

    $main = (string) str($this->actingAs($user)->get(route('access-guide'))->getContent())->after('<main')->before('</main>');

    preg_match_all('/<code class="text-sm">(intranet\.[a-z]+)<\/code>/', $main, $matches);
    expect($matches[1])->toBe(array_keys(config('intranet.group_roles')));

    foreach (RoleName::cases() as $role) {
        expect($main)->toContain(e($role->summary()));
    }
});

it('says admin and management can see HR records and that access is audited', function () {
    $user = User::factory()->create()->assignRole(RoleName::Volunteer->value);

    $this->actingAs($user)->get(route('access-guide'))
        ->assertSee('HR records</strong> can be seen by HR, management and admin', false)
        ->assertSee('Access is audited')
        ->assertSee('This applies to admin too');
});

it('links the page from the menu for everyone', function () {
    $user = User::factory()->create()->assignRole(RoleName::Student->value);

    $this->actingAs($user)->get(route('dashboard'))->assertSee('data-nav-key="access-guide"', false);
});
