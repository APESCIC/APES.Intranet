<?php

use App\Core\Access\RoleName;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('sends guests away from the roles screen', function () {
    $this->get(route('admin.roles.index'))->assertRedirect(route('login'));
});

it('shows the roles screen to admins only', function (RoleName $role, int $status) {
    $user = User::factory()->create()->assignRole($role->value);

    $this->actingAs($user)->get(route('admin.roles.index'))->assertStatus($status);
})->with([
    'staff' => [RoleName::Staff, 403],
    'volunteer' => [RoleName::Volunteer, 403],
    'student' => [RoleName::Student, 403],
    'manager' => [RoleName::Manager, 403],
    'hr' => [RoleName::Hr, 403],
    'legal' => [RoleName::Legal, 403],
    'admin' => [RoleName::Admin, 200],
]);

it('lists every role and offers no way to change them', function () {
    $admin = User::factory()->create()->assignRole(RoleName::Admin->value);

    $response = $this->actingAs($admin)->get(route('admin.roles.index'))->assertOk();

    foreach (RoleName::cases() as $role) {
        $response->assertSee($role->label());
    }
    $main = str($response->getContent())->after('<main')->before('</main>');
    expect((string) $main)->not->toContain('<form');
});

it('treats managers and flagged staff as supervisors', function () {
    $manager = User::factory()->create()->assignRole(RoleName::Manager->value);
    $flagged = User::factory()->create(['is_supervisor' => true])->assignRole(RoleName::Staff->value);
    $staff = User::factory()->create()->assignRole(RoleName::Staff->value);

    expect($manager->isSupervisor())->toBeTrue()
        ->and($flagged->isSupervisor())->toBeTrue()
        ->and($staff->isSupervisor())->toBeFalse();
});
