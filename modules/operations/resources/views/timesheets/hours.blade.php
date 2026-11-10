<x-layouts.app title="Volunteer hours" subtitle="Approved volunteer timesheets, totalled per person.">
    @include('operations::timesheets.nav')
    @include('operations::partials.flash')

    <form method="get" action="{{ route('operations.timesheets.hours') }}" class="sg-card mb-6 flex max-w-3xl flex-wrap items-end gap-3 p-6">
        <div>
            <label class="sg-label" for="from">From</label>
            <input id="from" type="date" name="from" class="sg-input" value="{{ $from->toDateString() }}">
        </div>
        <div>
            <label class="sg-label" for="to">To</label>
            <input id="to" type="date" name="to" class="sg-input" value="{{ $to->toDateString() }}">
        </div>
        <button type="submit" class="sg-btn sg-btn-secondary sg-btn-sm">Show</button>
        <a class="sg-btn sg-btn-primary sg-btn-sm" href="{{ route('operations.timesheets.hours.csv', ['from' => $from->toDateString(), 'to' => $to->toDateString()]) }}">Download CSV</a>
    </form>

    <section class="sg-card max-w-3xl p-6" aria-labelledby="totals-heading">
        <h2 id="totals-heading" class="mb-3 font-serif text-xl font-semibold">Totals for timesheets starting {{ $from->startOfWeek()->format('j M Y') }} to {{ $to->format('j M Y') }}</h2>
        <div class="overflow-x-auto">
            <table class="sg-table">
                <thead>
                    <tr><th scope="col">Volunteer</th><th scope="col">Team</th><th scope="col">Approved timesheets</th><th scope="col">Hours</th></tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr data-volunteer="{{ $row['user']->id }}">
                            <td>{{ $row['user']->name }}</td>
                            <td>{{ $row['user']->team ?? '—' }}</td>
                            <td>{{ $row['timesheets'] }}</td>
                            <td data-hours>{{ \Modules\Operations\Services\VolunteerHours::hours($row['minutes']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-sg-muted">No approved volunteer timesheets in this range.</td></tr>
                    @endforelse
                </tbody>
                @if ($rows->isNotEmpty())
                    <tfoot>
                        <tr>
                            <th scope="row" colspan="2">Total</th>
                            <td>{{ $rows->sum('timesheets') }}</td>
                            <td data-total-hours>{{ \Modules\Operations\Services\VolunteerHours::hours((int) $rows->sum('minutes')) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </section>
</x-layouts.app>
