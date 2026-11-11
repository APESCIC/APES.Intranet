<x-layouts.app :title="$queueLabel.' queue'" subtitle="Tickets for this queue. Requesters only ever see their own tickets and your public replies.">
    @include('helpdesk::partials.flash')

    <nav class="mb-6 flex flex-wrap gap-2" aria-label="Filter the queue">
        @foreach ($views as $key => $label)
            <a
                href="{{ route('helpdesk.queues.'.$queue, ['view' => $key]) }}"
                @class(['sg-btn sg-btn-sm', 'sg-btn-primary' => $view === $key, 'sg-btn-secondary' => $view !== $key])
                @if ($view === $key) aria-current="page" @endif
            >{{ $label }}</a>
        @endforeach
    </nav>

    <section class="sg-card p-6" aria-labelledby="queue-tickets">
        <h2 id="queue-tickets" class="mb-3 font-serif text-xl font-semibold">{{ $views[$view] }}</h2>
        @include('helpdesk::tickets.list', [
            'tickets' => $tickets,
            'label' => $queueLabel.' queue tickets',
            'empty' => 'No tickets here.',
            'showRequester' => true,
        ])
    </section>
</x-layouts.app>
