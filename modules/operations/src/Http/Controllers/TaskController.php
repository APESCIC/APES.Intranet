<?php

namespace Modules\Operations\Http\Controllers;

use App\Core\Access\AccessMatrix;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Operations\Models\Shift;
use Modules\Operations\Models\Task;
use Modules\Operations\Services\Tasks;

class TaskController
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $showDone = $request->boolean('done');

        $mine = Task::query()
            ->with(['assignee', 'shift'])
            ->assignedTo($user)
            ->when(! $showDone, fn ($q) => $q->where('status', '!=', Task::STATUS_DONE))
            ->orderByRaw('due_at is null')
            ->orderBy('due_at')
            ->limit(200)
            ->get();

        $managed = $user->can(AccessMatrix::TEAM_SUPERVISE)
            ? Task::query()
                ->with(['assignee', 'shift.site'])
                ->when(! $showDone, fn ($q) => $q->where('status', '!=', Task::STATUS_DONE))
                ->whereNotIn('id', $mine->pluck('id'))
                ->orderByRaw('due_at is null')
                ->orderBy('due_at')
                ->limit(300)
                ->get()
                ->filter(fn (Task $task) => Gate::forUser($user)->allows('changeStatus', $task))
                ->values()
            : collect();

        return view('operations::tasks.index', [
            'mine' => $mine,
            'managed' => $managed,
            'showDone' => $showDone,
            'canCreate' => $user->can('create', Task::class),
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->can('create', Task::class), 403);

        return view('operations::tasks.create', [
            'members' => User::query()->active()->permission(AccessMatrix::INTRANET_ACCESS)->orderBy('name')->get(['id', 'name', 'team']),
            'teams' => User::query()->active()->whereNotNull('team')->distinct()->orderBy('team')->pluck('team'),
            'shifts' => Shift::query()->published()->with(['site', 'member'])->where('ends_at', '>', now())->orderBy('starts_at')->limit(200)->get(),
        ]);
    }

    public function store(Request $request, Tasks $tasks): RedirectResponse
    {
        abort_unless($request->user()->can('create', Task::class), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:20000'],
            'assignee_type' => ['required', Rule::in([Task::ASSIGN_PERSON, Task::ASSIGN_TEAM, Task::ASSIGN_SHIFT])],
            'user_id' => ['required_if:assignee_type,person', 'nullable', 'integer', Rule::exists('users', 'id')],
            'team' => ['required_if:assignee_type,team', 'nullable', 'string', 'max:120'],
            'shift_id' => ['required_if:assignee_type,shift', 'nullable', 'integer', Rule::exists('operations_shifts', 'id')],
            'due_at' => ['nullable', 'date'],
            'recurrence' => ['nullable', Rule::in(array_keys(Task::RECURRENCES))],
        ]);

        $task = $tasks->create($data, $request->user());

        return redirect()->route('operations.tasks.show', $task)->with('status', 'Task added.');
    }

    public function show(Request $request, Task $task): View
    {
        abort_unless($request->user()->can('view', $task), 403);

        return view('operations::tasks.show', [
            'task' => $task->load(['assignee', 'shift.site', 'creator', 'comments.author', 'nextTask']),
            'canChangeStatus' => $request->user()->can('changeStatus', $task),
        ]);
    }

    public function updateStatus(Request $request, Task $task, Tasks $tasks): RedirectResponse
    {
        abort_unless($request->user()->can('changeStatus', $task), 403);

        $data = $request->validate(['status' => ['required', Rule::in(array_keys(Task::STATUSES))]]);
        $next = $tasks->changeStatus($task, $data['status'], $request->user());

        return back()->with('status', $next
            ? 'Task done. The next one is due '.$next->due_at->format('D j M H:i').'.'
            : 'Status saved.');
    }

    public function comment(Request $request, Task $task, Tasks $tasks): RedirectResponse
    {
        abort_unless($request->user()->can('comment', $task), 403);

        $data = $request->validate(['body' => ['required', 'string', 'max:20000']]);
        $tasks->comment($task, $request->user(), $data['body']);

        return back()->with('status', 'Comment added.');
    }
}
