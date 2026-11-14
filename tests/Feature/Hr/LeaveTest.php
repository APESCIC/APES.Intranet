<?php

use App\Core\Access\RoleName;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Hr\Models\Absence;
use Modules\Hr\Models\LeaveRequest;
use Modules\Hr\Services\LeaveService;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function leaveUser(object $test, array $roles): User
{
    $user = User::factory()->create()->syncRoles(array_map(fn (RoleName $r) => $r->value, $roles));
    $test->actingAs($user)->withSession(['intranet.auth_time' => now()->getTimestamp()]);

    return $user;
}

it('lets a member request leave and updates balance when a manager approves', function () {
    $manager = User::factory()->create()->syncRoles([RoleName::Staff->value, RoleName::Supervisor->value]);
    $member = User::factory()->create(['manager_id' => $manager->id])->assignRole(RoleName::Staff->value);

    $this->actingAs($member)->withSession(['intranet.auth_time' => now()->getTimestamp()]);
    $this->post(route('hr.leave.store'), [
        'starts_on' => '2026-12-01',
        'ends_on' => '2026-12-03',
        'reason' => 'Demo leave',
    ])->assertRedirect();

    $request = LeaveRequest::firstOrFail();
    expect($request->status)->toBe(LeaveRequest::STATUS_PENDING)
        ->and($request->days)->toBe(3.0);

    $this->actingAs($manager)->withSession(['intranet.auth_time' => now()->getTimestamp()]);
    $this->post(route('hr.leave.approve', $request))->assertRedirect();

    expect($request->refresh()->status)->toBe(LeaveRequest::STATUS_APPROVED)
        ->and(app(LeaveService::class)->balanceFor($member, 2026)->used_days)->toBe(3.0);
});

it('refuses approval from an unrelated supervisor', function () {
    $other = User::factory()->create()->syncRoles([RoleName::Staff->value, RoleName::Supervisor->value]);
    $manager = User::factory()->create()->syncRoles([RoleName::Staff->value, RoleName::Supervisor->value]);
    $member = User::factory()->create(['manager_id' => $manager->id])->assignRole(RoleName::Staff->value);

    $request = app(LeaveService::class)->request($member, now()->addMonth()->startOfWeek(), now()->addMonth()->startOfWeek()->addDays(1), null);

    $this->actingAs($other)->withSession(['intranet.auth_time' => now()->getTimestamp()]);
    $this->post(route('hr.leave.approve', $request))->assertForbidden();
});

it('lets HR record an absence', function () {
    $member = User::factory()->create()->assignRole(RoleName::Staff->value);
    leaveUser($this, [RoleName::Staff, RoleName::Hr]);

    $this->post(route('hr.leave.absence'), [
        'user_id' => $member->id,
        'absent_on' => '2026-11-20',
        'reason' => 'Demo sickness',
    ])->assertRedirect();

    expect(Absence::where('user_id', $member->id)->whereDate('absent_on', '2026-11-20')->exists())->toBeTrue();
});
