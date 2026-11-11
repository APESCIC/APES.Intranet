<?php

namespace Modules\Helpdesk\Policies;

use App\Models\User;
use Modules\Helpdesk\HelpdeskPermissions;
use Modules\Helpdesk\Models\Ticket;

class TicketPolicy
{
    /**
     * Requesters see their own tickets; queue members see the queue's.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        return $ticket->isRequestedBy($user) || $this->work($user, $ticket);
    }

    public function reply(User $user, Ticket $ticket): bool
    {
        return ! $ticket->isClosed() && $this->view($user, $ticket);
    }

    /**
     * Private notes, assignment, status and merging.
     */
    public function work(User $user, Ticket $ticket): bool
    {
        return HelpdeskPermissions::works($user, $ticket->queue);
    }
}
