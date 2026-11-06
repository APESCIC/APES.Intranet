<?php

use App\Core\Access\AccessMatrix;
use App\Core\Access\Http\Controllers\AccessGuideController;
use App\Core\Access\Http\Controllers\AccessReviewController;
use App\Core\Access\Http\Controllers\RoleIndexController;
use App\Core\Audit\Http\Controllers\AuditLogIndexController;
use App\Core\Auth\Http\Controllers\OidcController;
use App\Core\Qa\Http\Controllers\QaLoginController;
use App\Core\Qa\Http\Middleware\EnsureQaLoginEnabled;
use App\Core\System\Http\Controllers\SendTestEmailController;
use App\Core\System\Http\Controllers\SystemStatusController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});

Route::view('/privacy', 'core.privacy')->name('privacy');

Route::get('/login', [OidcController::class, 'show'])->name('login');
Route::get('/auth/redirect', [OidcController::class, 'redirect'])->name('auth.redirect');
Route::get('/auth/callback', [OidcController::class, 'callback'])->name('auth.callback');
Route::get('/auth/reauth', [OidcController::class, 'reauthenticate'])->middleware('auth')->name('auth.reauth');
Route::post('/logout', [OidcController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/access', AccessGuideController::class)
        ->middleware('can:'.AccessMatrix::INTRANET_ACCESS)
        ->name('access-guide');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/roles', RoleIndexController::class)->name('roles.index');
        Route::get('/audit', AuditLogIndexController::class)
            ->middleware('can:'.AccessMatrix::ADMIN_AUDIT_VIEW)
            ->name('audit.index');
        Route::get('/access-review', AccessReviewController::class)
            ->middleware(['can:'.AccessMatrix::ACCESS_REVIEW_VIEW, 'fresh-signin'])
            ->name('access-review.index');
        Route::middleware(['can:'.AccessMatrix::ADMIN_SYSTEM_VIEW, 'fresh-signin'])->group(function () {
            Route::get('/system', SystemStatusController::class)->name('system.index');
            Route::post('/system/test-email', SendTestEmailController::class)->name('system.test-email');
        });
    });
});

Route::middleware(EnsureQaLoginEnabled::class)->prefix('qa')->name('qa.')->group(function () {
    Route::get('/', [QaLoginController::class, 'index'])->name('index');
    Route::post('/login', [QaLoginController::class, 'store'])->name('login');
    Route::post('/logout', [QaLoginController::class, 'destroy'])->name('logout');
});
