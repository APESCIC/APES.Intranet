<?php

use App\Core\Auth\Http\Middleware\RequireFreshSignIn;
use App\Core\Health\HealthzController;
use App\Core\Http\Middleware\PreventCachingSignedInPages;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        // Outside the web group: no session or cookies, so a Redis outage gives a clean 503.
        then: function () {
            Route::get('/healthz', HealthzController::class)->name('healthz');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->alias(['fresh-signin' => RequireFreshSignIn::class]);
        $middleware->web(append: [PreventCachingSignedInPages::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
