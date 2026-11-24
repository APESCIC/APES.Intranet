<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FooterLink extends Model
{
    protected $fillable = [
        'group',
        'label',
        'icon',
        'target_type',
        'target_value',
        'visibility',
        'sort_order',
        'locked',
        'module_slug',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'locked' => 'boolean',
        ];
    }
}
