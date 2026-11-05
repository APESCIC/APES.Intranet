<?php

namespace App\Core\Auth\Exceptions;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Thrown when sensitive data is opened without a recent Cloudron sign-in.
 */
class FreshSignInRequired extends RuntimeException
{
    public function render(Request $request): RedirectResponse
    {
        if ($request->isMethod('GET')) {
            $request->session()->put('url.intended', $request->fullUrl());
        }

        return redirect()->route('auth.reauth');
    }
}
