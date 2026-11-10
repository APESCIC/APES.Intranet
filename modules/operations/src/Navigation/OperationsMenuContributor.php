<?php

namespace Modules\Operations\Navigation;

use App\Core\Navigation\MenuContributor;
use App\Core\Navigation\MenuMode;
use App\Core\Navigation\NavGroup;
use App\Core\Navigation\NavItem;
use App\Models\User;
use Modules\Operations\OperationsPermissions;

final class OperationsMenuContributor implements MenuContributor
{
    public function groups(User $user, MenuMode $mode): array
    {
        if ($mode !== MenuMode::Admin) {
            return [];
        }

        return [
            new NavGroup('admin-operations', 'Operations admin', [
                NavItem::route('operations-sites', 'Sites', 'map-pin', OperationsPermissions::SITES_MANAGE, 'admin.operations.sites.index', 'admin.operations.sites.*'),
            ]),
        ];
    }
}
