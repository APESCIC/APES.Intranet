<?php

namespace App\Core\Modules\Http\Middleware;

use App\Core\Modules\ModuleKind;
use App\Core\Modules\ModuleRegistry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureModuleEnabled
{
    public function __construct(private readonly ModuleRegistry $modules) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $slug, string $mode = 'page'): Response
    {
        if ($this->modules->enabled($slug)) {
            return $next($request);
        }

        $manifest = $this->modules->manifest($slug);
        $isWebhook = $mode === 'webhook'
            || ($manifest?->kind === ModuleKind::Integration && $request->is('webhooks/*', 'api/webhooks/*'));

        if ($isWebhook) {
            return response('Module unavailable', Response::HTTP_SERVICE_UNAVAILABLE)
                ->header('Retry-After', '300');
        }

        abort(Response::HTTP_NOT_FOUND);
    }
}
