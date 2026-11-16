<?php

namespace Modules\Learning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradeItem extends Model
{
    protected $table = 'learning_grade_items';

    protected $fillable = [
        'course_id',
        'title',
        'scheme_id',
        'rubric_id',
        'source_type',
        'source_id',
        'weight',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:4',
            'source_id' => 'integer',
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
     * @return BelongsTo<GradingScheme, $this>
     */
    public function scheme(): BelongsTo
    {
        return $this->belongsTo(GradingScheme::class, 'scheme_id');
    }

    /**
     * @return BelongsTo<Rubric, $this>
     */
    public function rubric(): BelongsTo
    {
        return $this->belongsTo(Rubric::class);
    }

    /**
     * @return HasMany<GradeMark, $this>
     */
    public function marks(): HasMany
    {
        return $this->hasMany(GradeMark::class);
    }

    public function hasMarks(): bool
    {
        return $this->marks()->exists();
    }
}
