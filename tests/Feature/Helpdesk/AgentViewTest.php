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

it('shows each queue only to the people who work it', function () {
    $it = Helpdesk::ticket($this->member, 'devices', ['subject' => 'Broken laptop']);
    $mgmt = Helpdesk::ticket($this->member, 'rota-and-shifts', ['subject' => 'Shift clash']);

    $this->actingAs($this->admin)->get(route('helpdesk.queues.it'))
        ->assertOk()->assertSee('Broken laptop')->assertDontSee('Shift clash');
    $this->actingAs($this->admin)->get(route('helpdesk.queues.management'))
        ->assertOk()->assertSee('Shift clash');
    $this->actingAs($this->manager)->get(route('helpdesk.queues.management'))
        ->assertOk()->assertSee('Shift clash')->assertDontSee('Broken laptop');

    $this->actingAs($this->manager)->get(route('helpdesk.queues.it'))->assertForbidden();
    $this->actingAs($this->manager)->get(route('helpdesk.tickets.show', $it))->assertForbidden();
    $this->actingAs($this->member)->get(route('helpdesk.queues.it'))->assertForbidden();
    $this->actingAs(Helpdesk::member(RoleName::Supervisor))->get(route('helpdesk.queues.management'))->assertForbidden();
    $this->actingAs($this->manager)->get(route('helpdesk.tickets.show', $mgmt))->assertOk();
});

it('lists the queues in the menu for the people who work them', function () {
    $this->actingAs($this->manager)->get(route('helpdesk.tickets.index'))
        ->assertSee('data-nav-key="helpdesk-management"', false)
        ->assertDontSee('data-nav-key="helpdesk-it"', false);
    $this->actingAs($this->admin)->get(route('helpdesk.tickets.index'))
        ->assertSee('data-nav-key="helpdesk-it"', false)
        ->assertSee('data-nav-key="helpdesk-management"', false);
    $this->actingAs($this->member)->get(route('helpdesk.tickets.index'))
        ->assertDontSee('Help desk queues');
});

it('never shows private notes to the requester', function () {
    $ticket = Helpdesk::ticket($this->member);

    $this->actingAs($this->admin)->post(route('helpdesk.tickets.note', $ticket), ['note' => '<p>Requester sounds annoyed, check the warranty</p>'])->assertRedirect();
    $this->actingAs($this->admin)->post(route('helpdesk.tickets.reply', $ticket), ['body' => '<p>We are ordering a new drum</p>'])->assertRedirect();

    expect($ticket->messages()->where('is_private', true)->count())->toBe(1);

    $this->actingAs($this->member)->get(route('helpdesk.tickets.show', $ticket))
        ->assertOk()
        ->assertSee('We are ordering a new drum')
        ->assertDontSee('check the warranty')
        ->assertDontSee('Private note')
        ->assertDontSee('data-private', false);

    $this->actingAs($this->admin)->get(route('helpdesk.tickets.show', $ticket))
        ->assertSee('check the warranty')
        ->assertSee('Private note');

    $this->actingAs($this->member)->post(route('helpdesk.tickets.note', $ticket), ['note' => '<p>sneaky</p>'])->assertForbidden();
});

it('logs every assignment change', function () {
    $ticket = Helpdesk::ticket($this->member);
    $otherAdmin = Helpdesk::member(RoleName::Admin, ['name' => 'Robin Example']);

    $this->actingAs($this->admin)->patch(route('helpdesk.tickets.assign', $ticket), ['assignee_id' => $this->admin->id])->assertRedirect();
    $this->actingAs($this->admin)->patch(route('helpdesk.tickets.assign', $ticket), ['assignee_id' => $otherAdmin->id])->assertRedirect();
    $this->actingAs($this->admin)->patch(route('helpdesk.tickets.assign', $ticket), ['assignee_id' => ''])->assertRedirect();

    $log = AuditLog::query()->where('event', 'helpdesk.ticket_assigned')->where('subject_id', $ticket->id)->orderBy('id')->get();
    expect($ticket->refresh()->assignee_id)->toBeNull()
        ->and($log)->toHaveCount(3)
        ->and($log[0]->context)->toEqual(['from' => null, 'to' => $this->admin->id])
        ->and($log[1]->context)->toEqual(['from' => $this->admin->id, 'to' => $otherAdmin->id])
        ->and($log[2]->context)->toEqual(['from' => $otherAdmin->id, 'to' => null])
        ->and($ticket->messages()->where('is_private', true)->where('body', 'like', '%assigned this ticket to Robin Example%')->exists())->toBeTrue();
});

