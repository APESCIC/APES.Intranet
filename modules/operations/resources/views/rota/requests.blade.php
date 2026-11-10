<x-layouts.app title="Claims and swaps" subtitle="Requests to change the rota. Each one needs a manager's approval.">
    @include('operations::rota.nav')
    @include('operations::partials.flash')

    @if ($toDecide->isNotEmpty())
        <section class="sg-card mb-6 p-6" aria-labelledby="decide-heading">
            <h2 id="decide-heading" class="mb-3 font-serif text-xl font-semibold">Waiting for your decision</h2>
            <ul class="grid gap-3">
                @foreach ($toDecide as $item)
                    <li class="rounded-sg-field border border-sg-info-mist p-3" data-request="{{ $item->id }}">
                        <p class="font-semibold">{{ $item->summary() }}</p>
                        @if ($item->note)<p class="text-sm">Note: {{ $item->note }}</p>@endif
                        <form method="post" action="{{ route('operations.rota.requests.decide', $item) }}" class="mt-2 flex flex-wrap items-center gap-2">
                            @csrf
                            <label class="sr-only" for="decision-note-{{ $item->id }}">Note to the members</label>
                            <input id="decision-note-{{ $item->id }}" name="decision_note" maxlength="500" class="sg-input" placeholder="Note (optional)">
                            <button type="submit" name="decision" value="approve" class="sg-btn sg-btn-primary sg-btn-sm">Approve</button>
                            <button type="submit" name="decision" value="decline" class="sg-btn sg-btn-secondary sg-btn-sm">Decline</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <section class="sg-card p-6" aria-labelledby="mine-heading">
        <h2 id="mine-heading" class="mb-3 font-serif text-xl font-semibold">My requests</h2>
        <ul class="grid gap-3">
            @forelse ($mine as $item)
                <li class="flex flex-wrap items-center justify-between gap-3 rounded-sg-field border border-sg-info-mist p-3">
                    <span>
                        <span class="font-semibold">{{ ucfirst($item->type) }}:</span>
                        {{ $item->shift->label() }} at {{ $item->shift->site->name }}
                        @if ($item->targetShift) for {{ $item->targetShift->label() }} ({{ $item->targetShift->member?->name }})@endif
                        @if ($item->decision_note)<br><span class="text-sm">Note: {{ $item->decision_note }}</span>@endif
                    </span>
                    <span class="flex items-center gap-2">
                        <span @class(['sg-badge', 'sg-badge--ok' => $item->status === 'approved', 'sg-badge--error' => $item->status === 'declined'])>{{ ucfirst($item->status) }}</span>
                        @if ($item->isPending())
                            <form method="post" action="{{ route('operations.rota.requests.cancel', $item) }}">
                                @csrf
                                <button type="submit" class="sg-btn sg-btn-ghost sg-btn-sm">Cancel</button>
                            </form>
                        @endif
                    </span>
                </li>
            @empty
                <li class="text-sg-muted">You have not asked for any changes.</li>
            @endforelse
        </ul>
    </section>
</x-layouts.app>
