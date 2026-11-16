<?php

namespace Modules\Learning\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Learning\LearningRole;
use Modules\Learning\LearningScope;

class LearningRoleAssignment extends Model
{
    protected $fillable = [
        'user_id',
        'role',
        'scope_type',
        'scope_id',
        'assigned_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => LearningRole::class,
            'scope_type' => LearningScope::class,
            'scope_id' => 'integer',
        ];
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
    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Whether this assignment covers the given course (global admin covers all).
     */
    public function coversCourse(?int $courseId): bool
    {
        return match ($this->scope_type) {
            LearningScope::Global => true,
            LearningScope::Course => $courseId !== null && $this->scope_id === $courseId,
            LearningScope::Cohort => false, // Cohort → course mapping lands with enrolment (#173).
        };
    }

    /**
     * Whether this assignment covers the given cohort.
     */
    public function coversCohort(?int $cohortId): bool
    {
        return match ($this->scope_type) {
            LearningScope::Global => true,
            LearningScope::Cohort => $cohortId !== null && $this->scope_id === $cohortId,
            LearningScope::Course => false,
        };
    }

    public function effectiveScopeId(): ?int
    {
        return $this->scope_id === 0 ? null : $this->scope_id;
    }
}
