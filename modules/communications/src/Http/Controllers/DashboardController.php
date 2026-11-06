<?php

namespace Modules\Communications\Http\Controllers;

use App\Core\Access\AccessMatrix;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Communications\Services\DashboardCards;

class DashboardController
{
    public function __invoke(Request $request, DashboardCards $cards): View
    {
        abort_unless($request->user()?->can(AccessMatrix::INTRANET_ACCESS), 403);

        return view('communications::dashboard.index', [
            'cards' => $cards->for($request->user()),
        ]);
    }
}
