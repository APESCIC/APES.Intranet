<?php

namespace App\Core\Analytics;

use App\Core\Audit\AuditLog;
use App\Core\Modules\BelongsToModule;
use App\Core\Modules\ModuleRegistry;
use Illuminate\Console\Command;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Carbon;

/**
 * Writes yesterday's operational counts (sign-ins and tagged module metrics)
 * and deletes rows older than 12 months.
 */
class RecordDailyAnalytics extends Command
{
    public const RETENTION_MONTHS = 12;

    public const METRIC_SIGN_INS = 'sign_ins';

    protected $signature = 'analytics:record-daily {--date= : Y-m-d to record (default yesterday)}';

    protected $description = 'Record daily operational totals for the admin dashboard and prune old rows';

    public function handle(Application $app, ModuleRegistry $modules): int
    {
        $day = $this->option('date')
            ? Carbon::parse((string) $this->option('date'))->startOfDay()
            : now()->subDay()->startOfDay();

        $written = 0;
        $written += $this->upsert($day, self::METRIC_SIGN_INS, $this->signInCount($day));

        foreach ($app->tagged('intranet.daily-metrics') as $contributor) {
            if (! $contributor instanceof DailyMetricContributor) {
                continue;
            }

            if ($contributor instanceof BelongsToModule && ! $modules->enabled($contributor->moduleSlug())) {
                continue;
            }

            foreach ($contributor->metricsFor($day) as $row) {
                $written += $this->upsert($day, $row['metric'], (int) $row['count']);
            }
        }

        $cutoff = now()->subMonthsNoOverflow(self::RETENTION_MONTHS)->toDateString();
        $pruned = AnalyticsDailyTotal::query()
            ->whereRaw('date(metric_date) < ?', [$cutoff])
            ->delete();

        $this->info("Wrote {$written} daily totals for {$day->toDateString()}; pruned {$pruned} rows older than ".self::RETENTION_MONTHS.' months.');

        return self::SUCCESS;
    }

    private function signInCount(Carbon $day): int
    {
        return AuditLog::query()
            ->where('event', 'auth.signed_in')
            ->where('occurred_at', '>=', $day)
            ->where('occurred_at', '<', $day->copy()->addDay())
            ->count();
    }

    private function upsert(Carbon $day, string $metric, int $count): int
    {
        AnalyticsDailyTotal::query()->updateOrCreate(
            [
                'metric_date' => $day->toDateString(),
                'metric' => $metric,
            ],
            ['count' => max(0, $count)],
        );

        return 1;
    }
}
