<div class="overflow-x-auto">
    <table class="sg-table">
        <thead>
            <tr><th scope="col">Task</th><th scope="col">For</th><th scope="col">Due</th><th scope="col">Status</th></tr>
        </thead>
        <tbody>
            @forelse ($tasks as $task)
                <tr data-task="{{ $task->id }}">
                    <td>
                        <a href="{{ route('operations.tasks.show', $task) }}">{{ $task->title }}</a>
                        @if ($task->recurrence)<span class="sg-badge">{{ \Modules\Operations\Models\Task::RECURRENCES[$task->recurrence] }}</span>@endif
                    </td>
                    <td>{{ $task->assigneeLabel() }}</td>
                    <td>
                        {{ $task->due_at?->format('D j M H:i') ?? '—' }}
                        @if ($task->isOverdue())<span class="sg-badge sg-badge--error" data-overdue>Overdue</span>@endif
                    </td>
                    <td><span @class(['sg-badge', 'sg-badge--ok' => $task->isDone()])>{{ \Modules\Operations\Models\Task::STATUSES[$task->status] }}</span></td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-sg-muted">{{ $empty }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
