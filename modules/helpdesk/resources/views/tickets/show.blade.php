<x-layouts.app :title="$ticket->reference().' · '.$ticket->subject" :subtitle="$ticket->queueLabel().' queue'">
    @include('helpdesk::partials.flash')

    <div class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
        <div class="grid content-start gap-6">
            <article class="sg-card space-y-2 p-6" aria-label="Ticket details">
                <p class="flex flex-wrap items-center gap-2">
                    <span @class(['sg-badge', 'sg-badge--ok' => ! $ticket->isActive()])>{{ $ticket->statusLabel() }}</span>
                    <span class="sg-badge">{{ $ticket->priorityLabel() }} priority</span>
                </p>
                <p><span class="text-sg-muted">Topic:</span> {{ $ticket->category?->name ?? '—' }}</p>
                <p><span class="text-sg-muted">Raised by:</span> {{ $ticket->requester?->name ?? '—' }} on {{ $ticket->created_at->format('l j F Y H:i') }}</p>
                <p><span class="text-sg-muted">Assigned to:</span> {{ $ticket->assignee?->name ?? 'Nobody yet' }}</p>
                @if ($ticket->mergedInto && $canWork)
                    <p><span class="text-sg-muted">Merged into:</span> <a href="{{ route('helpdesk.tickets.show', $ticket->mergedInto) }}">{{ $ticket->mergedInto->reference() }}</a></p>
                @endif
                @if ($canWork && $ticket->response_due_at)
                    <p data-sla="response">
                        <span class="text-sg-muted">First reply due:</span> {{ $ticket->response_due_at->format('D j M H:i') }}
                        @if ($ticket->first_responded_at) · replied {{ $ticket->first_responded_at->format('D j M H:i') }}@endif
                        @if ($ticket->responseBreached())<span class="sg-badge sg-badge--error">Missed</span>@endif
                    </p>
                    <p data-sla="resolve">
                        <span class="text-sg-muted">Resolve by:</span> {{ $ticket->resolve_due_at->format('D j M H:i') }}
                        @if ($ticket->resolveBreached())<span class="sg-badge sg-badge--error">Missed</span>@endif
                    </p>
                @endif
            </article>

            <section class="sg-card p-6" aria-labelledby="conversation-heading">
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

            @if ($canWork)
                <section class="sg-card p-6" aria-labelledby="note-heading">
                    <h2 id="note-heading" class="mb-3 font-serif text-xl font-semibold">Private note</h2>
                    <form method="post" action="{{ route('helpdesk.tickets.note', $ticket) }}" class="grid gap-3">
                        @csrf
                        <x-editor.tiptap name="note" label="Note for the queue" required />
                        <p class="text-sm text-sg-muted">Only people who work the {{ $ticket->queueLabel() }} queue see private notes. The requester never does.</p>
                        <div><button type="submit" class="sg-btn sg-btn-secondary sg-btn-sm">Add private note</button></div>
                    </form>
                </section>
            @endif
        </div>

        @if ($canWork)
            <aside class="grid content-start gap-6" aria-label="Work on this ticket">
                <form method="post" action="{{ route('helpdesk.tickets.assign', $ticket) }}" class="sg-card grid gap-3 p-6">
                    @csrf
                    @method('patch')
                    <label class="sg-label" for="assignee_id">Assigned to</label>
                    <select id="assignee_id" name="assignee_id" class="sg-input w-full">
                        <option value="">Nobody</option>
                        @foreach ($agents as $agent)
                            <option value="{{ $agent->id }}" @selected((int) $ticket->assignee_id === $agent->id)>{{ $agent->name }}{{ $agent->is(auth()->user()) ? ' (me)' : '' }}</option>
                        @endforeach
                    </select>
                    <div><button type="submit" class="sg-btn sg-btn-secondary sg-btn-sm">Save assignment</button></div>
                </form>

                <form method="post" action="{{ route('helpdesk.tickets.status', $ticket) }}" class="sg-card grid gap-3 p-6">
                    @csrf
                    @method('patch')
                    <label class="sg-label" for="status">Status</label>
                    <select id="status" name="status" class="sg-input w-full">
                        @foreach (\Modules\Helpdesk\Models\Ticket::STATUSES as $value => $label)
                            <option value="{{ $value }}" @selected($ticket->status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <div><button type="submit" class="sg-btn sg-btn-secondary sg-btn-sm">Save status</button></div>
                </form>

                <form method="post" action="{{ route('helpdesk.tickets.reclassify', $ticket) }}" class="sg-card grid gap-3 p-6">
                    @csrf
                    @method('patch')
                    <div>
                        <label class="sg-label" for="category_id">Topic</label>
                        <select id="category_id" name="category_id" class="sg-input w-full" aria-describedby="topic-help">
                            @foreach ($categories as $queue => $group)
                                <optgroup label="{{ \Modules\Helpdesk\Models\Ticket::QUEUES[$queue] ?? $queue }}">
                                    @foreach ($group as $category)
                                        <option value="{{ $category->id }}" @selected((int) $ticket->category_id === $category->id)>{{ $category->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <p id="topic-help" class="mt-1 text-sm text-sg-muted">A topic in another queue moves the ticket there.</p>
                    </div>
                    <div>
                        <label class="sg-label" for="priority">Priority</label>
                        <select id="priority" name="priority" class="sg-input w-full">
                            @foreach (\Modules\Helpdesk\Models\Ticket::PRIORITIES as $value => $label)
                                <option value="{{ $value }}" @selected($ticket->priority === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div><button type="submit" class="sg-btn sg-btn-secondary sg-btn-sm">Save topic and priority</button></div>
                </form>

                @unless ($ticket->isClosed())
                    <form method="post" action="{{ route('helpdesk.tickets.merge', $ticket) }}" class="sg-card grid gap-3 p-6">
                        @csrf
                        <label class="sg-label" for="target">Merge into another ticket</label>
                        <input id="target" name="target" class="sg-input w-full" placeholder="HD-0012" value="{{ old('target') }}" required aria-describedby="target-help">
                        <p id="target-help" class="text-sm text-sg-muted">This ticket closes and the requester is told where the request carries on.</p>
                        <div><button type="submit" class="sg-btn sg-btn-secondary sg-btn-sm">Merge duplicate</button></div>
                    </form>
                @endunless
            </aside>
        @endif
    </div>
</x-layouts.app>
