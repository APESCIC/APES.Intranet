<x-layouts.app :title="$ticket->reference().' · '.$ticket->subject" :subtitle="$ticket->queueLabel().' queue'">
    @include('helpdesk::partials.flash')

    <article class="sg-card mb-6 max-w-3xl space-y-2 p-6" aria-label="Ticket details">
        <p class="flex flex-wrap items-center gap-2">
            <span @class(['sg-badge', 'sg-badge--ok' => ! $ticket->isActive()])>{{ $ticket->statusLabel() }}</span>
            <span class="sg-badge">{{ $ticket->priorityLabel() }} priority</span>
        </p>
        <p><span class="text-sg-muted">Topic:</span> {{ $ticket->category?->name ?? '—' }}</p>
        <p><span class="text-sg-muted">Raised by:</span> {{ $ticket->requester?->name ?? '—' }} on {{ $ticket->created_at->format('l j F Y H:i') }}</p>
        <p><span class="text-sg-muted">Assigned to:</span> {{ $ticket->assignee?->name ?? 'Nobody yet' }}</p>
    </article>

    <section class="sg-card max-w-3xl p-6" aria-labelledby="conversation-heading">
        <h2 id="conversation-heading" class="mb-3 font-serif text-xl font-semibold">Conversation</h2>
        <ul class="mb-4 grid gap-3">
            @foreach ($messages as $message)
                <li @class(['rounded-sg-field border p-3', 'border-sg-warn bg-sg-warn-mist' => $message->is_private, 'border-sg-info-mist' => ! $message->is_private]) data-message="{{ $message->id }}" @if ($message->is_private) data-private @endif>
                    <p class="text-sm text-sg-muted">
                        {{ $message->author?->name ?? 'Help desk' }} · {{ $message->created_at->format('D j M H:i') }}
                        @if ($message->is_private)<span class="sg-badge sg-badge--warn">Private note</span>@endif
                    </p>
                    <div class="prose max-w-none">{!! $message->body !!}</div>
                </li>
            @endforeach
        </ul>

        @if ($canReply)
            <form method="post" action="{{ route('helpdesk.tickets.reply', $ticket) }}" class="grid gap-3">
                @csrf
                <x-editor.tiptap name="body" label="Reply" required />
                <p class="text-sm text-sg-muted">{{ $canWork ? 'The requester sees this reply.' : 'The help desk sees this reply.' }}</p>
                <div><button type="submit" class="sg-btn sg-btn-primary sg-btn-sm">Send reply</button></div>
            </form>
        @else
            <p class="text-sg-muted">This ticket is closed. Raise a new ticket if you still need help.</p>
        @endif
    </section>
</x-layouts.app>
