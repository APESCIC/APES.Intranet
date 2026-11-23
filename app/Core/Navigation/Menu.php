<?php

namespace App\Core\Navigation;

use App\Core\Access\AccessMatrix;
use App\Core\Access\RoleName;
use App\Core\Modules\BelongsToModule;
use App\Core\Modules\ModuleRegistry;
use App\Models\User;
use Modules\Microsoft365\Navigation\Microsoft365MenuContributor;

/**
 * The sidebar menu. Each item shows only to people with its permission.
 * Admins see the Admin inventory on admin.* pages and the Staff inventory
 * everywhere else. Modules add extra groups through MenuContributor bindings.
 * Items and contributors for a disabled module are skipped.
 */
final class Menu
{
    /** @deprecated Use Microsoft365MenuContributor::TEAMS_URL */
    public const TEAMS_URL = Microsoft365MenuContributor::TEAMS_URL;

    /** @var array<string, int> */
    private const GROUP_ORDER = [
        'home' => 0,
        'communications' => 10,
        'people' => 20,
        'operations' => 30,
        'support' => 40,
        'microsoft-365' => 50,
        'admin-users' => 100,
        'admin-system' => 110,
        'admin-modules' => 120,
        'admin-apps' => 130,
        'admin-operations' => 140,
        'admin-helpdesk' => 150,
        'hr' => 60,
    ];

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
            ], icon: 'layout-dashboard'),
            new NavGroup('support', 'Support', [
                NavItem::route('access-guide', 'Who can see what', 'key-round', AccessMatrix::INTRANET_ACCESS, 'access-guide'),
                NavItem::route('change-log', 'Change log', 'history', AccessMatrix::INTRANET_ACCESS, 'change-log.index', 'change-log.*'),
            ], icon: 'life-buoy'),
            new NavGroup('hr-legal', 'Support extras', [
                NavItem::soon('agreements', 'Agreements', 'file-pen', AccessMatrix::LEGAL_ACCESS),
            ], foldInto: 'support'),
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
            new NavGroup('home', 'Dashboard', [
                NavItem::route(
                    'admin-overview',
                    'Overview',
                    'layout-grid',
                    AccessMatrix::INTRANET_ACCESS,
                    'admin.overview',
                    description: 'Admin home and shortcuts to every section',
                ),
            ], icon: 'layout-grid'),
            new NavGroup('admin-users', 'Users and access', [
                NavItem::route(
                    'roles',
                    'Roles',
                    'shield-check',
                    AccessMatrix::ADMIN_ROLES_VIEW,
                    'admin.roles.index',
                    'admin.roles.*',
                    description: 'Cloudron groups, people counts and permissions',
                ),
                NavItem::route(
                    'audit',
                    'Audit log',
                    'scroll-text',
                    AccessMatrix::ADMIN_AUDIT_VIEW,
                    'admin.audit.index',
                    description: 'Recent admin and security events',
                ),
                NavItem::route(
                    'access-review',
                    'Access review',
                    'clipboard-check',
                    AccessMatrix::ACCESS_REVIEW_VIEW,
                    'admin.access-review.index',
                    description: 'Who has which roles right now',
                ),
                NavItem::route(
                    'totp',
                    '2FA status',
                    'shield-half',
                    AccessMatrix::ADMIN_SYSTEM_VIEW,
                    'admin.totp.index',
                    description: 'Two-factor enrolment across accounts',
                ),
            ], icon: 'shield-check'),
            new NavGroup('admin-system', 'System', [
                NavItem::route(
                    'system',
                    'System status',
                    'activity',
                    AccessMatrix::ADMIN_SYSTEM_VIEW,
                    'admin.system.index',
                    'admin.system.*',
                    description: 'Health checks, mail and Cloudron addons',
                ),
                NavItem::route(
                    'push-delivery',
                    'Push delivery',
                    'bell-ring',
                    AccessMatrix::ADMIN_SYSTEM_VIEW,
                    'admin.push-delivery.index',
                    description: 'Web push delivery diagnostics',
                ),
            ], icon: 'activity'),
            new NavGroup('admin-modules', 'Modules', [
                NavItem::route(
                    'modules',
                    'Modules',
                    'blocks',
                    AccessMatrix::ADMIN_MODULES_MANAGE,
                    'admin.modules.index',
                    'admin.modules.*',
                    description: 'Turn modules on or off for this site',
                ),
            ], icon: 'blocks'),
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
        $usingAdminInventory = $mode === MenuMode::Admin && $user->hasRole(RoleName::Admin->value);
        $groups = $usingAdminInventory ? self::adminDefinition() : self::staffDefinition();
        $contributorMode = $usingAdminInventory ? MenuMode::Admin : MenuMode::Staff;

        $groups = self::filterGroups($user, $groups);

        foreach (app()->tagged('intranet.menu') as $contributor) {
            if (! $contributor instanceof MenuContributor) {
                continue;
            }

            if ($contributor instanceof BelongsToModule
                && ! app(ModuleRegistry::class)->enabled($contributor->moduleSlug())) {
                continue;
            }

            $groups = array_merge($groups, self::filterGroups($user, $contributor->groups($user, $contributorMode)));
        }

        return self::presentGroups($groups);
    }

    /**
     * @param  list<NavGroup>  $groups
     * @return list<NavGroup>
     */
    private static function presentGroups(array $groups): array
    {
        $foldQueues = [];
        $mainGroups = [];

        foreach ($groups as $group) {
            if ($group->foldInto !== null) {
                $foldQueues[] = $group;

                continue;
            }

            $mainGroups[] = $group;
        }

        usort($mainGroups, function (NavGroup $left, NavGroup $right): int {
            $leftOrder = self::GROUP_ORDER[$left->key] ?? 1000;
            $rightOrder = self::GROUP_ORDER[$right->key] ?? 1000;

            if ($leftOrder === $rightOrder) {
                return 0;
            }

            return $leftOrder <=> $rightOrder;
        });

        $presented = [];
        $indexByKey = [];

        foreach ($mainGroups as $group) {
            if ($group->presentAs !== null) {
                $group = $group->withHeading($group->presentAs);
            }

            $indexByKey[$group->key] = count($presented);
            $presented[] = $group;
        }

        foreach ($foldQueues as $group) {
            $target = $group->foldInto;
            $items = $group->items;

            if ($items === [] || $target === null) {
                continue;
            }

            if (isset($indexByKey[$target])) {
                $index = $indexByKey[$target];
                $presented[$index] = $presented[$index]->withItems(array_merge($presented[$index]->items, $items));

                continue;
            }

            $created = match ($target) {
                'people' => new NavGroup('people', 'People', $items, icon: 'users'),
                'support' => new NavGroup('support', 'Support', $items, icon: 'life-buoy'),
                default => new NavGroup($target, $group->heading, $items, icon: $group->icon),
            };

            $indexByKey[$target] = count($presented);
            $presented[] = $created;
        }

        return $presented;
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
     */
    public static function activeGroup(array $groups): ?NavGroup
    {
        foreach ($groups as $group) {
            foreach ($group->items as $item) {
                if ($item->isActive()) {
                    return $group;
                }
            }
        }

        return null;
    }

    /**
     * @param  list<NavGroup>  $groups
     */
    public static function activeItem(array $groups): ?NavItem
    {
        foreach ($groups as $group) {
            foreach ($group->items as $item) {
                if ($item->isActive()) {
                    return $item;
                }
            }
        }

        return null;
    }

    /**
     * @param  list<NavGroup>  $groups
     * @return list<NavGroup>
     */
    private static function filterGroups(User $user, array $groups): array
    {
        $modules = app(ModuleRegistry::class);
        $filtered = [];

        foreach ($groups as $group) {
            $items = array_values(array_filter(
                $group->items,
                function (NavItem $item) use ($user, $modules): bool {
                    if (! $user->can($item->permission)) {
                        return false;
                    }

                    if ($item->module !== null && ! $modules->enabled($item->module)) {
                        return false;
                    }

                    return true;
                },
            ));

            if ($items !== []) {
                $filtered[] = $group->withItems($items);
            }
        }

        return $filtered;
    }
}
