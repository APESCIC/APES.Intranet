<?php

namespace Modules\Hr\Navigation;

use App\Core\Access\AccessMatrix;
use App\Core\Modules\BelongsToModule;
use App\Core\Navigation\MenuContributor;
use App\Core\Navigation\MenuMode;
use App\Core\Navigation\NavGroup;
use App\Core\Navigation\NavItem;
use App\Models\User;

final class HrMenuContributor implements BelongsToModule, MenuContributor
{
    public function moduleSlug(): string
    {
        return 'hr';
    }

    public function groups(User $user, MenuMode $mode): array
    {
        if ($mode === MenuMode::Admin) {
            return [];
        }

        return [
            new NavGroup('hr', 'HR', [
                NavItem::route('hr-leave', 'Leave', 'calendar-days', AccessMatrix::INTRANET_ACCESS, 'hr.leave.index', 'hr.leave.*', 'hr'),
                NavItem::route('hr-training', 'My training', 'graduation-cap', AccessMatrix::INTRANET_ACCESS, 'hr.training.mine', 'hr.training.mine', 'hr'),
                NavItem::route('hr-recognition', 'Give recognition', 'heart', AccessMatrix::INTRANET_ACCESS, 'hr.recognition.create', 'hr.recognition.*', 'hr'),
                NavItem::route('hr-appraisals', 'Appraisals', 'file-pen', AccessMatrix::INTRANET_ACCESS, 'hr.appraisals.index', 'hr.appraisals.*', 'hr'),
                NavItem::route('hr-records', 'HR records', 'id-card', AccessMatrix::HR_ACCESS, 'hr.records.index', 'hr.records.*', 'hr'),
                NavItem::route('hr-checklists', 'Onboarding', 'list-checks', AccessMatrix::HR_ACCESS, 'hr.checklists.index', 'hr.checklists.*', 'hr'),
                NavItem::route('hr-training-overdue', 'Overdue training', 'clipboard-list', AccessMatrix::HR_ACCESS, 'hr.training.overdue', 'hr.training.overdue', 'hr'),
                NavItem::route('hr-checks', 'DBS and right to work', 'shield-check', AccessMatrix::HR_SPECIAL_CATEGORY_VIEW, 'hr.checks.report', 'hr.checks.*', 'hr'),
            ], icon: 'briefcase'),
        ];
    }
}
