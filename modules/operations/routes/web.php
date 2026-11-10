<?php

use App\Core\Access\AccessMatrix;
use Illuminate\Support\Facades\Route;
use Modules\Operations\Http\Controllers\Admin\SiteController;
use Modules\Operations\Http\Controllers\AvailabilityController;
use Modules\Operations\Http\Controllers\RotaController;
use Modules\Operations\Http\Controllers\ShiftRequestController;
use Modules\Operations\Http\Controllers\ShiftTemplateController;
use Modules\Operations\OperationsPermissions;

Route::middleware(['web', 'auth'])->group(function () {
    Route::middleware('can:'.AccessMatrix::INTRANET_ACCESS)->prefix('operations')->name('operations.')->group(function () {
        Route::get('/rota', [RotaController::class, 'index'])->name('rota.index');
        Route::post('/rota/weeks', [RotaController::class, 'storeWeek'])->name('rota.weeks.store');
        Route::get('/rota/weeks/{week}', [RotaController::class, 'showWeek'])->name('rota.weeks.show');
        Route::post('/rota/weeks/{week}/shifts', [RotaController::class, 'storeShift'])->name('rota.weeks.shifts.store');
        Route::post('/rota/weeks/{week}/publish', [RotaController::class, 'publish'])->name('rota.weeks.publish');
        Route::patch('/rota/shifts/{shift}', [RotaController::class, 'updateShift'])->name('rota.shifts.update');
        Route::delete('/rota/shifts/{shift}', [RotaController::class, 'destroyShift'])->name('rota.shifts.destroy');

        Route::get('/rota/open', [ShiftRequestController::class, 'open'])->name('rota.open');
        Route::post('/rota/shifts/{shift}/claim', [ShiftRequestController::class, 'claim'])->name('rota.claim');
        Route::get('/rota/shifts/{shift}/swap', [ShiftRequestController::class, 'createSwap'])->name('rota.swap.create');
        Route::post('/rota/shifts/{shift}/swap', [ShiftRequestController::class, 'storeSwap'])->name('rota.swap.store');
        Route::get('/rota/requests', [ShiftRequestController::class, 'index'])->name('rota.requests.index');
        Route::post('/rota/requests/{shiftRequest}/decide', [ShiftRequestController::class, 'decide'])->name('rota.requests.decide');
        Route::post('/rota/requests/{shiftRequest}/cancel', [ShiftRequestController::class, 'cancel'])->name('rota.requests.cancel');

        Route::get('/rota/templates', [ShiftTemplateController::class, 'index'])->name('rota.templates.index');
        Route::post('/rota/templates', [ShiftTemplateController::class, 'store'])->name('rota.templates.store');
        Route::delete('/rota/templates/{template}', [ShiftTemplateController::class, 'destroy'])->name('rota.templates.destroy');

        Route::get('/rota/availability', [AvailabilityController::class, 'index'])->name('rota.availability.index');
        Route::post('/rota/availability', [AvailabilityController::class, 'store'])->name('rota.availability.store');
        Route::delete('/rota/availability/{availability}', [AvailabilityController::class, 'destroy'])->name('rota.availability.destroy');
    });

    Route::prefix('admin/operations')->name('admin.operations.')->group(function () {
        Route::middleware('can:'.OperationsPermissions::SITES_MANAGE)->prefix('sites')->name('sites.')->group(function () {
            Route::get('/', [SiteController::class, 'index'])->name('index');
            Route::post('/', [SiteController::class, 'store'])->name('store');
            Route::put('/{site}', [SiteController::class, 'update'])->name('update');
        });
    });
});
