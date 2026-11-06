<?php

namespace App\Core\Navigation;

use App\Models\User;

interface MenuContributor
{
    /**
     * Extra nav groups for the given user and menu mode.
     *
     * @return list<NavGroup>
     */
    public function groups(User $user, MenuMode $mode): array;
}
