<?php

use App\Core\Access\RoleName;
use App\Core\Audit\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Modules\Helpdesk\Models\Ticket;
use Tests\Support\Helpdesk;

uses(RefreshDatabase::class);

beforeEach(function () {
    Helpdesk::seed();
    Mail::fake();
    $this->member = Helpdesk::member(RoleName::Volunteer);
    $this->admin = Helpdesk::member(RoleName::Admin);
    $this->manager = Helpdesk::member(RoleName::Management);
});

it('routes lost device and 2FA reset tickets to the IT queue', function () {
    $this->actingAs($this->member)->post(route('helpdesk.tickets.store'), [
        'category_id' => Helpdesk::category('lost-device-or-2fa-reset')->id,
        'subject' => 'Lost my phone',
        'body' => '<p>I cannot sign in</p>',
    ])->assertRedirect();

    $ticket = Ticket::query()->firstOrFail();
    expect($ticket->queue)->toBe(Ticket::QUEUE_IT)
        ->and($ticket->priority)->toBe('high')
        ->and($ticket->requiresIdentityCheck())->toBeTrue();
});

it('cannot be resolved or closed until the identity check is recorded', function (string $status) {
    $ticket = Helpdesk::ticket($this->member, 'lost-device-or-2fa-reset');

    $this->actingAs($this->admin)->patch(route('helpdesk.tickets.status', $ticket), ['status' => $status])
        ->assertSessionHasErrors('status');
    expect($ticket->refresh()->status)->toBe(Ticket::STATUS_OPEN);

    $this->actingAs($this->admin)->patch(route('helpdesk.tickets.status', $ticket), ['status' => Ticket::STATUS_WAITING])
        ->assertSessionHasNoErrors();

    $this->actingAs($this->admin)->post(route('helpdesk.tickets.identity-check', $ticket), ['method' => 'callback'])->assertRedirect();
    $this->actingAs($this->admin)->patch(route('helpdesk.tickets.status', $ticket), ['status' => $status])
        ->assertSessionHasNoErrors();

    expect($ticket->refresh()->status)->toBe($status);
})->with([Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED]);

it('records the identity check and the 2FA reset in the audit log', function () {
    $ticket = Helpdesk::ticket($this->member, 'lost-device-or-2fa-reset');

    $this->actingAs($this->admin)->post(route('helpdesk.tickets.two-factor-reset', $ticket))->assertSessionHasErrors('reset');
    expect($ticket->refresh()->two_factor_reset_at)->toBeNull();

    $this->actingAs($this->admin)->post(route('helpdesk.tickets.identity-check', $ticket), ['method' => 'manager_video', 'note' => 'Manager confirmed on Teams'])->assertRedirect();
    $this->actingAs($this->admin)->post(route('helpdesk.tickets.two-factor-reset', $ticket))->assertRedirect();

    $ticket->refresh();
    expect($ticket->two_factor_reset_at)->not->toBeNull()
        ->and($ticket->two_factor_reset_by)->toBe($this->admin->id)
        ->and(AuditLog::query()->where('event', 'helpdesk.identity_checked')->where('subject_id', $ticket->id)->value('context'))
        ->toEqual(['method' => 'manager_video', 'member_id' => $this->member->id]);

    $reset = AuditLog::query()->where('event', 'helpdesk.two_factor_reset')->where('subject_id', $ticket->id)->firstOrFail();
    expect($reset->actor_id)->toBe($this->admin->id)
        ->and($reset->context)->toEqual(['member_id' => $this->member->id])
        ->and($ticket->messages()->where('is_private', true)->where('body', 'like', '%reset 2FA%')->exists())->toBeTrue();

    $this->actingAs($this->admin)->get(route('helpdesk.tickets.show', $ticket))
        ->assertSee('Their manager checked who they are on a video call')
        ->assertSee('2FA reset');
});

it('keeps the identity check away from the requester', function () {
    $ticket = Helpdesk::ticket($this->member, 'lost-device-or-2fa-reset');
    $this->actingAs($this->admin)->post(route('helpdesk.tickets.identity-check', $ticket), ['method' => 'callback', 'note' => 'IDNOTE']);

    $this->actingAs($this->member)->get(route('helpdesk.tickets.show', $ticket))
        ->assertOk()
        ->assertDontSee('data-identity-check', false)
        ->assertDontSee('IDNOTE')
        ->assertDontSee('identity check');
    $this->actingAs($this->member)->post(route('helpdesk.tickets.identity-check', $ticket), ['method' => 'callback'])->assertForbidden();
    $this->actingAs($this->member)->post(route('helpdesk.tickets.two-factor-reset', $ticket))->assertForbidden();
});

it('lets only the IT queue check identity and reset 2FA', function () {
    $ticket = Helpdesk::ticket($this->member, 'lost-device-or-2fa-reset');
    $ordinary = Helpdesk::ticket($this->member, 'devices');

    $this->actingAs($this->manager)->post(route('helpdesk.tickets.identity-check', $ticket), ['method' => 'callback'])->assertForbidden();
    $this->actingAs($this->admin)->post(route('helpdesk.tickets.identity-check', $ordinary), ['method' => 'callback'])->assertForbidden();

    $ticket->forceFill(['queue' => Ticket::QUEUE_MANAGEMENT])->save();
    $this->actingAs($this->admin)->post(route('helpdesk.tickets.identity-check', $ticket), ['method' => 'callback'])->assertForbidden();
});

it('only merges an unchecked 2FA ticket into another one that needs a check', function () {
    $reset = Helpdesk::ticket($this->member, 'lost-device-or-2fa-reset');
    $printer = Helpdesk::ticket($this->member, 'devices');
    $otherReset = Helpdesk::ticket($this->member, 'lost-device-or-2fa-reset');

    $this->actingAs($this->admin)->post(route('helpdesk.tickets.merge', $reset), ['target' => $printer->reference()])
        ->assertSessionHasErrors('target');
    expect($reset->refresh()->isClosed())->toBeFalse();

    $this->actingAs($this->admin)->post(route('helpdesk.tickets.merge', $reset), ['target' => $otherReset->reference()])
        ->assertSessionHasNoErrors();
    expect($reset->refresh()->merged_into_id)->toBe($otherReset->id);
});

it('lets admins choose which topics need an identity check', function () {
    $topic = Helpdesk::category('sign-in');

    $this->actingAs($this->admin)->put(route('admin.helpdesk.categories.update', $topic), [
        'name' => $topic->name, 'queue' => 'it', 'default_priority' => 'high', 'active' => '1', 'requires_identity_check' => '1',
    ])->assertRedirect();

    expect($topic->refresh()->requires_identity_check)->toBeTrue();
});
