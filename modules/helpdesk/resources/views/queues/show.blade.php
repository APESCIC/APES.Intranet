<x-layouts.app :title="$queueLabel.' queue'" subtitle="Tickets for this queue. Requesters only ever see their own tickets and your public replies.">
    @include('helpdesk::partials.flash')

    <nav class="mb-6 flex flex-wrap gap-2" aria-label="Filter the queue">
        @foreach ($views as $key => $label)
            <a
                href="{{ route('helpdesk.queues.'.$queue, ['view' => $key]) }}"
                @class(['sg-btn sg-btn-sm', 'sg-btn-primary' => $view === $key && ! $from, 'sg-btn-secondary' => $view !== $key || $from])
                @if ($view === $key && ! $from) aria-current="page" @endif
            >{{ $label }}</a>
        @endforeach
    </nav>

    <section class="sg-card p-6" aria-labelledby="queue-tickets">
        <h2 id="queue-tickets" class="mb-3 font-serif text-xl font-semibold">
            {{ $heading }}
            @if ($from && $to)
                <span class="block text-base font-normal text-sg-muted" data-period>Raised {{ $from->format('j M Y') }} to {{ $to->format('j M Y') }}</span>
            @endif
        </h2>
        @include('helpdesk::tickets.list', [
            'tickets' => $tickets,
            'label' => $queueLabel.' queue tickets',
            'empty' => 'No tickets here.',
            'showRequester' => true,
            'showSla' => true,
        ])
    </section>
</x-layouts.app>
