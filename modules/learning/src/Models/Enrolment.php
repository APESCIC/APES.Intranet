<?php

namespace Modules\Learning\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Learning\EnrolmentMethod;

class Enrolment extends Model
{
    protected $table = 'learning_enrolments';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ENDED = 'ended';

    protected $fillable = [
        'user_id',
        'course_id',
        'cohort_id',
        'method',
        'status',
        'source_group',
        'starts_at',
        'ends_at',
        'enrolled_at',
        'ended_at',
        'enrolled_by',
    ];

    protected function casts(): array
    {
        return [
            'method' => EnrolmentMethod::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'enrolled_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return BelongsTo<Cohort, $this>
     */
    public function cohort(): BelongsTo
    {
        return $this->belongsTo(Cohort::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function enrolledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enrolled_by');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isWithinDates(?\DateTimeInterface $at = null): bool
    {
        $at = $at ?? now();

        if ($this->starts_at !== null && $at < $this->starts_at) {
            return false;
        }

        if ($this->ends_at !== null && $at > $this->ends_at) {
            return false;
        }

        return true;
    }
}
