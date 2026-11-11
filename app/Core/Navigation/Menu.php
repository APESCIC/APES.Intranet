<?php

namespace App\Core\Navigation;

use App\Core\Access\AccessMatrix;
use App\Core\Access\RoleName;
use App\Models\User;

/**
 * The sidebar menu. Each item shows only to people with its permission.
 * Admins see the Admin inventory on admin.* pages and the Staff inventory
 * everywhere else. Modules add extra groups through MenuContributor bindings.
 */
final class Menu
{
    public const TEAMS_URL = 'https://teams.microsoft.com/';

    /**
     * Member-facing groups. System admin screens are not listed here.
     *
     * @return list<NavGroup>
     */
    public static function staffDefinition(): array
    {
        return [
            new NavGroup('home', 'Home', [
                NavItem::route('dashboard', 'Dashboard', 'layout-dashboard', AccessMatrix::INTRANET_ACCESS, 'dashboard'),
            ]),
            new NavGroup('communications', 'Communications', [
                NavItem::route('updates', 'Updates', 'newspaper', AccessMatrix::INTRANET_ACCESS, 'communications.updates.index', 'communications.updates.*'),
                NavItem::route('events', 'Events', 'calendar-days', AccessMatrix::INTRANET_ACCESS, 'communications.events.index', 'communications.events.*'),
                NavItem::external('teams', 'Chat in Teams', 'messages-square', AccessMatrix::INTRANET_ACCESS, self::TEAMS_URL),
            ]),
            new NavGroup('people', 'People', [
                NavItem::route('directory', 'Directory', 'users', AccessMatrix::INTRANET_ACCESS, 'people.directory.index', 'people.directory.*'),
                NavItem::route('org-chart', 'Organisation chart', 'network', AccessMatrix::INTRANET_ACCESS, 'people.org-chart'),
                NavItem::route('profile', 'My profile', 'circle-user', AccessMatrix::INTRANET_ACCESS, 'people.profile.edit'),
                NavItem::route('security', 'Security', 'shield', AccessMatrix::INTRANET_ACCESS, 'people.security'),
                NavItem::route('students', 'Student placements', 'graduation-cap', AccessMatrix::INTRANET_ACCESS, 'people.students.index', 'people.students.*'),
            ]),
            new NavGroup('operations', 'Operations', [
                NavItem::route('rota', 'Rota', 'calendar-days', AccessMatrix::INTRANET_ACCESS, 'operations.rota.index', 'operations.rota.*'),
                NavItem::route('clock', 'Clock in and out', 'clock', AccessMatrix::INTRANET_ACCESS, 'operations.clock.index'),
                NavItem::route('team-clock', 'Team clock', 'users', AccessMatrix::TEAM_SUPERVISE, 'operations.clock.team'),
                NavItem::route('timesheets', 'Timesheets', 'timer', AccessMatrix::INTRANET_ACCESS, 'operations.timesheets.index', 'operations.timesheets.*'),
                NavItem::route('tasks', 'Tasks', 'list-checks', AccessMatrix::INTRANET_ACCESS, 'operations.tasks.index', 'operations.tasks.*'),
                NavItem::route('checklists', 'Checklists', 'clipboard-check', AccessMatrix::INTRANET_ACCESS, 'operations.checklists.index', 'operations.checklists.*'),
                NavItem::route('forms', 'Forms', 'clipboard-list', AccessMatrix::INTRANET_ACCESS, 'operations.forms.index', 'operations.forms.*'),
            ]),
            new NavGroup('support', 'Support', [
                NavItem::route('help-desk', 'Help desk', 'life-buoy', AccessMatrix::INTRANET_ACCESS, 'helpdesk.tickets.index', 'helpdesk.tickets.*'),
                NavItem::route('handbook', 'Handbook', 'book-open', AccessMatrix::INTRANET_ACCESS, 'communications.handbook.index', 'communications.handbook.*'),
                NavItem::route('policies', 'Policies', 'file-text', AccessMatrix::INTRANET_ACCESS, 'communications.policies.index', 'communications.policies.*'),
                NavItem::route('access-guide', 'Who can see what', 'key-round', AccessMatrix::INTRANET_ACCESS, 'access-guide'),
                NavItem::route('change-log', 'Change log', 'history', AccessMatrix::INTRANET_ACCESS, 'change-log.index', 'change-log.*'),
            ]),
            new NavGroup('hr-legal', 'HR and legal', [
                NavItem::soon('hr', 'HR', 'briefcase', AccessMatrix::HR_ACCESS),
                NavItem::soon('agreements', 'Agreements', 'file-pen-line', AccessMatrix::LEGAL_ACCESS),
            ]),
        ];
    }

    /**
     * System administration inventory. Shown only in the Admin panel.
     *
     * @return list<NavGroup>
     */
    public static function adminDefinition(): array
    {
        return [
            new NavGroup('admin', 'Admin', [
                NavItem::route('admin-overview', 'Overview', 'layout-grid', AccessMatrix::INTRANET_ACCESS, 'admin.overview'),
                NavItem::route('roles', 'Roles', 'shield-check', AccessMatrix::ADMIN_ROLES_VIEW, 'admin.roles.index', 'admin.roles.*'),
                NavItem::route('audit', 'Audit log', 'scroll-text', AccessMatrix::ADMIN_AUDIT_VIEW, 'admin.audit.index'),
                NavItem::route('access-review', 'Access review', 'clipboard-check', AccessMatrix::ACCESS_REVIEW_VIEW, 'admin.access-review.index'),
                NavItem::route('system', 'System status', 'activity', AccessMatrix::ADMIN_SYSTEM_VIEW, 'admin.system.index', 'admin.system.*'),
                NavItem::route('totp', '2FA status', 'shield-half', AccessMatrix::ADMIN_SYSTEM_VIEW, 'admin.totp.index'),
            ]),
        ];
    }

    /**
     * @return list<NavGroup>
     */
    public static function definition(): array
    {
        return self::staffDefinition();
    }

    /**
     * @return list<NavGroup>
     */
    public static function for(?User $user, ?MenuMode $mode = null): array
    {
        if ($user === null) {
            return [];
        }

        $mode ??= self::resolveMode($user);
        $groups = $mode === MenuMode::Admin && $user->hasRole(RoleName::Admin->value)
            ? self::adminDefinition()
            : self::staffDefinition();

        $groups = self::filterGroups($user, $groups);

        foreach (app()->tagged('intranet.menu') as $contributor) {
            if ($contributor instanceof MenuContributor) {
                $groups = array_merge($groups, self::filterGroups($user, $contributor->groups($user, $mode)));
            }
        }

        return $groups;
    }

    public static function resolveMode(User $user): MenuMode
    {
        return $user->hasRole(RoleName::Admin->value) && self::currentRouteIsAdmin()
            ? MenuMode::Admin
            : MenuMode::Staff;
    }

    public static function currentRouteIsAdmin(): bool
    {
        return request()->routeIs('admin.*');
    }

    /**
     * @param  list<NavGroup>  $groups
     * @return list<NavGroup>
     */
    private static function filterGroups(User $user, array $groups): array
    {
        $filtered = [];

        foreach ($groups as $group) {
            $items = array_values(array_filter(
                $group->items,
                fn (NavItem $item) => $user->can($item->permission),
            ));

            if ($items !== []) {
                $filtered[] = $group->withItems($items);
            }
        }

        return $filtered;
    }
}
