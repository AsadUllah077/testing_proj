<?php

use App\Http\Controllers\User\DashboardController;
use App\Http\Controllers\User\PermissionController;
use App\Http\Controllers\User\RoleController;
use App\Http\Controllers\User\RolePermissionController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('users')
    ->name('users.')
    ->controller(DashboardController::class)
    ->group(function () {
        Route::get('/', 'index')->name(name: 'dashboard');
    });

Route::prefix('users')
    ->name('users.')
    ->controller(UserController::class)
    ->group(function () {
        Route::get('/list', 'index')->name('user.index');
        Route::get('/create', 'create')->name('user.create');
        Route::delete('/delete/{id}', 'destroy')->name('user.destroy');
        Route::get('/edit/{id}', 'edit')->name('user.edit');
        Route::put('/edit/{id}', 'update')->name('user.update');
        Route::get('/show/{id}', 'show')->name('user.show');
        Route::post('/store', 'store')->name('user.store');
        Route::get('/export/pdf', 'exportPdf')->name('user.export.pdf');

        // Permission Routes
        Route::prefix('permission')->group(function () {
            Route::get('/', [PermissionController::class, 'index'])->name('permission.index');
            Route::get('/create', [PermissionController::class, 'create'])->name('permission.create');
            Route::post('/', [PermissionController::class, 'store'])->name('permission.store');
            Route::get('/{id}/edit', [PermissionController::class, 'edit'])->name('permission.edit');
            Route::put('/{id}/update', [PermissionController::class, 'update'])->name('permission.update');
            Route::delete('/{id}/delete', [PermissionController::class, 'destroy'])->name('permission.destroy');
        });

        // Role Routes
        Route::prefix('role')->group(function () {
            Route::get('/', [RoleController::class, 'index'])->name('role.index');
            Route::get('/create', [RoleController::class, 'create'])->name('role.create');
            Route::post('/', [RoleController::class, 'store'])->name('role.store');
            Route::get('/{id}/edit', [RoleController::class, 'edit'])->name('role.edit');
            Route::put('/{id}', [RoleController::class, 'update'])->name('role.update');
            Route::delete('/{id}', [RoleController::class, 'destroy'])->name('role.destroy');
            Route::get('/{id}', [RoleController::class, 'show'])->name('role.show');
        });

        // Role Permission Routes
        Route::prefix('role/{role}/permission')->group(function () {
            Route::get('/', [RolePermissionController::class, 'create'])->name('role.permission.create');
            Route::post('/', [RolePermissionController::class, 'store'])->name('role.permission.store');
            Route::get('/json', [RolePermissionController::class, 'getRolePermissions'])->name('role.permission.json');
            Route::get('/users', [RolePermissionController::class, 'getUsersByRole'])->name('role.getUsers');
        });

    });

Route::post('/switch-company', [\App\Http\Controllers\User\CompanySwitchController::class, 'switch'])
    ->middleware('auth')
    ->name('company.switch');
