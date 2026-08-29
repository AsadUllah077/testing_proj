<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExportController;
use Illuminate\Support\Facades\Route;


Route::middleware(['guest'])->group(function () {
    Route::controller(AuthController::class)->group(function () {
        Route::get('/', 'index')->name('user.login.view');
        Route::post('/', 'login')->name('user.login');
        Route::get('/verify', 'verifyView')->name('user.verify.view');
        Route::post('/verify', 'verify')->name('user.verify');
    });
});


Route::middleware(['auth'])->group(function () {
    Route::controller(AuthController::class)->group(function () {
        Route::post('/logout', 'logout')->name('user.logout');
    });
    Route::get('/dashboard', function () {
        return view('pages.dashboard');
    })->name('dashboard');

    Route::get('/home', function () {
        return view('pages.home.dashboard');
    })->name('home.dashboard');

    Route::get('/activity-logs', [ActivityLogController::class, 'index'])
        ->name('activity.logs.index');
    Route::post('/export', [ExportController::class, 'export'])->name('export');
    Route::post('/export-progress', [ExportController::class, 'exportProgress'])->name('export.progress');

    require_once __DIR__ . '/home.php';
    require_once __DIR__ . '/qms.php';
    require_once __DIR__ . '/hrm.php';
    require_once __DIR__ . '/account.php';
    require_once __DIR__ . '/user.php';
    require_once __DIR__ . '/customer.php';
    require_once __DIR__ . '/vendor.php';
    require_once __DIR__ . '/texrate.php';
});
