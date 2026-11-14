<?php

namespace Modules\Hr\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChecklistTemplateItem extends Model
{
    public const ROLE_HR = 'hr';

    public const ROLE_MANAGER = 'manager';

    public const ROLE_IT = 'it';

    protected $table = 'hr_checklist_template_items';

    protected $fillable = ['template_id', 'owner_role', 'title', 'sort_order'];

    /**
     * @return BelongsTo<ChecklistTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ChecklistTemplate::class, 'template_id');
    }
}
