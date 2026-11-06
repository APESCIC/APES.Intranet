<?php

namespace Modules\Communications\Models;

use Illuminate\Database\Eloquent\Model;

class EventRsvp extends Model
{
    protected $fillable = ['event_id', 'user_id', 'status'];
}
