<?php

namespace Modules\Learning\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Learning\GradingSchemeType;

class GradingScheme extends Model
{
    protected $table = 'learning_grading_schemes';

    protected $fillable = [
        'course_id',
        'name',
        'type',
        'max_points',
        'pass_threshold',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => GradingSchemeType::class,
            'max_points' => 'decimal:4',
            'pass_threshold' => 'decimal:4',
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
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<GradeItem, $this>
     */
    public function gradeItems(): HasMany
    {
        return $this->hasMany(GradeItem::class, 'scheme_id');
    }
}
