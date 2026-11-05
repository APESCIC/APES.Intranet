<?php

namespace App\Core\Access\Contracts;

use App\Models\User;

/**
 * A record that belongs to one team, such as a rota, shift swap, timesheet,
 * checklist or student hours entry. Modules implement it on their models so
 * the "own team" policies can tell supervisors apart.
 */
interface BelongsToTeam
{
    public function isSupervisedBy(User $user): bool;
}
