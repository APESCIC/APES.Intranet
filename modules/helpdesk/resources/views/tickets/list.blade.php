<x-helpdesk::table-scroll :label="$label">
    <table class="sg-table">
        <thead>
            <tr>
                <th scope="col">Ticket</th>
                @if ($showRequester ?? false)<th scope="col">Raised by</th>@endif
                <th scope="col">Queue</th>
                <th scope="col">Priority</th>
                <th scope="col">Status</th>
                <th scope="col">Assigned to</th>
                <th scope="col">Updated</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($tickets as $ticket)
                <tr data-ticket="{{ $ticket->id }}">
                    <td>
                        <a href="{{ route('helpdesk.tickets.show', $ticket) }}">{{ $ticket->reference() }} · {{ $ticket->subject }}</a>
                        @if ($ticket->category)<span class="block text-sm text-sg-muted">{{ $ticket->category->name }}</span>@endif
                    </td>
                    @if ($showRequester ?? false)<td>{{ $ticket->requester?->name ?? '—' }}</td>@endif
                    <td>{{ $ticket->queueLabel() }}</td>
                    <td>{{ $ticket->priorityLabel() }}</td>
                    <td><span @class(['sg-badge', 'sg-badge--ok' => ! $ticket->isActive()])>{{ $ticket->statusLabel() }}</span></td>
                    <td>{{ $ticket->assignee?->name ?? 'Nobody yet' }}</td>
                    <td>{{ $ticket->updated_at->format('D j M H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="{{ ($showRequester ?? false) ? 7 : 6 }}" class="text-sg-muted">{{ $empty }}</td></tr>
            @endforelse
        </tbody>
    </table>
</x-helpdesk::table-scroll>
