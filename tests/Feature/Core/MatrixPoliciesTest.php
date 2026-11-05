<?php

use App\Core\Access\AccessMatrix;
use App\Core\Access\RoleName;
use App\Core\Access\SensitiveData;
use App\Core\Audit\AuditLog;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Tests\Fakes\FakeTeamRecord;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    Route::middleware(['web', 'auth'])->group(function () {
        Route::get('/_test/hr/{id}', function (SensitiveData $data, int $id) {
            $data->openHrRecord(FakeTeamRecord::make($id));

            return 'HR record '.$id;
        });
        Route::get('/_test/dbs/{id}', function (SensitiveData $data, int $id) {
            $data->openSpecialCategory(FakeTeamRecord::make($id), SensitiveData::DBS);

            return 'DBS '.$id;
        });
        Route::get('/_test/health/{id}', function (SensitiveData $data, int $id) {
            $data->openSpecialCategory(FakeTeamRecord::make($id), SensitiveData::HEALTH);

            return 'Health '.$id;
        });
        Route::get('/_test/legal/{id}', function (SensitiveData $data, int $id) {
            $data->openLegalRecord(FakeTeamRecord::make($id));

            return 'Agreement '.$id;
        });
    });
});

/**
 * @param  list<RoleName>  $roles
 */
function personWith(object $test, array $roles, bool $fresh = true): User
{
    $user = User::factory()->create()->syncRoles(array_map(fn (RoleName $role) => $role->value, $roles));
    $test->actingAs($user)->withSession(['intranet.auth_time' => $fresh ? now()->getTimestamp() : now()->subHour()->getTimestamp()]);

    return $user;
}

it('has a gate for every matrix row', function () {
    foreach (array_keys(AccessMatrix::rows()) as $ability) {
        expect(Gate::has($ability))->toBeTrue("No gate for {$ability}");
    }
});

it('lets a supervisor open their own team but not another team', function (string $ability) {
    $supervisor = personWith($this, [RoleName::Staff, RoleName::Supervisor]);
    $own = FakeTeamRecord::make(1, [$supervisor->id]);
    $other = FakeTeamRecord::make(2, [999]);

    expect(Gate::forUser($supervisor)->allows($ability, $own))->toBeTrue()
        ->and(Gate::forUser($supervisor)->allows($ability, $other))->toBeFalse();
})->with(['viewTeamRota', 'approveTeamSwap', 'approveTeamTimesheet', 'viewTeamChecklist', 'approveStudentHours']);

it('lets management and admin open every team, and nobody else', function (RoleName $role, bool $allowed) {
    $user = personWith($this, [RoleName::Staff, $role]);

    expect(Gate::forUser($user)->allows('viewTeamRota', FakeTeamRecord::make(2, [999])))->toBe($allowed)
        ->and(Gate::forUser($user)->allows('approveTeamTimesheet', FakeTeamRecord::make(2, [999])))->toBe($allowed);
})->with([
    'staff' => [RoleName::Staff, false],
    'hr' => [RoleName::Hr, false],
    'legal' => [RoleName::Legal, false],
    'supervisor' => [RoleName::Supervisor, false],
    'management' => [RoleName::Management, true],
    'admin' => [RoleName::Admin, true],
]);

it('keeps the student area for students and admin', function (RoleName $role, bool $allowed) {
    $user = personWith($this, [$role]);

    expect(Gate::forUser($user)->allows('useStudentArea'))->toBe($allowed);
})->with([
    'student' => [RoleName::Student, true],
    'admin' => [RoleName::Admin, true],
    'staff' => [RoleName::Staff, false],
    'volunteer' => [RoleName::Volunteer, false],
    'supervisor' => [RoleName::Supervisor, false],
]);

it('lets HR, management and admin open HR records, and refuses the rest', function (RoleName $role, int $status) {
    personWith($this, [RoleName::Staff, $role]);

    $this->get('/_test/hr/7')->assertStatus($status);
})->with([
    'hr' => [RoleName::Hr, 200],
    'management' => [RoleName::Management, 200],
    'admin' => [RoleName::Admin, 200],
    'legal' => [RoleName::Legal, 403],
    'supervisor' => [RoleName::Supervisor, 403],
    'staff' => [RoleName::Staff, 403],
]);

it('writes an audit entry for every view of DBS or health data, by any role', function (RoleName $role, string $path, string $category) {
    $user = personWith($this, [RoleName::Staff, $role]);

    $this->get($path)->assertOk();

    $entry = AuditLog::where('event', 'hr.special_category.viewed')->firstOrFail();
    expect($entry->actor_id)->toBe($user->id)
        ->and($entry->subject_id)->toBe(5)
        ->and($entry->context)->toBe(['category' => $category]);
})->with([
    'HR, DBS' => [RoleName::Hr, '/_test/dbs/5', 'dbs'],
    'HR, health' => [RoleName::Hr, '/_test/health/5', 'health'],
    'management, DBS' => [RoleName::Management, '/_test/dbs/5', 'dbs'],
    'admin, health' => [RoleName::Admin, '/_test/health/5', 'health'],
]);

it('refuses DBS and health data to roles without it, with no audit entry', function (RoleName $role) {
    personWith($this, [RoleName::Staff, $role]);

    $this->get('/_test/dbs/5')->assertForbidden();
    expect(AuditLog::where('event', 'hr.special_category.viewed')->exists())->toBeFalse();
})->with([RoleName::Supervisor, RoleName::Legal, RoleName::Staff]);

it('asks admin to sign in again for HR or legal data after the window', function (string $path) {
    personWith($this, [RoleName::Staff, RoleName::Admin], fresh: false);

    $this->get($path)->assertRedirect(route('auth.reauth'));
    expect(session('url.intended'))->toBe(url($path))
        ->and(AuditLog::whereIn('event', ['hr.record.viewed', 'legal.record.viewed'])->exists())->toBeFalse();
})->with(['/_test/hr/7', '/_test/legal/7', '/_test/dbs/7']);

it('audits admin access to HR and legal data', function () {
    $admin = personWith($this, [RoleName::Staff, RoleName::Admin]);

    $this->get('/_test/hr/7')->assertOk();
    $this->get('/_test/legal/8')->assertOk();

    expect(AuditLog::where('event', 'hr.record.viewed')->where('actor_id', $admin->id)->value('subject_id'))->toBe(7)
        ->and(AuditLog::where('event', 'legal.record.viewed')->where('actor_id', $admin->id)->value('subject_id'))->toBe(8);
});

it('lets legal and admin open agreements, and refuses management and HR', function (RoleName $role, int $status) {
    personWith($this, [RoleName::Staff, $role]);

    $this->get('/_test/legal/8')->assertStatus($status);
})->with([
    'legal' => [RoleName::Legal, 200],
    'admin' => [RoleName::Admin, 200],
    'management' => [RoleName::Management, 403],
    'hr' => [RoleName::Hr, 403],
]);
