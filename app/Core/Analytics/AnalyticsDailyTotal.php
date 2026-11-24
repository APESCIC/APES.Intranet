<?php

namespace App\Core\Analytics;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One day's count for a named metric. Never stores people or devices.
 *
 * @property Carbon $metric_date
 */
class AnalyticsDailyTotal extends Model
{
    protected $table = 'analytics_daily_totals';

    protected $fillable = ['metric_date', 'metric', 'count'];

    protected function casts(): array
    {
        return [
            'metric_date' => 'date',
            'count' => 'integer',
        ];
    }
}
