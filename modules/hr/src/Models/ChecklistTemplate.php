<?php

namespace Modules\Hr\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChecklistTemplate extends Model
{
    public const KIND_ONBOARDING = 'onboarding';

    public const KIND_OFFBOARDING = 'offboarding';

    public const MEMBER_TYPES = ['staff', 'volunteer', 'student'];

    protected $table = 'hr_checklist_templates';

    protected $fillable = ['kind', 'member_type', 'name', 'active'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    /**
     * @return HasMany<ChecklistTemplateItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ChecklistTemplateItem::class, 'template_id')->orderBy('sort_order');
    }
}
