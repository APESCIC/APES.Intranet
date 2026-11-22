<?php

namespace Modules\Learning\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    protected $table = 'learning_certificates';

    protected $fillable = [
        'user_id',
        'course_id',
        'course_completion_id',
        'verify_code',
        'cpd_hours',
        'issued_at',
        'expires_on',
        'expiry_reminded_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'cpd_hours' => 'decimal:2',
            'issued_at' => 'datetime',
            'expires_on' => 'date',
            'expiry_reminded_at' => 'datetime',
            'revoked_at' => 'datetime',
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
     * @return BelongsTo<CourseCompletion, $this>
     */
    public function courseCompletion(): BelongsTo
    {
        return $this->belongsTo(CourseCompletion::class);
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isExpired(): bool
    {
        if ($this->expires_on === null) {
            return false;
        }

        return $this->expires_on->endOfDay()->isPast();
    }

    public function isCurrent(): bool
    {
        return ! $this->isRevoked() && ! $this->isExpired();
    }
}
