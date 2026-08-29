<?php

use App\Http\Controllers\Ticket\DashboardController;
use App\Http\Controllers\Ticket\TicketCommentController;
use App\Http\Controllers\Ticket\TicketController;
use Illuminate\Support\Facades\Route;

Route::prefix('/tickets')
    ->name('ticket.')->group(function () {

        Route::controller(DashboardController::class)->group(function () {
            Route::get('/dashboard', 'index')->name('dashboard');
        });

        // Tickets CRUD
        Route::controller(TicketController::class)->group(function () {
            // Static routes first
            Route::post('/bulk-assign', 'bulkAssign')->name('tickets.bulk-assign');
            Route::get('/assigned-to-me', 'assignedTickets')->name('tickets.assigned');

            // Resource routes
            Route::get('/', 'index')->name('tickets.index');
            Route::get('/create', 'create')->name('tickets.create');
            Route::post('/', 'store')->name('tickets.store');
            Route::get('/{ticket}', 'show')->name('tickets.show');
            Route::put('/{ticket}', 'update')->name('tickets.update');
            Route::delete('/{ticket}', 'destroy')->name('tickets.destroy');
            
            // Item-specific actions
            Route::post('/{ticket}/start', 'start')->name('tickets.start');
            Route::post('/{ticket}/complete', 'complete')->name('tickets.complete');
        });

        // Ticket Comments
        Route::prefix('/{ticket}/comments')
            ->name('tickets.comments.')
            ->controller(TicketCommentController::class)
            ->group(function () {
                Route::post('/', 'store')->name('store');
                Route::delete('/{comment}', 'destroy')->name('destroy');
            });
    });
