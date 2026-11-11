<?php

namespace Modules\Helpdesk\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Modules\Helpdesk\Models\Ticket;

/**
 * Counts for tickets raised in a period. Each count uses the same query
 * as the queue list filter it links to, so the two always agree.
 */
final class Reports
{
    /**
     * Report figure => the queue list view that lists the same tickets.
     */
    public const VIEWS = ['raised' => 'all', 'open' => 'active', 'closed' => 'done', 'missed' => 'missed'];

    public const LABELS = ['raised' => 'raised', 'open' => 'still open', 'closed' => 'resolved or closed', 'missed' => 'missed an SLA target'];

    /**
     * @param  list<string>  $queues
     * @return array<string, array{raised: int, open: int, closed: int, missed: int, average_first_reply_hours: ?float}>
     */
    public function summary(array $queues, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $rows = [];

        foreach ($queues as $queue) {
            $base = fn (): Builder => Ticket::query()->inQueue($queue)->raisedBetween($from, $to);

            $replied = $base()->whereNotNull('first_responded_at')->get(['created_at', 'first_responded_at']);
            $average = $replied->isEmpty()
                ? null
                : round($replied->avg(fn (Ticket $t) => $t->created_at->diffInMinutes($t->first_responded_at)) / 60, 1);

            $rows[$queue] = [
                'raised' => $base()->count(),
                'open' => $base()->active()->count(),
                'closed' => $base()->finished()->count(),
                'missed' => $base()->breached()->count(),
                'average_first_reply_hours' => $average,
            ];
        }

        return $rows;
    }
}
