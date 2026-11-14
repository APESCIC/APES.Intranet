<?php

namespace Modules\Hr\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    protected $table = 'hr_leave_balances';

    protected $fillable = [
        'user_id',
        'year',
        'allowance_days',
        'used_days',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'allowance_days' => 'float',
            'used_days' => 'float',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function remainingDays(): float
    {
        return max(0, (float) $this->allowance_days - (float) $this->used_days);
    }
}
