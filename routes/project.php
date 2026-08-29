<?php

use App\Http\Controllers\Project\DashboardController;
use App\Http\Controllers\Project\ProjectController;
use App\Http\Controllers\Project\ProjectTaskController;
use Illuminate\Support\Facades\Route;

Route::prefix('/projects')
    ->name('project.')->group(function () {
        Route::controller(DashboardController::class)->group(function () {
            Route::get('/dashboard', 'index')->name('dashboard');
        });

        // Clients (must be before /{project} wildcard)
        Route::prefix('/clients')->name('clients.')->controller(\App\Http\Controllers\Project\ClientController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/export/pdf', 'exportPdf')->name('export.pdf');
            Route::get('/export/excel', 'exportExcel')->name('export.excel');
            Route::get('/{client}', 'show')->name('show');
            Route::get('/{client}/edit', 'edit')->name('edit');
            Route::put('/{client}', 'update')->name('update');
            Route::delete('/{client}', 'destroy')->name('destroy');
        });

        // Teams
        Route::prefix('/teams')->name('teams.')->controller(\App\Http\Controllers\Project\TeamController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{team}/edit', 'edit')->name('edit');
            Route::put('/{team}', 'update')->name('update');
            Route::delete('/{team}', 'destroy')->name('destroy');
        });

        // Projects CRUD
        Route::controller(ProjectController::class)->group(function () {
            Route::get('/', 'index')->name('projects.index');
            Route::get('/create', 'create')->name('projects.create');
            Route::post('/', 'store')->name('projects.store');
            Route::get('/export/pdf', 'exportPdf')->name('projects.export.pdf');
            Route::get('/export/excel', 'exportExcel')->name('projects.export.excel');
            Route::get('/{project}', 'show')->name('projects.show');
            Route::get('/{project}/edit', 'edit')->name('projects.edit');
            Route::put('/{project}', 'update')->name('projects.update');
            Route::delete('/{project}', 'destroy')->name('projects.destroy');
        });

        // Project Tasks
        Route::prefix('/{project}/tasks')->name('projects.tasks.')->controller(ProjectTaskController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::put('/{task}', 'update')->name('update');
            Route::delete('/{task}', 'destroy')->name('destroy');
        });
    });
