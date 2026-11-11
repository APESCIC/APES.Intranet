<?php

use App\Core\Access\RoleName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Modules\Helpdesk\Mail\HelpdeskMail;
use Modules\Helpdesk\Models\Ticket;
use Tests\Support\Helpdesk;

uses(RefreshDatabase::class);

beforeEach(function () {
    Helpdesk::seed();
    Mail::fake();
    $this->member = Helpdesk::member(RoleName::Volunteer, ['email' => 'requester@example.test']);
    $this->admin = Helpdesk::member(RoleName::Admin, ['email' => 'it-admin@example.test']);
    $this->manager = Helpdesk::member(RoleName::Management, ['email' => 'manager@example.test']);
});

function helpdeskMailText(HelpdeskMail $mail): string
{
    return $mail->subjectLine."\n".$mail->render();
}

it('emails the requester and the queue when a ticket is raised', function () {
    $this->actingAs($this->member)->post(route('helpdesk.tickets.store'), [
        'category_id' => Helpdesk::category('devices')->id, 'subject' => 'Printer jams', 'body' => '<p>x</p>',
    ]);
    $ticket = Ticket::query()->firstOrFail();

    Mail::assertSent(HelpdeskMail::class, fn (HelpdeskMail $m) => $m->hasTo('requester@example.test') && str_contains($m->subjectLine, $ticket->reference()));
    Mail::assertSent(HelpdeskMail::class, fn (HelpdeskMail $m) => $m->hasTo('it-admin@example.test') && str_contains($m->subjectLine, 'New ticket'));
    Mail::assertNotSent(HelpdeskMail::class, fn (HelpdeskMail $m) => $m->hasTo('manager@example.test'));
});

it('emails the requester on each public reply, and never sends a private note', function () {
    $ticket = Helpdesk::ticket($this->member);

    $this->actingAs($this->admin)->post(route('helpdesk.tickets.reply', $ticket), ['body' => '<p>Have you tried &amp; turning it off?</p>']);
    $this->actingAs($this->admin)->post(route('helpdesk.tickets.note', $ticket), ['note' => '<p>SECRET-NOTE they broke it themselves</p>']);
    $this->actingAs($this->admin)->post(route('helpdesk.tickets.reply', $ticket), ['body' => '<p>A new drum is on its way</p>']);

    $toRequester = Mail::sent(HelpdeskMail::class, fn (HelpdeskMail $m) => $m->hasTo('requester@example.test'));
    expect($toRequester)->toHaveCount(2)
        ->and(helpdeskMailText($toRequester[0]))->toContain('Have you tried & turning it off?')
        ->and(helpdeskMailText($toRequester[1]))->toContain('A new drum is on its way');

    foreach (Mail::sent(HelpdeskMail::class) as $mail) {
        expect(helpdeskMailText($mail))->not->toContain('SECRET-NOTE');
    }
});

it('keeps private notes out of every email, whoever it goes to', function () {
    $ticket = Helpdesk::ticket($this->member);
    $this->actingAs($this->admin)->post(route('helpdesk.tickets.note', $ticket), ['note' => '<p>SECRET-NOTE</p>']);
    $this->actingAs($this->admin)->patch(route('helpdesk.tickets.assign', $ticket), ['assignee_id' => Helpdesk::member(RoleName::Admin)->id]);
    $this->actingAs($this->member)->post(route('helpdesk.tickets.reply', $ticket), ['body' => '<p>Any news?</p>']);
    $this->actingAs($this->admin)->patch(route('helpdesk.tickets.status', $ticket), ['status' => 'resolved']);

    expect(Mail::sent(HelpdeskMail::class))->not->toBeEmpty();
    foreach (Mail::sent(HelpdeskMail::class) as $mail) {
        expect(helpdeskMailText($mail))->not->toContain('SECRET-NOTE');
    }
});

it('emails the assignee, or else the queue, when the requester replies', function () {
    $ticket = Helpdesk::ticket($this->member);
    $this->actingAs($this->member)->post(route('helpdesk.tickets.reply', $ticket), ['body' => '<p>Still jammed</p>']);
    Mail::assertSent(HelpdeskMail::class, fn (HelpdeskMail $m) => $m->hasTo('it-admin@example.test') && str_contains($m->render(), 'Still jammed'));

    $assignee = Helpdesk::member(RoleName::Admin, ['email' => 'assignee@example.test']);
    $ticket->update(['assignee_id' => $assignee->id]);
    Mail::fake();
    $this->actingAs($this->member)->post(route('helpdesk.tickets.reply', $ticket), ['body' => '<p>Hello?</p>']);

    Mail::assertSent(HelpdeskMail::class, fn (HelpdeskMail $m) => $m->hasTo('assignee@example.test'));
    Mail::assertNotSent(HelpdeskMail::class, fn (HelpdeskMail $m) => $m->hasTo('it-admin@example.test'));
    Mail::assertNotSent(HelpdeskMail::class, fn (HelpdeskMail $m) => $m->hasTo('requester@example.test'));
});

it('emails the requester once when the ticket is resolved and then closed', function () {
    $ticket = Helpdesk::ticket($this->member);

    $this->actingAs($this->admin)->patch(route('helpdesk.tickets.status', $ticket), ['status' => 'resolved']);
    $this->actingAs($this->admin)->patch(route('helpdesk.tickets.status', $ticket), ['status' => 'closed']);

    $sent = Mail::sent(HelpdeskMail::class, fn (HelpdeskMail $m) => $m->hasTo('requester@example.test'));
    expect($sent)->toHaveCount(1)->and($sent[0]->subjectLine)->toContain('Resolved');

    $other = Helpdesk::ticket($this->member);
    $this->actingAs($this->admin)->patch(route('helpdesk.tickets.status', $other), ['status' => 'closed']);
    Mail::assertSent(HelpdeskMail::class, fn (HelpdeskMail $m) => $m->hasTo('requester@example.test') && str_contains($m->subjectLine, $other->reference().' · Closed'));
});

it('tells the requester where a merged ticket carries on', function () {
    $original = Helpdesk::ticket($this->member);
    $duplicate = Helpdesk::ticket($this->member);

    $this->actingAs($this->admin)->post(route('helpdesk.tickets.merge', $duplicate), ['target' => $original->reference()]);

    Mail::assertSent(HelpdeskMail::class, fn (HelpdeskMail $m) => $m->hasTo('requester@example.test')
        && str_contains($m->subjectLine, $duplicate->reference())
        && str_contains($m->render(), 'merged with '.$original->reference()));
});

it('keeps the ticket when the email cannot be sent', function () {
    Mail::shouldReceive('to')->andThrow(new RuntimeException('SMTP down'));

    $this->actingAs($this->member)->post(route('helpdesk.tickets.store'), [
        'category_id' => Helpdesk::category('devices')->id, 'subject' => 'Printer jams', 'body' => '<p>x</p>',
    ])->assertRedirect();

    expect(Ticket::query()->count())->toBe(1);
});
