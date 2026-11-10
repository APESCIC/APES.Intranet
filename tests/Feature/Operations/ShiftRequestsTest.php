<?php

use App\Core\Access\RoleName;
use App\Core\Audit\AuditLog;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Modules\Operations\Mail\OperationsNoticeMail;
use Modules\Operations\Models\RotaWeek;
use Modules\Operations\Models\ShiftRequest;
use Tests\Support\Operations;

uses(RefreshDatabase::class);

beforeEach(function () {
    Operations::seedRoles();
    Mail::fake();
    $this->site = Operations::site();
    $this->week = Operations::week($this->site);
    $this->manager = Operations::member(RoleName::Supervisor, ['email' => 'manager@example.test']);
    $this->member = Operations::member(attributes: ['email' => 'member@example.test']);
    $this->colleague = Operations::member(attributes: ['email' => 'colleague@example.test']);
    $this->start = CarbonImmutable::now()->addDays(2)->setTime(9, 0);
});

function sentTo(string $email): bool
{
    return Mail::sent(OperationsNoticeMail::class, fn (OperationsNoticeMail $mail) => $mail->hasTo($email))->isNotEmpty();
}

it('fills an open shift only after a manager approves the claim', function () {
    $shift = Operations::shift($this->week, null, $this->start);

    $this->actingAs($this->member)->post(route('operations.rota.claim', $shift))->assertRedirect(route('operations.rota.requests.index'));

    expect($shift->refresh()->isOpen())->toBeTrue()
        ->and(sentTo('manager@example.test'))->toBeTrue()
        ->and(sentTo('member@example.test'))->toBeTrue();

    $claim = ShiftRequest::query()->firstOrFail();
    $this->actingAs($this->manager)->post(route('operations.rota.requests.decide', $claim), ['decision' => 'approve'])->assertRedirect();

    expect($shift->refresh()->user_id)->toBe($this->member->id)
        ->and($claim->refresh()->status)->toBe(ShiftRequest::STATUS_APPROVED)
        ->and(AuditLog::query()->where('event', 'operations.shift_claim_requested')->exists())->toBeTrue()
        ->and(AuditLog::query()->where('event', 'operations.shift_claim_approved')->exists())->toBeTrue();
    Mail::assertSent(OperationsNoticeMail::class, fn (OperationsNoticeMail $mail) => $mail->hasTo('member@example.test') && str_contains($mail->subjectLine, 'approved'));
});

it('swaps two shifts only after a manager approves, telling both members and the manager', function () {
    $mine = Operations::shift($this->week, $this->member, $this->start);
    $theirs = Operations::shift($this->week, $this->colleague, $this->start->addDay());

    $this->actingAs($this->member)->get(route('operations.rota.swap.create', $mine))->assertOk()->assertSee($this->colleague->name);
    $this->actingAs($this->member)->post(route('operations.rota.swap.store', $mine), ['target_shift_id' => $theirs->id])->assertRedirect();

    expect($mine->refresh()->user_id)->toBe($this->member->id)
        ->and(sentTo('colleague@example.test'))->toBeTrue()
        ->and(sentTo('manager@example.test'))->toBeTrue();

    $swap = ShiftRequest::query()->firstOrFail();
    $this->actingAs($this->manager)->post(route('operations.rota.requests.decide', $swap), ['decision' => 'approve']);

    expect($mine->refresh()->user_id)->toBe($this->colleague->id)
        ->and($theirs->refresh()->user_id)->toBe($this->member->id)
        ->and(AuditLog::query()->where('event', 'operations.shift_swap_approved')->value('context'))
        ->toMatchArray(['shift_user_id' => $this->colleague->id, 'target_user_id' => $this->member->id]);
});

it('leaves the rota alone when a manager declines', function () {
    $shift = Operations::shift($this->week, null, $this->start);
    $this->actingAs($this->member)->post(route('operations.rota.claim', $shift));
    $claim = ShiftRequest::query()->firstOrFail();

    $this->actingAs($this->manager)->post(route('operations.rota.requests.decide', $claim), ['decision' => 'decline', 'decision_note' => 'Already covered']);

    expect($shift->refresh()->isOpen())->toBeTrue()
        ->and($claim->refresh()->status)->toBe(ShiftRequest::STATUS_DECLINED)
        ->and(AuditLog::query()->where('event', 'operations.shift_claim_declined')->exists())->toBeTrue();
});

it('lets only a manager of the site team decide', function () {
    $shift = Operations::shift($this->week, null, $this->start);
    $this->actingAs($this->member)->post(route('operations.rota.claim', $shift));
    $claim = ShiftRequest::query()->firstOrFail();
    $otherSupervisor = Operations::member(RoleName::Supervisor, ['team' => 'Office']);

    $this->actingAs($this->colleague)->post(route('operations.rota.requests.decide', $claim), ['decision' => 'approve'])->assertForbidden();
    $this->actingAs($otherSupervisor)->post(route('operations.rota.requests.decide', $claim), ['decision' => 'approve'])->assertForbidden();

    expect($shift->refresh()->isOpen())->toBeTrue();
});

it('refuses claims on draft rotas and shifts that are not open', function () {
    $draft = Operations::week($this->site, RotaWeek::STATUS_DRAFT, CarbonImmutable::now()->startOfWeek()->addWeeks(2));
    $draftShift = Operations::shift($draft, null, CarbonImmutable::now()->addWeeks(2));
    $taken = Operations::shift($this->week, $this->colleague, $this->start);

    $this->actingAs($this->member)->post(route('operations.rota.claim', $draftShift))->assertSessionHasErrors('claim');
    $this->actingAs($this->member)->post(route('operations.rota.claim', $taken))->assertSessionHasErrors('claim');

    expect(ShiftRequest::query()->count())->toBe(0);
});

it('lists open shifts for members to claim', function () {
    Operations::shift($this->week, null, $this->start, role: 'Weekend cover');

    $this->actingAs($this->member)->get(route('operations.rota.open'))->assertOk()->assertSee('Weekend cover');
});
