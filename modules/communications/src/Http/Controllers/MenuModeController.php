<?php

namespace Modules\Communications\Http\Controllers;

use App\Core\Access\RoleName;
use App\Core\Navigation\MenuMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MenuModeController
{
    public function __invoke(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasRole(RoleName::Admin->value), 403);

        $data = $request->validate([
            'mode' => ['required', 'in:normal,admin'],
        ]);

        $mode = MenuMode::from($data['mode']);
        $request->session()->put(MenuMode::SESSION_KEY, $mode->value);

        return back();
    }
}
