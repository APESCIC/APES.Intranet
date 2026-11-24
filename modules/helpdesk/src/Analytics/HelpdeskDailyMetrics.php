<?php

namespace Modules\Helpdesk\Analytics;

use App\Core\Analytics\DailyMetricContributor;
use App\Core\Modules\BelongsToModule;
use Illuminate\Support\Carbon;
use Modules\Helpdesk\Models\Ticket;

final class HelpdeskDailyMetrics implements BelongsToModule, DailyMetricContributor
{
    public function moduleSlug(): string
    {
        return 'helpdesk';
    }

    public function metricsFor(Carbon $day): array
    {
        $rows = [];

        foreach (array_keys(Ticket::STATUSES) as $status) {
            $count = Ticket::query()
                ->where('status', $status)
                ->where('updated_at', '>=', $day)
                ->where('updated_at', '<', $day->copy()->addDay())
                ->count();

            $rows[] = [
                'metric' => 'helpdesk_tickets_'.$status,
                'count' => $count,
            ];
        }

        $rows[] = [
            'metric' => 'helpdesk_tickets_raised',
            'count' => Ticket::query()
                ->where('created_at', '>=', $day)
                ->where('created_at', '<', $day->copy()->addDay())
                ->count(),
        ];

        return $rows;
    }
}
