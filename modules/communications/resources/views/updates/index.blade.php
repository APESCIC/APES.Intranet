<x-layouts.app title="Updates" subtitle="News and announcements for your role.">
    @if ($canPublish)
        <p class="mb-4"><a class="sg-btn sg-btn-primary sg-btn-sm" href="{{ route('communications.updates.create') }}">Write an update</a></p>
    @endif
    <ul class="grid gap-4">
        @forelse ($posts as $post)
            <li class="sg-card p-5">
                <h2 class="font-serif text-xl font-semibold">
                    <a href="{{ route('communications.updates.show', $post) }}">{{ $post->title }}</a>
                    @if ($post->pinned)<span class="text-sm text-sg-muted"> · Pinned</span>@endif
                </h2>
                <p class="mt-1 text-sm text-sg-muted">{{ $post->published_at?->timezone(config('app.timezone'))->format('j M Y') }} · {{ $post->author?->name }}</p>
            </li>
        @empty
            <li class="text-sg-muted">No updates yet.</li>
        @endforelse
    </ul>
</x-layouts.app>
