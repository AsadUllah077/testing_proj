<?php

use App\Http\Controllers\Home\CompanyController;
use Illuminate\Support\Facades\Route;

Route::prefix('home')
    ->name('home.')
    ->controller(CompanyController::class)
    ->group(function () {
        // 👉 List all companies
        Route::get('/companies', 'index')->name('companies.index');
        Route::get('/companies/create', 'create')->name('companies.create');
        Route::post('/companies', 'store')->name('companies.store');
        Route::get('/companies/{id}', 'show')->name('companies.show');
        Route::get('/companies/{id}/edit', 'edit')->name('companies.edit');
        Route::put('/companies/{id}', 'update')->name('companies.update');
        Route::delete('/companies/{id}', 'destroy')->name('companies.destroy');
        Route::get('/companies/get-states/{country_id}', 'getStates')->name('companies.getStates');
       
    });
