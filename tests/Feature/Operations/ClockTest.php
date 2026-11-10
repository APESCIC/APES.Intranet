<?php

use App\Core\Access\RoleName;
use App\Core\Audit\AuditLog;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Operations\Mail\OperationsNoticeMail;
use Modules\Operations\Models\ClockRecord;
use Modules\Operations\Models\Site;
use Tests\Support\Operations;

uses(RefreshDatabase::class);

beforeEach(function () {
    Operations::seedRoles();
    $this->site = Site::query()->create(['name' => 'Reptile house', 'team' => 'Care', 'latitude' => 53.8008, 'longitude' => -1.5491, 'radius_metres' => 200]);
    $this->member = Operations::member();
    $this->shift = Operations::shift(Operations::week($this->site), $this->member, CarbonImmutable::now()->subMinutes(10));
});

it('clocks in, takes a break and clocks out', function () {
    $this->actingAs($this->member)->post(route('operations.clock.in'), ['target' => 'shift:'.$this->shift->id])->assertRedirect();
    $this->travel(60)->minutes();
    $this->actingAs($this->member)->post(route('operations.clock.break.start'));
    $this->travel(15)->minutes();
    $this->actingAs($this->member)->post(route('operations.clock.break.end'));
    $this->travel(45)->minutes();
    $this->actingAs($this->member)->post(route('operations.clock.out'))->assertRedirect();

    $record = ClockRecord::query()->with('breaks')->firstOrFail();

    expect($record->shift_id)->toBe($this->shift->id)
        ->and($record->site_id)->toBe($this->site->id)
        ->and($record->isOpen())->toBeFalse()
        ->and($record->breaks)->toHaveCount(1)
        ->and($record->workedMinutes())->toBe(105)
        ->and($record->geofence_result)->toBeNull()
        ->and(AuditLog::query()->where('event', 'operations.clocked_out')->value('context'))->toBe(['minutes' => 105]);
});

it('refuses a second clock-in while one is open', function () {
    $this->actingAs($this->member)->post(route('operations.clock.in'), ['target' => 'site:'.$this->site->id]);
    $this->actingAs($this->member)->post(route('operations.clock.in'), ['target' => 'site:'.$this->site->id])->assertSessionHasErrors('clock');

    expect(ClockRecord::query()->count())->toBe(1);
});

it('does not check location while the geofence is off, even if a position is sent', function () {
    $this->actingAs($this->member)->post(route('operations.clock.in'), [
        'target' => 'shift:'.$this->shift->id,
        'latitude' => '53.8008',
        'longitude' => '-1.5491',
    ]);

    expect(ClockRecord::query()->value('geofence_result'))->toBeNull();
});

it('stores only a pass or fail result once the geofence is approved and on, never coordinates', function () {
    config(['operations.geofence.enabled' => true, 'operations.geofence.dpia_approved_at' => '2026-11-01']);

    $this->actingAs($this->member)->post(route('operations.clock.in'), [
        'target' => 'shift:'.$this->shift->id,
        'latitude' => '53.801012',
        'longitude' => '-1.549187',
    ]);

    $record = ClockRecord::query()->firstOrFail();
    expect($record->geofence_result)->toBe('pass');

    $stored = json_encode([
        DB::table('operations_clock_records')->get(),
        DB::table('audit_logs')->get(),
    ]);
    expect($stored)->not->toContain('53.801012')->not->toContain('1.549187');

    $this->actingAs($this->member)->post(route('operations.clock.out'));
    $this->actingAs($this->member)->post(route('operations.clock.in'), ['target' => 'shift:'.$this->shift->id]);
    expect(ClockRecord::query()->latest('id')->value('geofence_result'))->toBe('fail');
});

it('shows the clock page in the installed app with a manifest shortcut', function () {
    $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true);

    expect(collect($manifest['shortcuts'])->pluck('url'))->toContain('/operations/clock');

    $this->actingAs($this->member)->get(route('operations.clock.index'))
        ->assertOk()
        ->assertSee('rel="manifest"', false)
        ->assertSee('data-clock-form', false)
        ->assertSee($this->shift->role)
        ->assertDontSee('data-geofence-notice', false);
});

it('flags a missed clock-out and emails the manager', function () {
    Mail::fake();
    Operations::member(RoleName::Supervisor, ['email' => 'manager@example.test']);
    $this->actingAs($this->member)->post(route('operations.clock.in'), ['target' => 'shift:'.$this->shift->id]);

    $this->artisan('operations:flag-missed-clock-outs')->assertSuccessful();
    expect(ClockRecord::query()->value('missed_clock_out_flagged_at'))->toBeNull();

    $this->travel(10)->hours();
    $this->artisan('operations:flag-missed-clock-outs')->assertSuccessful();
    $this->artisan('operations:flag-missed-clock-outs')->assertSuccessful();

    expect(ClockRecord::query()->value('missed_clock_out_flagged_at'))->not->toBeNull();
    Mail::assertSent(OperationsNoticeMail::class, 1);
    Mail::assertSent(OperationsNoticeMail::class, fn (OperationsNoticeMail $mail) => $mail->hasTo('manager@example.test') && str_contains($mail->subjectLine, 'Missed clock-out'));
});

it('lets the manager record a missed clock-out, but not a colleague', function () {
    $manager = Operations::member(RoleName::Supervisor);
    $this->actingAs($this->member)->post(route('operations.clock.in'), ['target' => 'shift:'.$this->shift->id]);
    $record = ClockRecord::query()->firstOrFail();
    $this->travel(10)->hours();

    $this->actingAs(Operations::member())->post(route('operations.clock.correct', $record), ['clock_out_at' => now()->subHours(2)->format('Y-m-d\TH:i')])->assertForbidden();

    $this->actingAs($manager)->get(route('operations.clock.team'))->assertOk()->assertSee($this->member->name);
    $this->actingAs($manager)->post(route('operations.clock.correct', $record), ['clock_out_at' => now()->subHours(2)->format('Y-m-d\TH:i')])->assertRedirect();

    expect($record->refresh()->isOpen())->toBeFalse()
        ->and($record->corrected_by)->toBe($manager->id)
        ->and(AuditLog::query()->where('event', 'operations.clock_out_corrected')->exists())->toBeTrue();
});
