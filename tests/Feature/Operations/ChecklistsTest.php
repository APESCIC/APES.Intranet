<?php

use App\Core\Access\RoleName;
use App\Core\Audit\AuditLog;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Modules\Operations\Mail\OperationsNoticeMail;
use Modules\Operations\Models\ChecklistRun;
use Modules\Operations\Models\ChecklistTemplate;
use Modules\Operations\Models\RotaWeek;
use Modules\Operations\Models\ShiftTemplate;
use Tests\Support\Operations;

uses(RefreshDatabase::class);

beforeEach(function () {
    Operations::seedRoles();
    Mail::fake();
    Storage::fake('local');
    $this->travelTo(CarbonImmutable::parse('2026-11-16 07:00'));
    $this->site = Operations::site();
    $this->supervisor = Operations::member(RoleName::Supervisor, ['email' => 'manager@example.test']);
    $this->member = Operations::member();
    $this->morning = ShiftTemplate::query()->create(['site_id' => $this->site->id, 'name' => 'Morning', 'role' => 'Animal care', 'weekday' => 1, 'starts_at' => '08:00', 'ends_at' => '12:00', 'active' => true]);
    $this->afternoon = ShiftTemplate::query()->create(['site_id' => $this->site->id, 'name' => 'Afternoon', 'role' => 'Animal care', 'weekday' => 1, 'starts_at' => '13:00', 'ends_at' => '17:00', 'active' => true]);
    $this->template = ChecklistTemplate::query()->create([
        'site_id' => $this->site->id, 'shift_template_id' => $this->morning->id, 'name' => 'Morning reptile checks',
        'due_time' => '10:00', 'items' => ['Basking lamps on', 'Water bowls refilled'], 'active' => true,
    ]);
});

function publishTemplatedWeek(object $test): RotaWeek
{
    $week = Operations::week($test->site, RotaWeek::STATUS_DRAFT, CarbonImmutable::parse('2026-11-16'));
    foreach ([$test->morning, $test->afternoon] as $template) {
        [$start, $end] = $template->timesInWeek(CarbonImmutable::parse('2026-11-16'));
        $week->shifts()->create(['site_id' => $test->site->id, 'shift_template_id' => $template->id, 'user_id' => $test->member->id, 'role' => $template->name, 'starts_at' => $start, 'ends_at' => $end]);
    }

    $test->actingAs($test->supervisor)->post(route('operations.rota.weeks.publish', $week))->assertRedirect();

    return $week;
}

it('puts the checklist on the right shift when the rota is published', function () {
    $week = publishTemplatedWeek($this);
    $morningShift = $week->shifts()->where('shift_template_id', $this->morning->id)->first();

    $runs = ChecklistRun::query()->with('items')->get();
    expect($runs)->toHaveCount(1)
        ->and($runs->first()->shift_id)->toBe($morningShift->id)
        ->and($runs->first()->due_at->toDateTimeString())->toBe('2026-11-16 10:00:00')
        ->and($runs->first()->items->pluck('label')->all())->toBe(['Basking lamps on', 'Water bowls refilled']);

    $this->actingAs($this->supervisor)->post(route('operations.rota.weeks.publish', $week));
    expect(ChecklistRun::query()->count())->toBe(1);

    $this->actingAs($this->member)->get(route('operations.rota.index'))
        ->assertOk()
        ->assertSee('data-shift-checklist="'.$runs->first()->id.'"', false);
});

it('alerts the manager once when a checklist is missed by its due time', function () {
    publishTemplatedWeek($this);

    $this->travelTo(CarbonImmutable::parse('2026-11-16 09:59'));
    $this->artisan('operations:alert-missed-checklists')->assertSuccessful();
    Mail::assertNotSent(OperationsNoticeMail::class, fn ($mail) => str_contains($mail->subjectLine, 'Missed checklist'));

    $this->travelTo(CarbonImmutable::parse('2026-11-16 10:01'));
    $this->artisan('operations:alert-missed-checklists')->assertSuccessful();
    $this->artisan('operations:alert-missed-checklists')->assertSuccessful();

    Mail::assertSent(OperationsNoticeMail::class, fn ($mail) => $mail->hasTo('manager@example.test') && str_contains($mail->subjectLine, 'Missed checklist'));
    expect(Mail::sent(OperationsNoticeMail::class, fn ($mail) => str_contains($mail->subjectLine, 'Missed checklist')))->toHaveCount(1)
        ->and(AuditLog::query()->where('event', 'operations.checklist_missed')->count())->toBe(1);
});

it('completes a checklist with a photo and note, then keeps it read-only', function () {
    publishTemplatedWeek($this);
    $run = ChecklistRun::query()->with('items')->firstOrFail();
    [$lamps, $water] = $run->items->all();

    $this->actingAs($this->member)->put(route('operations.checklists.runs.update', $run), [
        'action' => 'complete',
        'items' => [$lamps->id => ['done' => 1]],
    ])->assertSessionHasErrors('items');
    expect($run->refresh()->isComplete())->toBeFalse();

    $this->actingAs($this->member)->put(route('operations.checklists.runs.update', $run), [
        'action' => 'complete',
        'items' => [$lamps->id => ['done' => 1], $water->id => ['note' => 'Tap off, reported to the office']],
        'photos' => [$lamps->id => UploadedFile::fake()->image('lamp.jpg')],
    ])->assertRedirect(route('operations.checklists.runs.show', $run));

    expect($run->refresh()->isComplete())->toBeTrue()
        ->and($lamps->refresh()->photo_path)->not->toBeNull()
        ->and($water->refresh()->note)->toBe('Tap off, reported to the office');
    Storage::disk('local')->assertExists($lamps->photo_path);

    $this->actingAs($this->member)->put(route('operations.checklists.runs.update', $run), [
        'action' => 'save',
        'items' => [$water->id => ['done' => 1]],
    ])->assertForbidden();
    expect($water->refresh()->done)->toBeFalse();

    $this->actingAs($this->member)->get(route('operations.checklists.runs.show', $run))
        ->assertOk()
        ->assertSee('data-read-only', false)
        ->assertDontSee('Complete checklist');
});

it('keeps checklists to the member on the shift and the team managers', function () {
    publishTemplatedWeek($this);
    $run = ChecklistRun::query()->firstOrFail();

    $this->actingAs($this->supervisor)->get(route('operations.checklists.runs.show', $run))->assertOk();
    $this->actingAs(Operations::member())->get(route('operations.checklists.runs.show', $run))->assertForbidden();
    $this->actingAs(Operations::member(RoleName::Supervisor, ['team' => 'Office']))->get(route('operations.checklists.runs.show', $run))->assertForbidden();
});

it('lets a supervisor add a checklist that joins already published shifts', function () {
    publishTemplatedWeek($this);

    $this->actingAs($this->supervisor)->post(route('operations.checklists.templates.store'), [
        'site_id' => $this->site->id,
        'name' => 'Lock-up',
        'due_time' => '16:45',
        'items' => "Doors locked\nAlarm set",
    ])->assertSessionHas('status', fn ($status) => str_contains($status, '2 published shift'));

    $this->actingAs($this->member)->post(route('operations.checklists.templates.store'), [
        'site_id' => $this->site->id, 'name' => 'Nope', 'due_time' => '10:00', 'items' => 'X',
    ])->assertForbidden();
});
