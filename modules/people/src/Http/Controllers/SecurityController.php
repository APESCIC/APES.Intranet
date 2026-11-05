<?php

namespace Modules\People\Http\Controllers;

use App\Core\Access\AccessMatrix;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SecurityController
{
    public function __invoke(Request $request): View
    {
        abort_unless($request->user()?->can(AccessMatrix::INTRANET_ACCESS), 403);

        return view('people::security.show', [
            'profileUrl' => (string) config('intranet.cloudron.profile_url'),
        ]);
    }
}
