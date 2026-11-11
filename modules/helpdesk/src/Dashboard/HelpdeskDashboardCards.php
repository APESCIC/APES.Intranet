<?php

namespace Modules\Helpdesk\Dashboard;

use App\Core\Dashboard\DashboardCardContributor;
use App\Models\User;
use Modules\Helpdesk\HelpdeskPermissions;
use Modules\Helpdesk\Models\Ticket;

final class HelpdeskDashboardCards implements DashboardCardContributor
{
    public function cards(User $user): array
    {
        $cards = [];

        foreach (HelpdeskPermissions::queuesFor($user) as $queue) {
            $open = Ticket::query()->inQueue($queue)->active()->count();
            if ($open === 0) {
                continue;
            }

            $breached = Ticket::query()->inQueue($queue)->active()->breached()->count();
            $cards[] = [
                'key' => 'helpdesk-queue-'.$queue,
                'title' => Ticket::QUEUES[$queue].' queue',
                'body' => trans_choice(':count open ticket|:count open tickets', $open)
                    .($breached > 0 ? ', '.$breached.' past SLA.' : '.'),
                'href' => route('helpdesk.queues.'.$queue, $breached > 0 ? ['view' => 'breached'] : []),
            ] + ($breached > 0 ? ['tone' => 'warn'] : []);
        }

        $mine = Ticket::query()->requestedBy($user)->active()->count();
        if ($mine > 0) {
            $cards[] = [
                'key' => 'helpdesk-mine',
                'title' => 'Your help desk tickets',
                'body' => trans_choice(':count ticket is open.|:count tickets are open.', $mine),
                'href' => route('helpdesk.tickets.index'),
            ];
        }

        return $cards;
    }
}
