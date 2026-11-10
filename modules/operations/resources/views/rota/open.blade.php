<x-layouts.app title="Open shifts" subtitle="Shifts with nobody on them yet. A manager approves each claim.">
    @include('operations::rota.nav')
    @include('operations::partials.flash')

    <section class="sg-card p-6" aria-labelledby="open-heading">
        <h2 id="open-heading" class="sr-only">Open shifts</h2>
        @if ($shifts->isEmpty())
            <p class="text-sg-muted">There are no open shifts right now.</p>
        @else
            <ul class="grid gap-3">
                @foreach ($shifts as $shift)
                    <li class="flex flex-wrap items-center justify-between gap-3 rounded-sg-field border border-sg-info-mist p-3" data-open-shift="{{ $shift->id }}">
                        <span><span class="font-semibold">{{ $shift->label() }}</span> · {{ $shift->site->name }}</span>
                        @if ($pendingShiftIds->contains($shift->id))
                            <span class="sg-badge">Claim waiting for approval</span>
                        @else
                            <form method="post" action="{{ route('operations.rota.claim', $shift) }}" class="flex flex-wrap items-center gap-2">
                                @csrf
                                <label class="sr-only" for="note-{{ $shift->id }}">Note for the manager</label>
                                <input id="note-{{ $shift->id }}" name="note" maxlength="500" class="sg-input" placeholder="Note (optional)">
                                <button type="submit" class="sg-btn sg-btn-primary sg-btn-sm">Claim<span class="sr-only"> {{ $shift->label() }}</span></button>
                            </form>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</x-layouts.app>
