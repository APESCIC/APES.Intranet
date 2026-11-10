<?php

use App\Core\Access\RoleName;
use App\Core\Audit\AuditLog;
use App\Models\User;
use Database\Seeders\OperationsPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Operations\Models\Site;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(OperationsPermissionsSeeder::class);
});

it('lets an admin add and edit a site, with an audit entry', function () {
    $admin = User::factory()->create()->assignRole(RoleName::Admin->value);

    $this->actingAs($admin)->post(route('admin.operations.sites.store'), [
        'name' => 'Reptile house',
        'team' => 'Care',
    ])->assertRedirect();

    $site = Site::query()->firstOrFail();
    expect($site->team)->toBe('Care')->and($site->active)->toBeTrue();

    $this->actingAs($admin)->put(route('admin.operations.sites.update', $site), [
        'name' => 'Reptile house',
        'team' => 'Care',
        'latitude' => '53.8',
        'longitude' => '-1.55',
        'radius_metres' => 200,
        'active' => '1',
    ])->assertRedirect();

    expect($site->refresh()->hasGeofenceArea())->toBeTrue()
        ->and(AuditLog::query()->where('event', 'operations.site_updated')->exists())->toBeTrue();
});

it('keeps the site screen to admins', function () {
    $management = User::factory()->create()->assignRole(RoleName::Management->value);

    $this->actingAs($management)->get(route('admin.operations.sites.index'))->assertForbidden();
});

it('shows the sites link in the Admin panel menu only', function () {
    $admin = User::factory()->create()->assignRole(RoleName::Admin->value);

    $this->actingAs($admin)->get(route('admin.operations.sites.index'))
        ->assertOk()
        ->assertSee('Operations admin');

    $this->actingAs($admin)->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Operations admin');
});

it('treats a supervisor as overseeing only the site of their own team', function () {
    $site = Site::query()->create(['name' => 'Aviary', 'team' => 'Care']);
    $care = User::factory()->create(['team' => 'Care'])->assignRole(RoleName::Supervisor->value);
    $office = User::factory()->create(['team' => 'Office'])->assignRole(RoleName::Supervisor->value);
    $management = User::factory()->create(['team' => 'Office'])->assignRole(RoleName::Management->value);

    expect($care->can('viewTeamRota', $site))->toBeTrue()
        ->and($office->can('viewTeamRota', $site))->toBeFalse()
        ->and($management->can('viewTeamRota', $site))->toBeTrue();
});
