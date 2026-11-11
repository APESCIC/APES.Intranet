<?php

namespace Modules\Helpdesk\Services;

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\Helpdesk\HelpdeskPermissions;
use Modules\Helpdesk\Mail\HelpdeskMail;
use Modules\Helpdesk\Models\Message;
use Modules\Helpdesk\Models\Ticket;
use Throwable;

/**
 * Help desk emails go through Cloudron sendmail. A mail failure is reported
 * but never undoes the ticket change. Private notes are never passed in.
 */
final class TicketMailer
{
    public function raised(Ticket $ticket): void
    {
        $this->send([$ticket->requester], $ticket, 'We have your request', [
            'Thank you. Your ticket '.$ticket->reference().' ('.$ticket->subject.') is with the '.$ticket->queueLabel().' team.',
            'We will reply on the intranet and email you when we do.',
        ]);

        $this->send($this->others(HelpdeskPermissions::agents($ticket->queue), $ticket->requester), $ticket, 'New ticket', [
            'New ticket in the '.$ticket->queueLabel().' queue from '.($ticket->requester->name ?? 'a member').'.',
            'Topic: '.($ticket->category->name ?? '—').'. Priority: '.$ticket->priorityLabel().'.',
        ]);
    }

    public function replied(Ticket $ticket, Message $reply, User $author): void
    {
        $text = $this->plain($reply->body);

        if (! $ticket->isRequestedBy($author)) {
            $this->send([$ticket->requester], $ticket, 'New reply', [
                $author->name.' replied to your ticket:',
                $text,
            ]);

            return;
        }

        $people = $ticket->assignee ? [$ticket->assignee] : HelpdeskPermissions::agents($ticket->queue);
        $this->send($this->others($people, $author), $ticket, 'Reply from the requester', [
            $author->name.' replied:',
            $text,
        ]);
    }

    public function assigned(Ticket $ticket, User $assignee, User $actor): void
    {
        $this->send($this->others([$assignee], $actor), $ticket, 'Assigned to you', [
            $actor->name.' assigned this ticket to you.',
            'Priority: '.$ticket->priorityLabel().'.',
        ]);
    }

    public function finished(Ticket $ticket): void
    {
        $lines = $ticket->status === Ticket::STATUS_RESOLVED
            ? ['We have marked your ticket as resolved.', 'If it is not fixed, reply on the intranet and it opens again.']
            : ['Your ticket is now closed.', 'If you still need help, raise a new ticket on the intranet.'];

        $this->send([$ticket->requester], $ticket, $ticket->status === Ticket::STATUS_RESOLVED ? 'Resolved' : 'Closed', $lines);
    }

    /**
     * @param  iterable<User|null>  $people
     * @param  list<string>  $lines
     */
    private function send(iterable $people, Ticket $ticket, string $what, array $lines): void
    {
        $subject = $ticket->reference().' · '.$what.': '.Str::limit($ticket->subject, 80);
        $sent = [];

        foreach ($people as $person) {
            if ($person === null || blank($person->email) || isset($sent[$person->getKey()])) {
                continue;
            }
            $sent[$person->getKey()] = true;

            try {
                Mail::to($person->email)->send(new HelpdeskMail($subject, $lines, route('helpdesk.tickets.show', $ticket)));
            } catch (Throwable $e) {
                report($e);
            }
        }
    }

    /**
     * @param  iterable<User|null>  $people
     * @return list<User>
     */
    private function others(iterable $people, ?User $except): array
    {
        $list = [];
        foreach ($people as $person) {
            if ($person !== null && ! $person->is($except)) {
                $list[] = $person;
            }
        }

        return $list;
    }

    private function plain(string $html): string
    {
        $text = preg_replace('/<\/(p|li|h2|h3|blockquote)>|<br\s*\/?>/i', "\n", $html) ?? $html;

        return trim(html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
}
