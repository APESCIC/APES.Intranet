<?php

namespace App\Core\AdminDashboard\Http\Controllers;

use App\Core\Access\RoleName;
use App\Core\AdminDashboard\AdminDashboard;
use App\Core\AdminDashboard\AdminDashboardLayouts;
use App\Core\AdminDashboard\DirectorGate;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminDashboardLayoutController
{
    public function save(Request $request, AdminDashboard $dashboard, AdminDashboardLayouts $layouts): RedirectResponse
    {
        $user = $this->admin($request);
        $available = collect($dashboard->cardsFor($user))->pluck('key')->all();

        $ordered = array_values(array_filter(
            (array) $request->input('ordered_keys', []),
            fn ($key) => is_string($key) && in_array($key, $available, true),
        ));
        $hidden = array_values(array_filter(
            (array) $request->input('hidden_keys', []),
            fn ($key) => is_string($key) && in_array($key, $available, true),
        ));

        foreach ($available as $key) {
            if (! in_array($key, $ordered, true) && ! in_array($key, $hidden, true)) {
                $ordered[] = $key;
            }
        }

        $layouts->savePersonal($user, $ordered, $hidden);

        return redirect()->route('admin.overview')->with('status', 'Your admin dashboard layout was saved.');
    }

    public function reset(Request $request, AdminDashboardLayouts $layouts): RedirectResponse
    {
        $user = $this->admin($request);
        $layouts->resetPersonal($user);

        return redirect()->route('admin.overview')->with('status', 'Your admin dashboard was reset to the shared default.');
    }

    public function setSharedDefault(Request $request, AdminDashboard $dashboard, AdminDashboardLayouts $layouts, DirectorGate $director): RedirectResponse
    {
        $user = $this->admin($request);
        abort_unless($director->allows($user), 403);
        abort_unless($request->boolean('confirm'), 422);

        $available = $dashboard->cardsFor($user);
        $visible = $layouts->apply($user, $available);
        $hidden = $layouts->hiddenCards($user, $available);
        $layouts->saveSharedDefault(
            array_column($visible, 'key'),
            array_column($hidden, 'key'),
        );

        return redirect()->route('admin.overview')->with('status', 'The shared default admin dashboard layout was updated.');
    }

    public function move(Request $request, AdminDashboard $dashboard, AdminDashboardLayouts $layouts): RedirectResponse
    {
        $user = $this->admin($request);
        $key = (string) $request->input('key', '');
        $direction = (string) $request->input('direction', '');
        $available = $dashboard->cardsFor($user);
        $visible = $layouts->apply($user, $available);
        $keys = array_column($visible, 'key');
        $index = array_search($key, $keys, true);

        if ($index === false) {
            return redirect()->route('admin.overview', ['edit' => 1]);
        }

        $swap = $direction === 'up' ? $index - 1 : $index + 1;
        if ($swap < 0 || $swap >= count($keys)) {
            return redirect()->route('admin.overview', ['edit' => 1]);
        }

        [$keys[$index], $keys[$swap]] = [$keys[$swap], $keys[$index]];
        $layout = $layouts->layoutFor($user);
        $hidden = $layout === null ? [] : ($layout->hidden_keys ?? []);
        $layouts->savePersonal($user, $keys, $hidden);

        return redirect()->route('admin.overview', ['edit' => 1]);
    }

    public function hide(Request $request, AdminDashboard $dashboard, AdminDashboardLayouts $layouts): RedirectResponse
    {
        $user = $this->admin($request);
        $key = (string) $request->input('key', '');
        $available = $dashboard->cardsFor($user);
        $keys = array_column($available, 'key');
        if (! in_array($key, $keys, true)) {
            return redirect()->route('admin.overview', ['edit' => 1]);
        }

        $visible = array_column($layouts->apply($user, $available), 'key');
        $layout = $layouts->layoutFor($user);
        $hidden = $layout === null ? [] : ($layout->hidden_keys ?? []);
        $hidden[] = $key;
        $hidden = array_values(array_unique($hidden));
        $ordered = array_values(array_filter($visible, fn (string $k) => $k !== $key));
        $layouts->savePersonal($user, $ordered, $hidden);

        return redirect()->route('admin.overview', ['edit' => 1]);
    }

    public function show(Request $request, AdminDashboard $dashboard, AdminDashboardLayouts $layouts): RedirectResponse
    {
        $user = $this->admin($request);
        $key = (string) $request->input('key', '');
        $available = $dashboard->cardsFor($user);
        $keys = array_column($available, 'key');
        if (! in_array($key, $keys, true)) {
            return redirect()->route('admin.overview', ['edit' => 1]);
        }

        $layout = $layouts->layoutFor($user);
        $currentHidden = $layout === null ? [] : ($layout->hidden_keys ?? []);
        $hidden = array_values(array_filter($currentHidden, fn (string $k) => $k !== $key));
        $ordered = array_column($layouts->apply($user, $available), 'key');
        if (! in_array($key, $ordered, true)) {
            $ordered[] = $key;
        }
        $layouts->savePersonal($user, $ordered, $hidden);

        return redirect()->route('admin.overview', ['edit' => 1]);
    }

    private function admin(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->hasRole(RoleName::Admin->value), 403);

        return $user;
    }
}
