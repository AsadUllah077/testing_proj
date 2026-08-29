<?php

use App\Http\Controllers\Account\Vendor\VendorController;
use Illuminate\Support\Facades\Route;

Route::prefix('vendors')
    ->name('account.bill.vendors.')
    ->middleware('permission:account.vendors.view')
    ->group(function () {
        Route::get('/', [VendorController::class, 'index'])->name('index');
        Route::post('/store', [VendorController::class, 'store'])->middleware('permission:account.vendors.create')->name('store');
        Route::get('/edit/{id}', [VendorController::class, 'edit'])->middleware('permission:account.vendors.edit')->name('edit');
        Route::put('/update/{id}', [VendorController::class, 'update'])->middleware('permission:account.vendors.edit')->name('update');
        Route::delete('/delete/{id}', [VendorController::class, 'destroy'])->middleware('permission:account.vendors.delete')->name('destroy');
        Route::get('/{id}/logs', [VendorController::class, 'logs'])->name('logs');
        Route::delete('/bulk-delete', [VendorController::class, 'bulkDelete'])->middleware('permission:account.vendors.bulk-delete')->name('bulk-delete');
        Route::post('/check-email', [VendorController::class, 'checkEmail'])->name('check-email');
        Route::post('/restore/{id}', [VendorController::class, 'restore'])->name('restore');
        Route::post('/export', [VendorController::class, 'export'])->middleware('permission:account.vendors.export')->name('export');
        Route::post('/export-progress', [VendorController::class, 'exportProgress'])->name('export.progress');
        Route::post('/store-inline', [VendorController::class, 'storeInline'])
            ->middleware('permission:account.vendors.store-inline')
            ->name('store.inline');
        Route::get('/export-pdf', [VendorController::class, 'exportPdf'])->name('export-pdf');
        Route::get('/export-excel', [VendorController::class, 'exportExcel'])->name('export-excel');
        Route::get('/paginate', [VendorController::class, 'paginate'])->name('paginate');
        Route::get('/{id}', [VendorController::class, 'show'])->name('show');
    });
