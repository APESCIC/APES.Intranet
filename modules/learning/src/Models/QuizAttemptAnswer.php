<?php

namespace Modules\Learning\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizAttemptAnswer extends Model
{
    protected $table = 'learning_quiz_attempt_answers';

    protected $fillable = [
        'attempt_id',
        'question_id',
        'response',
        'needs_manual_mark',
        'is_correct',
        'score',
        'marked_by',
        'marked_at',
        'marker_feedback',
    ];

    protected function casts(): array
    {
        return [
            'needs_manual_mark' => 'boolean',
            'is_correct' => 'boolean',
            'score' => 'decimal:4',
            'marked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<QuizAttempt, $this>
     */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class, 'attempt_id');
    }

    /**
     * @return BelongsTo<Question, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function marker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    public function awaitsManualMark(): bool
    {
        return $this->needs_manual_mark && $this->marked_at === null;
    }
}
