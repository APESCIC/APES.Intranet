<?php

namespace Modules\Hr\Navigation;

use App\Core\Access\AccessMatrix;
use App\Core\Navigation\MenuContributor;
use App\Core\Navigation\MenuMode;
use App\Core\Navigation\NavGroup;
use App\Core\Navigation\NavItem;
use App\Models\User;

final class HrMenuContributor implements MenuContributor
{
    public function groups(User $user, MenuMode $mode): array
    {
        if ($mode === MenuMode::Admin) {
            return [];
        }

        return [
            new NavGroup('hr', 'HR', [
                NavItem::route('hr-leave', 'Leave', 'calendar-days', AccessMatrix::INTRANET_ACCESS, 'hr.leave.index', 'hr.leave.*'),
                NavItem::route('hr-training', 'My training', 'graduation-cap', AccessMatrix::INTRANET_ACCESS, 'hr.training.mine', 'hr.training.mine'),
                NavItem::route('hr-records', 'HR records', 'id-card', AccessMatrix::HR_ACCESS, 'hr.records.index', 'hr.records.*'),
                NavItem::route('hr-checklists', 'Onboarding', 'list-checks', AccessMatrix::HR_ACCESS, 'hr.checklists.index', 'hr.checklists.*'),
                NavItem::route('hr-training-overdue', 'Overdue training', 'clipboard-list', AccessMatrix::HR_ACCESS, 'hr.training.overdue', 'hr.training.overdue'),
                NavItem::route('hr-checks', 'DBS and right to work', 'shield-check', AccessMatrix::HR_SPECIAL_CATEGORY_VIEW, 'hr.checks.report', 'hr.checks.*'),
            ]),
        ];
    }
}
