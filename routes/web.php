<?php

use App\Core\Access\AccessMatrix;
use App\Core\Access\Http\Controllers\AccessGuideController;
use App\Core\Access\Http\Controllers\AccessReviewController;
use App\Core\Access\Http\Controllers\RoleIndexController;
use App\Core\AdminDashboard\Http\Controllers\AdminDashboardLayoutController;
use App\Core\Appearance\Http\Controllers\AppearancePreferencesController;
use App\Core\Audit\Http\Controllers\AuditLogIndexController;
use App\Core\Auth\Http\Controllers\OidcController;
use App\Core\Changelog\Http\Controllers\ChangeLogController;
use App\Core\Legal\Http\Controllers\AdminFooterLinkController;
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
use App\Http\Controllers\LegalPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});

Route::get('/privacy', [LegalPageController::class, 'show'])->defaults('slug', 'privacy')->name('privacy');
Route::get('/cookies', [LegalPageController::class, 'show'])->defaults('slug', 'cookies')->name('legal.cookies');
Route::get('/terms', [LegalPageController::class, 'show'])->defaults('slug', 'terms')->name('legal.terms');
Route::get('/accessibility', [LegalPageController::class, 'show'])->defaults('slug', 'accessibility')->name('legal.accessibility');
Route::get('/hosting-and-security', [LegalPageController::class, 'show'])->defaults('slug', 'hosting-and-security')->name('legal.hosting-and-security');
Route::get('/legal/{slug}/versions', [LegalPageController::class, 'versions'])->name('legal.versions');

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
        Route::post('/layout', [AdminDashboardLayoutController::class, 'save'])->name('overview.layout.save');
        Route::post('/layout/reset', [AdminDashboardLayoutController::class, 'reset'])->name('overview.layout.reset');
        Route::post('/layout/shared-default', [AdminDashboardLayoutController::class, 'setSharedDefault'])->name('overview.layout.shared-default');
        Route::post('/layout/move', [AdminDashboardLayoutController::class, 'move'])->name('overview.layout.move');
        Route::post('/layout/hide', [AdminDashboardLayoutController::class, 'hide'])->name('overview.layout.hide');
        Route::post('/layout/show', [AdminDashboardLayoutController::class, 'show'])->name('overview.layout.show');
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
        Route::middleware(['can:'.AccessMatrix::LEGAL_PAGES_MANAGE, 'fresh-signin'])->prefix('legal')->name('legal.')->group(function () {
            Route::get('footer-links', [AdminFooterLinkController::class, 'index'])->name('footer-links.index');
            Route::post('footer-links', [AdminFooterLinkController::class, 'store'])->name('footer-links.store');
            Route::put('footer-links/{footerLink}', [AdminFooterLinkController::class, 'update'])->name('footer-links.update');
            Route::delete('footer-links/{footerLink}', [AdminFooterLinkController::class, 'destroy'])->name('footer-links.destroy');
            Route::post('footer-links/{footerLink}/reorder', [AdminFooterLinkController::class, 'reorder'])->name('footer-links.reorder');
        });
        Route::middleware(['can:'.AccessMatrix::ADMIN_MODULES_MANAGE, 'fresh-signin'])->prefix('modules')->name('modules.')->group(function () {
            Route::get('/', [AdminModulesController::class, 'index'])->name('index');
            Route::get('/{slug}', [AdminModulesController::class, 'show'])->name('show');
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
