<?php

namespace App\Core\Access\Policies;

use App\Core\Access\AccessMatrix;
use App\Models\User;

/**
 * Roles come from Cloudron directory groups, so the intranet only reads them.
 */
class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(AccessMatrix::ADMIN_ROLES_VIEW);
    }
}
