<?php

namespace Modules\Hr\Models;

use App\Core\Access\RoleName;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Hr\HrPermissions;

class Appraisal extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SELF_SUBMITTED = 'self_submitted';

    public const STATUS_MANAGER_SUBMITTED = 'manager_submitted';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_LOCKED = 'locked';

    protected $table = 'hr_appraisals';

    protected $fillable = [
        'cycle_id',
        'user_id',
        'manager_id',
        'self_review',
        'manager_review',
        'status',
        'locked_at',
        'member_signed_at',
        'member_signed_by',
        'manager_signed_at',
        'manager_signed_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'locked_at' => 'datetime',
            'member_signed_at' => 'datetime',
            'manager_signed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<AppraisalCycle, $this>
     */
    public function cycle(): BelongsTo
    {
        return $this->belongsTo(AppraisalCycle::class, 'cycle_id');
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
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * @return HasMany<AppraisalObjective, $this>
     */
    public function objectives(): HasMany
    {
        return $this->hasMany(AppraisalObjective::class, 'appraisal_id')->orderBy('sort_order');
    }

    public function isLocked(): bool
    {
        return $this->status === self::STATUS_LOCKED || $this->locked_at !== null;
    }

    public function bothSigned(): bool
    {
        return $this->member_signed_at !== null && $this->manager_signed_at !== null;
    }

    public function canBeViewedBy(User $viewer): bool
    {
        if ($viewer->can(HrPermissions::ACCESS)) {
            return true;
        }

        if ((int) $viewer->id === (int) $this->user_id) {
            return true;
        }

        return (int) $viewer->id === (int) $this->manager_id
            || (int) $viewer->id === (int) $this->user?->manager_id;
    }

    public function canBeEditedBy(User $viewer): bool
    {
        if ($this->isLocked()) {
            return false;
        }

        if ($viewer->can(HrPermissions::ACCESS) || $viewer->hasRole(RoleName::Admin->value)) {
            return true;
        }

        if ((int) $viewer->id === (int) $this->user_id) {
            return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SELF_SUBMITTED], true);
        }

        return (int) $viewer->id === (int) $this->manager_id
            || (int) $viewer->id === (int) $this->user?->manager_id;
    }
}
