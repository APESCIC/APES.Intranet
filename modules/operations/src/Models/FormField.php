<?php

namespace Modules\Operations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormField extends Model
{
    public const TYPES = [
        'text' => 'Short text',
        'textarea' => 'Long text',
        'number' => 'Number',
        'date' => 'Date',
        'select' => 'Choice from a list',
        'checkbox' => 'Tick box',
        'photo' => 'Photo',
        'signature' => 'Signature',
    ];

    protected $table = 'operations_form_fields';

    protected $fillable = ['form_id', 'label', 'type', 'required', 'options', 'sort_order'];

    /**
     * @return BelongsTo<Form, $this>
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function inputName(): string
    {
        return 'field_'.$this->id;
    }

    public function hasFile(): bool
    {
        return in_array($this->type, ['photo', 'signature'], true);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'required' => 'boolean',
            'options' => 'array',
        ];
    }
}