it('only assigns people who work the queue', function () {
    $ticket = Helpdesk::ticket($this->member);

    $this->actingAs($this->admin)->patch(route('helpdesk.tickets.assign', $ticket), ['assignee_id' => $this->manager->id])
        ->assertSessionHasErrors('assignee_id');
    $this->actingAs($this->manager)->patch(route('helpdesk.tickets.assign', $ticket), ['assignee_id' => $this->manager->id])
        ->assertForbidden();

    expect($ticket->refresh()->assignee_id)->toBeNull();
});

it('records when a ticket is resolved and closed', function () {
    $ticket = Helpdesk::ticket($this->member);

    $this->actingAs($this->admin)->patch(route('helpdesk.tickets.status', $ticket), ['status' => 'resolved'])->assertRedirect();
    expect($ticket->refresh()->resolved_at)->not->toBeNull()->and($ticket->closed_at)->toBeNull();

    $this->actingAs($this->admin)->patch(route('helpdesk.tickets.status', $ticket), ['status' => 'closed'])->assertRedirect();
    expect($ticket->refresh()->closed_at)->not->toBeNull()
        ->and(AuditLog::query()->where('event', 'helpdesk.ticket_status_changed')->count())->toBe(2);

    $this->actingAs($this->member)->patch(route('helpdesk.tickets.status', $ticket), ['status' => 'open'])->assertForbidden();
});

it('merges a duplicate into the ticket that carries on', function () {
    $original = Helpdesk::ticket($this->member, attributes: ['subject' => 'Printer jams']);
    $duplicate = Helpdesk::ticket($this->member, attributes: ['subject' => 'Printer still jams']);

    $this->actingAs($this->admin)->post(route('helpdesk.tickets.merge', $duplicate), ['target' => $original->reference()])
        ->assertRedirect(route('helpdesk.tickets.show', $original));

    expect($duplicate->refresh()->status)->toBe(Ticket::STATUS_CLOSED)
        ->and($duplicate->merged_into_id)->toBe($original->id)
        ->and(AuditLog::query()->where('event', 'helpdesk.ticket_merged')->where('subject_id', $duplicate->id)->exists())->toBeTrue();

    $this->actingAs($this->member)->get(route('helpdesk.tickets.show', $duplicate))
        ->assertSee('merged with '.$original->reference());
    $this->actingAs($this->admin)->get(route('helpdesk.tickets.show', $original))
        ->assertSee('merged '.$duplicate->reference());
});

it('refuses to merge into itself, a closed ticket or a queue the person does not work', function () {
    $ticket = Helpdesk::ticket($this->member);
    $closed = Helpdesk::ticket($this->member, attributes: ['status' => Ticket::STATUS_CLOSED]);
    $mgmt = Helpdesk::ticket($this->member, 'rota-and-shifts');
    $it = Helpdesk::ticket($this->member);

    foreach ([$ticket->reference(), $closed->reference(), 'HD-9999'] as $target) {
        $this->actingAs($this->admin)->post(route('helpdesk.tickets.merge', $ticket), ['target' => $target])->assertSessionHasErrors('target');
    }
    $this->actingAs($this->manager)->post(route('helpdesk.tickets.merge', $mgmt), ['target' => $it->reference()])->assertSessionHasErrors('target');

    expect($ticket->refresh()->merged_into_id)->toBeNull()->and($mgmt->refresh()->merged_into_id)->toBeNull();
});
