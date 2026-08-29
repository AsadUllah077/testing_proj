<?php

use App\Http\Controllers\Account\OrganizationSetting\TaxRateController;
use Illuminate\Support\Facades\Route;


// Route::prefix('settings/tax-rate')->group(function () {
//     Route::get('/', [TaxRateController::class, 'index'])->name('account.organization-settings.tax_rate.index');
//     Route::post('/store', [TaxRateController::class, 'store'])->name('account.organization-settings.tax-rate.store');
//     Route::get('/edit/{id}', [TaxRateController::class, 'edit'])->name('account.organization-settings.tax-rate.edit');
//     Route::put('/update/{id}', [TaxRateController::class, 'update'])->name('account.organization-settings.tax-rate.update');
//     Route::delete('/delete/{id}', [TaxRateController::class, 'destroy'])->name('account.organization-settings.tax-rate.delete');
// });

Route::prefix('settings/tax-rate')->name('account.organization-settings.tax_rate.')->group(function () {
    Route::get('/', [TaxRateController::class, 'index'])->name('index');
    Route::post('/store', [TaxRateController::class, 'store'])->name('store');
    Route::get('/edit/{id}', [TaxRateController::class, 'edit'])->name('edit');
    Route::put('/update/{id}', [TaxRateController::class, 'update'])->name('update');
    // Route::delete('/customers/{id}', [TaxRateController::class, 'destroy'])->name('destroy');
    Route::delete('/delete/{id}', [TaxRateController::class, 'destroy'])->name('delete');
    Route::get(
        '/{id}/logs',
        [TaxRateController::class, 'logs']
    )->name('logs');
    Route::get(
        '/all-logs',
        [TaxRateController::class, 'allLogs']
    )->name('all.logs');
});
