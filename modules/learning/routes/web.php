<?php

use Illuminate\Support\Facades\Route;
use Modules\Learning\Http\Controllers\MyLearningController;
use Modules\Learning\LearningPermissions;

Route::middleware(['web', 'auth', 'can:'.LearningPermissions::ACCESS, 'module:learning'])
    ->prefix('learning')
    ->name('learning.')
    ->group(function () {
        Route::get('/', MyLearningController::class)->name('mine');
    });
