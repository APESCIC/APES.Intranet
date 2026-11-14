<?php

use App\Core\Access\AccessMatrix;
use Illuminate\Support\Facades\Route;
use Modules\Hr\Http\Controllers\ChecklistController;
use Modules\Hr\Http\Controllers\HrRecordController;
use Modules\Hr\Http\Controllers\IdentityCheckController;
use Modules\Hr\Http\Controllers\LeaveController;

Route::middleware(['web', 'auth', 'can:'.AccessMatrix::INTRANET_ACCESS])->prefix('hr')->name('hr.')->group(function () {
    Route::get('/leave', [LeaveController::class, 'index'])->name('leave.index');
    Route::post('/leave', [LeaveController::class, 'store'])->name('leave.store');
    Route::post('/leave/{leaveRequest}/approve', [LeaveController::class, 'approve'])->whereNumber('leaveRequest')->name('leave.approve');
    Route::post('/leave/{leaveRequest}/decline', [LeaveController::class, 'decline'])->whereNumber('leaveRequest')->name('leave.decline');
    Route::post('/leave/absence', [LeaveController::class, 'storeAbsence'])->name('leave.absence');
});

Route::middleware(['web', 'auth', 'can:'.AccessMatrix::HR_ACCESS])->prefix('hr')->name('hr.')->group(function () {
    Route::get('/records', [HrRecordController::class, 'index'])->name('records.index');
    Route::get('/records/{user}', [HrRecordController::class, 'show'])->whereNumber('user')->name('records.show');
    Route::put('/records/{user}', [HrRecordController::class, 'update'])->whereNumber('user')->name('records.update');

    Route::middleware('can:'.AccessMatrix::HR_SPECIAL_CATEGORY_VIEW)->group(function () {
        Route::get('/checks/report', [IdentityCheckController::class, 'report'])->name('checks.report');
        Route::post('/records/{user}/checks', [IdentityCheckController::class, 'store'])->whereNumber('user')->name('checks.store');
    });

    Route::get('/checklists', [ChecklistController::class, 'index'])->name('checklists.index');
    Route::post('/checklists', [ChecklistController::class, 'store'])->name('checklists.store');
    Route::get('/checklists/{checklist}', [ChecklistController::class, 'show'])->whereNumber('checklist')->name('checklists.show');
    Route::post('/checklists/items/{item}/complete', [ChecklistController::class, 'completeItem'])->whereNumber('item')->name('checklists.items.complete');
});
