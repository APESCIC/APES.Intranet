<?php

use App\Core\Access\AccessMatrix;
use App\Core\Access\Http\Controllers\RoleIndexController;
use App\Core\Qa\Http\Controllers\QaLoginController;
use App\Core\Qa\Http\Middleware\EnsureQaLoginEnabled;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::view('/dashboard', 'core.dashboard')
        ->middleware('can:'.AccessMatrix::INTRANET_ACCESS)
        ->name('dashboard');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/roles', RoleIndexController::class)->name('roles.index');
    });
});

Route::middleware(EnsureQaLoginEnabled::class)->prefix('qa')->name('qa.')->group(function () {
    Route::get('/', [QaLoginController::class, 'index'])->name('index');
    Route::post('/login', [QaLoginController::class, 'store'])->name('login');
    Route::post('/logout', [QaLoginController::class, 'destroy'])->name('logout');
});
