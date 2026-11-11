<?php

use App\Core\Access\AccessMatrix;
use Illuminate\Support\Facades\Route;
use Modules\Helpdesk\HelpdeskPermissions;
use Modules\Helpdesk\Http\Controllers\Admin\CategoryController;
use Modules\Helpdesk\Http\Controllers\QueueController;
use Modules\Helpdesk\Http\Controllers\TicketController;
use Modules\Helpdesk\Http\Controllers\TicketWorkController;
use Modules\Helpdesk\Models\Ticket;

Route::middleware(['web', 'auth'])->group(function () {
    Route::middleware('can:'.AccessMatrix::INTRANET_ACCESS)->prefix('help-desk')->name('helpdesk.')->group(function () {
        Route::get('/', [TicketController::class, 'index'])->name('tickets.index');
        Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
        Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
        Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->whereNumber('ticket')->name('tickets.show');
        Route::post('/tickets/{ticket}/replies', [TicketController::class, 'reply'])->whereNumber('ticket')->name('tickets.reply');

        Route::post('/tickets/{ticket}/notes', [TicketWorkController::class, 'note'])->whereNumber('ticket')->name('tickets.note');
        Route::patch('/tickets/{ticket}/assignee', [TicketWorkController::class, 'assign'])->whereNumber('ticket')->name('tickets.assign');
        Route::patch('/tickets/{ticket}/status', [TicketWorkController::class, 'status'])->whereNumber('ticket')->name('tickets.status');
        Route::post('/tickets/{ticket}/merge', [TicketWorkController::class, 'merge'])->whereNumber('ticket')->name('tickets.merge');
        Route::patch('/tickets/{ticket}/topic', [TicketWorkController::class, 'reclassify'])->whereNumber('ticket')->name('tickets.reclassify');

        foreach (array_keys(Ticket::QUEUES) as $queue) {
            Route::get('/queues/'.$queue, [QueueController::class, 'show'])->defaults('queue', $queue)->name('queues.'.$queue);
        }
    });

    Route::middleware('can:'.HelpdeskPermissions::CATEGORIES_MANAGE)->prefix('admin/help-desk/topics')->name('admin.helpdesk.categories.')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::post('/', [CategoryController::class, 'store'])->name('store');
        Route::put('/{category}', [CategoryController::class, 'update'])->name('update');
    });
});
