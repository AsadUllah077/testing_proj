<?php

// routes/web.php or routes/tasks.php

use App\Http\Controllers\HRM\TaskController;

use Illuminate\Support\Facades\Route;

Route::prefix('tasks')
    ->name('tasks.')

    ->group(function () {
        Route::get('/', [TaskController::class, 'index'])->name('index');
        Route::get('/create', [TaskController::class, 'create'])->name('create');
        Route::post('/store', [TaskController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [TaskController::class, 'edit'])->name('edit');
        Route::put('/{id}/update', [TaskController::class, 'update'])->name('update');
        Route::delete('/{id}/destroy', [TaskController::class, 'destroy'])->name('destroy');
        Route::get('/{id}/show', [TaskController::class, 'show'])->name('show');
        Route::post('/{id}/update-status', [TaskController::class, 'updateStatus'])->name('update-status');
        Route::post('/{id}/mark-completed', [TaskController::class, 'markAsCompleted'])->name('mark-completed');
        Route::get('/get-statistics', [TaskController::class, 'getStatistics'])->name('statistics');
        Route::get('/calendar-data', [TaskController::class, 'getCalendarData'])->name('calendar-data');
        // Add to your task routes

    });
// Task deliverables routes
Route::post('/tasks/deliverables/store', [TaskController::class, 'storeDeliverable'])->name('tasks.deliverables.store');
Route::get('/tasks/deliverables/{id}/details', [TaskController::class, 'getDeliverableDetails'])->name('tasks.deliverables.details');
Route::delete('/tasks/deliverables/{id}/delete', [TaskController::class, 'deleteDeliverable'])->name('tasks.deliverables.delete');
Route::post('tasks/{id}/manager-action', [TaskController::class, 'managerAction'])->name('tasks.manager-action');
// Bulk actions routes
Route::post('/tasks/bulk-complete', [TaskController::class, 'bulkComplete'])->name('tasks.bulk-complete');
Route::delete('/tasks/bulk-delete', [TaskController::class, 'bulkDelete'])->name('tasks.bulk-delete');
// Bulk status change route
Route::post('/tasks/bulk-status', [TaskController::class, 'bulkStatus'])->name('tasks.bulk-status');

// Individual task status change route
Route::post('/tasks/{task}/change-status', [TaskController::class, 'changeStatus'])->name('tasks.change-status');
// Task Import Routes
Route::get('/tasks/import', [TaskController::class, 'showImportForm'])->name('tasks.import');
Route::get('/tasks/download-template', [TaskController::class, 'downloadTemplate'])->name('tasks.download-template');
Route::post('/tasks/validate-import', [TaskController::class, 'validateImport'])->name('tasks.validate-import');
Route::post('/tasks/process-import', [TaskController::class, 'processImport'])->name('tasks.process-import');
// Bulk operations
Route::post('/tasks/bulk-update', [TaskController::class, 'bulkUpdate'])->name('tasks.bulk-update');
Route::post('/tasks/bulk-delete', [TaskController::class, 'bulkDelete'])->name('tasks.bulk-delete');


