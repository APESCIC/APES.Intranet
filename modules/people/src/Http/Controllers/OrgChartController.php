<?php

namespace Modules\People\Http\Controllers;

use App\Core\Access\AccessMatrix;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\People\Support\ManagerGraph;

class OrgChartController
{
    public function __construct() {}

    public function __invoke(Request $request): View
    {
        abort_unless($request->user()?->can(AccessMatrix::INTRANET_ACCESS), 403);

        $tree = ManagerGraph::tree();
        $list = User::query()
            ->directoryVisible()
            ->with('manager')
            ->orderBy('name')
            ->get();

        return view('people::org-chart.index', [
            'tree' => $tree,
            'list' => $list,
        ]);
    }
}
