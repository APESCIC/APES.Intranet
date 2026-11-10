<?php

namespace Modules\Operations\Models;

use App\Core\Access\Contracts\BelongsToTeam;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One checklist on one shift. Read-only once completed.
 */
class ChecklistRun extends Model implements BelongsToTeam
{
    protected $table = 'operations_checklist_runs';

    protected $fillable = ['checklist_template_id', 'shift_id', 'site_id', 'due_at'];

    /**
     * @return BelongsTo<ChecklistTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ChecklistTemplate::class, 'checklist_template_id');
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
     * @return BelongsTo<User, $this>
     */
    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * @return HasMany<ChecklistRunItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ChecklistRunItem::class)->orderBy('sort_order');
    }

    public function isComplete(): bool
    {
        return $this->completed_at !== null;
    }

    public function isMissed(): bool
    {
        return ! $this->isComplete() && $this->due_at->isPast();
    }

    public function isSupervisedBy(User $user): bool
    {
        return $this->site?->isSupervisedBy($user) ?? false;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'missed_alerted_at' => 'immutable_datetime',
        ];
    }
}
