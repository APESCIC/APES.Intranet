<x-layouts.app title="Rota" subtitle="Your published shifts, and the rotas you look after.">
    @include('operations::rota.nav')
    @include('operations::partials.flash')

    <section class="sg-card mb-6 p-6" aria-labelledby="my-shifts">
        <h2 id="my-shifts" class="mb-3 font-serif text-xl font-semibold">My shifts</h2>
        @if ($myShifts->isEmpty())
            <p class="text-sg-muted">You have no published shifts coming up.</p>
        @else
            <div class="overflow-x-auto">
                <table class="sg-table">
                    <thead>
                        <tr><th scope="col">When</th><th scope="col">Role</th><th scope="col">Site</th><th scope="col"><span class="sr-only">Actions</span></th></tr>
                    </thead>
                    <tbody>
                        @foreach ($myShifts as $shift)
                            <tr data-shift="{{ $shift->id }}">
                                <td>{{ $shift->starts_at->format('D j M H:i') }}–{{ $shift->ends_at->format('H:i') }}</td>
                                <td>{{ $shift->role }}</td>
                                <td>{{ $shift->site->name }}</td>
                                <td><a class="sg-btn sg-btn-ghost sg-btn-sm" href="{{ route('operations.rota.swap.create', $shift) }}">Ask to swap<span class="sr-only"> {{ $shift->label() }}</span></a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    @if ($sites->isNotEmpty())
        <section class="sg-card mb-6 max-w-3xl p-6" aria-labelledby="new-week">
            <h2 id="new-week" class="mb-3 font-serif text-xl font-semibold">Create a week from templates</h2>
            <form method="post" action="{{ route('operations.rota.weeks.store') }}" class="grid gap-3 sm:grid-cols-3">
                @csrf
                <div>
                    <label class="sg-label" for="site_id">Site</label>
                    <select id="site_id" name="site_id" class="sg-input w-full" required>
                        @foreach ($sites as $site)
                            <option value="{{ $site->id }}">{{ $site->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="sg-label" for="week_of">Any day in the week</label>
                    <input id="week_of" type="date" name="week_of" class="sg-input w-full" value="{{ old('week_of', now()->addWeek()->startOfWeek()->toDateString()) }}" required>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="sg-btn sg-btn-primary sg-btn-sm">Create draft week</button>
                </div>
            </form>
        </section>

        <section class="sg-card p-6" aria-labelledby="weeks">
            <h2 id="weeks" class="mb-3 font-serif text-xl font-semibold">Rotas you look after</h2>
            @if ($weeks->isEmpty())
                <p class="text-sg-muted">No rotas yet.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="sg-table">
                        <thead>
                            <tr><th scope="col">Week of</th><th scope="col">Site</th><th scope="col">Shifts</th><th scope="col">Open</th><th scope="col">Status</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($weeks as $week)
                                <tr>
                                    <td><a href="{{ route('operations.rota.weeks.show', $week) }}">{{ $week->week_start->format('j M Y') }}</a></td>
                                    <td>{{ $week->site->name }}</td>
                                    <td>{{ $week->shifts_count }}</td>
                                    <td>{{ $week->open_shifts_count }}</td>
                                    <td><span @class(['sg-badge', 'sg-badge--ok' => $week->isPublished()])>{{ ucfirst($week->status) }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endif
</x-layouts.app>
