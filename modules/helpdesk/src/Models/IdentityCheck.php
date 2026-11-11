<?php

namespace Modules\Helpdesk\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * How IT confirmed who the member is before a 2FA reset. Only the people
 * who work the IT queue see it.
 */
class IdentityCheck extends Model
{
    public const METHODS = [
        'callback' => 'Called them back on a phone number we already hold',
        'manager_in_person' => 'Their manager checked who they are in person',
        'manager_video' => 'Their manager checked who they are on a video call',
    ];

    protected $table = 'helpdesk_identity_checks';

    protected $fillable = ['ticket_id', 'method', 'note', 'checked_by', 'checked_at'];

    /**
     * @return BelongsTo<User, $this>
     */
    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    public function methodLabel(): string
    {
        return self::METHODS[$this->method] ?? $this->method;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'checked_at' => 'immutable_datetime',
        ];
    }
}
