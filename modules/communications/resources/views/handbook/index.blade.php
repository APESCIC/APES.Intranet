<x-layouts.app title="Handbook" subtitle="Staff, volunteer and student guidance.">
    <form method="get" class="mb-4 flex gap-2" role="search">
        <label class="sr-only" for="handbook-q">Search handbook</label>
        <input id="handbook-q" class="sg-input" type="search" name="q" value="{{ $q }}" placeholder="Search chapters">
        <button class="sg-btn sg-btn-secondary" type="submit">Search</button>
    </form>
    @if ($canPublish)
        <p class="mb-4"><a class="sg-btn sg-btn-primary sg-btn-sm" href="{{ route('communications.handbook.create') }}">New chapter</a></p>
    @endif
    <ul class="grid gap-3">
        @forelse ($chapters as $chapter)
            <li><a class="font-semibold text-sg-teal underline" href="{{ route('communications.handbook.show', $chapter) }}">{{ $chapter->title }}</a></li>
        @empty
            <li class="text-sg-muted">No published chapters yet.</li>
        @endforelse
    </ul>
</x-layouts.app>
