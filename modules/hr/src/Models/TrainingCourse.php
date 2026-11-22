<?php

namespace Modules\Hr\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Learning\Models\Course as LearningCourse;

class TrainingCourse extends Model
{
    protected $table = 'hr_training_courses';

    protected $fillable = ['code', 'title', 'lms_url', 'active', 'learning_course_id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    /**
     * Optional native Learning Module course that also satisfies this requirement.
     *
     * @return BelongsTo<LearningCourse, $this>
     */
    public function learningCourse(): BelongsTo
    {
        return $this->belongsTo(LearningCourse::class, 'learning_course_id');
    }

    /**
     * @return HasMany<TrainingRequirement, $this>
     */
    public function requirements(): HasMany
    {
        return $this->hasMany(TrainingRequirement::class, 'course_id');
    }

    /**
     * @return HasMany<TrainingCompletion, $this>
     */
    public function completions(): HasMany
    {
        return $this->hasMany(TrainingCompletion::class, 'course_id');
    }
}
