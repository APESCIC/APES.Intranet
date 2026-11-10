<?php

namespace Modules\Operations\Models;

use App\Core\Access\Contracts\BelongsToTeam;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Operations\Support\TeamAccess;

class ClockRecord extends Model implements BelongsToTeam
{
    protected $table = 'operations_clock_records';

    protected $fillable = ['user_id', 'shift_id', 'site_id', 'clock_in_at', 'clock_out_at', 'geofence_result'];

    /**
     * @return BelongsTo<User, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<Shift, $this>
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * @return BelongsTo<Site, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * @return HasMany<ClockBreak, $this>
     */
    public function breaks(): HasMany
    {
        return $this->hasMany(ClockBreak::class)->orderBy('started_at');
    }

    public function isOpen(): bool
    {
        return $this->clock_out_at === null;
    }

    public function openBreak(): ?ClockBreak
    {
        return $this->breaks->firstWhere('ended_at', null);
    }

    public function workedMinutes(): int
    {
        $end = $this->clock_out_at ?? now()->toImmutable();
        $total = (int) floor($this->clock_in_at->diffInMinutes($end, true));

        $breaks = $this->breaks->sum(fn (ClockBreak $break) => (int) floor($break->started_at->diffInMinutes($break->ended_at ?? $end, true)));

        return max(0, $total - (int) $breaks);
    }

    public function isSupervisedBy(User $user): bool
    {
        if ($this->site !== null && $this->site->isSupervisedBy($user)) {
            return true;
        }

        return $this->member !== null && TeamAccess::supervises($user, $this->member);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'clock_in_at' => 'immutable_datetime',
            'clock_out_at' => 'immutable_datetime',
            'missed_clock_out_flagged_at' => 'immutable_datetime',
        ];
    }
}
