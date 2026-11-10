<?php

use App\Core\Access\RoleName;
use App\Core\Audit\AuditLog;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Modules\Operations\Mail\OperationsNoticeMail;
use Modules\Operations\Models\Availability;
use Modules\Operations\Models\RotaWeek;
use Modules\Operations\Models\Shift;
use Modules\Operations\Models\ShiftTemplate;
use Modules\Operations\Services\ClashDetector;
use Tests\Support\Operations;

uses(RefreshDatabase::class);

beforeEach(function () {
    Operations::seedRoles();
    $this->site = Operations::site();
});

it('lets management create a week from the site templates', function () {
    $management = Operations::member(RoleName::Management, ['team' => 'Office']);
    $this->actingAs($management)->post(route('operations.rota.templates.store'), [
        'site_id' => $this->site->id,
        'name' => 'Morning feed',
        'role' => 'Animal care',
        'weekdays' => [1, 3, 5],
        'starts_at' => '08:00',
        'ends_at' => '12:00',
    ])->assertRedirect();
    ShiftTemplate::query()->create(['site_id' => $this->site->id, 'name' => 'Night check', 'role' => 'Night care', 'weekday' => 7, 'starts_at' => '22:00', 'ends_at' => '02:00', 'active' => true]);

    $this->actingAs($management)->post(route('operations.rota.weeks.store'), [
        'site_id' => $this->site->id,
        'week_of' => '2026-11-18',
    ])->assertRedirect();

    $week = RotaWeek::query()->firstOrFail();
    $shifts = $week->shifts()->get();

    expect($week->week_start->toDateString())->toBe('2026-11-16')
        ->and($week->status)->toBe(RotaWeek::STATUS_DRAFT)
        ->and($shifts)->toHaveCount(4)
        ->and($shifts->first()->starts_at->toDateTimeString())->toBe('2026-11-16 08:00:00')
        ->and($shifts->last()->ends_at->toDateTimeString())->toBe('2026-11-23 02:00:00')
        ->and($shifts->every(fn (Shift $s) => $s->isOpen()))->toBeTrue()
        ->and(AuditLog::query()->where('event', 'operations.rota_created')->exists())->toBeTrue();

    $this->actingAs($management)->post(route('operations.rota.weeks.store'), [
        'site_id' => $this->site->id,
        'week_of' => '2026-11-20',
    ])->assertSessionHasErrors('week_of');
});

it('shows members only their own published shifts', function () {
    $member = Operations::member();
    $colleague = Operations::member();
    $published = Operations::week($this->site);
    $draft = Operations::week($this->site, RotaWeek::STATUS_DRAFT, CarbonImmutable::now()->startOfWeek()->addWeek());

    Operations::shift($published, $member, CarbonImmutable::now()->addDay()->setTime(9, 0), role: 'Published feed');
    Operations::shift($draft, $member, CarbonImmutable::now()->addWeek()->setTime(9, 0), role: 'Draft feed');
    Operations::shift($published, $colleague, CarbonImmutable::now()->addDay()->setTime(13, 0), role: 'Colleague clean');

    $this->actingAs($member)->get(route('operations.rota.index'))
        ->assertOk()
        ->assertSee('Published feed')
        ->assertDontSee('Draft feed')
        ->assertDontSee('Colleague clean');

    $this->actingAs($member)->get(route('operations.rota.weeks.show', $draft))->assertForbidden();
});

it('flags a shift that clashes with unavailability', function () {
    $supervisor = Operations::member(RoleName::Supervisor);
    $member = Operations::member();
    $week = Operations::week($this->site, RotaWeek::STATUS_DRAFT);
    $start = CarbonImmutable::now()->startOfWeek()->addDays(2)->setTime(9, 0);
    $shift = Operations::shift($week, null, $start);
    Availability::query()->create(['user_id' => $member->id, 'starts_at' => $start->addHours(2), 'ends_at' => $start->addHours(4), 'note' => 'College']);

    $this->actingAs($supervisor)->patch(route('operations.rota.shifts.update', $shift), ['user_id' => $member->id])
        ->assertSessionHas('status', fn (string $status) => str_contains($status, 'clash'));

    expect(app(ClashDetector::class)->forShift($shift->refresh()))->toHaveCount(1);

    $this->actingAs($supervisor)->get(route('operations.rota.weeks.show', $week))
        ->assertOk()
        ->assertSee('data-clash-count="1"', false)
        ->assertSee('College');
});

