<x-layouts.app title="Timesheets to approve" subtitle="Submitted timesheets from the teams you look after.">
    @include('operations::timesheets.nav')
    @include('operations::partials.flash')

    <section class="sg-card max-w-3xl p-6" aria-labelledby="review-heading">
        <h2 id="review-heading" class="sr-only">Waiting for approval</h2>
        <x-operations::table-scroll label="Timesheets to approve">
            <table class="sg-table">
                <thead>
                    <tr><th scope="col">Member</th><th scope="col">Week of</th><th scope="col">Hours</th></tr>
                </thead>
                <tbody>
                    @forelse ($timesheets as $timesheet)
                        <tr>
                            <td><a href="{{ route('operations.timesheets.show', $timesheet) }}">{{ $timesheet->member->name }}</a></td>
                            <td>{{ $timesheet->period_start->format('j M Y') }}</td>
                            <td>{{ $timesheet->hoursLabel() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-sg-muted">Nothing waiting for you.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-operations::table-scroll>
    </section>
</x-layouts.app>
