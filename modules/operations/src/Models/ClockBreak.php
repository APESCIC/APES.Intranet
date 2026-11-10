<?php

namespace Modules\Operations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClockBreak extends Model
{
    protected $table = 'operations_clock_breaks';

    protected $fillable = ['clock_record_id', 'started_at', 'ended_at'];

    /**
     * @return BelongsTo<ClockRecord, $this>
     */
    public function record(): BelongsTo
    {
        return $this->belongsTo(ClockRecord::class, 'clock_record_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'immutable_datetime',
            'ended_at' => 'immutable_datetime',
        ];
    }
}
