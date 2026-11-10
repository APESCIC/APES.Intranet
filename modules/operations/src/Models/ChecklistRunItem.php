<?php

namespace Modules\Operations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChecklistRunItem extends Model
{
    protected $table = 'operations_checklist_run_items';

    protected $fillable = ['checklist_run_id', 'label', 'done', 'note', 'photo_path', 'sort_order'];

    /**
     * @return BelongsTo<ChecklistRun, $this>
     */
    public function run(): BelongsTo
    {
        return $this->belongsTo(ChecklistRun::class, 'checklist_run_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'done' => 'boolean',
        ];
    }
}
