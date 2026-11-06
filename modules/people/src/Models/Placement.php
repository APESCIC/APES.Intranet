<?php

namespace Modules\People\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Placement extends Model
{
    protected $fillable = [
        'student_id',
        'supervisor_id',
        'title',
        'organisation',
        'starts_on',
        'ends_on',
        'guidance',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /**
     * @return HasMany<PlacementHour, $this>
     */
    public function hours(): HasMany
    {
        return $this->hasMany(PlacementHour::class);
    }

    public function approvedMinutes(): int
    {
        return (int) $this->hours()->where('status', PlacementHour::STATUS_APPROVED)->sum('minutes');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }
}
