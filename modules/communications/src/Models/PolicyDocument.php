<?php

namespace Modules\Communications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PolicyDocument extends Model
{
    protected $fillable = [
        'title', 'sharepoint_url', 'owner_name', 'review_due_on', 'active',
    ];

    protected function casts(): array
    {
        return [
            'review_due_on' => 'date',
            'active' => 'boolean',
        ];
    }

    public function reads(): HasMany
    {
        return $this->hasMany(PolicyRead::class);
    }
}
