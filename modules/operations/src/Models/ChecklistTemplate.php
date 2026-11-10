<?php

namespace Modules\Operations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChecklistTemplate extends Model
{
    protected $table = 'operations_checklist_templates';

    protected $fillable = ['site_id', 'shift_template_id', 'name', 'due_time', 'items', 'active', 'created_by'];

    /**
     * @return BelongsTo<Site, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * @return BelongsTo<ShiftTemplate, $this>
     */
    public function shiftTemplate(): BelongsTo
    {
        return $this->belongsTo(ShiftTemplate::class);
    }

    public function appliesTo(Shift $shift): bool
    {
        return $this->active
            && (int) $this->site_id === (int) $shift->site_id
            && ($this->shift_template_id === null || (int) $this->shift_template_id === (int) $shift->shift_template_id);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'items' => 'array',
            'active' => 'boolean',
        ];
    }
}
