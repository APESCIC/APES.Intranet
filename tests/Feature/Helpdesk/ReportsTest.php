<?php

use App\Core\Access\RoleName;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Modules\Helpdesk\Models\Ticket;
use Modules\Helpdesk\Services\Reports;
use Tests\Support\Helpdesk;

uses(RefreshDatabase::class);

beforeEach(function () {
    Helpdesk::seed();
    Mail::fake();
    $this->member = Helpdesk::member(RoleName::Volunteer);
    $this->admin = Helpdesk::member(RoleName::Admin);
    $this->manager = Helpdesk::member(RoleName::Management);
});

/**
 * A mix of queues, statuses, late and on-time tickets, inside and outside 2-8 November.
 */
function helpdeskReportFixtures(object $test): void
{
    $raise = function (string $at, string $category, array $attributes = []) use ($test): Ticket {
        $test->travelTo(CarbonImmutable::parse($at));

        return Helpdesk::ticket($test->member, $category, $attributes);
    };

    $raise('2026-10-30 10:00', 'devices');
    $raise('2026-11-02 00:10', 'devices', ['priority' => 'urgent']);
    $raise('2026-11-03 09:00', 'devices', ['status' => Ticket::STATUS_RESOLVED, 'resolved_at' => '2026-11-03 11:00', 'first_responded_at' => '2026-11-03 10:00']);
    $raise('2026-11-04 09:00', 'devices', ['status' => Ticket::STATUS_CLOSED, 'resolved_at' => '2026-11-20 09:00', 'closed_at' => '2026-11-20 09:00', 'first_responded_at' => '2026-11-04 12:00']);
    $raise('2026-11-05 09:00', 'devices', ['status' => Ticket::STATUS_WAITING, 'first_responded_at' => '2026-11-05 09:30']);
    $raise('2026-11-08 23:50', 'rota-and-shifts', ['priority' => 'low']);
    $raise('2026-11-06 09:00', 'rota-and-shifts', ['status' => Ticket::STATUS_CLOSED, 'resolved_at' => '2026-11-06 10:00', 'closed_at' => '2026-11-06 10:00']);
    $raise('2026-11-09 00:01', 'rota-and-shifts');

    $test->travelTo(CarbonImmutable::parse('2026-11-11 12:00'));
}

it('reports numbers that match the ticket list for the same period', function () {
    helpdeskReportFixtures($this);
    $period = ['from' => '2026-11-02', 'to' => '2026-11-08'];

    $report = $this->actingAs($this->admin)->get(route('helpdesk.reports.index', $period))->assertOk()->getContent();

    $expected = [
        'it' => ['raised' => 4, 'open' => 2, 'closed' => 2, 'missed' => 3],
        'management' => ['raised' => 2, 'open' => 1, 'closed' => 1, 'missed' => 0],
    ];

    foreach ($expected as $queue => $figures) {
        foreach ($figures as $figure => $count) {
            preg_match('/data-count="'.$queue.'-'.$figure.'">(\d+)</', $report, $match);
            expect((int) $match[1])->toBe($count, "{$queue} {$figure} in the report");

            $list = $this->actingAs($this->admin)
                ->get(route('helpdesk.queues.'.$queue, ['view' => Reports::VIEWS[$figure]] + $period))
                ->assertOk()
                ->getContent();
            expect(substr_count($list, 'data-ticket="'))->toBe($count, "{$queue} {$figure} in the ticket list");
        }
    }
});

it('exports the same numbers as a CSV', function () {
    helpdeskReportFixtures($this);

    $response = $this->actingAs($this->admin)->get(route('helpdesk.reports.csv', ['from' => '2026-11-02', 'to' => '2026-11-08']))->assertOk();

    expect($response->headers->get('content-type'))->toContain('text/csv');
    $lines = array_map('str_getcsv', array_filter(explode("\n", trim($response->getContent()))));
    expect($lines[0][0])->toBe('Queue')
        ->and(array_slice($lines[1], 0, 7))->toBe(['IT', '2026-11-02', '2026-11-08', '4', '2', '2', '3'])
        ->and(array_slice($lines[2], 0, 7))->toBe(['Management', '2026-11-02', '2026-11-08', '2', '1', '1', '0']);
});

it('works out the average time to the first reply', function () {
    helpdeskReportFixtures($this);

    $rows = app(Reports::class)->summary(['it'], CarbonImmutable::parse('2026-11-02'), CarbonImmutable::parse('2026-11-08'));

    expect($rows['it']['average_first_reply_hours'])->toBe(1.5);
});

it('only reports on the queues the person works', function () {
    helpdeskReportFixtures($this);

    $this->actingAs($this->manager)->get(route('helpdesk.reports.index', ['from' => '2026-11-02', 'to' => '2026-11-08']))
        ->assertOk()
        ->assertSee('data-count="management-raised"', false)
        ->assertDontSee('data-count="it-raised"', false);

    $this->actingAs($this->member)->get(route('helpdesk.reports.index'))->assertForbidden();
    $this->actingAs($this->member)->get(route('helpdesk.reports.csv'))->assertForbidden();
    $this->actingAs(Helpdesk::member(RoleName::Supervisor))->get(route('helpdesk.reports.index'))->assertForbidden();
});

it('defaults to the last 30 days', function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-30 12:00'));

    $this->actingAs($this->admin)->get(route('helpdesk.reports.index'))
        ->assertOk()
        ->assertSee('Raised 1 Nov 2026 to 30 Nov 2026');
});
