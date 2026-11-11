<x-layouts.app title="Help desk" subtitle="Ask IT or Management for help, and follow your requests here.">
    @include('helpdesk::partials.flash')

    <div class="mb-6 flex flex-wrap gap-2">
        <a class="sg-btn sg-btn-primary sg-btn-sm" href="{{ route('helpdesk.tickets.create') }}">
            <i data-lucide="plus" aria-hidden="true"></i>
            Raise a ticket
        </a>
    </div>

    <section class="sg-card p-6" aria-labelledby="my-tickets">
        <h2 id="my-tickets" class="mb-3 font-serif text-xl font-semibold">My tickets</h2>
        @include('helpdesk::tickets.list', ['tickets' => $tickets, 'label' => 'My tickets', 'empty' => 'You have not raised any tickets yet.'])
    </section>
</x-layouts.app>
