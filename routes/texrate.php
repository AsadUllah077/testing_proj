<?php

use App\Http\Controllers\Account\OrganizationSetting\TaxRateController;
use Illuminate\Support\Facades\Route;

Route::prefix('settings/tax-rate')
    ->name('account.organization-settings.tax_rate.')
    ->middleware('permission:account.tax-rate.view')
    ->group(function () {
        Route::get('/', [TaxRateController::class, 'index'])->name('index');
        Route::post('/store', [TaxRateController::class, 'store'])->middleware('permission:account.tax-rate.create')->name('store');
        Route::get('/edit/{id}', [TaxRateController::class, 'edit'])->middleware('permission:account.tax-rate.edit')->name('edit');
        Route::put('/update/{id}', [TaxRateController::class, 'update'])->middleware('permission:account.tax-rate.edit')->name('update');
        Route::delete('/delete/{id}', [TaxRateController::class, 'destroy'])->middleware('permission:account.tax-rate.delete')->name('delete');
        Route::get('/{id}/logs', [TaxRateController::class, 'logs'])->name('logs');
        Route::get('/all-logs', [TaxRateController::class, 'allLogs'])->name('all.logs');
    });
