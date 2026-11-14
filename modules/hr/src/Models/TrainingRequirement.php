<?php

namespace Modules\Hr\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingRequirement extends Model
{
    protected $table = 'hr_training_requirements';

    protected $fillable = ['course_id', 'role', 'renew_months'];

    /**
     * @return BelongsTo<TrainingCourse, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(TrainingCourse::class, 'course_id');
    }
}
