<?php

use App\Http\Controllers\Home\CompanyController;
use Illuminate\Support\Facades\Route;

Route::prefix('home')
    ->name('home.')
    ->middleware('permission:home.dashboard.view')
    ->group(function () {
        Route::controller(CompanyController::class)->group(function () {
            Route::get('/companies', 'index')->middleware('permission:home.companies.view')->name('companies.index');
            Route::get('/companies/create', 'create')->middleware('permission:home.companies.create')->name('companies.create');
            Route::post('/companies', 'store')->middleware('permission:home.companies.create')->name('companies.store');
            Route::get('/companies/{id}', 'show')->middleware('permission:home.companies.show')->name('companies.show');
            Route::get('/companies/{id}/edit', 'edit')->middleware('permission:home.companies.edit')->name('companies.edit');
            Route::put('/companies/{id}', 'update')->middleware('permission:home.companies.edit')->name('companies.update');
            Route::delete('/companies/{id}', 'destroy')->middleware('permission:home.companies.delete')->name('companies.destroy');
            Route::get('/companies/get-states/{country_id}', 'getStates')->name('companies.getStates');
        });
    });
