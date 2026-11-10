<?php

namespace Modules\Operations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimesheetEntry extends Model
{
    protected $table = 'operations_timesheet_entries';

    protected $fillable = ['timesheet_id', 'clock_record_id', 'worked_on', 'minutes', 'description'];

    /**
     * @return BelongsTo<Timesheet, $this>
     */
    public function timesheet(): BelongsTo
    {
        return $this->belongsTo(Timesheet::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'worked_on' => 'immutable_date',
            'minutes' => 'integer',
        ];
    }
}
