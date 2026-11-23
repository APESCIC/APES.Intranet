<?php

namespace Modules\Operations\Navigation;

use App\Core\Access\AccessMatrix;
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
        if ($mode === MenuMode::Admin) {
            return [
                new NavGroup('admin-operations', 'Operations', [
                    NavItem::route(
                        'operations-sites',
                        'Sites',
                        'map-pin',
                        OperationsPermissions::SITES_MANAGE,
                        'admin.operations.sites.index',
                        'admin.operations.sites.*',
                        'operations',
                        'Locations used on rotas and forms',
                    ),
                    NavItem::route(
                        'operations-forms',
                        'Form builder',
                        'file-pen-line',
                        OperationsPermissions::FORMS_MANAGE,
                        'admin.operations.forms.index',
                        'admin.operations.forms.*',
                        'operations',
                        'Build and publish staff forms',
                    ),
                ], icon: 'briefcase'),
            ];
        }

        return [
            new NavGroup('operations', 'Operations', [
                NavItem::route('rota', 'Rota', 'calendar-days', AccessMatrix::INTRANET_ACCESS, 'operations.rota.index', 'operations.rota.*', 'operations'),
                NavItem::route('clock', 'Clock in and out', 'clock', AccessMatrix::INTRANET_ACCESS, 'operations.clock.index', null, 'operations'),
                NavItem::route('team-clock', 'Team clock', 'users', AccessMatrix::TEAM_SUPERVISE, 'operations.clock.team', null, 'operations'),
                NavItem::route('timesheets', 'Timesheets', 'timer', AccessMatrix::INTRANET_ACCESS, 'operations.timesheets.index', 'operations.timesheets.*', 'operations'),
                NavItem::route('tasks', 'Tasks', 'list-checks', AccessMatrix::INTRANET_ACCESS, 'operations.tasks.index', 'operations.tasks.*', 'operations'),
                NavItem::route('checklists', 'Checklists', 'clipboard-check', AccessMatrix::INTRANET_ACCESS, 'operations.checklists.index', 'operations.checklists.*', 'operations'),
                NavItem::route('forms', 'Forms', 'clipboard-list', AccessMatrix::INTRANET_ACCESS, 'operations.forms.index', 'operations.forms.*', 'operations'),
            ], icon: 'briefcase', presentAs: 'Work'),
        ];
    }
}
