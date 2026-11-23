<?php

use App\Core\Access\RoleName;
use App\Core\Audit\AuditLog;
use App\Core\Notifications\Notifications\IntranetAlert;
use App\Models\User;
use Database\Seeders\HrPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Modules\Hr\Models\IdentityCheck;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(HrPermissionsSeeder::class);
});

/**
 * @param  list<RoleName>  $roles
 */
function checkActor(object $test, array $roles): User
{
    $user = User::factory()->create()->syncRoles(array_map(fn (RoleName $role) => $role->value, $roles));
    $test->actingAs($user)->withSession(['intranet.auth_time' => now()->getTimestamp()]);

    return $user;
}

it('lets HR add a DBS check with a SharePoint link only', function () {
    $member = User::factory()->create()->assignRole(RoleName::Staff->value);
    checkActor($this, [RoleName::Staff, RoleName::Hr]);

    $this->post(route('hr.checks.store', $member), [
        'check_type' => IdentityCheck::TYPE_DBS,
        'reference' => 'DBS-DEMO-1',
        'checked_on' => now()->toDateString(),
        'expires_on' => now()->addYear()->toDateString(),
        'document_url' => 'https://sharepoint.example.test/sites/hr/dbs-demo',
    ])->assertRedirect(route('hr.records.show', $member));

    $check = IdentityCheck::firstOrFail();
    expect($check->user_id)->toBe($member->id)
        ->and($check->reference)->toBe('DBS-DEMO-1')
        ->and($check->document_url)->toContain('sharepoint.example.test');

    expect(AuditLog::where('event', 'hr.special_category.viewed')->exists())->toBeTrue()
        ->and(AuditLog::where('event', 'hr.identity_check.created')->exists())->toBeTrue();
});

it('shows missing and expired checks on the HR report', function () {
    $member = User::factory()->create(['name' => 'Sam Example'])->assignRole(RoleName::Staff->value);
    IdentityCheck::query()->create([
        'user_id' => $member->id,
        'check_type' => IdentityCheck::TYPE_DBS,
        'reference' => 'OLD',
        'expires_on' => now()->subDay()->toDateString(),
    ]);
    checkActor($this, [RoleName::Staff, RoleName::Hr]);

    $this->get(route('hr.checks.report'))
        ->assertOk()
        ->assertSee('Sam Example')
        ->assertSee('Expired')
        ->assertSee('Right to work');
});

it('refuses the checks report to staff without special category access', function () {
    checkActor($this, [RoleName::Staff]);

    $this->get(route('hr.checks.report'))->assertForbidden();
});

it('sends a 30-day expiry reminder to HR viewers', function () {
    Notification::fake();

    $member = User::factory()->create()->assignRole(RoleName::Staff->value);
    $hr = User::factory()->create()->syncRoles([RoleName::Staff->value, RoleName::Hr->value]);

    IdentityCheck::query()->create([
        'user_id' => $member->id,
        'check_type' => IdentityCheck::TYPE_RIGHT_TO_WORK,
        'reference' => 'RTW-1',
        'expires_on' => now()->addDays(30)->toDateString(),
    ]);

    $this->artisan('hr:remind-identity-check-expiry --days=30')
        ->assertSuccessful();

    Notification::assertSentTo($hr, IntranetAlert::class);
    expect(IdentityCheck::first()->expiry_reminded_at)->not->toBeNull();

    $this->artisan('hr:remind-identity-check-expiry --days=30')->assertSuccessful();
    Notification::assertSentToTimes($hr, IntranetAlert::class, 1);
});
