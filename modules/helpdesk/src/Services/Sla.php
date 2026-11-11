<?php

namespace Modules\Helpdesk\Services;

use Modules\Helpdesk\Models\Ticket;

final class Sla
{
    /**
     * Sets the reply and resolve deadlines from when the ticket was raised.
     */
    public function apply(Ticket $ticket): void
    {
        $hours = config('helpdesk.sla_hours.'.$ticket->priority) ?? config('helpdesk.sla_hours.normal');
        $raised = $ticket->created_at ?? now();

        $ticket->forceFill([
            'response_due_at' => $raised->copy()->addHours((int) $hours['respond']),
            'resolve_due_at' => $raised->copy()->addHours((int) $hours['resolve']),
        ]);
    }
}
