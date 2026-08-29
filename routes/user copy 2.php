<?php

use App\Http\Controllers\User\DashboardController;
use App\Http\Controllers\User\PermissionController;
use App\Http\Controllers\User\RoleController;
use App\Http\Controllers\User\RolePermissionController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('users')
    ->name('users.')
    ->middleware('permission:users.dashboard.view')
    ->group(function () {
        Route::controller(DashboardController::class)->group(function () {
            Route::get('/', 'index')->name('dashboard');
        });

        // Users
        Route::controller(UserController::class)->group(function () {
            Route::prefix('list')
                ->name('user.')
                ->middleware('permission:users.user.view')
                ->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('/create', 'create')->middleware('permission:users.user.create')->name('create');
                    Route::delete('/delete/{id}', 'destroy')->middleware('permission:users.user.delete')->name('destroy');
                    Route::get('/edit/{id}', 'edit')->middleware('permission:users.user.edit')->name('edit');
                    Route::put('/edit/{id}', 'update')->middleware('permission:users.user.edit')->name('update');
                    Route::get('/show/{id}', 'show')->name('show');
                    Route::post('/store', 'store')->middleware('permission:users.user.create')->name('store');
                    Route::get('/export/pdf', 'exportPdf')->middleware('permission:users.user.export')->name('export.pdf');
                });

            // Permissions
            Route::prefix('permission')
                ->name('permission.')
                ->middleware('permission:users.permission.view')
                ->group(function () {
                    Route::get('/', [PermissionController::class, 'index'])->name('index');
                    Route::get('/create', [PermissionController::class, 'create'])->middleware('permission:users.permission.create')->name('create');
                    Route::post('/', [PermissionController::class, 'store'])->middleware('permission:users.permission.create')->name('store');
                    Route::get('/{id}/edit', [PermissionController::class, 'edit'])->middleware('permission:users.permission.edit')->name('edit');
                    Route::put('/{id}/update', [PermissionController::class, 'update'])->middleware('permission:users.permission.edit')->name('update');
                    Route::delete('/{id}/delete', [PermissionController::class, 'destroy'])->middleware('permission:users.permission.delete')->name('destroy');
                });

            // Roles
            Route::prefix('role')
                ->name('role.')
                ->middleware('permission:users.role.view')
                ->group(function () {
                    Route::get('/', [RoleController::class, 'index'])->name('index');
                    Route::get('/create', [RoleController::class, 'create'])->middleware('permission:users.role.create')->name('create');
                    Route::post('/', [RoleController::class, 'store'])->middleware('permission:users.role.create')->name('store');
                    Route::get('/{id}/edit', [RoleController::class, 'edit'])->middleware('permission:users.role.edit')->name('edit');
                    Route::put('/{id}', [RoleController::class, 'update'])->middleware('permission:users.role.edit')->name('update');
                    Route::delete('/{id}', [RoleController::class, 'destroy'])->middleware('permission:users.role.delete')->name('destroy');
                    Route::get('/{id}', [RoleController::class, 'show'])->name('show');
                });

            // Role Permissions
            Route::prefix('role/{role}/permission')
                ->name('role.permission.')
                ->middleware('permission:users.role-permission.view')
                ->group(function () {
                    Route::get('/', [RolePermissionController::class, 'create'])->name('create');
                    Route::post('/', [RolePermissionController::class, 'store'])->middleware('permission:users.role-permission.assign')->name('store');
                    Route::get('/json', [RolePermissionController::class, 'getRolePermissions'])->name('json');
                });
        });
    });
