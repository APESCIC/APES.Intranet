<?php

namespace Modules\Helpdesk\Services;

use App\Core\Audit\AuditLogger;
use App\Core\Support\HtmlSanitizer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\HelpdeskPermissions;
use Modules\Helpdesk\Models\Category;
use Modules\Helpdesk\Models\Message;
use Modules\Helpdesk\Models\Ticket;

final class Tickets
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly HtmlSanitizer $sanitizer,
        private readonly Sla $sla,
        private readonly TicketMailer $mailer,
    ) {}

    /**
     * The category decides the queue.
     */
    public function raise(User $requester, Category $category, string $subject, string $body, ?string $priority = null): Ticket
    {
        $ticket = DB::transaction(function () use ($requester, $category, $subject, $body, $priority) {
            $ticket = Ticket::query()->create([
                'requester_id' => $requester->getKey(),
                'queue' => $category->queue,
                'category_id' => $category->getKey(),
                'priority' => $priority ?? $category->default_priority,
                'status' => Ticket::STATUS_OPEN,
                'subject' => $subject,
            ]);
            $this->sla->apply($ticket);
            $ticket->save();

            $ticket->messages()->create([
                'author_id' => $requester->getKey(),
                'body' => $this->sanitizer->clean($body),
                'is_private' => false,
            ]);

            $this->audit->record('helpdesk.ticket_raised', $ticket, ['queue' => $ticket->queue, 'category' => $category->slug], $requester);

            return $ticket;
        });

        $this->mailer->raised($ticket->load(['requester', 'category']));

        return $ticket;
    }

    /**
     * A reply from the requester reopens a ticket that was waiting or resolved.
     */
    public function reply(Ticket $ticket, User $author, string $body): Message
    {
        $message = DB::transaction(function () use ($ticket, $author, $body) {
            $message = $ticket->messages()->create([
                'author_id' => $author->getKey(),
                'body' => $this->sanitizer->clean($body),
                'is_private' => false,
            ]);

            if ($ticket->isRequestedBy($author) && in_array($ticket->status, [Ticket::STATUS_WAITING, Ticket::STATUS_RESOLVED], true)) {
                $ticket->forceFill(['status' => Ticket::STATUS_OPEN, 'resolved_at' => null])->save();
            } elseif ($ticket->first_responded_at === null && ! $ticket->isRequestedBy($author) && HelpdeskPermissions::works($author, $ticket->queue)) {
                $ticket->forceFill(['first_responded_at' => now()])->save();
            } else {
                $ticket->touch();
            }

            return $message;
        });

        $this->mailer->replied($ticket->load(['requester', 'assignee']), $message, $author);

        return $message;
    }

    public function note(Ticket $ticket, User $author, string $body): Message
    {
        $message = $ticket->messages()->create([
            'author_id' => $author->getKey(),
            'body' => $this->sanitizer->clean($body),
            'is_private' => true,
        ]);
        $ticket->touch();

        return $message;
    }

    /**
     * Every change is in the audit log and shows as a private note.
     */
    public function assign(Ticket $ticket, ?User $assignee, User $actor): void
    {
        if ((int) $ticket->assignee_id === (int) $assignee?->getKey()) {
            return;
        }

        DB::transaction(function () use ($ticket, $assignee, $actor) {
            $before = $ticket->assignee;
            $ticket->forceFill(['assignee_id' => $assignee?->getKey()])->save();

            $this->audit->record('helpdesk.ticket_assigned', $ticket, [
                'from' => $before?->getKey(),
                'to' => $assignee?->getKey(),
            ], $actor);

            $this->systemNote($ticket, $actor, $assignee
                ? $actor->name.' assigned this ticket to '.$assignee->name.'.'
                : $actor->name.' took this ticket off '.($before->name ?? 'its assignee').'.');
        });

        if ($assignee !== null) {
            $this->mailer->assigned($ticket, $assignee, $actor);
        }
    }

    /**
     * The requester is emailed once when the ticket is resolved or closed,
     * not again when a resolved ticket is closed.
     */
    public function changeStatus(Ticket $ticket, string $status, User $actor): void
    {
        if ($ticket->status === $status) {
            return;
        }

        $wasFinished = in_array($ticket->status, [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED], true);
        DB::transaction(fn () => $this->applyStatus($ticket, $status, $actor));

        if (! $wasFinished && ! $ticket->isActive()) {
            $this->mailer->finished($ticket->load('requester'));
        }
    }

    /**
     * A new topic moves the ticket to that topic's queue, and a new priority
     * resets the SLA targets from when the ticket was raised. A ticket that
     * changes queue loses its assignee, who may not work the new queue.
     */
    public function reclassify(Ticket $ticket, Category $category, string $priority, User $actor): void
    {
        if ((int) $ticket->category_id === (int) $category->getKey() && $ticket->priority === $priority) {
            return;
        }

        DB::transaction(function () use ($ticket, $category, $priority, $actor) {
            $before = ['queue' => $ticket->queue, 'category' => $ticket->category?->slug, 'priority' => $ticket->priority];
            $movesQueue = $ticket->queue !== $category->queue;

            $ticket->forceFill([
                'category_id' => $category->getKey(),
                'queue' => $category->queue,
                'priority' => $priority,
                'assignee_id' => $movesQueue ? null : $ticket->assignee_id,
            ]);
            if ($before['priority'] !== $priority) {
                $this->sla->apply($ticket);
            }
            $ticket->save();

            $this->audit->record('helpdesk.ticket_reclassified', $ticket, [
                'from' => $before,
                'to' => ['queue' => $category->queue, 'category' => $category->slug, 'priority' => $priority],
            ], $actor);

            $this->systemNote($ticket, $actor, $movesQueue
                ? $actor->name.' moved this ticket to the '.$ticket->queueLabel().' queue ('.$category->name.', '.$ticket->priorityLabel().' priority).'
                : $actor->name.' changed the topic or priority to '.$category->name.', '.$ticket->priorityLabel().' priority.');
        });
    }

    /**
     * Closes the duplicate and points it at the ticket that carries on.
     */
    public function merge(Ticket $duplicate, Ticket $target, User $actor): void
    {
        $message = DB::transaction(function () use ($duplicate, $target, $actor) {
            $duplicate->forceFill(['merged_into_id' => $target->getKey()])->save();
            $message = $duplicate->messages()->create([
                'author_id' => $actor->getKey(),
                'body' => $this->sanitizer->clean('<p>This request was merged with '.e($target->reference()).', which covers the same thing. The help desk carries on there.</p>'),
                'is_private' => false,
            ]);
            $this->applyStatus($duplicate, Ticket::STATUS_CLOSED, $actor);

            $this->systemNote($target, $actor, $actor->name.' merged '.$duplicate->reference().' ('.$duplicate->subject.') into this ticket.');
            $this->audit->record('helpdesk.ticket_merged', $duplicate, ['into' => $target->getKey()], $actor);

            return $message;
        });

        $this->mailer->replied($duplicate->load('requester'), $message, $actor);
    }

    private function applyStatus(Ticket $ticket, string $status, User $actor): void
    {
        $before = $ticket->status;
        $ticket->forceFill([
            'status' => $status,
            'resolved_at' => match ($status) {
                Ticket::STATUS_RESOLVED => now(),
                Ticket::STATUS_CLOSED => $ticket->resolved_at ?? now(),
                default => null,
            },
            'closed_at' => $status === Ticket::STATUS_CLOSED ? now() : null,
        ])->save();

        $this->audit->record('helpdesk.ticket_status_changed', $ticket, ['from' => $before, 'to' => $status], $actor);
    }

    private function systemNote(Ticket $ticket, User $actor, string $text): void
    {
        $ticket->messages()->create([
            'author_id' => $actor->getKey(),
            'body' => '<p>'.e($text).'</p>',
            'is_private' => true,
        ]);
    }
}
