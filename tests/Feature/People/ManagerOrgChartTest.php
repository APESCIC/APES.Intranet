<?php

use App\Core\Access\RoleName;
use App\Core\Audit\AuditLog;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('lets HR set a manager and writes an audit entry', function () {
    $hr = User::factory()->create()->assignRole(RoleName::Hr->value);
    $manager = User::factory()->create(['name' => 'Manager One'])->assignRole(RoleName::Staff->value);
    $member = User::factory()->create(['name' => 'Member One'])->assignRole(RoleName::Staff->value);

    $this->actingAs($hr)
        ->patch(route('people.manager.update', $member), ['manager_id' => $manager->id])
        ->assertRedirect();

    expect($member->refresh()->manager_id)->toBe($manager->id);
    expect(AuditLog::query()->where('event', 'people.manager_changed')->exists())->toBeTrue();
});

it('blocks self-management and loops', function () {
    $hr = User::factory()->create()->assignRole(RoleName::Hr->value);
    $a = User::factory()->create()->assignRole(RoleName::Staff->value);
    $b = User::factory()->create(['manager_id' => $a->id])->assignRole(RoleName::Staff->value);

    $this->actingAs($hr)
        ->patch(route('people.manager.update', $a), ['manager_id' => $a->id])
        ->assertSessionHasErrors('manager_id');

    $this->actingAs($hr)
        ->patch(route('people.manager.update', $a), ['manager_id' => $b->id])
        ->assertSessionHasErrors('manager_id');
});

it('renders the org chart and accessible list', function () {
    $viewer = User::factory()->create()->assignRole(RoleName::Staff->value);
    $manager = User::factory()->create(['name' => 'Top Manager'])->assignRole(RoleName::Staff->value);
    User::factory()->create(['name' => 'Report Person', 'manager_id' => $manager->id])->assignRole(RoleName::Staff->value);

    $this->actingAs($viewer)
        ->get(route('people.org-chart'))
        ->assertOk()
        ->assertSee('Top Manager')
        ->assertSee('Report Person')
        ->assertSee('Accessible list')
        ->assertSee('reports to Top Manager');
});
