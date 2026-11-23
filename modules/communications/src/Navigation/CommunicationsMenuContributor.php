<?php

namespace Modules\Communications\Navigation;

use App\Core\Access\AccessMatrix;
use App\Core\Modules\BelongsToModule;
use App\Core\Navigation\MenuContributor;
use App\Core\Navigation\MenuMode;
use App\Core\Navigation\NavGroup;
use App\Core\Navigation\NavItem;
use App\Models\User;

final class CommunicationsMenuContributor implements BelongsToModule, MenuContributor
{
    public function moduleSlug(): string
    {
        return 'communications';
    }

    public function groups(User $user, MenuMode $mode): array
    {
        if ($mode === MenuMode::Admin) {
            return [];
        }

        return [
            new NavGroup('communications', 'Communications', [
                NavItem::route('updates', 'Updates', 'newspaper', AccessMatrix::INTRANET_ACCESS, 'communications.updates.index', 'communications.updates.*', 'communications'),
                NavItem::route('events', 'Events', 'calendar-days', AccessMatrix::INTRANET_ACCESS, 'communications.events.index', 'communications.events.*', 'communications'),
                NavItem::route('handbook', 'Handbook', 'book-open', AccessMatrix::INTRANET_ACCESS, 'communications.handbook.index', 'communications.handbook.*', 'communications'),
                NavItem::route('policies', 'Policies', 'file-text', AccessMatrix::INTRANET_ACCESS, 'communications.policies.index', 'communications.policies.*', 'communications'),
            ], icon: 'megaphone'),
        ];
    }
}
