<x-layouts.app :title="$event->title">
    <article class="sg-card max-w-3xl p-5">
        <p class="text-sm text-sg-muted">{{ $event->starts_at->timezone(config('app.timezone'))->format('D j M Y g:ia') }}@if($event->location) · {{ $event->location }}@endif</p>
        <div class="prose mt-4 max-w-none">{!! $event->body !!}</div>
        <div class="mt-4 flex flex-wrap gap-2">
            <a class="sg-btn sg-btn-secondary sg-btn-sm" href="{{ route('communications.events.ical', $event) }}">Download iCal</a>
        </div>
        <form method="post" action="{{ route('communications.events.rsvp', $event) }}" class="mt-6 flex flex-wrap gap-2">
            @csrf
            @foreach (['going' => 'Going', 'maybe' => 'Maybe', 'declined' => 'Cannot attend'] as $value => $label)
                <button class="sg-btn sg-btn-sm {{ ($rsvp?->status === $value) ? 'sg-btn-primary' : 'sg-btn-secondary' }}" name="status" value="{{ $value }}" type="submit">{{ $label }}</button>
            @endforeach
        </form>
    </article>
</x-layouts.app>
