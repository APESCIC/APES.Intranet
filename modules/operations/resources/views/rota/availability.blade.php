<x-layouts.app title="My unavailability" subtitle="Tell rota builders when you cannot work.">
    @include('operations::rota.nav')
    @include('operations::partials.flash')

    <section class="sg-card mb-6 max-w-3xl p-6" aria-labelledby="add-period">
        <h2 id="add-period" class="mb-3 font-serif text-xl font-semibold">Add a time you cannot work</h2>
        <form method="post" action="{{ route('operations.rota.availability.store') }}" class="grid gap-3 sm:grid-cols-2">
            @csrf
            <div>
                <label class="sg-label" for="starts_at">From</label>
                <input id="starts_at" type="datetime-local" name="starts_at" class="sg-input w-full" value="{{ old('starts_at') }}" required>
            </div>
            <div>
                <label class="sg-label" for="ends_at">Until</label>
                <input id="ends_at" type="datetime-local" name="ends_at" class="sg-input w-full" value="{{ old('ends_at') }}" required>
            </div>
            <div class="sm:col-span-2">
                <label class="sg-label" for="note">Note (optional, seen by rota builders)</label>
                <input id="note" name="note" maxlength="200" class="sg-input w-full" value="{{ old('note') }}">
            </div>
            <div class="sm:col-span-2">
                <button type="submit" class="sg-btn sg-btn-primary sg-btn-sm">Save</button>
            </div>
        </form>
        <p class="mt-3 text-sm text-sg-muted">Do not give health or other sensitive reasons here.</p>
    </section>

    <section class="sg-card max-w-3xl p-6" aria-labelledby="periods-heading">
        <h2 id="periods-heading" class="mb-3 font-serif text-xl font-semibold">Coming up</h2>
        <ul class="grid gap-2">
            @forelse ($periods as $period)
                <li class="flex flex-wrap items-center justify-between gap-2 rounded-sg-field border border-sg-info-mist p-3">
                    <span>{{ $period->starts_at->format('D j M H:i') }} to {{ $period->ends_at->format('D j M H:i') }}@if ($period->note) · {{ $period->note }}@endif</span>
                    <form method="post" action="{{ route('operations.rota.availability.destroy', $period) }}">
                        @csrf
                        @method('delete')
                        <button type="submit" class="sg-btn sg-btn-ghost sg-btn-sm">Remove</button>
                    </form>
                </li>
            @empty
                <li class="text-sg-muted">Nothing set.</li>
            @endforelse
        </ul>
    </section>
</x-layouts.app>
