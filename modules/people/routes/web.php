<?php

use App\Core\Access\AccessMatrix;
use Illuminate\Support\Facades\Route;
use Modules\People\Http\Controllers\DirectoryController;
use Modules\People\Http\Controllers\ManagerController;
use Modules\People\Http\Controllers\OrgChartController;
use Modules\People\Http\Controllers\PeoplePickerController;
use Modules\People\Http\Controllers\ProfileController;
use Modules\People\Http\Controllers\SecurityController;
use Modules\People\Http\Controllers\StudentPlacementController;
use Modules\People\Http\Controllers\TotpStatusController;

Route::middleware(['web', 'auth'])->group(function () {
    Route::middleware('can:'.AccessMatrix::INTRANET_ACCESS)->group(function () {
        Route::get('/people', [DirectoryController::class, 'index'])->name('people.directory.index');
        Route::get('/people/picker', PeoplePickerController::class)->name('people.picker');
        Route::get('/people/org-chart', OrgChartController::class)->name('people.org-chart');
        Route::get('/people/{user}', [DirectoryController::class, 'show'])->name('people.directory.show');
        Route::get('/profile/me', [ProfileController::class, 'edit'])->name('people.profile.edit');
        Route::put('/profile/me', [ProfileController::class, 'update'])->name('people.profile.update');
        Route::get('/profile/{user}', [ProfileController::class, 'show'])->name('people.profile.show');
        Route::patch('/people/{user}/manager', [ManagerController::class, 'update'])->name('people.manager.update');
        Route::get('/security', SecurityController::class)->name('people.security');
    });

    Route::prefix('students')->name('people.students.')->group(function () {
        Route::get('/', [StudentPlacementController::class, 'index'])->name('index');
        Route::get('/placements/{placement}', [StudentPlacementController::class, 'show'])->name('show');
        Route::post('/placements/{placement}/hours', [StudentPlacementController::class, 'storeHours'])->name('hours.store');
        Route::post('/hours/{hour}/review', [StudentPlacementController::class, 'reviewHours'])->name('hours.review');
    });

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/2fa-status', [TotpStatusController::class, 'index'])->name('totp.index');
        Route::post('/2fa-status/refresh', [TotpStatusController::class, 'refresh'])->name('totp.refresh');
    });
});
