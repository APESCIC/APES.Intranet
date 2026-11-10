<?php

namespace Modules\Communications\Http\Controllers;

use App\Core\Access\RoleName;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The old Normal/Admin form posted here. Pages open in old tabs may still
 * post to it, so it sends people to the matching panel and stores nothing.
 */
class MenuModeController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $isAdmin = $request->user()?->hasRole(RoleName::Admin->value) ?? false;

        return $isAdmin && $request->input('mode') === 'admin'
            ? redirect()->route('admin.overview')
            : redirect()->route('dashboard');
    }
}
