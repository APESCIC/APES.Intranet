<?php

namespace Modules\Learning\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assignment extends Model
{
    protected $table = 'learning_assignments';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    protected $fillable = [
        'course_id',
        'lesson_id',
        'title',
        'instructions',
        'allow_text',
        'allow_file',
        'allowed_extensions',
        'max_file_bytes',
        'status',
        'due_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'allow_text' => 'boolean',
            'allow_file' => 'boolean',
            'allowed_extensions' => 'array',
            'max_file_bytes' => 'integer',
            'due_at' => 'datetime',
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
     * @return HasMany<AssignmentSubmission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    /**
     * @return HasMany<AssignmentFeedback, $this>
     */
    public function feedbackRows(): HasMany
    {
        return $this->hasMany(AssignmentFeedback::class);
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }
}
