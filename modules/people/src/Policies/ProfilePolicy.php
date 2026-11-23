<?php

namespace Modules\People\Policies;

use App\Core\Access\AccessMatrix;
use App\Models\User;
use Modules\Hr\HrPermissions;

class ProfilePolicy
{
    public function view(User $viewer, User $subject): bool
    {
        return $viewer->can(AccessMatrix::INTRANET_ACCESS)
            && ($subject->is_active || $viewer->can(HrPermissions::ACCESS) || $viewer->is($subject));
    }

    public function update(User $viewer, User $subject): bool
    {
        return $viewer->is($subject) && $viewer->can(AccessMatrix::INTRANET_ACCESS);
    }

    public function setManager(User $actor, User $subject): bool
    {
        return $actor->can(HrPermissions::ACCESS) || $actor->can(AccessMatrix::ADMIN_ROLES_VIEW);
    }
}
