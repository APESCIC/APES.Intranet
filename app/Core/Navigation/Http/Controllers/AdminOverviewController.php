<?php

namespace App\Core\Navigation\Http\Controllers;

use App\Core\Access\RoleName;
use App\Core\AdminDashboard\AdminDashboard;
use App\Core\AdminDashboard\AdminDashboardLayouts;
use App\Core\AdminDashboard\DirectorGate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The Admin panel's landing page: a card dashboard built from tagged
 * contributors. Each card keeps its own permission and module checks.
 */
class AdminOverviewController
{
    public function __invoke(Request $request, AdminDashboard $dashboard, AdminDashboardLayouts $layouts, DirectorGate $director): View
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->hasRole(RoleName::Admin->value), 403);

        $available = $dashboard->cardsFor($user);
        $editing = $request->boolean('edit');
        $visible = $layouts->apply($user, $available);
        $hidden = $layouts->hiddenCards($user, $available);
        $groups = [];

        foreach ($visible as $card) {
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
            'editing' => $editing,
            'flatCards' => $visible,
            'hiddenCards' => $hidden,
            'hasPersonalLayout' => $layouts->personalLayout($user) !== null,
            'isDirector' => $director->allows($user),
        ]);
    }
}
