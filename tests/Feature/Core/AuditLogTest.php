<?php

use App\Core\Access\RoleName;
use App\Core\Audit\AuditLog;
use App\Core\Audit\AuditLogger;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    fakeCloudron($this);
});

it('records a sign-in with the time, person and IP address', function () {
    $this->travelTo(now()->setTime(9, 30));

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7']);
    signInWithCloudron($this, ['intranet.staff']);

    $user = User::where('email', 'alex@example.test')->firstOrFail();
    $entry = AuditLog::where('event', 'auth.signed_in')->firstOrFail();

    expect($entry->actor_id)->toBe($user->id)
        ->and($entry->ip_address)->toBe('203.0.113.7')
        ->and($entry->occurred_at->format('H:i'))->toBe('09:30')
        ->and($entry->context)->toMatchArray(['method' => 'cloudron', 'roles' => ['staff']]);
});

it('records role changes with what was added and removed', function () {
    signInWithCloudron($this, ['intranet.staff', 'intranet.manager']);
    $this->post(route('logout'));
    signInWithCloudron($this, ['intranet.staff', 'intranet.hr']);

    $changes = AuditLog::where('event', 'roles.changed')->orderBy('id')->get();

    expect($changes)->toHaveCount(2)
        ->and($changes[0]->context)->toMatchArray(['added' => ['staff', 'manager'], 'removed' => []])
        ->and($changes[1]->context)->toMatchArray(['added' => ['hr'], 'removed' => ['manager']]);
});

it('does not record a role change when nothing changed', function () {
    signInWithCloudron($this, ['intranet.staff']);
    $this->post(route('logout'));
    signInWithCloudron($this, ['intranet.staff']);

    expect(AuditLog::where('event', 'roles.changed')->count())->toBe(1);
});

it('records denied sign-ins and sign-outs', function () {
    signInWithCloudron($this, ['lms.student']);
    signInWithCloudron($this, ['intranet.staff']);
    $this->post(route('logout'));

    expect(AuditLog::where('event', 'auth.sign_in_denied')->first()?->context)->toBe(['reason' => 'no_intranet_group'])
        ->and(AuditLog::where('event', 'auth.signed_out')->count())->toBe(1);
});

it('shows the audit log to admins only', function (RoleName $role, int $status) {
    $user = User::factory()->create()->assignRole($role->value);

    $this->actingAs($user)->get(route('admin.audit.index'))->assertStatus($status);
})->with([
    'staff' => [RoleName::Staff, 403],
    'manager' => [RoleName::Manager, 403],
    'hr' => [RoleName::Hr, 403],
    'legal' => [RoleName::Legal, 403],
    'admin' => [RoleName::Admin, 200],
]);

it('lists entries read-only and records that an admin viewed it', function () {
    $admin = User::factory()->create(['name' => 'Ada Admin'])->assignRole(RoleName::Admin->value);
    app(AuditLogger::class)->record('auth.signed_in', $admin, ['method' => 'cloudron'], $admin);

    $response = $this->actingAs($admin)->get(route('admin.audit.index'))->assertOk();

    $response->assertSee('auth.signed_in')->assertSee('Ada Admin');
    expect((string) str($response->getContent())->after('<main')->before('</main>'))->not->toContain('<form')
        ->and(AuditLog::where('event', 'admin.audit.viewed')->where('actor_id', $admin->id)->exists())->toBeTrue();
});

it('refuses to change or delete a single entry', function () {
    $entry = app(AuditLogger::class)->record('auth.signed_in');

    expect(fn () => $entry->update(['event' => 'edited']))->toThrow(LogicException::class)
        ->and(fn () => $entry->delete())->toThrow(LogicException::class);
});

it('prunes entries older than the retention period', function () {
    config(['intranet.retention.audit_log_days' => 90]);
    $logger = app(AuditLogger::class);

    $this->travelTo(now()->subDays(91));
    $logger->record('old.entry');
    $this->travelBack();
    $this->travelTo(now()->subDays(89));
    $logger->record('recent.entry');
    $this->travelBack();

    $this->artisan('audit:prune')->assertSuccessful();

    expect(AuditLog::pluck('event')->all())->toBe(['recent.entry']);
});

it('never uses a retention period under 30 days', function () {
    expect(config('intranet.retention.audit_log_days'))->toBeGreaterThanOrEqual(30);
});

it('schedules the prune command daily', function () {
    $this->artisan('schedule:list')->expectsOutputToContain('audit:prune')->assertSuccessful();
});

it('links the privacy notice in the footer and shows it without signing in', function () {
    $this->get(route('login'))->assertSee(route('privacy'), false);
    $this->get(route('privacy'))->assertOk()->assertSee('Privacy notice')->assertSee('audit log');
});
