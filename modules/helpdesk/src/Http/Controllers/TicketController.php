<?php

namespace Modules\Helpdesk\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Helpdesk\HelpdeskPermissions;
use Modules\Helpdesk\Models\Category;
use Modules\Helpdesk\Models\Ticket;
use Modules\Helpdesk\Services\Tickets;

class TicketController
{
    public function index(Request $request): View
    {
        $tickets = Ticket::query()
            ->with(['category', 'assignee'])
            ->requestedBy($request->user())
            ->latest('updated_at')
            ->limit((int) config('helpdesk.list_limit'))
            ->get();

        return view('helpdesk::tickets.index', ['tickets' => $tickets]);
    }

    public function create(Request $request): View
    {
        $categories = Category::query()->active()->ordered()->get();

        return view('helpdesk::tickets.create', [
            'categories' => $categories->groupBy('queue'),
            'selected' => $categories->firstWhere('slug', $request->query('category'))?->getKey(),
        ]);
    }

    public function store(Request $request, Tickets $tickets): RedirectResponse
    {
        $data = $request->validate([
            'category_id' => ['required', 'integer', Rule::exists('helpdesk_categories', 'id')->where('active', true)],
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:20000'],
            'priority' => ['nullable', Rule::in(array_keys(Ticket::PRIORITIES))],
        ]);

        $ticket = $tickets->raise(
            $request->user(),
            Category::query()->findOrFail($data['category_id']),
            $data['subject'],
            $data['body'],
            $data['priority'] ?? null,
        );

        return redirect()->route('helpdesk.tickets.show', $ticket)
            ->with('status', 'Ticket '.$ticket->reference().' raised. We will reply here and by email.');
    }

    public function show(Request $request, Ticket $ticket): View
    {
        $user = $request->user();
        abort_unless($user->can('view', $ticket), 403);

        $canWork = $user->can('work', $ticket);
        $ticket->load(['requester', 'assignee', 'category', 'mergedInto']);

        return view('helpdesk::tickets.show', [
            'ticket' => $ticket,
            'messages' => ($canWork ? $ticket->messages() : $ticket->publicMessages())->with('author')->get(),
            'canWork' => $canWork,
            'canReply' => $user->can('reply', $ticket),
            'agents' => $canWork ? HelpdeskPermissions::agents($ticket->queue) : collect(),
            'categories' => $canWork
                ? Category::query()->where(fn ($q) => $q->active()->orWhere('id', $ticket->category_id))->ordered()->get()->groupBy('queue')
                : collect(),
        ]);
    }

    public function reply(Request $request, Ticket $ticket, Tickets $tickets): RedirectResponse
    {
        abort_unless($request->user()->can('reply', $ticket), 403);

        $data = $request->validate(['body' => ['required', 'string', 'max:20000']]);
        $tickets->reply($ticket, $request->user(), $data['body']);

        return back()->with('status', 'Reply sent.');
    }
}
