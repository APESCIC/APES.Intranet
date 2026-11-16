<?php

namespace Modules\Learning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradeCategory extends Model
{
    protected $table = 'learning_grade_categories';

    protected $fillable = [
        'course_id',
        'name',
        'weight',
        'drop_policy',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:4',
            'sort_order' => 'integer',
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
     * @return HasMany<GradeItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(GradeItem::class, 'category_id');
    }
}
