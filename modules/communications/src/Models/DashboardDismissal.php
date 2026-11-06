<?php

namespace Modules\Communications\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardDismissal extends Model
{
    protected $fillable = ['user_id', 'card_key', 'dismissed_until'];

    protected function casts(): array
    {
        return ['dismissed_until' => 'datetime'];
    }
}
