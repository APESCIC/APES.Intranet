<?php

namespace Modules\Communications\Models;

use Illuminate\Database\Eloquent\Model;

class UpdateRead extends Model
{
    public $timestamps = false;

    protected $fillable = ['update_post_id', 'user_id', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }
}
