<?php

use App\Core\Access\Http\Controllers\RoleIndexController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/roles', RoleIndexController::class)->name('roles.index');
});
