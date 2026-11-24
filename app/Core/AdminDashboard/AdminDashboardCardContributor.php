<?php

namespace App\Core\AdminDashboard;

use App\Models\User;

/**
 * Lets Core or a module add cards to `/admin` without the overview importing
 * it. Bind implementations with the `intranet.admin-dashboard` container tag.
 *
 * Each card must declare a `permission`. Optional `module` hides the card when
 * that module is switched off. The aggregator never includes a card the person
 * may not see; contributors must not load data for cards they omit.
 */
interface AdminDashboardCardContributor
{
    /**
     * @return list<array{
     *     key: string,
     *     title: string,
     *     body: string,
     *     href: ?string,
     *     permission: string,
     *     module?: string|null,
     *     tone?: string,
     *     icon?: string,
     *     external?: bool,
     *     section?: string,
     *     group?: string,
     *     group_heading?: string
     * }>
     */
    public function cards(User $user): array;
}
