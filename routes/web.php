<?php

use App\Core\Access\AccessMatrix;
use App\Core\Access\Http\Controllers\RoleIndexController;
use App\Core\Auth\Http\Controllers\OidcController;
use App\Core\Qa\Http\Controllers\QaLoginController;
use App\Core\Qa\Http\Middleware\EnsureQaLoginEnabled;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});

Route::get('/login', [OidcController::class, 'show'])->name('login');
Route::get('/auth/redirect', [OidcController::class, 'redirect'])->name('auth.redirect');
Route::get('/auth/callback', [OidcController::class, 'callback'])->name('auth.callback');
Route::post('/logout', [OidcController::class, 'logout'])->middleware('auth')->name('logout');

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
