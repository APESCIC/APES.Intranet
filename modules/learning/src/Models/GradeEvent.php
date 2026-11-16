<?php

namespace Modules\Learning\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

/**
 * Append-only grade history row. Updates and deletes are refused.
 */
class GradeEvent extends Model
{
    protected $table = 'learning_grade_events';

    protected $fillable = [
        'grade_item_id',
        'user_id',
        'actor_id',
        'event_type',
        'old_score',
        'new_score',
        'reason',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'old_score' => 'decimal:6',
            'new_score' => 'decimal:6',
            'occurred_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new RuntimeException('Grade history events cannot be edited.');
        });

        static::deleting(function (): void {
            throw new RuntimeException('Grade history events cannot be deleted.');
        });
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
    public function learner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
