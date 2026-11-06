<?php

namespace Modules\Communications\Policies;

use App\Models\User;
use Modules\Communications\Models\UpdatePost;

class UpdatePostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('intranet.access');
    }

    public function view(User $user, UpdatePost $model): bool
    {
        return $user->can('intranet.access');
    }

    public function create(User $user): bool
    {
        return $user->can('comms.publish');
    }

    public function update(User $user, UpdatePost $model): bool
    {
        return $user->can('comms.publish');
    }

    public function delete(User $user, UpdatePost $model): bool
    {
        return $user->can('comms.publish');
    }
}
