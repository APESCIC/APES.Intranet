<?php

use App\Core\Access\RoleName;
use App\Core\Audit\AuditLog;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Modules\Helpdesk\Models\Category;
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

it('routes a ticket to the queue its topic belongs to', function () {
    Helpdesk::category('rota-and-shifts')->update(['queue' => Ticket::QUEUE_IT]);

    $this->actingAs($this->member)->post(route('helpdesk.tickets.store'), [
        'category_id' => Helpdesk::category('rota-and-shifts')->id, 'subject' => 'Rota app crashes', 'body' => '<p>x</p>',
    ])->assertRedirect();

    expect(Ticket::query()->value('queue'))->toBe(Ticket::QUEUE_IT);
});

it('moves a ticket to another queue when the topic changes, and drops the assignee', function () {
    $ticket = Helpdesk::ticket($this->member, 'devices', ['assignee_id' => $this->admin->id]);

    $this->actingAs($this->admin)->patch(route('helpdesk.tickets.reclassify', $ticket), [
        'category_id' => Helpdesk::category('buildings-and-equipment')->id, 'priority' => 'normal',
    ])->assertRedirect();

    $ticket->refresh();
    expect($ticket->queue)->toBe(Ticket::QUEUE_MANAGEMENT)
        ->and($ticket->assignee_id)->toBeNull()
        ->and(AuditLog::query()->where('event', 'helpdesk.ticket_reclassified')->value('context'))
        ->toMatchArray(['from' => ['queue' => 'it', 'category' => 'devices', 'priority' => 'normal']]);

    $this->actingAs($this->manager)->get(route('helpdesk.queues.management'))->assertSee($ticket->subject);
});

it('sends someone who no longer works the ticket back to their queue', function () {
    $ticket = Helpdesk::ticket($this->member, 'rota-and-shifts');

    $this->actingAs($this->manager)->patch(route('helpdesk.tickets.reclassify', $ticket), [
        'category_id' => Helpdesk::category('devices')->id, 'priority' => 'high',
    ])->assertRedirect(route('helpdesk.queues.management'));

    expect($ticket->refresh()->queue)->toBe(Ticket::QUEUE_IT);
});

it('sets SLA targets from the priority', function (string $priority, int $respond, int $resolve) {
    $this->travelTo(CarbonImmutable::parse('2026-11-16 09:00'));
    $ticket = Helpdesk::ticket($this->member, attributes: ['priority' => $priority]);

    expect($ticket->response_due_at->toDateTimeString())->toBe(CarbonImmutable::parse('2026-11-16 09:00')->addHours($respond)->toDateTimeString())
        ->and($ticket->resolve_due_at->toDateTimeString())->toBe(CarbonImmutable::parse('2026-11-16 09:00')->addHours($resolve)->toDateTimeString());
})->with([
    'urgent' => ['urgent', 4, 24],
    'high' => ['high', 24, 72],
    'normal' => ['normal', 48, 120],
    'low' => ['low', 120, 240],
]);

it('flags a breach on the queue view once a target passes', function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-16 09:00'));
    $late = Helpdesk::ticket($this->member, attributes: ['priority' => 'urgent', 'subject' => 'Server room alarm']);
    $fine = Helpdesk::ticket($this->member, attributes: ['priority' => 'low', 'subject' => 'New mouse please']);

    $this->actingAs($this->admin)->get(route('helpdesk.queues.it'))->assertDontSee('data-breached', false);

    $this->travelTo(CarbonImmutable::parse('2026-11-16 13:30'));
    $response = $this->actingAs($this->admin)->get(route('helpdesk.queues.it'))->assertOk();
    expect(substr_count($response->getContent(), 'data-breached'))->toBe(1);
    expect($late->refresh()->isBreached())->toBeTrue()->and($fine->refresh()->isBreached())->toBeFalse();

    $this->actingAs($this->admin)->get(route('helpdesk.queues.it', ['view' => 'breached']))
        ->assertSee('Server room alarm')
        ->assertDontSee('New mouse please');
});

