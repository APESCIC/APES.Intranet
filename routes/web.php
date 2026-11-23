<?php

use App\Core\Access\AccessMatrix;
use App\Core\Access\Http\Controllers\AccessGuideController;
use App\Core\Access\Http\Controllers\AccessReviewController;
use App\Core\Access\Http\Controllers\RoleIndexController;
use App\Core\Appearance\Http\Controllers\AppearancePreferencesController;
use App\Core\Audit\Http\Controllers\AuditLogIndexController;
use App\Core\Auth\Http\Controllers\OidcController;
use App\Core\Changelog\Http\Controllers\ChangeLogController;
use App\Core\Modules\Http\Controllers\AdminModulesController;
use App\Core\Navigation\Http\Controllers\AdminOverviewController;
use App\Core\Notifications\Http\Controllers\NotificationInboxController;
use App\Core\Notifications\Http\Controllers\NotificationPreferencesController;
use App\Core\Notifications\Http\Controllers\PushDeliveryStatsController;
use App\Core\Notifications\Http\Controllers\PushSubscriptionController;
use App\Core\Notifications\Http\Controllers\TestPushNotificationController;
use App\Core\Notifications\Http\Controllers\VapidPublicKeyController;
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
    Route::post('/appearance/preferences', AppearancePreferencesController::class)
        ->middleware('throttle:60,1')
        ->name('appearance.preferences.update');

    Route::get('/access', AccessGuideController::class)
        ->middleware('can:'.AccessMatrix::INTRANET_ACCESS)
        ->name('access-guide');

    Route::middleware('can:'.AccessMatrix::INTRANET_ACCESS)->group(function () {
        Route::get('/change-log', [ChangeLogController::class, 'index'])->name('change-log.index');
        Route::get('/change-log/{version}', [ChangeLogController::class, 'show'])
            ->where('version', 'v[0-9]+\.[0-9]+\.[0-9]+')
            ->name('change-log.show');

        Route::get('/push/vapid-public-key', VapidPublicKeyController::class)
            ->name('push.vapid-public-key');
        Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store'])
            ->name('push-subscriptions.store');
        Route::delete('/push-subscriptions', [PushSubscriptionController::class, 'destroy'])
            ->name('push-subscriptions.destroy');

        Route::get('/notifications/preferences', [NotificationPreferencesController::class, 'edit'])
            ->name('notifications.preferences.edit');
        Route::put('/notifications/preferences', [NotificationPreferencesController::class, 'update'])
            ->name('notifications.preferences.update');
        Route::post('/notifications/preferences/test', TestPushNotificationController::class)
            ->name('notifications.preferences.test');

        Route::get('/notifications', [NotificationInboxController::class, 'index'])
            ->name('notifications.inbox');
        Route::post('/notifications/read-all', [NotificationInboxController::class, 'markAllRead'])
            ->name('notifications.mark-all-read');
        Route::post('/notifications/{notification}/read', [NotificationInboxController::class, 'markRead'])
            ->name('notifications.read');
    });

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/', AdminOverviewController::class)->name('overview');
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
            Route::get('/push-delivery', PushDeliveryStatsController::class)->name('push-delivery.index');
        });
        Route::middleware(['can:'.AccessMatrix::ADMIN_MODULES_MANAGE, 'fresh-signin'])->prefix('modules')->name('modules.')->group(function () {
            Route::get('/', [AdminModulesController::class, 'index'])->name('index');
            Route::post('/{slug}/state', [AdminModulesController::class, 'updateState'])->name('state');
            Route::post('/{slug}/settings', [AdminModulesController::class, 'updateSettings'])->name('settings');
        });
    });
});

Route::middleware(EnsureQaLoginEnabled::class)->prefix('qa')->name('qa.')->group(function () {
    Route::get('/', [QaLoginController::class, 'index'])->name('index');
    Route::post('/login', [QaLoginController::class, 'store'])->name('login');
    Route::post('/logout', [QaLoginController::class, 'destroy'])->name('logout');
});
