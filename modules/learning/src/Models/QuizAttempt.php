<?php

namespace Modules\Learning\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Learning\QuizAttemptStatus;

class QuizAttempt extends Model
{
    protected $table = 'learning_quiz_attempts';

    protected $fillable = [
        'quiz_id',
        'user_id',
        'attempt_number',
        'status',
        'started_at',
        'due_at',
        'submitted_at',
        'auto_score',
        'manual_score',
        'drawn_question_ids',
    ];

    protected function casts(): array
    {
        return [
            'status' => QuizAttemptStatus::class,
            'attempt_number' => 'integer',
            'started_at' => 'datetime',
            'due_at' => 'datetime',
            'submitted_at' => 'datetime',
            'auto_score' => 'decimal:4',
            'manual_score' => 'decimal:4',
            'drawn_question_ids' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Quiz, $this>
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<QuizAttemptAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(QuizAttemptAnswer::class, 'attempt_id');
    }

    public function isOpen(): bool
    {
        return $this->status === QuizAttemptStatus::InProgress
            && ($this->due_at === null || $this->due_at->isFuture());
    }
}
