<?php

use App\Core\Access\AccessMatrix;
use Illuminate\Support\Facades\Route;
use Modules\Helpdesk\Http\Controllers\TicketController;

Route::middleware(['web', 'auth'])->group(function () {
    Route::middleware('can:'.AccessMatrix::INTRANET_ACCESS)->prefix('help-desk')->name('helpdesk.')->group(function () {
        Route::get('/', [TicketController::class, 'index'])->name('tickets.index');
        Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
        Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
        Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->whereNumber('ticket')->name('tickets.show');
        Route::post('/tickets/{ticket}/replies', [TicketController::class, 'reply'])->whereNumber('ticket')->name('tickets.reply');
    });
});
