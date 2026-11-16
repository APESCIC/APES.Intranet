<?php

namespace Modules\Learning\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

/**
 * Stub for #175. Confirms the module route and menu wire up.
 */
class MyLearningController extends Controller
{
    public function __invoke(): View
    {
        return view('learning::mine', [
            'title' => 'My learning',
        ]);
    }
}
