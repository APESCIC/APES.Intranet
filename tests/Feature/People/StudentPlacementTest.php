<?php

use App\Core\Access\RoleName;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\People\Models\Placement;
use Modules\People\Models\PlacementHour;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('shows a student only their own placement', function () {
    $student = User::factory()->create()->assignRole(RoleName::Student->value);
    $other = User::factory()->create()->assignRole(RoleName::Student->value);
    $own = Placement::query()->create([
        'student_id' => $student->id,
        'title' => 'Own Placement',
        'organisation' => 'APES',
    ]);
    Placement::query()->create([
        'student_id' => $other->id,
        'title' => 'Other Placement',
        'organisation' => 'Elsewhere',
    ]);

    $this->actingAs($student)
        ->get(route('people.students.index'))
        ->assertOk()
        ->assertSee('Own Placement')
        ->assertDontSee('Other Placement');

    $this->actingAs($student)
        ->get(route('people.students.show', $own))
        ->assertOk();
});

it('lets a supervisor approve or return hours', function () {
    $student = User::factory()->create()->assignRole(RoleName::Student->value);
    $supervisor = User::factory()->create()->assignRole([RoleName::Staff->value, RoleName::Supervisor->value]);
    $placement = Placement::query()->create([
        'student_id' => $student->id,
        'supervisor_id' => $supervisor->id,
        'title' => 'Care Placement',
    ]);
    $hour = PlacementHour::query()->create([
        'placement_id' => $placement->id,
        'student_id' => $student->id,
        'worked_on' => now()->toDateString(),
        'minutes' => 120,
        'status' => PlacementHour::STATUS_PENDING,
    ]);

    $this->actingAs($supervisor)
        ->post(route('people.students.hours.review', $hour), ['decision' => 'approved'])
        ->assertRedirect();

    expect($hour->refresh()->status)->toBe(PlacementHour::STATUS_APPROVED);
    expect($placement->fresh()->approvedMinutes())->toBe(120);
});
