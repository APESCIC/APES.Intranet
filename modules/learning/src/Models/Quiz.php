<?php

namespace Modules\Learning\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quiz extends Model
{
    protected $table = 'learning_quizzes';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    protected $fillable = [
        'course_id',
        'lesson_id',
        'title',
        'instructions',
        'attempt_limit',
        'time_limit_seconds',
        'random_draw_count',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'attempt_limit' => 'integer',
            'time_limit_seconds' => 'integer',
            'random_draw_count' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return BelongsTo<Lesson, $this>
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsToMany<Question, $this>
     */
    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'learning_quiz_question')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /**
     * @return HasMany<QuizAttempt, $this>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    /**
     * @return HasMany<QuizExtraTime, $this>
     */
    public function extraTimes(): HasMany
    {
        return $this->hasMany(QuizExtraTime::class);
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }
}
