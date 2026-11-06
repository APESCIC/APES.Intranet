<?php

namespace Modules\Communications\Models;

use Illuminate\Database\Eloquent\Model;

class PolicyRead extends Model
{
    public $timestamps = false;

    protected $fillable = ['policy_document_id', 'user_id', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }
}
