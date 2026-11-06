<?php

namespace Modules\Communications\Policies;

use App\Models\User;
use Modules\Communications\Models\PolicyDocument;

class PolicyDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('intranet.access');
    }

    public function view(User $user, PolicyDocument $model): bool
    {
        return $user->can('intranet.access');
    }

    public function create(User $user): bool
    {
        return $user->can('comms.publish');
    }

    public function update(User $user, PolicyDocument $model): bool
    {
        return $user->can('comms.publish');
    }

    public function delete(User $user, PolicyDocument $model): bool
    {
        return $user->can('comms.publish');
    }
}
