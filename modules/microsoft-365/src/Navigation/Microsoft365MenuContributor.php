<?php

namespace Modules\Microsoft365\Navigation;

use App\Core\Access\AccessMatrix;
use App\Core\Modules\BelongsToModule;
use App\Core\Navigation\MenuContributor;
use App\Core\Navigation\MenuMode;
use App\Core\Navigation\NavGroup;
use App\Core\Navigation\NavItem;
use App\Models\User;
use Modules\Microsoft365\Microsoft365Permissions;
use Modules\Microsoft365\Services\MicrosoftLinkRegistry;
use Modules\Microsoft365\Support\MicrosoftHelp;

final class Microsoft365MenuContributor implements BelongsToModule, MenuContributor
{
    public function __construct(private readonly MicrosoftLinkRegistry $links) {}

    public function moduleSlug(): string
    {
        return 'microsoft-365';
    }

    public function groups(User $user, MenuMode $mode): array
    {
        if ($mode === MenuMode::Admin) {
            return [
                new NavGroup('admin-m365', 'Microsoft 365 admin', [
                    NavItem::route(
                        'm365-links',
                        'Microsoft 365 links',
                        'app-window',
                        Microsoft365Permissions::MANAGE,
                        'admin.m365-links.index',
                        'admin.m365-links.*',
                        'microsoft-365',
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
                'microsoft-365',
            );
        }

        return [new NavGroup('microsoft-365', 'Apps', $items, MicrosoftHelp::note(), MicrosoftHelp::links())];
    }
}
