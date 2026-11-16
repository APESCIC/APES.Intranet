<?php

namespace Modules\Learning\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Learning\GradeReleaseState;

class GradeMark extends Model
{
    protected $table = 'learning_grade_marks';

    protected $fillable = [
        'grade_item_id',
        'user_id',
        'raw_score',
        'passed',
        'release_state',
        'marked_by',
        'moderated_by',
        'released_by',
        'marked_at',
        'moderated_at',
        'released_at',
    ];

    protected function casts(): array
    {
        return [
            'raw_score' => 'decimal:6',
            'passed' => 'boolean',
            'release_state' => GradeReleaseState::class,
            'marked_at' => 'datetime',
            'moderated_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<GradeItem, $this>
     */
    public function gradeItem(): BelongsTo
    {
        return $this->belongsTo(GradeItem::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function marker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }
}
