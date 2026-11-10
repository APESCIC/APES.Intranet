<?php

namespace Modules\Operations\Dashboard;

use App\Core\Dashboard\DashboardCardContributor;
use App\Models\User;
use Modules\Operations\Models\ChecklistRun;
use Modules\Operations\Models\Shift;
use Modules\Operations\Models\Task;

final class OperationsDashboardCards implements DashboardCardContributor
{
    public function cards(User $user): array
    {
        $cards = [];

        $next = Shift::query()
            ->published()
            ->with('site')
            ->where('user_id', $user->getKey())
            ->where('ends_at', '>=', now())
            ->orderBy('starts_at')
            ->first();

        if ($next !== null) {
            $cards[] = [
                'key' => 'next-shift',
                'title' => 'Your next shift',
                'body' => $next->label().' at '.$next->site->name.'.',
                'href' => route('operations.rota.index'),
            ];
        }

        $checklists = ChecklistRun::query()
            ->whereNull('completed_at')
            ->whereHas('shift', fn ($q) => $q->published()->where('user_id', $user->getKey()))
            ->where('due_at', '<=', now()->endOfDay())
            ->count();
        if ($checklists > 0) {
            $cards[] = [
                'key' => 'checklists-due',
                'title' => 'Checklists to do',
                'body' => $checklists === 1 ? '1 checklist is due on your shifts today.' : "{$checklists} checklists are due on your shifts today.",
                'href' => route('operations.checklists.index'),
            ];
        }

        $overdue = Task::query()->assignedTo($user)->overdue()->count();
        if ($overdue > 0) {
            $cards[] = [
                'key' => 'overdue-tasks',
                'title' => 'Overdue tasks',
                'body' => $overdue === 1 ? '1 task is overdue.' : "{$overdue} tasks are overdue.",
                'href' => route('operations.tasks.index'),
                'tone' => 'warn',
            ];
        }

        return $cards;
    }
}
