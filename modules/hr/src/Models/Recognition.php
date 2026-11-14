<?php

namespace Modules\Hr\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recognition extends Model
{
    protected $table = 'hr_recognitions';

    protected $fillable = [
        'giver_id',
        'recipient_id',
        'message',
        'posted_to_feed',
        'update_post_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['posted_to_feed' => 'boolean'];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function giver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'giver_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }
}
