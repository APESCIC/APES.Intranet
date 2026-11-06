<?php

namespace Modules\Communications\Policies;

use App\Models\User;
use Modules\Communications\Models\MicrosoftLink;

class MicrosoftLinkPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('intranet.access');
    }

    public function view(User $user, MicrosoftLink $model): bool
    {
        return $user->can('intranet.access');
    }

    public function create(User $user): bool
    {
        return $user->can('comms.m365.manage');
    }

    public function update(User $user, MicrosoftLink $model): bool
    {
        return $user->can('comms.m365.manage');
    }

    public function delete(User $user, MicrosoftLink $model): bool
    {
        return $user->can('comms.m365.manage');
    }
}
