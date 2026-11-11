<?php

namespace Modules\Helpdesk\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    protected $fillable = ['requester_id', 'queue', 'category_id', 'priority', 'status', 'subject', 'assignee_id'];

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
        ];
    }
}
