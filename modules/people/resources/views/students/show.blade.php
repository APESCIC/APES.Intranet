<x-layouts.app :title="$placement->title" subtitle="Placement">
    @if (session('status'))
        <p class="mb-4 rounded-sg-field bg-sg-ok-mist p-3 text-sg-ok" role="status">{{ session('status') }}</p>
    @endif

    <article class="sg-card mb-6 max-w-3xl space-y-3 p-6">
        <p><span class="text-sg-muted">Student:</span> {{ $placement->student->name }}</p>
        <p><span class="text-sg-muted">Supervisor:</span> {{ $placement->supervisor?->name ?: 'Not set' }}</p>
        @if ($placement->guidance)
            <div>
                <h2 class="font-serif text-lg font-semibold">Guidance</h2>
                <p class="whitespace-pre-wrap">{{ $placement->guidance }}</p>
            </div>
        @endif
        <p class="font-semibold">Approved total: {{ intdiv($approvedMinutes, 60) }}h {{ $approvedMinutes % 60 }}m</p>
    </article>

    @if ((int) auth()->id() === (int) $placement->student_id)
        <form method="post" action="{{ route('people.students.hours.store', $placement) }}" class="sg-card mb-6 max-w-xl space-y-3 p-6">
            @csrf
            <h2 class="font-serif text-lg font-semibold">Log hours</h2>
            <div>
                <label class="mb-1 block text-sm font-semibold" for="worked_on">Date</label>
                <input id="worked_on" type="date" name="worked_on" value="{{ old('worked_on', now()->toDateString()) }}" class="w-full rounded-sg-field border border-sg-info-mist bg-white px-3 py-2" required>
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold" for="minutes">Minutes</label>
                <input id="minutes" type="number" name="minutes" min="15" max="720" step="15" value="{{ old('minutes', 60) }}" class="w-full rounded-sg-field border border-sg-info-mist bg-white px-3 py-2" required>
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold" for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="3" class="w-full rounded-sg-field border border-sg-info-mist bg-white px-3 py-2">{{ old('notes') }}</textarea>
            </div>
            <button type="submit" class="sg-btn sg-btn-primary sg-btn-sm">Submit for sign-off</button>
        </form>
    @endif

    <section class="sg-card max-w-3xl p-6" aria-labelledby="hours-heading">
        <h2 id="hours-heading" class="mb-3 font-serif text-lg font-semibold">Hours log</h2>
        <ul class="space-y-3">
            @foreach ($placement->hours as $hour)
                <li class="rounded-sg-field border border-sg-info-mist p-3" data-hour="{{ $hour->id }}">
                    <p class="font-semibold">{{ $hour->worked_on->toFormattedDateString() }} · {{ $hour->minutes }} minutes · {{ ucfirst($hour->status) }}</p>
                    @if ($hour->notes)<p class="text-sm">{{ $hour->notes }}</p>@endif
                    @can('review', $hour)
                        @if ($hour->status === 'pending')
                            <form method="post" action="{{ route('people.students.hours.review', $hour) }}" class="mt-2 flex flex-wrap gap-2">
                                @csrf
                                <input type="hidden" name="decision" value="approved">
                                <button class="sg-btn sg-btn-primary sg-btn-sm" type="submit">Approve</button>
                            </form>
                            <form method="post" action="{{ route('people.students.hours.review', $hour) }}" class="mt-2 flex flex-wrap gap-2">
                                @csrf
                                <input type="hidden" name="decision" value="returned">
                                <label class="sr-only" for="reviewer_notes_{{ $hour->id }}">Return notes</label>
                                <input id="reviewer_notes_{{ $hour->id }}" name="reviewer_notes" class="rounded-sg-field border border-sg-info-mist bg-white px-3 py-2" placeholder="Reason">
                                <button class="sg-btn sg-btn-secondary sg-btn-sm" type="submit">Return</button>
                            </form>
                        @endif
                    @endcan
                </li>
            @endforeach
        </ul>
    </section>
</x-layouts.app>
