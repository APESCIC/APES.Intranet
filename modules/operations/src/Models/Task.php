<?php

namespace Modules\Operations\Models;

use App\Core\Access\Contracts\BelongsToTeam;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Operations\Support\TeamAccess;

class Task extends Model implements BelongsToTeam
{
    public const ASSIGN_PERSON = 'person';

    public const ASSIGN_TEAM = 'team';

    public const ASSIGN_SHIFT = 'shift';

    public const STATUS_OPEN = 'open';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_DONE = 'done';

    public const STATUSES = [self::STATUS_OPEN => 'Open', self::STATUS_IN_PROGRESS => 'In progress', self::STATUS_DONE => 'Done'];

    public const RECURRENCES = ['daily' => 'Every day', 'weekly' => 'Every week', 'monthly' => 'Every month'];

    protected $table = 'operations_tasks';

    protected $fillable = [
        'title', 'description', 'assignee_type', 'user_id', 'team', 'shift_id',
        'due_at', 'recurrence', 'status', 'created_by', 'previous_task_id',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
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
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<TaskComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class)->oldest();
    }

    /**
     * @return HasOne<Task, $this>
     */
    public function nextTask(): HasOne
    {
        return $this->hasOne(self::class, 'previous_task_id');
    }

    public function isDone(): bool
    {
        return $this->status === self::STATUS_DONE;
    }

    public function isOverdue(): bool
    {
        return ! $this->isDone() && $this->due_at !== null && $this->due_at->isPast();
    }

    public function isAssignedTo(User $user): bool
    {
        return match ($this->assignee_type) {
            self::ASSIGN_PERSON => (int) $this->user_id === (int) $user->getKey(),
            self::ASSIGN_TEAM => filled($this->team) && $this->team === $user->team,
            self::ASSIGN_SHIFT => $this->shift !== null && (int) $this->shift->user_id === (int) $user->getKey(),
            default => false,
        };
    }

    public function isSupervisedBy(User $user): bool
    {
        return match ($this->assignee_type) {
            self::ASSIGN_PERSON => $this->assignee !== null && TeamAccess::supervises($user, $this->assignee),
            self::ASSIGN_TEAM => filled($this->team) && $this->team === $user->team,
            self::ASSIGN_SHIFT => $this->shift?->isSupervisedBy($user) ?? false,
            default => false,
        };
    }

    public function assigneeLabel(): string
    {
        return match ($this->assignee_type) {
            self::ASSIGN_PERSON => $this->assignee->name ?? 'Nobody',
            self::ASSIGN_TEAM => 'Team: '.$this->team,
            self::ASSIGN_SHIFT => $this->shift ? 'Shift: '.$this->shift->label() : 'A removed shift',
            default => '—',
        };
    }

    /**
     * Tasks for the person, their team or their shifts.
     *
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    public function scopeAssignedTo(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $q) use ($user) {
            $q->where(fn (Builder $p) => $p->where('assignee_type', self::ASSIGN_PERSON)->where('user_id', $user->getKey()))
                ->orWhere(fn (Builder $t) => $t->where('assignee_type', self::ASSIGN_TEAM)->whereNotNull('team')->where('team', $user->team))
                ->orWhere(fn (Builder $s) => $s->where('assignee_type', self::ASSIGN_SHIFT)->whereHas('shift', fn (Builder $shift) => $shift->where('user_id', $user->getKey())));
        });
    }

    /**
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', '!=', self::STATUS_DONE)->whereNotNull('due_at')->where('due_at', '<', now());
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
        ];
    }
}
