<?php

namespace Modules\Helpdesk\Services;

use App\Core\Audit\AuditLogger;
use App\Core\Support\HtmlSanitizer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Models\Category;
use Modules\Helpdesk\Models\Message;
use Modules\Helpdesk\Models\Ticket;

final class Tickets
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly HtmlSanitizer $sanitizer,
    ) {}

    /**
     * The category decides the queue.
     */
    public function raise(User $requester, Category $category, string $subject, string $body, ?string $priority = null): Ticket
    {
        return DB::transaction(function () use ($requester, $category, $subject, $body, $priority) {
            $ticket = Ticket::query()->create([
                'requester_id' => $requester->getKey(),
                'queue' => $category->queue,
                'category_id' => $category->getKey(),
                'priority' => $priority ?? $category->default_priority,
                'status' => Ticket::STATUS_OPEN,
                'subject' => $subject,
            ]);

            $ticket->messages()->create([
                'author_id' => $requester->getKey(),
                'body' => $this->sanitizer->clean($body),
                'is_private' => false,
            ]);

            $this->audit->record('helpdesk.ticket_raised', $ticket, ['queue' => $ticket->queue, 'category' => $category->slug], $requester);

            return $ticket;
        });
    }

    /**
     * A reply from the requester reopens a ticket that was waiting or resolved.
     */
    public function reply(Ticket $ticket, User $author, string $body): Message
    {
        return DB::transaction(function () use ($ticket, $author, $body) {
            $message = $ticket->messages()->create([
                'author_id' => $author->getKey(),
                'body' => $this->sanitizer->clean($body),
                'is_private' => false,
            ]);

            if ($ticket->isRequestedBy($author) && in_array($ticket->status, [Ticket::STATUS_WAITING, Ticket::STATUS_RESOLVED], true)) {
                $ticket->forceFill(['status' => Ticket::STATUS_OPEN, 'resolved_at' => null])->save();
            } else {
                $ticket->touch();
            }

            return $message;
        });
    }
}
