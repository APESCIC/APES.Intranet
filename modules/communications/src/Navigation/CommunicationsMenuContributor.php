<?php

namespace Modules\Communications\Navigation;

use App\Core\Access\AccessMatrix;
use App\Core\Navigation\MenuContributor;
use App\Core\Navigation\MenuMode;
use App\Core\Navigation\NavGroup;
use App\Core\Navigation\NavItem;
use App\Models\User;
use Modules\Communications\CommunicationsPermissions;
use Modules\Communications\Services\MicrosoftLinkRegistry;
use Modules\Communications\Support\MicrosoftHelp;

final class CommunicationsMenuContributor implements MenuContributor
{
    public function __construct(private readonly MicrosoftLinkRegistry $links) {}

    public function groups(User $user, MenuMode $mode): array
    {
        if ($mode === MenuMode::Admin) {
            return [
                new NavGroup('admin-comms', 'Communications admin', [
                    NavItem::route(
                        'm365-links',
                        'Microsoft 365 links',
                        'app-window',
                        CommunicationsPermissions::MANAGE_M365,
                        'admin.m365-links.index',
                        'admin.m365-links.*',
                    ),
                ]),
            ];
        }

        $links = $this->links->forUser($user);
        if ($links->isEmpty()) {
            return [];
        }

        $items = [];
        foreach ($links as $link) {
            $items[] = NavItem::external(
                'm365-'.$link->key,
                $link->name,
                $link->icon,
                AccessMatrix::INTRANET_ACCESS,
                $link->url,
            );
        }

        return [new NavGroup('microsoft-365', 'Microsoft 365', $items, MicrosoftHelp::note(), MicrosoftHelp::links())];
    }
}
