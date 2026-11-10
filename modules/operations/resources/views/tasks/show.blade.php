<x-layouts.app :title="$task->title" :subtitle="$task->assigneeLabel()">
    @include('operations::partials.flash')

    <article class="sg-card mb-6 max-w-3xl space-y-3 p-6">
        <p class="flex flex-wrap items-center gap-2">
            <span @class(['sg-badge', 'sg-badge--ok' => $task->isDone()])>{{ \Modules\Operations\Models\Task::STATUSES[$task->status] }}</span>
            @if ($task->isOverdue())<span class="sg-badge sg-badge--error">Overdue</span>@endif
            @if ($task->recurrence)<span class="sg-badge">{{ \Modules\Operations\Models\Task::RECURRENCES[$task->recurrence] }}</span>@endif
        </p>
        <p><span class="text-sg-muted">Due:</span> {{ $task->due_at?->format('l j F Y H:i') ?? 'No due date' }}</p>
        <p><span class="text-sg-muted">Added by:</span> {{ $task->creator?->name ?? '—' }}</p>
        @if ($task->description)
            <div class="prose max-w-none">{!! $task->description !!}</div>
        @endif
        @if ($task->nextTask)
            <p><a href="{{ route('operations.tasks.show', $task->nextTask) }}">Next one, due {{ $task->nextTask->due_at?->format('D j M H:i') }}</a></p>
        @endif

        @if ($canChangeStatus)
            <form method="post" action="{{ route('operations.tasks.status', $task) }}" class="flex flex-wrap items-end gap-2">
                @csrf
                @method('patch')
                <div>
                    <label class="sg-label" for="status">Status</label>
                    <select id="status" name="status" class="sg-input">
                        @foreach (\Modules\Operations\Models\Task::STATUSES as $value => $label)
                            <option value="{{ $value }}" @selected($task->status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="sg-btn sg-btn-primary sg-btn-sm">Save status</button>
            </form>
        @endif
    </article>

    <section class="sg-card max-w-3xl p-6" aria-labelledby="comments-heading">
        <h2 id="comments-heading" class="mb-3 font-serif text-xl font-semibold">Comments</h2>
        <ul class="mb-4 grid gap-3">
            @forelse ($task->comments as $comment)
                <li class="rounded-sg-field border border-sg-info-mist p-3">
                    <p class="text-sm text-sg-muted">{{ $comment->author->name }} · {{ $comment->created_at->format('D j M H:i') }}</p>
                    <div class="prose max-w-none">{!! $comment->body !!}</div>
                </li>
            @empty
                <li class="text-sg-muted">No comments yet.</li>
            @endforelse
        </ul>
        <form method="post" action="{{ route('operations.tasks.comment', $task) }}" class="grid gap-3">
            @csrf
            <x-editor.tiptap name="body" label="Add a comment" required />
            <div><button type="submit" class="sg-btn sg-btn-secondary sg-btn-sm">Post comment</button></div>
        </form>
    </section>
</x-layouts.app>
