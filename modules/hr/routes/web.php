<?php

use App\Core\Access\AccessMatrix;
use Illuminate\Support\Facades\Route;
use Modules\Hr\Http\Controllers\HrRecordController;

Route::middleware(['web', 'auth', 'can:'.AccessMatrix::HR_ACCESS])->prefix('hr')->name('hr.')->group(function () {
    Route::get('/records', [HrRecordController::class, 'index'])->name('records.index');
    Route::get('/records/{user}', [HrRecordController::class, 'show'])->whereNumber('user')->name('records.show');
    Route::put('/records/{user}', [HrRecordController::class, 'update'])->whereNumber('user')->name('records.update');
});
