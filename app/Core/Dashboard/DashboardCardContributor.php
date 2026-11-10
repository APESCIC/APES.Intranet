<?php

namespace App\Core\Dashboard;

use App\Models\User;

/**
 * Lets a module add cards to the dashboard without the dashboard importing
 * it. Bind implementations with the `intranet.dashboard` container tag.
 */
interface DashboardCardContributor
{
    /**
     * @return list<array{key: string, title: string, body: string, href: ?string, tone?: string}>
     */
    public function cards(User $user): array;
}
