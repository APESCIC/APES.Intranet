<?php

namespace Modules\Operations\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftTemplate extends Model
{
    public const WEEKDAYS = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];

    protected $table = 'operations_shift_templates';

    protected $fillable = ['site_id', 'name', 'role', 'weekday', 'starts_at', 'ends_at', 'active'];

    /**
     * @return BelongsTo<Site, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function weekdayName(): string
    {
        return self::WEEKDAYS[$this->weekday] ?? '';
    }

    /**
     * Start and end on the template's weekday in the week starting on the
     * given Monday. A shift that ends before it starts runs past midnight.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function timesInWeek(CarbonImmutable $weekStart): array
    {
        $day = $weekStart->addDays($this->weekday - 1);
        $start = $day->setTimeFromTimeString($this->starts_at);
        $end = $day->setTimeFromTimeString($this->ends_at);

        return [$start, $end->lessThanOrEqualTo($start) ? $end->addDay() : $end];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
            'active' => 'boolean',
        ];
    }
}
