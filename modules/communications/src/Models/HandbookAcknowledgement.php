<?php

namespace Modules\Communications\Models;

use Illuminate\Database\Eloquent\Model;

class HandbookAcknowledgement extends Model
{
    public $timestamps = false;

    protected $fillable = ['handbook_version_id', 'user_id', 'acknowledged_at'];

    protected function casts(): array
    {
        return ['acknowledged_at' => 'datetime'];
    }
}
