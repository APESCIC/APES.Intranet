<?php

namespace App\Core\Legal\Http\Middleware;

use App\Core\Legal\TermsAcceptance;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends signed-in people to accept Terms of use until they accept the current
 * version, except on legal pages, sign-out, health, and QA routes.
 */
class EnsureTermsAccepted
{
    public function __construct(private readonly TermsAcceptance $termsAcceptance) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user === null || ! $this->termsAcceptance->isEnabled()) {
            return $next($request);
        }

        if ($this->isExempt($request)) {
            return $next($request);
        }

        if ($this->termsAcceptance->requiredVersion($user) === null) {
            return $next($request);
        }

        if ($request->isMethod('GET')) {
            $request->session()->put('url.intended', $request->fullUrl());
        }

        return redirect()->route('legal.accept.show');
    }

    private function isExempt(Request $request): bool
    {
        if ($request->is('healthz')) {
            return true;
        }

        return $request->routeIs(
            'privacy',
            'legal.cookies',
            'legal.terms',
            'legal.accessibility',
            'legal.hosting-and-security',
            'legal.versions',
            'logout',
            'login',
            'auth.redirect',
            'auth.callback',
            'legal.accept.show',
            'legal.accept.store',
            'legal.notice.dismiss',
            'qa.*',
        );
    }
}
