<?php

namespace Modules\Operations\Dashboard;

use App\Core\Dashboard\DashboardCardContributor;
use App\Models\User;
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
