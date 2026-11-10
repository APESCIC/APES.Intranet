<?php

namespace Modules\Operations\Policies;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Modules\Operations\Models\ChecklistRun;

class ChecklistRunPolicy
{
    public function view(User $user, ChecklistRun $run): bool
    {
        return $this->isOnShift($user, $run) || Gate::forUser($user)->allows('viewTeamChecklist', $run);
    }

    /**
     * History is read-only once a checklist is completed.
     */
    public function update(User $user, ChecklistRun $run): bool
    {
        return ! $run->isComplete() && $this->view($user, $run);
    }

    private function isOnShift(User $user, ChecklistRun $run): bool
    {
        return $run->shift !== null && (int) $run->shift->user_id === (int) $user->getKey();
    }
}
