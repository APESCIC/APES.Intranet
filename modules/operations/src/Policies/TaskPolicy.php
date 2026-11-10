<?php

namespace Modules\Operations\Policies;

use App\Core\Access\AccessMatrix;
use App\Models\User;
use Modules\Operations\Models\Task;
use Modules\Operations\OperationsPermissions;

class TaskPolicy
{
    public function create(User $user): bool
    {
        return $user->can(OperationsPermissions::TASKS_MANAGE);
    }

    public function view(User $user, Task $task): bool
    {
        return $task->isAssignedTo($user)
            || (int) $task->created_by === (int) $user->getKey()
            || $this->manages($user, $task);
    }

    /**
     * Only assignees and managers change a task's status.
     */
    public function changeStatus(User $user, Task $task): bool
    {
        return $task->isAssignedTo($user) || $this->manages($user, $task);
    }

    public function comment(User $user, Task $task): bool
    {
        return $this->view($user, $task);
    }

    private function manages(User $user, Task $task): bool
    {
        if ($user->can(AccessMatrix::TEAM_MANAGE)) {
            return true;
        }

        return $user->can(AccessMatrix::TEAM_SUPERVISE)
            && ((int) $task->created_by === (int) $user->getKey() || $task->isSupervisedBy($user));
    }
}
