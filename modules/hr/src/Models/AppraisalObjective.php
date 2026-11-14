<?php

namespace Modules\Hr\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppraisalObjective extends Model
{
    protected $table = 'hr_appraisal_objectives';

    protected $fillable = ['appraisal_id', 'title', 'notes', 'sort_order'];

    /**
     * @return BelongsTo<Appraisal, $this>
     */
    public function appraisal(): BelongsTo
    {
        return $this->belongsTo(Appraisal::class, 'appraisal_id');
    }
}
