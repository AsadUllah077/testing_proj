<?php

use App\Http\Controllers\HRM\AttendanceController;
use App\Http\Controllers\HRM\DashboardController as HRMDashboardController;
use App\Http\Controllers\HRM\DepartmentController;
use App\Http\Controllers\HRM\EmployeeController;
use App\Http\Controllers\HRM\EmployeeTypeController;
use App\Http\Controllers\HRM\LeaveController;
use App\Http\Controllers\HRM\LeaveTypeController;
use Illuminate\Support\Facades\Route;

Route::prefix('hrm')
    ->name('hrm.')
    ->controller(HRMDashboardController::class)
    ->group(function () {
        // 👉 List all companies
        Route::get('/dashboard', 'index')->name('index');
    });

Route::prefix('hrm')->group(function () {
    Route::get('/index/employee', [EmployeeController::class, 'index'])->name('hrm.employee.index');
    Route::get('/index/employee-type', [EmployeeTypeController::class, 'index'])->name('hrm.employee-type.index');
    Route::get('/index/department', [DepartmentController::class, 'index'])->name('hrm.department.index');
    Route::get('/index/leave-types', [LeaveTypeController::class, 'index'])->name('hrm.leave-types.index');
    // Route::get('/create/employee-type', [EmployeeTypeController::class, 'create'])->name('hrm.employee-type.create');
    // Route::post('/create/employee-type', [EmployeeTypeController::class, 'store'])->name('hrm.employee-type.store');

});
Route::prefix('hrm')->name('hrm.')->group(function () {
    // Employee Type Routes
    Route::prefix('employee-type')->name('employee-type.')->group(function () {
        Route::get('/', [EmployeeTypeController::class, 'index'])->name('index');
        Route::get('/create', [EmployeeTypeController::class, 'create'])->name('create');
        Route::post('/store', [EmployeeTypeController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [EmployeeTypeController::class, 'edit'])->name('edit');
        Route::put('/{id}/update', [EmployeeTypeController::class, 'update'])->name('update');
        Route::delete('/{id}/destroy', [EmployeeTypeController::class, 'destroy'])->name('destroy');
        Route::get('/{id}/show', [EmployeeTypeController::class, 'show'])->name('show');
    });

    // Department Routes
    Route::prefix('department')->name('department.')->group(function () {
        Route::get('/', [DepartmentController::class, 'index'])->name('index');
        Route::get('/create', [DepartmentController::class, 'create'])->name('create');
        Route::post('/store', [DepartmentController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [DepartmentController::class, 'edit'])->name('edit');
        Route::put('/{id}/update', [DepartmentController::class, 'update'])->name('update');
        Route::delete('/{id}/destroy', [DepartmentController::class, 'destroy'])->name('destroy');
        Route::get('/{id}/show', [DepartmentController::class, 'show'])->name('show');
    });

    // LeaveType Routes
    Route::prefix('leave-types')->name('leave-types.')->group(function () {
        Route::get('/', [LeaveTypeController::class, 'index'])->name('index');
        Route::get('/create', [LeaveTypeController::class, 'create'])->name('create');
        Route::post('/store', [LeaveTypeController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [LeaveTypeController::class, 'edit'])->name('edit');
        Route::put('/{id}/update', [LeaveTypeController::class, 'update'])->name('update');
        Route::delete('/{id}/destroy', [LeaveTypeController::class, 'destroy'])->name('destroy');
        Route::get('/{id}/show', [LeaveTypeController::class, 'show'])->name('show');
    });

    // Leave Routes
    Route::prefix('leave')->name('leave.')->group(function () {
        Route::get('/', [LeaveController::class, 'index'])->name('index');
        Route::get('/create', [LeaveController::class, 'create'])->name('create');
        Route::post('/store', [LeaveController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [LeaveController::class, 'edit'])->name('edit');
        Route::put('/{id}/update', [LeaveController::class, 'update'])->name('update');
        Route::delete('/{id}/destroy', [LeaveController::class, 'destroy'])->name('destroy');
        Route::get('/{id}/show', [LeaveController::class, 'show'])->name('show');
        Route::post('/calculate-days', [LeaveController::class, 'calculateDays'])->name('calculate-days');
        Route::get('/get-balance', [LeaveController::class, 'getEmployeeBalance'])->name('get-balance');
        Route::post('/{id}/update-status', [LeaveController::class, 'updateStatus'])->name('update-status');
        Route::get('/get-leave-type-by-category', [LeaveController::class, 'getLeaveTypeByCategory'])->name('get-leave-type-by-category');
        Route::get('/leave/calendar-data', [LeaveController::class, 'getCalendarData'])->name('calendar-data');
        Route::get('/leave/details', [LeaveController::class, 'getLeaveDetails'])->name('details');
    });
    Route::get('/leave/calendar', [LeaveController::class, 'calendar'])->name('calendar');
    // Employee Routes
    Route::prefix('employee')->name('employee.')->group(function () {
        Route::get('/', [EmployeeController::class, 'index'])->name('index');
        Route::get('/create', [EmployeeController::class, 'create'])->name('create');
        Route::post('/store', [EmployeeController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [EmployeeController::class, 'edit'])->name('edit');
        Route::put('/{id}/update', [EmployeeController::class, 'update'])->name('update');
        Route::delete('/{id}/destroy', [EmployeeController::class, 'destroy'])->name('destroy');
        Route::get('/{id}/show', [EmployeeController::class, 'show'])->name('show');
        Route::post('/{id}/change-status', [EmployeeController::class, 'changeStatus'])->name('change-status');
        Route::get('/get-managers', [EmployeeController::class, 'getManagers'])->name('get-managers');
        Route::delete('/employee/document/{id}', [EmployeeController::class, 'destroyDocument'])->name('document.destroy');
    });

    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/', [AttendanceController::class, 'index'])->name('index');
        Route::get('/create', [AttendanceController::class, 'create'])->name('create');
        Route::post('/', [AttendanceController::class, 'store'])->name('store');
        Route::get('/{id}/show', [AttendanceController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [AttendanceController::class, 'edit'])->name('edit');
        Route::put('/{id}', [AttendanceController::class, 'update'])->name('update');
        Route::delete('/{id}', [AttendanceController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-checkin', [AttendanceController::class, 'bulkCheckIn'])->name('bulk.checkin');
        Route::post('/bulk-checkout', [AttendanceController::class, 'bulkCheckOut'])->name('bulk.checkout');
        Route::post('/import', [AttendanceController::class, 'import'])->name('import');
        Route::get('/export', [AttendanceController::class, 'export'])->name('export');
        Route::get('/download-template', [AttendanceController::class, 'downloadTemplate'])->name('download-template');
    });

});
