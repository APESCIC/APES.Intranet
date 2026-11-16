<?php

namespace Modules\Learning\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssignmentFeedback extends Model
{
    protected $table = 'learning_assignment_feedback';

    protected $fillable = [
        'assignment_id',
        'user_id',
        'submission_id',
        'body',
        'author_id',
        'released_at',
    ];

    protected function casts(): array
    {
        return [
            'released_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Assignment, $this>
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function learner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<AssignmentSubmission, $this>
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(AssignmentSubmission::class, 'submission_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function isReleased(): bool
    {
        return $this->released_at !== null;
    }
}
