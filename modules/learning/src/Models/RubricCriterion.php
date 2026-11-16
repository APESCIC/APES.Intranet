<?php

namespace Modules\Learning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RubricCriterion extends Model
{
    protected $table = 'learning_rubric_criteria';

    protected $fillable = [
        'rubric_id',
        'title',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Rubric, $this>
     */
    public function rubric(): BelongsTo
    {
        return $this->belongsTo(Rubric::class);
    }

    /**
     * @return HasMany<RubricLevel, $this>
     */
    public function levels(): HasMany
    {
        return $this->hasMany(RubricLevel::class, 'criterion_id')->orderBy('sort_order');
    }

    public function maxPoints(): float
    {
        return (float) ($this->levels()->max('points') ?? 0);
    }
}
