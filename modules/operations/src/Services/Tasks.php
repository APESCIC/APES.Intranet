<?php

namespace Modules\Operations\Services;

use App\Core\Audit\AuditLogger;
use App\Core\Support\HtmlSanitizer;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Operations\Models\Task;
use Modules\Operations\Models\TaskComment;
use Modules\Operations\Support\Notifier;

final class Tasks
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly HtmlSanitizer $sanitizer,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $creator): Task
    {
        $task = Task::query()->create([
            'title' => $data['title'],
            'description' => $this->sanitizer->clean($data['description'] ?? ''),
            'assignee_type' => $data['assignee_type'],
            'user_id' => $data['assignee_type'] === Task::ASSIGN_PERSON ? $data['user_id'] : null,
            'team' => $data['assignee_type'] === Task::ASSIGN_TEAM ? $data['team'] : null,
            'shift_id' => $data['assignee_type'] === Task::ASSIGN_SHIFT ? $data['shift_id'] : null,
            'due_at' => $data['due_at'] ?? null,
            'recurrence' => $data['recurrence'] ?? null,
            'status' => Task::STATUS_OPEN,
            'created_by' => $creator->getKey(),
        ]);

        $this->audit->record('operations.task_created', $task, ['assignee_type' => $task->assignee_type], $creator);

        if ($task->assignee_type === Task::ASSIGN_PERSON) {
            Notifier::send([$task->assignee], 'New task: '.$task->title, [$creator->name.' gave you a task: '.$task->title.'.'], route('operations.tasks.show', $task));
        }

        return $task;
    }

    /**
     * Completing a recurring task creates the next one.
     */
    public function changeStatus(Task $task, string $status, User $actor): ?Task
    {
        return DB::transaction(function () use ($task, $status, $actor) {
            $before = $task->status;
            $task->forceFill([
                'status' => $status,
                'completed_at' => $status === Task::STATUS_DONE ? now() : null,
                'completed_by' => $status === Task::STATUS_DONE ? $actor->getKey() : null,
            ])->save();

            $this->audit->record('operations.task_status_changed', $task, ['from' => $before, 'to' => $status], $actor);

            if ($status !== Task::STATUS_DONE || $task->recurrence === null || $task->nextTask()->exists()) {
                return null;
            }

            $next = $task->replicate(['status', 'completed_at', 'completed_by', 'previous_task_id']);
            $next->forceFill([
                'status' => Task::STATUS_OPEN,
                'due_at' => $this->nextDue($task->due_at ?? CarbonImmutable::now(), $task->recurrence),
                'previous_task_id' => $task->getKey(),
            ])->save();

            $this->audit->record('operations.task_recurred', $next, ['previous_task_id' => $task->getKey()], $actor);

            return $next;
        });
    }

    public function comment(Task $task, User $author, string $body): TaskComment
    {
        return $task->comments()->create([
            'user_id' => $author->getKey(),
            'body' => $this->sanitizer->clean($body),
        ]);
    }

    /**
     * A task completed late moves on to the next date still in the future.
     */
    private function nextDue(CarbonImmutable $due, string $recurrence): CarbonImmutable
    {
        do {
            $due = match ($recurrence) {
                'weekly' => $due->addWeek(),
                'monthly' => $due->addMonthNoOverflow(),
                default => $due->addDay(),
            };
        } while ($due->isPast());

        return $due;
    }
}
