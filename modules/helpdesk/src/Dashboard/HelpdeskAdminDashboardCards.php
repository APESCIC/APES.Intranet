<?php

namespace Modules\Helpdesk\Dashboard;

use App\Core\AdminDashboard\AdminDashboardCardContributor;
use App\Core\Modules\BelongsToModule;
use App\Models\User;
use Modules\Helpdesk\HelpdeskPermissions;
use Modules\Helpdesk\Models\Ticket;

final class HelpdeskAdminDashboardCards implements AdminDashboardCardContributor, BelongsToModule
{
    public function moduleSlug(): string
    {
        return 'helpdesk';
    }

    public function cards(User $user): array
    {
        $cards = [];

        foreach (HelpdeskPermissions::queuesFor($user) as $queue) {
            $open = Ticket::query()->inQueue($queue)->active()->count();
            $breached = Ticket::query()->inQueue($queue)->active()->breached()->count();
            $permission = HelpdeskPermissions::forQueue($queue);

            $cards[] = [
                'key' => 'helpdesk-admin-queue-'.$queue,
                'title' => Ticket::QUEUES[$queue].' queue',
                'body' => $open === 0
                    ? 'No open tickets in this queue.'
                    : trans_choice(':count open ticket|:count open tickets', $open)
                        .($breached > 0 ? ', '.$breached.' past SLA.' : '.'),
                'href' => route('helpdesk.queues.'.$queue),
                'permission' => $permission,
                'module' => 'helpdesk',
                'icon' => 'inbox',
                'group' => 'help-desk',
                'group_heading' => 'Help desk',
                'tone' => $breached > 0 ? 'warn' : null,
            ];
        }

        if ($user->can(HelpdeskPermissions::REPORTS_VIEW)) {
            $byStatus = [];
            foreach (Ticket::STATUSES as $status => $label) {
                $byStatus[] = $label.': '.Ticket::query()->where('status', $status)->count();
            }

            $pastSla = Ticket::query()->active()->breached()->count();

            $cards[] = [
                'key' => 'helpdesk-admin-by-status',
                'title' => 'Tickets by status',
                'body' => implode(' · ', $byStatus),
                'href' => route('helpdesk.reports.index'),
                'permission' => HelpdeskPermissions::REPORTS_VIEW,
                'module' => 'helpdesk',
                'icon' => 'pie-chart',
                'group' => 'help-desk',
                'group_heading' => 'Help desk',
            ];

            $cards[] = [
                'key' => 'helpdesk-admin-past-sla',
                'title' => 'Past SLA',
                'body' => $pastSla === 0
                    ? 'No open tickets are past their SLA.'
                    : trans_choice(':count ticket is past SLA.|:count tickets are past SLA.', $pastSla),
                'href' => route('helpdesk.reports.index'),
                'permission' => HelpdeskPermissions::REPORTS_VIEW,
                'module' => 'helpdesk',
                'icon' => 'timer-off',
                'group' => 'help-desk',
                'group_heading' => 'Help desk',
                'tone' => $pastSla > 0 ? 'warn' : null,
            ];
        }

        return $cards;
    }
}
