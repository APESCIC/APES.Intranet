<?php

use App\Core\Access\RoleName;
use App\Core\Hr\Contracts\ApprovedLeaveLookup;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Hr\Models\LeaveRequest;
use Modules\Operations\Models\RotaWeek;
use Modules\Operations\Services\ClashDetector;
use Tests\Support\Operations;

uses(RefreshDatabase::class);

beforeEach(function () {
    Operations::seedRoles();
    $this->site = Operations::site();
});

it('blocks assigning a member who has approved leave on that day', function () {
    $supervisor = Operations::member(RoleName::Supervisor);
    $member = Operations::member();
    $week = Operations::week($this->site, RotaWeek::STATUS_DRAFT);
    $start = CarbonImmutable::parse('2026-11-18 09:00:00');
    $shift = Operations::shift($week, null, $start);

    LeaveRequest::query()->create([
        'user_id' => $member->id,
        'starts_on' => '2026-11-18',
        'ends_on' => '2026-11-19',
        'days' => 2,
        'status' => LeaveRequest::STATUS_APPROVED,
        'reason' => 'Demo leave',
    ]);

    $this->actingAs($supervisor)
        ->patch(route('operations.rota.shifts.update', $shift), ['user_id' => $member->id])
        ->assertSessionHasErrors('user_id');

    expect($shift->refresh()->user_id)->toBeNull()
        ->and(app(ApprovedLeaveLookup::class)->overlaps($member->id, $start, $start->addHours(8)))->toBeTrue();

    $shift->user_id = $member->id;
    $messages = app(ClashDetector::class)->forShift($shift);
    expect($messages)->not->toBeEmpty()
        ->and(collect($messages)->contains(fn (string $line) => str_contains($line, 'approved leave')))->toBeTrue();
});

it('allows assigning when leave does not cover the shift day', function () {
    $supervisor = Operations::member(RoleName::Supervisor);
    $member = Operations::member();
    $week = Operations::week($this->site, RotaWeek::STATUS_DRAFT);
    $start = CarbonImmutable::parse('2026-11-20 09:00:00');
    $shift = Operations::shift($week, null, $start);

    LeaveRequest::query()->create([
        'user_id' => $member->id,
        'starts_on' => '2026-11-18',
        'ends_on' => '2026-11-19',
        'days' => 2,
        'status' => LeaveRequest::STATUS_APPROVED,
        'reason' => 'Demo leave',
    ]);

    $this->actingAs($supervisor)
        ->patch(route('operations.rota.shifts.update', $shift), ['user_id' => $member->id])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status');

    expect($shift->refresh()->user_id)->toBe($member->id);
});
