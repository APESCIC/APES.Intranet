<?php

namespace App\Core\Auth\Http\Middleware;

use App\Core\Audit\AuditLogger;
use App\Core\Auth\FreshSignIn;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends anyone whose Cloudron sign-in is older than the window back through
 * Cloudron, then returns them to the page they asked for. Use it on HR,
 * agreements and other sensitive pages, for every role including admin.
 */
class RequireFreshSignIn
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(Request $request, Closure $next): Response
    {
        $fresh = new FreshSignIn($request->session());

        if ($request->user() === null || $fresh->isFresh()) {
            return $next($request);
        }

        $this->audit->record('auth.fresh_signin_required', $request->user(), ['path' => '/'.ltrim($request->path(), '/')]);

        if ($request->isMethod('GET')) {
            $request->session()->put('url.intended', $request->fullUrl());
        }

        return redirect()->route('auth.reauth');
    }
}
