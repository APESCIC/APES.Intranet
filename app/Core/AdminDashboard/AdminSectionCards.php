<?php

namespace App\Core\AdminDashboard;

use App\Core\Navigation\Menu;
use App\Core\Navigation\MenuMode;
use App\Models\User;

/**
 * Admin menu links as dashboard cards, so `/admin` stays useful until modules
 * add analytics cards. Menu already filters by permission; each card still
 * declares its permission for the aggregator.
 */
final class AdminSectionCards implements AdminDashboardCardContributor
{
    public function cards(User $user): array
    {
        $cards = [];

        foreach (Menu::for($user, MenuMode::Admin) as $group) {
            foreach ($group->items as $item) {
                if ($item->routeName === 'admin.overview' || $item->isSoon()) {
                    continue;
                }

                $href = $item->url();
                if ($href === null) {
                    continue;
                }

                $cards[] = [
                    'key' => 'section-'.$item->key,
                    'title' => $item->label,
                    'body' => $item->description ?? 'Open this admin section.',
                    'href' => $href,
                    'permission' => $item->permission,
                    'module' => $item->module,
                    'icon' => $item->icon,
                    'external' => $item->isExternal(),
                    'section' => $item->key,
                    'group' => $group->key,
                    'group_heading' => $group->heading,
                ];
            }
        }

        return $cards;
    }
}
