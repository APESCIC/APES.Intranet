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
});

it('lets a member raise a ticket to the IT queue or the Management queue', function (string $category, string $queue) {
    $this->actingAs($this->member)->post(route('helpdesk.tickets.store'), [
        'category_id' => Helpdesk::category($category)->id,
        'subject' => 'Need a hand',
        'body' => '<p>Please help</p><script>alert(1)</script>',
    ])->assertRedirect();

    $ticket = Ticket::query()->latest('id')->firstOrFail();
    expect($ticket->queue)->toBe($queue)
        ->and($ticket->requester_id)->toBe($this->member->id)
        ->and($ticket->status)->toBe(Ticket::STATUS_OPEN)
        ->and($ticket->priority)->toBe(Helpdesk::category($category)->default_priority)
        ->and($ticket->messages()->value('body'))->toBe('<p>Please help</p>')
        ->and(AuditLog::query()->where('event', 'helpdesk.ticket_raised')->where('subject_id', $ticket->id)->exists())->toBeTrue();

    $this->actingAs($this->member)->get(route('helpdesk.tickets.show', $ticket))
        ->assertOk()
        ->assertSee($ticket->reference())
        ->assertSee(Ticket::QUEUES[$queue].' queue');
})->with([
    'IT' => ['devices', Ticket::QUEUE_IT],
    'Management' => ['rota-and-shifts', Ticket::QUEUE_MANAGEMENT],
]);

it('uses the priority the member chose', function () {
    $this->actingAs($this->member)->post(route('helpdesk.tickets.store'), [
        'category_id' => Helpdesk::category('devices')->id,
        'subject' => 'Laptop on fire',
        'body' => '<p>Smoke</p>',
        'priority' => 'urgent',
    ])->assertRedirect();

    expect(Ticket::query()->value('priority'))->toBe('urgent');
});

it('refuses a retired topic', function () {
    $category = Helpdesk::category('devices');
    $category->update(['active' => false]);

    $this->actingAs($this->member)->post(route('helpdesk.tickets.store'), [
        'category_id' => $category->id, 'subject' => 'x', 'body' => '<p>x</p>',
    ])->assertSessionHasErrors('category_id');
});

it('never shows a member other people\'s tickets', function () {
    $mine = Helpdesk::ticket($this->member, attributes: ['subject' => 'My printer']);
    $colleague = Helpdesk::member();
    $theirs = Helpdesk::ticket($colleague, attributes: ['subject' => 'Their secret request']);

    $this->actingAs($this->member)->get(route('helpdesk.tickets.index'))
        ->assertOk()
        ->assertSee('My printer')
        ->assertDontSee('Their secret request');

    $this->actingAs($this->member)->get(route('helpdesk.tickets.show', $theirs))->assertForbidden();
    $this->actingAs($this->member)->post(route('helpdesk.tickets.reply', $theirs), ['body' => '<p>Hi</p>'])->assertForbidden();
    $this->actingAs($colleague)->get(route('helpdesk.tickets.show', $mine))->assertForbidden();

    $supervisor = Helpdesk::member(RoleName::Supervisor);
    $this->actingAs($supervisor)->get(route('helpdesk.tickets.show', $mine))->assertForbidden();
});

it('reopens a waiting ticket when the requester replies, and refuses replies once closed', function () {
    $ticket = Helpdesk::ticket($this->member, attributes: ['status' => Ticket::STATUS_WAITING]);

    $this->actingAs($this->member)->post(route('helpdesk.tickets.reply', $ticket), ['body' => '<p>Still broken</p>'])->assertRedirect();
    expect($ticket->refresh()->status)->toBe(Ticket::STATUS_OPEN)
        ->and($ticket->messages()->count())->toBe(2);

    $ticket->update(['status' => Ticket::STATUS_CLOSED]);
    $this->actingAs($this->member)->post(route('helpdesk.tickets.reply', $ticket), ['body' => '<p>Hello?</p>'])->assertForbidden();
});

it('preselects a topic from the link that opened the form', function () {
    $this->actingAs($this->member)->get(route('helpdesk.tickets.create', ['category' => 'microsoft-365']))
        ->assertOk()
        ->assertSee('value="'.Helpdesk::category('microsoft-365')->id.'" selected', false);
});

it('shows the help desk in the menu', function () {
    $this->actingAs($this->member)->get(route('helpdesk.tickets.index'))
        ->assertOk()
        ->assertSee('data-nav-key="help-desk"', false)
        ->assertDontSee('sg-nav-link--soon" data-nav-key="help-desk"', false);
});
