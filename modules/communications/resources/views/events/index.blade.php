<x-layouts.app title="Events" subtitle="Calendar of upcoming APES events.">
    @if ($canPublish)
        <p class="mb-4"><a class="sg-btn sg-btn-primary sg-btn-sm" href="{{ route('communications.events.create') }}">Add an event</a></p>
    @endif
    <ul class="grid gap-4">
        @forelse ($events as $event)
            <li class="sg-card p-5">
                <h2 class="font-serif text-xl font-semibold"><a href="{{ route('communications.events.show', $event) }}">{{ $event->title }}</a></h2>
                <p class="mt-1 text-sm text-sg-muted">{{ $event->starts_at->timezone(config('app.timezone'))->format('D j M Y g:ia') }}@if($event->location) · {{ $event->location }}@endif</p>
            </li>
        @empty
            <li class="text-sg-muted">No events yet.</li>
        @endforelse
    </ul>
</x-layouts.app>
