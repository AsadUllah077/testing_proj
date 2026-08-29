<?php

use App\Http\Controllers\Account\Vendor\VendorController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;


// Vendors
// Route::prefix('vendors')->group(function () {
//     Route::get('/', [VendorController::class, 'index'])->name('vendors.index');
//     Route::post('/store', [VendorController::class, 'store']);
//     Route::get('/edit/{id}', [VendorController::class, 'edit']);
//     Route::put('/update/{id}', [VendorController::class, 'update']);
//     Route::delete('/delete/{id}', [VendorController::class, 'destroy']);
// });

Route::prefix('vendors')->name('account.bill.vendors.')->group(function () {
    Route::get('/', [VendorController::class, 'index'])->name('index');
    Route::post('/store', [VendorController::class, 'store']);
    Route::get('/edit/{id}', [VendorController::class, 'edit']);
    Route::put('/update/{id}', [VendorController::class, 'update']);
    Route::delete('/delete/{id}', [VendorController::class, 'destroy']);
    Route::get(
        '/{id}/logs',
        [VendorController::class, 'logs']
    )->name('logs');
    Route::delete(
        '/bulk-delete',
        [VendorController::class, 'bulkDelete']
    )->name('bulk-delete');
    Route::post('/check-email', [VendorController::class, 'checkEmail']);
    Route::post('/restore/{id}', [VendorController::class, 'restore']);
    Route::post('/export', [VendorController::class, 'export'])->name('export');
    Route::post('/export-progress', [VendorController::class, 'exportProgress'])->name('export.progress');
    Route::post('/store-inline', [VendorController::class, 'storeInline'])
        ->name('store.inline');
    Route::get('/paginate', [VendorController::class, 'paginate'])
        ->name('paginate');
});