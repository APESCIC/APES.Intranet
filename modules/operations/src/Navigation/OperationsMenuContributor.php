<?php

namespace Modules\Operations\Navigation;

use App\Core\Modules\BelongsToModule;
use App\Core\Navigation\MenuContributor;
use App\Core\Navigation\MenuMode;
use App\Core\Navigation\NavGroup;
use App\Core\Navigation\NavItem;
use App\Models\User;
use Modules\Operations\OperationsPermissions;

final class OperationsMenuContributor implements BelongsToModule, MenuContributor
{
    public function moduleSlug(): string
    {
        return 'operations';
    }

    public function groups(User $user, MenuMode $mode): array
    {
        if ($mode !== MenuMode::Admin) {
            return [];
        }

        return [
            new NavGroup('admin-operations', 'Operations admin', [
                NavItem::route('operations-sites', 'Sites', 'map-pin', OperationsPermissions::SITES_MANAGE, 'admin.operations.sites.index', 'admin.operations.sites.*', 'operations'),
                NavItem::route('operations-forms', 'Form builder', 'file-pen-line', OperationsPermissions::FORMS_MANAGE, 'admin.operations.forms.index', 'admin.operations.forms.*', 'operations'),
            ]),
        ];
    }
}
