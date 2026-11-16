<?php

namespace Modules\Learning\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rubric extends Model
{
    protected $table = 'learning_rubrics';

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'created_by',
    ];

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
     * @return HasMany<RubricCriterion, $this>
     */
    public function criteria(): HasMany
    {
        return $this->hasMany(RubricCriterion::class)->orderBy('sort_order');
    }
}
