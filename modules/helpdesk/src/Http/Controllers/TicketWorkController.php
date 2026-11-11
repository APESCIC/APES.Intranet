<?php

namespace Modules\Helpdesk\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Helpdesk\HelpdeskPermissions;
use Modules\Helpdesk\Models\Category;
use Modules\Helpdesk\Models\IdentityCheck;
use Modules\Helpdesk\Models\Ticket;
use Modules\Helpdesk\Services\Tickets;

/**
 * What the people who work a queue do with its tickets.
 */
class TicketWorkController
{
    public function note(Request $request, Ticket $ticket, Tickets $tickets): RedirectResponse
    {
        abort_unless($request->user()->can('work', $ticket), 403);

        $data = $request->validate(['note' => ['required', 'string', 'max:20000']]);
        $tickets->note($ticket, $request->user(), $data['note']);

        return back()->with('status', 'Private note added.');
    }

    public function assign(Request $request, Ticket $ticket, Tickets $tickets): RedirectResponse
    {
        abort_unless($request->user()->can('work', $ticket), 403);

        $agents = HelpdeskPermissions::agents($ticket->queue);
        $data = $request->validate([
            'assignee_id' => ['nullable', 'integer', Rule::in($agents->modelKeys())],
        ], ['assignee_id.in' => 'Choose someone who works the '.$ticket->queueLabel().' queue.']);

        $assignee = filled($data['assignee_id'] ?? null) ? User::query()->findOrFail($data['assignee_id']) : null;
        $tickets->assign($ticket, $assignee, $request->user());

        return back()->with('status', $assignee ? 'Assigned to '.$assignee->name.'.' : 'Nobody is assigned now.');
    }

    public function status(Request $request, Ticket $ticket, Tickets $tickets): RedirectResponse
    {
        abort_unless($request->user()->can('work', $ticket), 403);

        $data = $request->validate(['status' => ['required', Rule::in(array_keys(Ticket::STATUSES))]]);
        $tickets->changeStatus($ticket, $data['status'], $request->user());

        return back()->with('status', 'Status saved: '.Ticket::STATUSES[$data['status']].'.');
    }

    public function reclassify(Request $request, Ticket $ticket, Tickets $tickets): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->can('work', $ticket), 403);

        $data = $request->validate([
            'category_id' => ['required', 'integer'],
            'priority' => ['required', Rule::in(array_keys(Ticket::PRIORITIES))],
        ]);
        $category = Category::query()->find($data['category_id']);
        if ($category === null || (! $category->active && ! $category->is($ticket->category))) {
            throw ValidationException::withMessages(['category_id' => 'Choose a topic that is in use.']);
        }
        $tickets->reclassify($ticket, $category, $data['priority'], $user);

        if (! $user->can('view', $ticket->refresh())) {
            return redirect()->route('helpdesk.queues.'.HelpdeskPermissions::queuesFor($user)[0])
                ->with('status', $ticket->reference().' moved to the '.$ticket->queueLabel().' queue.');
        }

        return back()->with('status', 'Topic and priority saved.');
    }

    public function identityCheck(Request $request, Ticket $ticket, Tickets $tickets): RedirectResponse
    {
        $this->authoriseTwoFactorWork($request->user(), $ticket);

        $data = $request->validate([
            'method' => ['required', Rule::in(array_keys(IdentityCheck::METHODS))],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        $tickets->recordIdentityCheck($ticket, $data['method'], $data['note'] ?? null, $request->user());

        return back()->with('status', 'Identity check recorded.');
    }

    public function twoFactorReset(Request $request, Ticket $ticket, Tickets $tickets): RedirectResponse
    {
        $this->authoriseTwoFactorWork($request->user(), $ticket);

        $tickets->recordTwoFactorReset($ticket, $request->user());

        return back()->with('status', '2FA reset recorded.');
    }

    /**
     * Only the IT queue checks identity and resets 2FA.
     */
    private function authoriseTwoFactorWork(User $user, Ticket $ticket): void
    {
        abort_unless(
            $ticket->queue === Ticket::QUEUE_IT && $ticket->requiresIdentityCheck() && $user->can('work', $ticket) && ! $ticket->isClosed(),
            403,
        );
    }

    public function merge(Request $request, Ticket $ticket, Tickets $tickets): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->can('work', $ticket), 403);

        $data = $request->validate(['target' => ['required', 'string', 'max:20']]);
        $targetId = (int) preg_replace('/\D/', '', $data['target']);
        $target = Ticket::query()->find($targetId);

        if ($ticket->isClosed()) {
            throw ValidationException::withMessages(['target' => 'This ticket is already closed.']);
        }
        if ($target === null || $target->is($ticket) || $target->isClosed() || ! $user->can('work', $target)) {
            throw ValidationException::withMessages(['target' => 'Enter the reference of another open ticket in a queue you work, for example HD-0012.']);
        }
        if ($ticket->requiresIdentityCheck() && ! $ticket->hasIdentityCheck() && ! $target->requiresIdentityCheck()) {
            throw ValidationException::withMessages(['target' => 'This ticket needs an identity check, so it can only be merged into another ticket that needs one.']);
        }

        $tickets->merge($ticket, $target, $user);

        return redirect()->route('helpdesk.tickets.show', $target)->with('status', $ticket->reference().' merged into '.$target->reference().'.');
    }
}
