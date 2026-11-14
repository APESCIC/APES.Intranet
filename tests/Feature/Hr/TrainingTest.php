<?php

use App\Core\Access\RoleName;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Hr\Models\TrainingCourse;
use Modules\Hr\Services\TrainingService;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/**
 * @param  list<RoleName>  $roles
 */
function trainingActor(object $test, array $roles): User
{
    $user = User::factory()->create()->syncRoles(array_map(fn (RoleName $role) => $role->value, $roles));
    $test->actingAs($user)->withSession(['intranet.auth_time' => now()->getTimestamp()]);

    return $user;
}

it('shows a member their required courses and LMS links', function () {
    $member = trainingActor($this, [RoleName::Staff]);

    $this->get(route('hr.training.mine'))
        ->assertOk()
        ->assertSee('Safeguarding essentials')
        ->assertSee('https://lms.intranet.apes.org.uk')
        ->assertSee('overdue or missing');

    $rows = app(TrainingService::class)->forMember($member);
    expect($rows)->not->toBeEmpty()
        ->and($rows->every(fn (array $row) => $row['overdue']))->toBeTrue();
});

it('lets HR record a completion and clear the overdue report', function () {
    $hr = trainingActor($this, [RoleName::Staff, RoleName::Hr]);
    $member = User::factory()->create(['name' => 'Trainee Example'])->assignRole(RoleName::Staff->value);
    app(TrainingService::class)->ensureDefaults();
    $course = TrainingCourse::query()->where('code', 'safeguarding')->firstOrFail();

    $this->get(route('hr.training.overdue'))
        ->assertOk()
        ->assertSee('Trainee Example');

    $this->post(route('hr.training.completions.store'), [
        'user_id' => $member->id,
        'course_id' => $course->id,
        'completed_on' => now()->toDateString(),
    ])->assertRedirect();

    $row = app(TrainingService::class)->forMember($member)
        ->first(fn (array $r) => $r['course']->id === $course->id);

    expect($row['overdue'])->toBeFalse()
        ->and($row['completion'])->not->toBeNull();
});

it('refuses the overdue report to staff without HR', function () {
    trainingActor($this, [RoleName::Staff]);
    $this->get(route('hr.training.overdue'))->assertForbidden();
});
