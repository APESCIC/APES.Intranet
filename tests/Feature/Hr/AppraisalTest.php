<?php

use App\Core\Access\RoleName;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Hr\Models\Appraisal;
use Modules\Hr\Services\AppraisalService;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/**
 * @param  list<RoleName>  $roles
 */
function appraisalActor(object $test, array $roles, ?User $user = null): User
{
    $user ??= User::factory()->create();
    $user->syncRoles(array_map(fn (RoleName $role) => $role->value, $roles));
    $test->actingAs($user)->withSession(['intranet.auth_time' => now()->getTimestamp()]);

    return $user;
}

it('lets only the member, manager and HR see an appraisal', function () {
    $manager = User::factory()->create()->assignRole(RoleName::Supervisor->value);
    $member = User::factory()->create(['manager_id' => $manager->id])->assignRole(RoleName::Staff->value);
    $stranger = User::factory()->create()->assignRole(RoleName::Staff->value);
    $hr = appraisalActor($this, [RoleName::Staff, RoleName::Hr]);

    $this->post(route('hr.appraisals.cycles.store'), [
        'name' => '2026 annual',
        'year' => 2026,
        'starts_on' => '2026-01-01',
        'ends_on' => '2026-12-31',
    ])->assertRedirect();

    $this->post(route('hr.appraisals.store'), [
        'cycle_id' => 1,
        'user_id' => $member->id,
    ])->assertRedirect();

    $appraisal = Appraisal::query()->firstOrFail();

    appraisalActor($this, [RoleName::Staff], $member);
    $this->get(route('hr.appraisals.show', $appraisal))->assertOk();

    appraisalActor($this, [RoleName::Supervisor], $manager);
    $this->get(route('hr.appraisals.show', $appraisal))->assertOk();

    appraisalActor($this, [RoleName::Staff, RoleName::Hr], $hr);
    $this->get(route('hr.appraisals.show', $appraisal))->assertOk();

    appraisalActor($this, [RoleName::Staff], $stranger);
    $this->get(route('hr.appraisals.show', $appraisal))->assertForbidden();
});

it('locks a completed appraisal so it cannot be edited', function () {
    $manager = User::factory()->create()->assignRole(RoleName::Supervisor->value);
    $member = User::factory()->create(['manager_id' => $manager->id])->assignRole(RoleName::Staff->value);
    $hr = appraisalActor($this, [RoleName::Staff, RoleName::Hr]);
    $service = app(AppraisalService::class);
    $cycle = $service->openCycle('2026 annual', 2026, '2026-01-01', '2026-12-31');
    $appraisal = $service->startFor($cycle, $member, $manager);

    appraisalActor($this, [RoleName::Staff], $member);
    $this->post(route('hr.appraisals.self', $appraisal), ['self_review' => 'I did well.'])->assertRedirect();

    appraisalActor($this, [RoleName::Supervisor], $manager);
    $this->post(route('hr.appraisals.manager', $appraisal), ['manager_review' => 'Agreed.'])->assertRedirect();
    $this->post(route('hr.appraisals.lock', $appraisal))->assertRedirect();

    expect($appraisal->refresh()->isLocked())->toBeTrue()
        ->and($appraisal->status)->toBe(Appraisal::STATUS_LOCKED);

    appraisalActor($this, [RoleName::Staff], $member);
    $this->post(route('hr.appraisals.self', $appraisal), ['self_review' => 'Changed mind.'])
        ->assertSessionHasErrors('self_review');

    expect($appraisal->refresh()->self_review)->toBe('I did well.');
});
