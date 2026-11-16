<?php

namespace Modules\Learning\Models;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-lesson progress for a learner. Intentionally has no grade or score
 * fields — completion is separate from assessment (v0.6.7+).
 *
 * @property CarbonInterface|null $started_at
 * @property CarbonInterface|null $completed_at
 */
class LessonProgress extends Model
{
    protected $table = 'learning_lesson_progress';

    protected $fillable = [
        'user_id',
        'lesson_id',
        'lesson_version_id',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
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
     * @return BelongsTo<Lesson, $this>
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * @return BelongsTo<LessonVersion, $this>
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(LessonVersion::class, 'lesson_version_id');
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    public function isStarted(): bool
    {
        return $this->started_at !== null;
    }
}
