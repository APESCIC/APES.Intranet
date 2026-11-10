<x-layouts.app title="Timesheets" subtitle="Weekly timesheets built from your clock records.">
    @include('operations::timesheets.nav')
    @include('operations::partials.flash')

    <section class="sg-card mb-6 max-w-xl p-6" aria-labelledby="build-heading">
        <h2 id="build-heading" class="mb-3 font-serif text-xl font-semibold">Build a timesheet</h2>
        <form method="post" action="{{ route('operations.timesheets.build') }}" class="flex flex-wrap items-end gap-3">
            @csrf
            <div>
                <label class="sg-label" for="week_of">Any day in the week</label>
                <input id="week_of" type="date" name="week_of" max="{{ now()->toDateString() }}" class="sg-input" value="{{ old('week_of', now()->toDateString()) }}" required>
            </div>
            <button type="submit" class="sg-btn sg-btn-primary sg-btn-sm">Build from clock records</button>
        </form>
    </section>

    <section class="sg-card max-w-3xl p-6" aria-labelledby="mine-heading">
        <h2 id="mine-heading" class="mb-3 font-serif text-xl font-semibold">My timesheets</h2>
        <x-operations::table-scroll label="My timesheets">
            <table class="sg-table">
                <thead>
                    <tr><th scope="col">Week of</th><th scope="col">Hours</th><th scope="col">Status</th></tr>
                </thead>
                <tbody>
                    @forelse ($timesheets as $timesheet)
                        <tr>
                            <td><a href="{{ route('operations.timesheets.show', $timesheet) }}">{{ $timesheet->period_start->format('j M Y') }}</a></td>
                            <td>{{ $timesheet->hoursLabel() }}</td>
                            <td>@include('operations::timesheets.status', ['timesheet' => $timesheet])</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-sg-muted">No timesheets yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-operations::table-scroll>
    </section>
</x-layouts.app>
