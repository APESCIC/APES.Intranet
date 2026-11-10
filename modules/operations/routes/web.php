<?php

use App\Core\Access\AccessMatrix;
use Illuminate\Support\Facades\Route;
use Modules\Operations\Http\Controllers\Admin\SiteController;
use Modules\Operations\OperationsPermissions;

Route::middleware(['web', 'auth'])->group(function () {
    Route::middleware('can:'.AccessMatrix::INTRANET_ACCESS)->prefix('operations')->name('operations.')->group(function () {
        //
    });

    Route::prefix('admin/operations')->name('admin.operations.')->group(function () {
        Route::middleware('can:'.OperationsPermissions::SITES_MANAGE)->prefix('sites')->name('sites.')->group(function () {
            Route::get('/', [SiteController::class, 'index'])->name('index');
            Route::post('/', [SiteController::class, 'store'])->name('store');
            Route::put('/{site}', [SiteController::class, 'update'])->name('update');
        });
    });
});
