<?php

namespace Modules\Hr\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberChecklistItem extends Model
{
    protected $table = 'hr_member_checklist_items';

    protected $fillable = [
        'checklist_id',
        'owner_role',
        'title',
        'sort_order',
        'completed_at',
        'completed_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<MemberChecklist, $this>
     */
    public function checklist(): BelongsTo
    {
        return $this->belongsTo(MemberChecklist::class, 'checklist_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function isComplete(): bool
    {
        return $this->completed_at !== null;
    }
}
