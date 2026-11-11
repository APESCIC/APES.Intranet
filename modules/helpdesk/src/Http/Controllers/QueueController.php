<?php

namespace Modules\Helpdesk\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Helpdesk\HelpdeskPermissions;
use Modules\Helpdesk\Models\Ticket;

class QueueController
{
    public const VIEWS = [
        'active' => 'Open and waiting',
        'mine' => 'Assigned to me',
        'unassigned' => 'Not assigned',
        'done' => 'Resolved and closed',
        'all' => 'Everything',
    ];

    public function show(Request $request, string $queue): View
    {
        $user = $request->user();
        abort_unless(HelpdeskPermissions::works($user, $queue), 403);

        $filters = $request->validate([
            'view' => ['nullable', Rule::in(array_keys(self::VIEWS))],
        ]);
        $view = $filters['view'] ?? 'active';

        $tickets = Ticket::query()
            ->with(['category', 'assignee', 'requester'])
            ->inQueue($queue)
            ->when($view === 'active', fn ($q) => $q->active())
            ->when($view === 'mine', fn ($q) => $q->active()->where('assignee_id', $user->getKey()))
            ->when($view === 'unassigned', fn ($q) => $q->active()->whereNull('assignee_id'))
            ->when($view === 'done', fn ($q) => $q->whereNotIn('status', Ticket::ACTIVE_STATUSES))
            ->latest('updated_at')
            ->limit((int) config('helpdesk.list_limit'))
            ->get();

        return view('helpdesk::queues.show', [
            'queue' => $queue,
            'queueLabel' => Ticket::QUEUES[$queue],
            'tickets' => $tickets,
            'view' => $view,
            'views' => self::VIEWS,
        ]);
    }
}
