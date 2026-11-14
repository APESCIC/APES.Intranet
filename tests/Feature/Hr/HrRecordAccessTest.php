<?php

use App\Core\Access\RoleName;
use App\Core\Audit\AuditLog;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Hr\Models\HrRecord;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/**
 * @param  list<RoleName>  $roles
 */
function hrActor(object $test, array $roles, bool $fresh = true): User
{
    $user = User::factory()->create()->syncRoles(array_map(fn (RoleName $role) => $role->value, $roles));
    $test->actingAs($user)->withSession(['intranet.auth_time' => $fresh ? now()->getTimestamp() : now()->subHour()->getTimestamp()]);

    return $user;
}

it('lets HR open the records index and refuses staff', function (RoleName $role, int $status) {
    hrActor($this, [RoleName::Staff, $role]);

    $this->get(route('hr.records.index'))->assertStatus($status);
})->with([
    'hr' => [RoleName::Hr, 200],
    'management' => [RoleName::Management, 200],
    'admin' => [RoleName::Admin, 200],
    'staff' => [RoleName::Staff, 403],
    'supervisor' => [RoleName::Supervisor, 403],
    'legal' => [RoleName::Legal, 403],
]);

it('audits every HR record view for admin and management', function () {
    $member = User::factory()->create(['name' => 'Pat Example', 'email' => 'pat@example.test'])->assignRole(RoleName::Staff->value);
    $admin = hrActor($this, [RoleName::Staff, RoleName::Admin]);

    $this->get(route('hr.records.show', $member))->assertOk()->assertSee('Pat Example');

    $entry = AuditLog::where('event', 'hr.record.viewed')->firstOrFail();
    expect($entry->actor_id)->toBe($admin->id)
        ->and(HrRecord::where('user_id', $member->id)->exists())->toBeTrue();
});

it('audits HR role views of ordinary HR records', function () {
    $member = User::factory()->create()->assignRole(RoleName::Staff->value);
    $hr = hrActor($this, [RoleName::Staff, RoleName::Hr]);

    $this->get(route('hr.records.show', $member))->assertOk();

    $entry = AuditLog::where('event', 'hr.record.viewed')->firstOrFail();
    expect($entry->actor_id)->toBe($hr->id)
        ->and(HrRecord::where('user_id', $member->id)->exists())->toBeTrue();
});

it('asks for a fresh sign-in before opening an HR record', function () {
    $member = User::factory()->create()->assignRole(RoleName::Staff->value);
    hrActor($this, [RoleName::Staff, RoleName::Hr], fresh: false);

    $this->get(route('hr.records.show', $member))->assertRedirect(route('auth.reauth'));
});

it('lets HR edit a record and audits the change', function () {
    $member = User::factory()->create()->assignRole(RoleName::Staff->value);
    $hr = hrActor($this, [RoleName::Staff, RoleName::Hr]);

    $this->put(route('hr.records.update', $member), [
        'employment_status' => 'Active',
        'contract_type' => 'Permanent',
        'start_date' => '2024-01-15',
        'notes' => 'Fake demo note only.',
    ])->assertRedirect(route('hr.records.show', $member));

    $record = HrRecord::where('user_id', $member->id)->firstOrFail();
    expect($record->employment_status)->toBe('Active')
        ->and($record->contract_type)->toBe('Permanent')
        ->and($record->notes)->toBe('Fake demo note only.');

    $entry = AuditLog::where('event', 'hr.record.updated')->firstOrFail();
    expect($entry->actor_id)->toBe($hr->id)
        ->and($entry->context['member_id'])->toBe($member->id)
        ->and($entry->context['fields'])->toContain('employment_status');
});

it('lets management view but not edit HR records', function () {
    $member = User::factory()->create()->assignRole(RoleName::Staff->value);
    hrActor($this, [RoleName::Staff, RoleName::Management]);

    $this->get(route('hr.records.show', $member))
        ->assertOk()
        ->assertSee('Only HR or admin can edit');

    $this->put(route('hr.records.update', $member), [
        'employment_status' => 'Should not save',
    ])->assertForbidden();
});
