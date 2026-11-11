<?php

namespace Modules\Helpdesk\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Helpdesk\HelpdeskPermissions;
use Modules\Helpdesk\Models\Ticket;

class QueueController
{
    /**
     * The filters shown as buttons above the queue.
     */
    public const VIEWS = [
        'active' => 'Open and waiting',
        'mine' => 'Assigned to me',
        'unassigned' => 'Not assigned',
        'breached' => 'Past SLA',
        'done' => 'Resolved and closed',
        'all' => 'Everything',
    ];

    /**
     * Reached from the reports: every ticket that missed a target, whatever its status.
     */
    public const MISSED_VIEW = ['missed' => 'Missed an SLA target'];

    public function show(Request $request, string $queue): View
    {
        $user = $request->user();
        abort_unless(HelpdeskPermissions::works($user, $queue), 403);

        $filters = $request->validate([
            'view' => ['nullable', Rule::in(array_keys(self::VIEWS + self::MISSED_VIEW))],
            'from' => ['nullable', 'date_format:Y-m-d', 'required_with:to'],
            'to' => ['nullable', 'date_format:Y-m-d', 'required_with:from', 'after_or_equal:from'],
        ]);
        $view = $filters['view'] ?? 'active';
        $from = isset($filters['from']) ? CarbonImmutable::parse($filters['from']) : null;
        $to = isset($filters['to']) ? CarbonImmutable::parse($filters['to']) : null;

        $tickets = Ticket::query()
            ->with(['category', 'assignee', 'requester'])
            ->inQueue($queue)
            ->when($from && $to, fn ($q) => $q->raisedBetween($from, $to))
            ->when($view === 'active', fn ($q) => $q->active())
            ->when($view === 'mine', fn ($q) => $q->active()->where('assignee_id', $user->getKey()))
            ->when($view === 'unassigned', fn ($q) => $q->active()->whereNull('assignee_id'))
            ->when($view === 'breached', fn ($q) => $q->active()->breached())
            ->when($view === 'missed', fn ($q) => $q->breached())
            ->when($view === 'done', fn ($q) => $q->finished())
            ->latest('updated_at')
            ->limit((int) config('helpdesk.list_limit'))
            ->get();

        return view('helpdesk::queues.show', [
            'queue' => $queue,
            'queueLabel' => Ticket::QUEUES[$queue],
            'tickets' => $tickets,
            'view' => $view,
            'views' => self::VIEWS,
            'heading' => (self::VIEWS + self::MISSED_VIEW)[$view],
            'from' => $from,
            'to' => $to,
        ]);
    }
}
