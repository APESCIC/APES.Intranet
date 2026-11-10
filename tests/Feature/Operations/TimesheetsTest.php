<?php

use App\Core\Access\RoleName;
use App\Core\Audit\AuditLog;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Modules\Operations\Models\ClockRecord;
use Modules\Operations\Models\Timesheet;
use Modules\Operations\Services\Timesheets;
use Tests\Support\Operations;

uses(RefreshDatabase::class);

beforeEach(function () {
    Operations::seedRoles();
    Mail::fake();
    $this->travelTo(CarbonImmutable::parse('2026-11-20 12:00'));
    $this->member = Operations::member(RoleName::Volunteer);
    $this->manager = Operations::member(RoleName::Supervisor);
});

function clockRecord(User $member, string $in, int $minutes): ClockRecord
{
    $start = CarbonImmutable::parse($in);

    return ClockRecord::query()->create(['user_id' => $member->id, 'clock_in_at' => $start, 'clock_out_at' => $start->addMinutes($minutes)]);
}

function approvedTimesheet(User $member, User $manager, string $weekOf): Timesheet
{
    $service = app(Timesheets::class);
    $timesheet = $service->build($member, CarbonImmutable::parse($weekOf));
    $service->submit($timesheet, $member);
    $service->review($timesheet->refresh(), $manager, true);

    return $timesheet->refresh();
}

it('builds a week from finished clock records only', function () {
    clockRecord($this->member, '2026-11-16 09:00', 240);
    clockRecord($this->member, '2026-11-18 13:00', 90);
    clockRecord($this->member, '2026-11-09 09:00', 600);
    ClockRecord::query()->create(['user_id' => $this->member->id, 'clock_in_at' => '2026-11-19 09:00']);

    $this->actingAs($this->member)->post(route('operations.timesheets.build'), ['week_of' => '2026-11-18'])->assertRedirect();

    $timesheet = Timesheet::query()->firstOrFail();
    expect($timesheet->period_start->toDateString())->toBe('2026-11-16')
        ->and($timesheet->entries()->count())->toBe(2)
        ->and($timesheet->total_minutes)->toBe(330);
});

it('locks a timesheet once a manager approves it', function () {
    clockRecord($this->member, '2026-11-16 09:00', 240);
    $timesheet = approvedTimesheet($this->member, $this->manager, '2026-11-16');

    expect($timesheet->status)->toBe(Timesheet::STATUS_APPROVED)
        ->and($timesheet->isLocked())->toBeTrue()
        ->and(AuditLog::query()->where('event', 'operations.timesheet_approved')->exists())->toBeTrue();

    clockRecord($this->member, '2026-11-17 09:00', 240);
    $this->actingAs($this->member)->post(route('operations.timesheets.build'), ['week_of' => '2026-11-17'])->assertSessionHasErrors('week_of');
    $this->actingAs($this->member)->post(route('operations.timesheets.submit', $timesheet))->assertSessionHasErrors('timesheet');
    $this->actingAs($this->manager)->post(route('operations.timesheets.decide', $timesheet), ['decision' => 'return', 'review_note' => 'Too late'])->assertSessionHasErrors('review_note');

    expect($timesheet->refresh()->total_minutes)->toBe(240)->and($timesheet->status)->toBe(Timesheet::STATUS_APPROVED);
});

it('returns a timesheet with a note so the member can fix and resubmit it', function () {
    clockRecord($this->member, '2026-11-16 09:00', 240);
    $timesheet = app(Timesheets::class)->build($this->member, CarbonImmutable::parse('2026-11-16'));
    $this->actingAs($this->member)->post(route('operations.timesheets.submit', $timesheet))->assertRedirect();

    $this->actingAs($this->manager)->post(route('operations.timesheets.decide', $timesheet), ['decision' => 'return'])->assertSessionHasErrors('review_note');
    $this->actingAs($this->manager)->post(route('operations.timesheets.decide', $timesheet), ['decision' => 'return', 'review_note' => 'Missing Tuesday'])->assertRedirect();

    expect($timesheet->refresh()->status)->toBe(Timesheet::STATUS_RETURNED)->and($timesheet->isLocked())->toBeFalse();

    clockRecord($this->member, '2026-11-17 09:00', 60);
    $this->actingAs($this->member)->post(route('operations.timesheets.submit', $timesheet))->assertRedirect();
    expect($timesheet->refresh()->status)->toBe(Timesheet::STATUS_SUBMITTED)->and($timesheet->total_minutes)->toBe(300);
});

it('shows members only their own timesheets', function () {
    clockRecord($this->member, '2026-11-16 09:00', 240);
    $timesheet = app(Timesheets::class)->build($this->member, CarbonImmutable::parse('2026-11-16'));
    $colleague = Operations::member(RoleName::Volunteer);

    $this->actingAs($this->member)->get(route('operations.timesheets.show', $timesheet))->assertOk();
    $this->actingAs($colleague)->get(route('operations.timesheets.show', $timesheet))->assertForbidden();
    $this->actingAs($colleague)->get(route('operations.timesheets.index'))->assertOk()->assertDontSee('16 Nov 2026');
    $this->actingAs($this->manager)->get(route('operations.timesheets.show', $timesheet))->assertOk();
});

it('exports volunteer hours to CSV with the same totals as the screen', function () {
    $second = Operations::member(RoleName::Volunteer, ['name' => '=Formula Volunteer']);
    $staff = Operations::member(RoleName::Staff);
    clockRecord($this->member, '2026-11-02 09:00', 240);
    clockRecord($this->member, '2026-11-10 09:00', 135);
    clockRecord($second, '2026-11-11 09:00', 50);
    clockRecord($staff, '2026-11-11 09:00', 480);
    approvedTimesheet($this->member, $this->manager, '2026-11-02');
    approvedTimesheet($this->member, $this->manager, '2026-11-10');
    approvedTimesheet($second, $this->manager, '2026-11-11');
    approvedTimesheet($staff, $this->manager, '2026-11-11');
    clockRecord($this->member, '2026-11-17 09:00', 600);
    app(Timesheets::class)->build($this->member, CarbonImmutable::parse('2026-11-17'));

    $hr = Operations::member(RoleName::Hr);
    $query = ['from' => '2026-11-01', 'to' => '2026-11-30'];

    $page = $this->actingAs($hr)->get(route('operations.timesheets.hours', $query))->assertOk()->getContent();
    preg_match_all('/data-hours>([\d.]+)</', $page, $screen);
    preg_match('/data-total-hours>([\d.]+)</', $page, $screenTotal);

    $csv = $this->actingAs($hr)->get(route('operations.timesheets.hours.csv', $query))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=utf-8')
        ->getContent();
    $rows = array_map('str_getcsv', array_filter(explode("\n", trim($csv))));
    $body = array_slice($rows, 1, -1);
    $total = end($rows);

    expect($screen[1])->toBe(['0.83', '6.25'])
        ->and(array_column($body, 5))->toBe($screen[1])
        ->and($total[5])->toBe($screenTotal[1])
        ->and($total[5])->toBe('7.08')
        ->and($body[0][0])->toBe("'=Formula Volunteer");

    $this->actingAs($this->member)->get(route('operations.timesheets.hours', $query))->assertForbidden();
});
