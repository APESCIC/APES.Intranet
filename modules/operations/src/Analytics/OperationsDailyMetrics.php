<?php

namespace Modules\Operations\Analytics;

use App\Core\Analytics\DailyMetricContributor;
use App\Core\Modules\BelongsToModule;
use Illuminate\Support\Carbon;
use Modules\Operations\Models\ClockRecord;

final class OperationsDailyMetrics implements BelongsToModule, DailyMetricContributor
{
    public function moduleSlug(): string
    {
        return 'operations';
    }

    public function metricsFor(Carbon $day): array
    {
        return [[
            'metric' => 'clock_ins',
            'count' => ClockRecord::query()
                ->where('clock_in_at', '>=', $day)
                ->where('clock_in_at', '<', $day->copy()->addDay())
                ->count(),
        ]];
    }
}
