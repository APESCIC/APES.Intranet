<?php

use App\Core\Access\AccessMatrix;
use Illuminate\Support\Facades\Route;
use Modules\Communications\CommunicationsPermissions;
use Modules\Communications\Http\Controllers\DashboardController;
use Modules\Communications\Http\Controllers\DismissDashboardCardController;
use Modules\Communications\Http\Controllers\EventController;
use Modules\Communications\Http\Controllers\HandbookController;
use Modules\Communications\Http\Controllers\MenuModeController;
use Modules\Communications\Http\Controllers\MicrosoftLinkController;
use Modules\Communications\Http\Controllers\PolicyDocumentController;
use Modules\Communications\Http\Controllers\UpdatePostController;

Route::middleware(['web', 'auth'])->group(function () {
    Route::middleware('can:'.AccessMatrix::INTRANET_ACCESS)->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::post('/dashboard/dismiss/{card}', DismissDashboardCardController::class)->name('dashboard.dismiss');

        Route::get('/updates', [UpdatePostController::class, 'index'])->name('communications.updates.index');
        Route::get('/updates/create', [UpdatePostController::class, 'create'])->name('communications.updates.create');
        Route::post('/updates', [UpdatePostController::class, 'store'])->name('communications.updates.store');
        Route::get('/updates/{update}', [UpdatePostController::class, 'show'])->name('communications.updates.show');
        Route::post('/updates/{update}/comments', [UpdatePostController::class, 'comment'])->name('communications.updates.comment');

        Route::get('/events', [EventController::class, 'index'])->name('communications.events.index');
        Route::get('/events/create', [EventController::class, 'create'])->name('communications.events.create');
        Route::post('/events', [EventController::class, 'store'])->name('communications.events.store');
        Route::get('/events/{event}', [EventController::class, 'show'])->name('communications.events.show');
        Route::post('/events/{event}/rsvp', [EventController::class, 'rsvp'])->name('communications.events.rsvp');
        Route::get('/events/{event}/ical', [EventController::class, 'ical'])->name('communications.events.ical');

        Route::get('/handbook', [HandbookController::class, 'index'])->name('communications.handbook.index');
        Route::get('/handbook/create', [HandbookController::class, 'create'])->name('communications.handbook.create');
        Route::post('/handbook', [HandbookController::class, 'store'])->name('communications.handbook.store');
        Route::get('/handbook/{chapter:slug}', [HandbookController::class, 'show'])->name('communications.handbook.show');
        Route::post('/handbook/{chapter:slug}/versions', [HandbookController::class, 'publishVersion'])->name('communications.handbook.versions.store');
        Route::post('/handbook/{chapter:slug}/acknowledge', [HandbookController::class, 'acknowledge'])->name('communications.handbook.acknowledge');
        Route::get('/handbook/{chapter:slug}/acknowledgements.csv', [HandbookController::class, 'exportAcknowledgements'])->name('communications.handbook.acknowledgements');

        Route::get('/policies', [PolicyDocumentController::class, 'index'])->name('communications.policies.index');
        Route::post('/policies', [PolicyDocumentController::class, 'store'])->name('communications.policies.store');
        Route::post('/policies/{policy}/read', [PolicyDocumentController::class, 'markRead'])->name('communications.policies.read');

        Route::post('/menu-mode', MenuModeController::class)->name('communications.menu-mode');
    });

    Route::middleware('can:'.CommunicationsPermissions::MANAGE_M365)->prefix('admin/m365-links')->name('admin.m365-links.')->group(function () {
        Route::get('/', [MicrosoftLinkController::class, 'index'])->name('index');
        Route::post('/', [MicrosoftLinkController::class, 'store'])->name('store');
        Route::put('/{link}', [MicrosoftLinkController::class, 'update'])->name('update');
    });
});
