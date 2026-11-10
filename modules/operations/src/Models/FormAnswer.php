<?php

namespace Modules\Operations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormAnswer extends Model
{
    protected $table = 'operations_form_answers';

    protected $fillable = ['submission_id', 'field_id', 'value', 'file_path'];

    /**
     * @return BelongsTo<FormSubmission, $this>
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(FormSubmission::class, 'submission_id');
    }

    /**
     * @return BelongsTo<FormField, $this>
     */
    public function field(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'field_id');
    }
}
