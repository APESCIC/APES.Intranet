<?php

use Illuminate\Support\Facades\Route;
use Modules\Microsoft365\Http\Controllers\MicrosoftLinkController;
use Modules\Microsoft365\Microsoft365Permissions;

Route::middleware(['web', 'auth', 'can:'.Microsoft365Permissions::MANAGE, 'module:microsoft-365'])
    ->prefix('admin/m365-links')
    ->name('admin.m365-links.')
    ->group(function () {
        Route::get('/', [MicrosoftLinkController::class, 'index'])->name('index');
        Route::post('/', [MicrosoftLinkController::class, 'store'])->name('store');
        Route::put('/{link}', [MicrosoftLinkController::class, 'update'])->name('update');
    });
