<?php

namespace Modules\Operations\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A period when a member cannot work.
 */
class Availability extends Model
{
    protected $table = 'operations_availabilities';

    protected $fillable = ['user_id', 'starts_at', 'ends_at', 'note'];

    /**
     * @return BelongsTo<User, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
        ];
    }
}
