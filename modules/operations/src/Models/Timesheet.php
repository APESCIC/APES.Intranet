<?php

namespace Modules\Operations\Models;

use App\Core\Access\Contracts\BelongsToTeam;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Operations\Support\TeamAccess;

class Timesheet extends Model implements BelongsToTeam
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_RETURNED = 'returned';

    protected $table = 'operations_timesheets';

    protected $fillable = ['user_id', 'period_start', 'period_end', 'status', 'total_minutes'];

    /**
     * @return BelongsTo<User, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return HasMany<TimesheetEntry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(TimesheetEntry::class)->orderBy('worked_on');
    }

    public function isLocked(): bool
    {
        return $this->locked_at !== null;
    }

    public function isEditable(): bool
    {
        return ! $this->isLocked() && in_array($this->status, [self::STATUS_DRAFT, self::STATUS_RETURNED], true);
    }

    public function hoursLabel(): string
    {
        return intdiv($this->total_minutes, 60).'h '.($this->total_minutes % 60).'m';
    }

    public function isSupervisedBy(User $user): bool
    {
        return $this->member !== null && TeamAccess::supervises($user, $this->member);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period_start' => 'immutable_date',
            'period_end' => 'immutable_date',
            'total_minutes' => 'integer',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'locked_at' => 'datetime',
        ];
    }
}
