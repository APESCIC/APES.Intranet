<?php

namespace Modules\Hr\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One HR record per intranet member. Ordinary fields only; DBS and other
 * special category data live on related models opened through SensitiveData.
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $employment_status
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property string|null $contract_type
 * @property string|null $work_location
 * @property string|null $emergency_contact_name
 * @property string|null $emergency_contact_phone
 * @property string|null $notes
 */
class HrRecord extends Model
{
    protected $fillable = [
        'user_id',
        'employment_status',
        'start_date',
        'end_date',
        'contract_type',
        'work_location',
        'emergency_contact_name',
        'emergency_contact_phone',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
