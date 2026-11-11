<?php

namespace Modules\Helpdesk\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A public reply, which the requester sees, or a private note, which only
 * the people who work the ticket's queue see.
 */
class Message extends Model
{
    protected $table = 'helpdesk_messages';

    protected $fillable = ['ticket_id', 'author_id', 'body', 'is_private'];

    /**
     * @return BelongsTo<Ticket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_private' => 'boolean',
        ];
    }
}
