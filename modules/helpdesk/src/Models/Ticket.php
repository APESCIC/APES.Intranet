<?php

namespace Modules\Helpdesk\Models;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Ticket extends Model
{
    public const QUEUE_IT = 'it';

    public const QUEUE_MANAGEMENT = 'management';

    public const QUEUES = [self::QUEUE_IT => 'IT', self::QUEUE_MANAGEMENT => 'Management'];

    public const STATUS_OPEN = 'open';

    public const STATUS_WAITING = 'waiting';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_CLOSED = 'closed';

    public const STATUSES = [
        self::STATUS_OPEN => 'Open',
        self::STATUS_WAITING => 'Waiting for the requester',
        self::STATUS_RESOLVED => 'Resolved',
        self::STATUS_CLOSED => 'Closed',
    ];

    public const ACTIVE_STATUSES = [self::STATUS_OPEN, self::STATUS_WAITING];

    public const PRIORITIES = ['urgent' => 'Urgent', 'high' => 'High', 'normal' => 'Normal', 'low' => 'Low'];

    protected $table = 'helpdesk_tickets';

    protected $fillable = ['requester_id', 'queue', 'category_id', 'priority', 'status', 'subject', 'assignee_id', 'merged_into_id'];

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
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<Ticket, $this>
     */
    public function mergedInto(): BelongsTo
    {
        return $this->belongsTo(self::class, 'merged_into_id');
    }

    /**
     * @return HasOne<IdentityCheck, $this>
     */
    public function identityCheck(): HasOne
    {
        return $this->hasOne(IdentityCheck::class)->latestOfMany();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function twoFactorResetter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'two_factor_reset_by');
    }

    public function requiresIdentityCheck(): bool
    {
        return (bool) $this->category?->requires_identity_check;
    }

    public function hasIdentityCheck(): bool
    {
        return $this->identityCheck()->exists();
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->oldest()->orderBy('id');
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function publicMessages(): HasMany
    {
        return $this->messages()->where('is_private', false);
    }

    public function reference(): string
    {
        return 'HD-'.str_pad((string) $this->getKey(), 4, '0', STR_PAD_LEFT);
    }

    public function queueLabel(): string
    {
        return self::QUEUES[$this->queue] ?? $this->queue;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function priorityLabel(): string
    {
        return self::PRIORITIES[$this->priority] ?? $this->priority;
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true);
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function isRequestedBy(User $user): bool
    {
        return (int) $this->requester_id === (int) $user->getKey();
    }

    /**
     * Resolving a ticket without a public reply also stops the reply clock.
     */
    public function responseBreached(): bool
    {
        return $this->response_due_at !== null && ($this->first_responded_at ?? $this->resolved_at ?? now())->greaterThan($this->response_due_at);
    }

    public function resolveBreached(): bool
    {
        return $this->resolve_due_at !== null && ($this->resolved_at ?? now())->greaterThan($this->resolve_due_at);
    }

    /**
     * Missed either target, now or when it was met late.
     */
    public function isBreached(): bool
    {
        return $this->responseBreached() || $this->resolveBreached();
    }

    /**
     * The same rule as isBreached(), for counting in the database.
     *
     * @param  Builder<Ticket>  $query
     * @return Builder<Ticket>
     */
    public function scopeBreached(Builder $query): Builder
    {
        $now = now();

        return $query->where(fn (Builder $q) => $q
            ->where(fn (Builder $r) => $r->whereNull('first_responded_at')->whereNull('resolved_at')->where('response_due_at', '<', $now))
            ->orWhereColumn('first_responded_at', '>', 'response_due_at')
            ->orWhere(fn (Builder $r) => $r->whereNull('first_responded_at')->whereColumn('resolved_at', '>', 'response_due_at'))
            ->orWhere(fn (Builder $r) => $r->whereNull('resolved_at')->where('resolve_due_at', '<', $now))
            ->orWhereColumn('resolved_at', '>', 'resolve_due_at'));
    }

    /**
     * @param  Builder<Ticket>  $query
     * @return Builder<Ticket>
     */
    public function scopeRequestedBy(Builder $query, User $user): Builder
    {
        return $query->where('requester_id', $user->getKey());
    }

    /**
     * @param  Builder<Ticket>  $query
     * @return Builder<Ticket>
     */
    public function scopeInQueue(Builder $query, string $queue): Builder
    {
        return $query->where('queue', $queue);
    }

    /**
     * Raised on or between the two dates, whole days.
     *
     * @param  Builder<Ticket>  $query
     * @return Builder<Ticket>
     */
    public function scopeRaisedBetween(Builder $query, CarbonInterface $from, CarbonInterface $to): Builder
    {
        return $query->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);
    }

    /**
     * @param  Builder<Ticket>  $query
     * @return Builder<Ticket>
     */
    public function scopeFinished(Builder $query): Builder
    {
        return $query->whereNotIn('status', self::ACTIVE_STATUSES);
    }

    /**
     * @param  Builder<Ticket>  $query
     * @return Builder<Ticket>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'resolved_at' => 'immutable_datetime',
            'closed_at' => 'immutable_datetime',
            'response_due_at' => 'immutable_datetime',
            'resolve_due_at' => 'immutable_datetime',
            'first_responded_at' => 'immutable_datetime',
            'two_factor_reset_at' => 'immutable_datetime',
        ];
    }
}
