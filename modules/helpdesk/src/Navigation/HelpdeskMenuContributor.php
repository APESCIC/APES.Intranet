<?php

namespace Modules\Helpdesk\Navigation;

use App\Core\Navigation\MenuContributor;
use App\Core\Navigation\MenuMode;
use App\Core\Navigation\NavGroup;
use App\Core\Navigation\NavItem;
use App\Models\User;
use Modules\Helpdesk\HelpdeskPermissions;

final class HelpdeskMenuContributor implements MenuContributor
{
    public function groups(User $user, MenuMode $mode): array
    {
        if ($mode === MenuMode::Admin) {
            return [];
        }

        return [
            new NavGroup('helpdesk-queues', 'Help desk queues', [
                NavItem::route('helpdesk-it', 'IT queue', 'monitor-cog', HelpdeskPermissions::QUEUE_IT, 'helpdesk.queues.it'),
                NavItem::route('helpdesk-management', 'Management queue', 'briefcase', HelpdeskPermissions::QUEUE_MANAGEMENT, 'helpdesk.queues.management'),
            ]),
        ];
    }
}