it('flags a member booked on two overlapping shifts', function () {
    $member = Operations::member();
    $week = Operations::week($this->site, RotaWeek::STATUS_DRAFT);
    $start = CarbonImmutable::now()->startOfWeek()->addDay()->setTime(9, 0);
    $first = Operations::shift($week, $member, $start);
    Operations::shift($week, $member, $start->addHours(4));
    $later = Operations::shift($week, $member, $start->addHours(13), 2);

    expect(app(ClashDetector::class)->forShift($first))->toHaveCount(1)
        ->and(app(ClashDetector::class)->forShift($later))->toBe([]);
});

it('keeps a supervisor to the rotas of their own team', function () {
    $other = Operations::site('Office', 'Front desk');
    $supervisor = Operations::member(RoleName::Supervisor);
    $ownWeek = Operations::week($this->site, RotaWeek::STATUS_DRAFT);
    $otherWeek = Operations::week($other, RotaWeek::STATUS_DRAFT);

    $this->actingAs($supervisor)->get(route('operations.rota.weeks.show', $ownWeek))->assertOk();
    $this->actingAs($supervisor)->get(route('operations.rota.weeks.show', $otherWeek))->assertForbidden();
    $this->actingAs(Operations::member())->post(route('operations.rota.weeks.store'), ['site_id' => $this->site->id, 'week_of' => now()->toDateString()])->assertForbidden();
});

it('publishes a week and emails each member with shifts', function () {
    Mail::fake();
    $supervisor = Operations::member(RoleName::Supervisor);
    $member = Operations::member(attributes: ['email' => 'keeper@example.test']);
    $week = Operations::week($this->site, RotaWeek::STATUS_DRAFT);
    Operations::shift($week, $member, CarbonImmutable::now()->startOfWeek()->addDay()->setTime(9, 0));
    Operations::shift($week, null, CarbonImmutable::now()->startOfWeek()->addDays(2)->setTime(9, 0));

    $this->actingAs($supervisor)->post(route('operations.rota.weeks.publish', $week))->assertRedirect();

    expect($week->refresh()->isPublished())->toBeTrue()
        ->and(AuditLog::query()->where('event', 'operations.rota_published')->value('context'))->toMatchArray(['shifts' => 2, 'open_shifts' => 1]);
    Mail::assertSent(OperationsNoticeMail::class, 1);
    Mail::assertSent(OperationsNoticeMail::class, fn (OperationsNoticeMail $mail) => $mail->hasTo('keeper@example.test'));
});

it('shows the next published shift on the dashboard', function () {
    $member = Operations::member();
    Operations::shift(Operations::week($this->site), $member, CarbonImmutable::now()->addHours(3), role: 'Snake feed');

    $this->actingAs($member)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Your next shift')
        ->assertSee('Snake feed');
});

it('lets members record and remove their own unavailability', function () {
    $member = Operations::member();
    $this->actingAs($member)->post(route('operations.rota.availability.store'), [
        'starts_at' => now()->addDay()->format('Y-m-d\TH:i'),
        'ends_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
        'note' => 'Holiday',
    ])->assertRedirect();

    $period = Availability::query()->firstOrFail();
    $this->actingAs(Operations::member())->delete(route('operations.rota.availability.destroy', $period))->assertForbidden();
    $this->actingAs($member)->delete(route('operations.rota.availability.destroy', $period))->assertRedirect();

    expect(Availability::query()->count())->toBe(0);
});