it('stops the reply clock at the first reply from the queue, not from the requester', function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-16 09:00'));
    $ticket = Helpdesk::ticket($this->member, attributes: ['priority' => 'urgent']);

    $this->travelTo(CarbonImmutable::parse('2026-11-16 10:00'));
    $this->actingAs($this->member)->post(route('helpdesk.tickets.reply', $ticket), ['body' => '<p>Any news?</p>']);
    expect($ticket->refresh()->first_responded_at)->toBeNull();

    $this->actingAs($this->admin)->post(route('helpdesk.tickets.reply', $ticket), ['body' => '<p>On it</p>']);
    expect($ticket->refresh()->first_responded_at->toDateTimeString())->toBe('2026-11-16 10:00:00');

    $this->travelTo(CarbonImmutable::parse('2026-11-16 14:00'));
    expect($ticket->refresh()->responseBreached())->toBeFalse()
        ->and(Ticket::query()->breached()->count())->toBe(0);

    $this->travelTo(CarbonImmutable::parse('2026-11-17 10:00'));
    expect($ticket->refresh()->resolveBreached())->toBeTrue()
        ->and(Ticket::query()->breached()->count())->toBe(1);
});

it('resets the targets when the priority changes', function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-16 09:00'));
    $ticket = Helpdesk::ticket($this->member, attributes: ['priority' => 'low']);

    $this->travelTo(CarbonImmutable::parse('2026-11-16 11:00'));
    $this->actingAs($this->admin)->patch(route('helpdesk.tickets.reclassify', $ticket), [
        'category_id' => $ticket->category_id, 'priority' => 'urgent',
    ])->assertRedirect();

    expect($ticket->refresh()->response_due_at->toDateTimeString())->toBe('2026-11-16 13:00:00');
});

it('warns queue members on the dashboard about breaches', function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-16 09:00'));
    Helpdesk::ticket($this->member, attributes: ['priority' => 'urgent']);
    Helpdesk::ticket($this->member);
    $this->travelTo(CarbonImmutable::parse('2026-11-16 18:00'));

    $this->actingAs($this->admin)->get(route('dashboard'))
        ->assertSee('IT queue')
        ->assertSee('2 open tickets, 1 past SLA.');
    $this->actingAs($this->member)->get(route('dashboard'))
        ->assertSee('Your help desk tickets')
        ->assertSee('2 tickets are open.')
        ->assertDontSee('past SLA');
});

it('lets only admins set up topics', function () {
    $this->actingAs($this->admin)->post(route('admin.helpdesk.categories.store'), [
        'name' => 'Website problem', 'queue' => 'it', 'default_priority' => 'high', 'active' => '1',
    ])->assertRedirect();

    $topic = Category::query()->where('slug', 'website-problem')->firstOrFail();
    expect($topic->queue)->toBe('it')->and($topic->active)->toBeTrue();

    $this->actingAs($this->admin)->put(route('admin.helpdesk.categories.update', $topic), [
        'name' => 'Website problem', 'queue' => 'management', 'default_priority' => 'low',
    ])->assertRedirect();
    expect($topic->refresh()->queue)->toBe('management')->and($topic->active)->toBeFalse()
        ->and(AuditLog::query()->whereIn('event', ['helpdesk.category_created', 'helpdesk.category_updated'])->count())->toBe(2);

    $this->actingAs($this->admin)->get(route('admin.helpdesk.categories.index'))->assertOk()->assertSee('Website problem');
    $this->actingAs($this->manager)->get(route('admin.helpdesk.categories.index'))->assertForbidden();
    $this->actingAs($this->manager)->post(route('admin.helpdesk.categories.store'), ['name' => 'x', 'queue' => 'it', 'default_priority' => 'low'])->assertForbidden();
});
