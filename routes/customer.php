<?php

use App\Http\Controllers\Account\Customer\CustomerController;
use Illuminate\Support\Facades\Route;

Route::prefix('customers')
    ->name('account.sale.customers.')
    ->middleware('permission:account.customers.view')
    ->group(function () {
        Route::get('/', [CustomerController::class, 'index'])->name('index');
        Route::post('/store', [CustomerController::class, 'store'])->middleware('permission:account.customers.create')->name('store');
        Route::get('/edit/{id}', [CustomerController::class, 'edit'])->middleware('permission:account.customers.edit')->name('edit');
        Route::put('/update/{id}', [CustomerController::class, 'update'])->middleware('permission:account.customers.edit')->name('update');
        Route::delete('/customers/{id}', [CustomerController::class, 'destroy'])->middleware('permission:account.customers.delete')->name('destroy');
        Route::get('/{id}/logs', [CustomerController::class, 'logs'])->name('logs');
        Route::get('/all-logs', [CustomerController::class, 'allLogs'])->name('all-logs');
        Route::delete('bulk-delete', [CustomerController::class, 'bulkDelete'])->middleware('permission:account.customers.bulk-delete')->name('bulk-delete');
        Route::post('/check-email', [CustomerController::class, 'checkEmail'])->name('check-email');
        Route::post('/restore/{id}', [CustomerController::class, 'restore'])->name('restore');
        Route::post('/customers/store-inline', [CustomerController::class, 'storeInline'])
            ->middleware('permission:account.customers.store-inline')
            ->name('store.inline');
        Route::get('/export-pdf', [CustomerController::class, 'exportPdf'])->name('export-pdf');
        Route::get('/export-excel', [CustomerController::class, 'exportExcel'])->name('export-excel');
        Route::get('/customers/paginate', [CustomerController::class, 'paginate'])->name('paginate');
        Route::get('/{id}', [CustomerController::class, 'show'])->name('show');
    });
