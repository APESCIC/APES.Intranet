<?php

namespace Modules\Operations\Models;

use App\Core\Access\Contracts\BelongsToTeam;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A member's claim on an open shift, or a request to swap shifts with a
 * colleague. The rota changes only when a manager approves it.
 */
class ShiftRequest extends Model implements BelongsToTeam
{
    public const TYPE_CLAIM = 'claim';

    public const TYPE_SWAP = 'swap';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_DECLINED = 'declined';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'operations_shift_requests';

    protected $fillable = ['type', 'shift_id', 'target_shift_id', 'requester_id', 'status', 'note'];

    /**
     * @return BelongsTo<Shift, $this>
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * @return BelongsTo<Shift, $this>
     */
    public function targetShift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'target_shift_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isSupervisedBy(User $user): bool
    {
        return $this->shift?->isSupervisedBy($user) ?? false;
    }

    public function summary(): string
    {
        if ($this->type === self::TYPE_CLAIM) {
            return ($this->requester->name ?? 'A member').' claims '.$this->shift->label().' at '.($this->shift->site->name ?? 'the site');
        }

        return ($this->requester->name ?? 'A member').' swaps '.$this->shift->label()
            .' with '.($this->targetShift?->member->name ?? 'a colleague').' ('.$this->targetShift?->label().')';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'decided_at' => 'datetime',
        ];
    }
}
