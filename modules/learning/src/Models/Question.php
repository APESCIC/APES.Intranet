<?php

namespace Modules\Learning\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Learning\QuestionType;

class Question extends Model
{
    protected $table = 'learning_questions';

    protected $fillable = [
        'course_id',
        'type',
        'stem',
        'options',
        'correct_answer',
        'points',
        'needs_manual_mark',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => QuestionType::class,
            'options' => 'array',
            'correct_answer' => 'array',
            'needs_manual_mark' => 'boolean',
            'points' => 'integer',
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
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsToMany<Quiz, $this>
     */
    public function quizzes(): BelongsToMany
    {
        return $this->belongsToMany(Quiz::class, 'learning_quiz_question')
            ->withPivot('sort_order')
            ->withTimestamps();
    }
}
