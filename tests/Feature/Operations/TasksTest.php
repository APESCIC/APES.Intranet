<?php

use App\Core\Access\RoleName;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Modules\Operations\Models\Task;
use Tests\Support\Operations;

uses(RefreshDatabase::class);

beforeEach(function () {
    Operations::seedRoles();
    Mail::fake();
    $this->supervisor = Operations::member(RoleName::Supervisor);
    $this->member = Operations::member();
});

function task(array $attributes): Task
{
    return Task::query()->create($attributes + ['title' => 'Clean the vivariums', 'assignee_type' => Task::ASSIGN_PERSON, 'status' => Task::STATUS_OPEN]);
}

it('lets a supervisor assign a task to a person, team or shift', function () {
    $shift = Operations::shift(Operations::week(Operations::site()), $this->member, CarbonImmutable::now()->addDay());

    $this->actingAs($this->supervisor)->post(route('operations.tasks.store'), [
        'title' => 'Order mealworms', 'assignee_type' => 'person', 'user_id' => $this->member->id,
        'description' => '<p>Two tubs</p><script>alert(1)</script>',
    ])->assertRedirect();
    $this->actingAs($this->supervisor)->post(route('operations.tasks.store'), ['title' => 'Team huddle', 'assignee_type' => 'team', 'team' => 'Care'])->assertRedirect();
    $this->actingAs($this->supervisor)->post(route('operations.tasks.store'), ['title' => 'Lock up', 'assignee_type' => 'shift', 'shift_id' => $shift->id])->assertRedirect();

    expect(Task::query()->assignedTo($this->member)->count())->toBe(3)
        ->and(Task::query()->where('title', 'Order mealworms')->value('description'))->not->toContain('<script');

    $this->actingAs($this->member)->post(route('operations.tasks.store'), ['title' => 'Nope', 'assignee_type' => 'team', 'team' => 'Care'])->assertForbidden();
});

it('creates the next recurring task when one is completed', function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-16 08:00'));
    $task = task(['user_id' => $this->member->id, 'due_at' => '2026-11-16 10:00', 'recurrence' => 'weekly', 'created_by' => $this->supervisor->id]);

    $this->actingAs($this->member)->patch(route('operations.tasks.status', $task), ['status' => 'done'])->assertRedirect();

    $next = Task::query()->where('previous_task_id', $task->id)->firstOrFail();
    expect($task->refresh()->isDone())->toBeTrue()
        ->and($next->status)->toBe(Task::STATUS_OPEN)
        ->and($next->due_at->toDateTimeString())->toBe('2026-11-23 10:00:00')
        ->and($next->user_id)->toBe($this->member->id)
        ->and($next->recurrence)->toBe('weekly');

    $this->actingAs($this->member)->patch(route('operations.tasks.status', $task), ['status' => 'open']);
    $this->actingAs($this->member)->patch(route('operations.tasks.status', $task), ['status' => 'done']);
    expect(Task::query()->where('previous_task_id', $task->id)->count())->toBe(1);
});

it('moves a late recurring task on to the next future date', function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-20 12:00'));
    $task = task(['user_id' => $this->member->id, 'due_at' => '2026-11-16 10:00', 'recurrence' => 'daily']);

    $this->actingAs($this->member)->patch(route('operations.tasks.status', $task), ['status' => 'done']);

    expect(Task::query()->where('previous_task_id', $task->id)->firstOrFail()->due_at->toDateTimeString())->toBe('2026-11-21 10:00:00');
});

it('shows overdue tasks on the dashboard', function () {
    task(['user_id' => $this->member->id, 'due_at' => now()->subHour()]);
    task(['user_id' => $this->member->id, 'due_at' => now()->addHour()]);

    $this->actingAs($this->member)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Overdue tasks')
        ->assertSee('1 task is overdue.');

    $this->actingAs(Operations::member(attributes: ['team' => 'Office']))->get(route('dashboard'))->assertDontSee('Overdue tasks');
});

it('lets only assignees and managers change status', function () {
    $task = task(['user_id' => $this->member->id, 'created_by' => $this->supervisor->id]);
    $colleague = Operations::member();
    $otherTeamSupervisor = Operations::member(RoleName::Supervisor, ['team' => 'Office']);

    $this->actingAs($colleague)->patch(route('operations.tasks.status', $task), ['status' => 'done'])->assertForbidden();
    $this->actingAs($otherTeamSupervisor)->patch(route('operations.tasks.status', $task), ['status' => 'done'])->assertForbidden();
    expect($task->refresh()->status)->toBe(Task::STATUS_OPEN);

    $this->actingAs($this->member)->patch(route('operations.tasks.status', $task), ['status' => 'in_progress'])->assertRedirect();
    $this->actingAs($this->supervisor)->patch(route('operations.tasks.status', $task), ['status' => 'done'])->assertRedirect();
    expect($task->refresh()->isDone())->toBeTrue();
});

it('lets the assignee comment with sanitised rich text', function () {
    $task = task(['user_id' => $this->member->id]);

    $this->actingAs($this->member)->post(route('operations.tasks.comment', $task), ['body' => '<p>Done <strong>half</strong></p><img src=x onerror=alert(1)>'])->assertRedirect();

    $this->actingAs($this->member)->get(route('operations.tasks.show', $task))
        ->assertOk()
        ->assertSee('<strong>half</strong>', false)
        ->assertDontSee('onerror', false);
    $this->actingAs(Operations::member(attributes: ['team' => 'Office']))->get(route('operations.tasks.show', $task))->assertForbidden();
});
