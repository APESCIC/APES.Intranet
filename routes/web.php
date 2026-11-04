<?php

use App\Core\Access\Http\Controllers\RoleIndexController;
use App\Core\Qa\Http\Controllers\QaLoginController;
use App\Core\Qa\Http\Middleware\EnsureQaLoginEnabled;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/roles', RoleIndexController::class)->name('roles.index');
});

Route::middleware(EnsureQaLoginEnabled::class)->prefix('qa')->name('qa.')->group(function () {
    Route::get('/', [QaLoginController::class, 'index'])->name('index');
    Route::post('/login', [QaLoginController::class, 'store'])->name('login');
    Route::post('/logout', [QaLoginController::class, 'destroy'])->name('logout');
});
