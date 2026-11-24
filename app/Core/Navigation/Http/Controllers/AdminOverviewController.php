<?php

namespace App\Core\Navigation\Http\Controllers;

use App\Core\Access\RoleName;
use App\Core\AdminDashboard\AdminDashboard;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The Admin panel's landing page: a card dashboard built from tagged
 * contributors. Each card keeps its own permission and module checks.
 */
class AdminOverviewController
{
    public function __invoke(Request $request, AdminDashboard $dashboard): View
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->hasRole(RoleName::Admin->value), 403);

        $cards = $dashboard->cardsFor($user);
        $groups = [];

        foreach ($cards as $card) {
            $groupKey = $card['group'] ?? 'cards';
            if (! isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'key' => $groupKey,
                    'heading' => $card['group_heading'] ?? 'Dashboard',
                    'cards' => [],
                ];
            }
            $groups[$groupKey]['cards'][] = $card;
        }

        return view('core.admin.overview', [
            'groups' => array_values($groups),
        ]);
    }
}
