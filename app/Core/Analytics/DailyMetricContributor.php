<?php

namespace App\Core\Analytics;

use Illuminate\Support\Carbon;

/**
 * Supplies date + metric + count rows for the nightly daily-totals job.
 * Bind with the `intranet.daily-metrics` container tag. Use BelongsToModule
 * so a switched-off module is skipped without calling metricsFor().
 */
interface DailyMetricContributor
{
    /**
     * @return list<array{metric: string, count: int}>
     */
    public function metricsFor(Carbon $day): array;
}
