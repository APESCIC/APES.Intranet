<x-layouts.app :title="ucfirst($checklist->kind).' — '.$checklist->user->name" subtitle="Tasks for HR, manager and IT.">
    @if (session('status'))
        <p class="mb-4 rounded-sg-field bg-sg-ok-mist p-3 text-sg-ok" role="status">{{ session('status') }}</p>
    @endif

    <p class="mb-4">
        <a class="text-sg-brand underline" href="{{ route('hr.checklists.index') }}">All checklists</a>
        · Status: {{ $checklist->status }}
        · Type: {{ $checklist->member_type }}
        @if ($checklist->it_ticket_id)
            · IT ticket #{{ $checklist->it_ticket_id }}
        @endif
    </p>

    <section class="sg-card p-6" aria-labelledby="tasks-heading">
        <h2 id="tasks-heading" class="mb-3 font-serif text-xl font-semibold">Tasks</h2>
        <ul class="space-y-3">
            @foreach ($checklist->items as $item)
                <li class="flex flex-wrap items-center justify-between gap-2 border-b border-sg-border/60 pb-3" data-item="{{ $item->id }}">
                    <span>
                        <span class="text-sg-muted uppercase text-xs tracking-wide">{{ $item->owner_role }}</span>
                        — {{ $item->title }}
                        @if ($item->isComplete())
                            <span class="text-sg-ok"> (done)</span>
                        @endif
                    </span>
                    @if (! $item->isComplete() && $checklist->status === 'open')
                        <form method="post" action="{{ route('hr.checklists.items.complete', $item) }}">
                            @csrf
                            <button class="sg-btn sg-btn-secondary sg-btn-sm" type="submit">Mark complete</button>
                        </form>
                    @endif
                </li>
            @endforeach
        </ul>
    </section>
</x-layouts.app>
