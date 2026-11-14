<?php

namespace Modules\Hr\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MemberChecklist extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_COMPLETED = 'completed';

    protected $table = 'hr_member_checklists';

    protected $fillable = [
        'user_id',
        'template_id',
        'kind',
        'member_type',
        'status',
        'started_by',
        'it_ticket_id',
        'completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<ChecklistTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ChecklistTemplate::class, 'template_id');
    }

    /**
     * @return HasMany<MemberChecklistItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(MemberChecklistItem::class, 'checklist_id')->orderBy('sort_order');
    }
}
