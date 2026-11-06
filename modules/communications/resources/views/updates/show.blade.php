<x-layouts.app :title="$post->title">
    <article class="sg-card max-w-3xl p-5">
        <p class="text-sm text-sg-muted">{{ $post->published_at?->timezone(config('app.timezone'))->format('j M Y g:ia') }} · {{ $post->author?->name }}</p>
        <div class="prose mt-4 max-w-none">{!! $post->body !!}</div>
        <p class="mt-4 text-sm text-sg-muted">Read by {{ $readCount }} {{ $readCount === 1 ? 'person' : 'people' }}.</p>
    </article>
    @if ($post->comments_enabled)
        <section class="mt-6 max-w-3xl" aria-labelledby="comments-heading">
            <h2 id="comments-heading" class="font-serif text-xl font-semibold">Comments</h2>
            <ul class="mt-3 grid gap-3">
                @foreach ($post->comments as $comment)
                    <li class="sg-card p-4">
                        <p class="text-sm font-semibold">{{ $comment->user?->name }}</p>
                        <p class="mt-1">{{ $comment->body }}</p>
                    </li>
                @endforeach
            </ul>
            <form method="post" action="{{ route('communications.updates.comment', $post) }}" class="mt-4 grid gap-2">
                @csrf
                <label class="sr-only" for="comment-body">Comment</label>
                <textarea id="comment-body" name="body" class="sg-input" rows="3" required></textarea>
                <button class="sg-btn sg-btn-secondary sg-btn-sm" type="submit">Add comment</button>
            </form>
        </section>
    @endif
</x-layouts.app>
