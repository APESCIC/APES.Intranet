<?php

namespace Modules\Hr\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingCompletion extends Model
{
    protected $table = 'hr_training_completions';

    protected $fillable = [
        'user_id',
        'course_id',
        'completed_on',
        'expires_on',
        'recorded_by',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'completed_on' => 'date',
            'expires_on' => 'date',
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
     * @return BelongsTo<TrainingCourse, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(TrainingCourse::class, 'course_id');
    }

    public function isCurrent(): bool
    {
        return $this->expires_on === null || $this->expires_on->isFuture() || $this->expires_on->isToday();
    }
}
