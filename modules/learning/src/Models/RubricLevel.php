<?php

namespace Modules\Learning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RubricLevel extends Model
{
    protected $table = 'learning_rubric_levels';

    protected $fillable = [
        'criterion_id',
        'label',
        'points',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'decimal:4',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<RubricCriterion, $this>
     */
    public function criterion(): BelongsTo
    {
        return $this->belongsTo(RubricCriterion::class, 'criterion_id');
    }
}
