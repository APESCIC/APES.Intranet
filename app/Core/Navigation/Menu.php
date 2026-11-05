<?php

namespace App\Core\Navigation;

use App\Core\Access\AccessMatrix;
use App\Models\User;

/**
 * The sidebar menu. Each item shows only to people with its permission.
 */
final class Menu
{
    public const TEAMS_URL = 'https://teams.microsoft.com/';

    /**
     * @return list<NavGroup>
     */
    public static function definition(): array
    {
        return [
            new NavGroup('home', 'Home', [
                NavItem::route('dashboard', 'Dashboard', 'layout-dashboard', AccessMatrix::INTRANET_ACCESS, 'dashboard'),
            ]),
            new NavGroup('communications', 'Communications', [
                NavItem::soon('updates', 'Updates', 'newspaper', AccessMatrix::INTRANET_ACCESS),
                NavItem::external('teams', 'Chat in Teams', 'messages-square', AccessMatrix::INTRANET_ACCESS, self::TEAMS_URL),
            ]),
            new NavGroup('people', 'People', [
                NavItem::soon('directory', 'Directory', 'users', AccessMatrix::INTRANET_ACCESS),
                NavItem::soon('org-chart', 'Organisation chart', 'network', AccessMatrix::INTRANET_ACCESS),
            ]),
            new NavGroup('operations', 'Operations', [
                NavItem::soon('rota', 'Rota', 'calendar-days', AccessMatrix::INTRANET_ACCESS),
                NavItem::soon('clock', 'Clock in and out', 'clock', AccessMatrix::INTRANET_ACCESS),
                NavItem::soon('tasks', 'Tasks', 'list-checks', AccessMatrix::INTRANET_ACCESS),
                NavItem::soon('forms', 'Forms', 'clipboard-list', AccessMatrix::INTRANET_ACCESS),
            ]),
            new NavGroup('support', 'Support', [
                NavItem::soon('help-desk', 'Help desk', 'life-buoy', AccessMatrix::INTRANET_ACCESS),
                NavItem::soon('handbook', 'Handbook and training', 'book-open', AccessMatrix::INTRANET_ACCESS),
                NavItem::route('access-guide', 'Who can see what', 'key-round', AccessMatrix::INTRANET_ACCESS, 'access-guide'),
            ]),
            new NavGroup('hr-legal', 'HR and legal', [
                NavItem::soon('hr', 'HR', 'briefcase', AccessMatrix::HR_ACCESS),
                NavItem::soon('agreements', 'Agreements', 'file-pen-line', AccessMatrix::LEGAL_ACCESS),
            ]),
            new NavGroup('admin', 'Admin', [
                NavItem::route('roles', 'Roles', 'shield-check', AccessMatrix::ADMIN_ROLES_VIEW, 'admin.roles.index', 'admin.roles.*'),
                NavItem::route('audit', 'Audit log', 'scroll-text', AccessMatrix::ADMIN_AUDIT_VIEW, 'admin.audit.index'),
            ]),
        ];
    }

    /**
     * @return list<NavGroup>
     */
    public static function for(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        $groups = [];

        foreach (self::definition() as $group) {
            $items = array_values(array_filter(
                $group->items,
                fn (NavItem $item) => $user->can($item->permission),
            ));

            if ($items !== []) {
                $groups[] = new NavGroup($group->key, $group->heading, $items);
            }
        }

        return $groups;
    }
}
