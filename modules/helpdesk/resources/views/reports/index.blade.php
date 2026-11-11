<x-layouts.app title="Help desk reports" subtitle="Tickets raised in the period, for the queues you work. Each number opens the matching tickets.">
    @include('helpdesk::partials.flash')

    <form method="get" action="{{ route('helpdesk.reports.index') }}" class="sg-card mb-6 flex flex-wrap items-end gap-3 p-6">
        <div>
            <label class="sg-label" for="from">From</label>
            <input id="from" type="date" name="from" class="sg-input" value="{{ $from->toDateString() }}" required>
        </div>
        <div>
            <label class="sg-label" for="to">To</label>
            <input id="to" type="date" name="to" class="sg-input" value="{{ $to->toDateString() }}" required>
        </div>
        <button type="submit" class="sg-btn sg-btn-primary sg-btn-sm">Show</button>
        <a class="sg-btn sg-btn-secondary sg-btn-sm" href="{{ route('helpdesk.reports.csv', ['from' => $from->toDateString(), 'to' => $to->toDateString()]) }}">Download CSV</a>
    </form>

    <section class="sg-card p-6" aria-labelledby="report-heading">
        <h2 id="report-heading" class="mb-3 font-serif text-xl font-semibold">Raised {{ $from->format('j M Y') }} to {{ $to->format('j M Y') }}</h2>
        <x-helpdesk::table-scroll label="Help desk report">
            <table class="sg-table">
                <thead>
                    <tr>
                        <th scope="col">Queue</th>
                        <th scope="col">Raised</th>
                        <th scope="col">Still open</th>
                        <th scope="col">Resolved or closed</th>
                        <th scope="col">Missed an SLA target</th>
                        <th scope="col">Average first reply</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $queue => $row)
                        <tr>
                            <th scope="row">{{ \Modules\Helpdesk\Models\Ticket::QUEUES[$queue] }}</th>
                            @foreach (\Modules\Helpdesk\Services\Reports::VIEWS as $figure => $view)
                                <td>
                                    <a href="{{ route('helpdesk.queues.'.$queue, ['view' => $view, 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}" data-count="{{ $queue }}-{{ $figure }}">{{ $row[$figure] }}<span class="sr-only"> {{ \Modules\Helpdesk\Models\Ticket::QUEUES[$queue] }} tickets: {{ \Modules\Helpdesk\Services\Reports::LABELS[$figure] }}</span></a>
                                </td>
                            @endforeach
                            <td>{{ $row['average_first_reply_hours'] === null ? '—' : $row['average_first_reply_hours'].' hours' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-helpdesk::table-scroll>
    </section>
</x-layouts.app>
