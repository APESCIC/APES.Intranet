<?php

namespace App\Core\Navigation\Http\Controllers;

use App\Core\Access\RoleName;
use App\Core\Navigation\Menu;
use App\Core\Navigation\MenuMode;
use App\Core\Navigation\NavGroup;
use App\Core\Navigation\NavItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The Admin panel's landing page: links to the admin sections the person may
 * open. Each linked page keeps its own permission and fresh sign-in checks.
 */
class AdminOverviewController
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->hasRole(RoleName::Admin->value), 403);

        $groups = [];
        foreach (Menu::for($user, MenuMode::Admin) as $group) {
            $items = array_values(array_filter(
                $group->items,
                fn (NavItem $item) => $item->routeName !== 'admin.overview',
            ));

            if ($items !== []) {
                $groups[] = new NavGroup($group->key, $group->heading, $items);
            }
        }

        return view('core.admin.overview', ['groups' => $groups]);
    }
}
