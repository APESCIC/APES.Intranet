<?php

namespace Modules\People\Navigation;

use App\Core\Access\AccessMatrix;
use App\Core\Modules\BelongsToModule;
use App\Core\Navigation\MenuContributor;
use App\Core\Navigation\MenuMode;
use App\Core\Navigation\NavGroup;
use App\Core\Navigation\NavItem;
use App\Models\User;

final class PeopleMenuContributor implements BelongsToModule, MenuContributor
{
    public function moduleSlug(): string
    {
        return 'people';
    }

    public function groups(User $user, MenuMode $mode): array
    {
        if ($mode === MenuMode::Admin) {
            return [];
        }

        return [
            new NavGroup('people', 'People', [
                NavItem::route('directory', 'Directory', 'users', AccessMatrix::INTRANET_ACCESS, 'people.directory.index', 'people.directory.*', 'people'),
                NavItem::route('org-chart', 'Organisation chart', 'network', AccessMatrix::INTRANET_ACCESS, 'people.org-chart', null, 'people'),
                NavItem::route('students', 'Student placements', 'graduation-cap', AccessMatrix::INTRANET_ACCESS, 'people.students.index', 'people.students.*', 'people'),
            ], icon: 'users'),
        ];
    }
}
