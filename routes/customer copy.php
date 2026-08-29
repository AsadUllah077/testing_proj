<?php

use App\Http\Controllers\Account\Customer\CustomerController;
use Illuminate\Support\Facades\Route;

Route::prefix('customers')->name('account.sale.customers.')->group(function () {
    Route::get('/', [CustomerController::class, 'index'])->name('index');
    Route::post('/store', [CustomerController::class, 'store'])->name('store');
    Route::get('/edit/{id}', [CustomerController::class, 'edit'])->name('edit');
    Route::put('/update/{id}', [CustomerController::class, 'update'])->name('update');
    Route::delete('/customers/{id}', [CustomerController::class, 'destroy'])->name('destroy');
    Route::get(
        '/{id}/logs',
        [CustomerController::class, 'logs']
    )->name('logs');
    Route::get(
        '/all-logs',
        [CustomerController::class, 'allLogs']
    )->name(name: 'all-logs');
    Route::delete(
        'bulk-delete',
        [CustomerController::class, 'bulkDelete']
    )->name('bulk-delete');

    Route::post('/check-email', [CustomerController::class, 'checkEmail']);
    Route::post('/restore/{id}', [CustomerController::class, 'restore']);
    Route::post('/customers/store-inline', [CustomerController::class, 'storeInline'])
        ->name('store.inline');
    Route::get('/customers/paginate', [CustomerController::class, 'paginate'])
        ->name('paginate');
});
