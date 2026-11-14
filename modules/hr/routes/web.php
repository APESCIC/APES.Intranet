<?php

use App\Core\Access\AccessMatrix;
use Illuminate\Support\Facades\Route;
use Modules\Hr\Http\Controllers\HrRecordController;
use Modules\Hr\Http\Controllers\IdentityCheckController;

Route::middleware(['web', 'auth', 'can:'.AccessMatrix::HR_ACCESS])->prefix('hr')->name('hr.')->group(function () {
    Route::get('/records', [HrRecordController::class, 'index'])->name('records.index');
    Route::get('/records/{user}', [HrRecordController::class, 'show'])->whereNumber('user')->name('records.show');
    Route::put('/records/{user}', [HrRecordController::class, 'update'])->whereNumber('user')->name('records.update');

    Route::middleware('can:'.AccessMatrix::HR_SPECIAL_CATEGORY_VIEW)->group(function () {
        Route::get('/checks/report', [IdentityCheckController::class, 'report'])->name('checks.report');
        Route::post('/records/{user}/checks', [IdentityCheckController::class, 'store'])->whereNumber('user')->name('checks.store');
    });
});
