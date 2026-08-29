<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\HomeDashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ScannerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['guest'])->group(function () {
    Route::controller(AuthController::class)->group(function () {
        Route::get('/', 'index')->name('user.login.view');
        Route::post('/', 'login')->name('user.login');
        Route::get('/b2c/login', 'redirectToB2c')->name('user.b2c.login');
        Route::get('/b2c/callback', 'handleB2cCallback')->name('user.b2c.callback');
        Route::get('/verify', 'verifyView')->name('user.verify.view');
        Route::post('/verify', 'verify')->name('user.verify');

        // Forgot Password
        Route::get('/forgot-password', 'forgotPasswordView')->name('password.request');
        Route::post('/forgot-password', 'sendResetLinkEmail')->name('password.email');
        Route::get('/reset-password/{token}', 'resetPasswordView')->name('password.reset');
        Route::post('/reset-password', 'updatePassword')->name('password.update');
    });
});

Route::middleware(['auth'])->group(function () {
    Route::controller(AuthController::class)->group(function () {
        Route::post('/logout', 'logout')->name('user.logout');
    });
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/home', [HomeDashboardController::class, 'index'])->name('home.dashboard');

    Route::get('/activity-logs', [ActivityLogController::class, 'index'])
        ->name('activity.logs.index');

    // Profile update - any authenticated user can update their own profile
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/export', [ExportController::class, 'export'])->name('export');
    Route::post('/export-progress', [ExportController::class, 'exportProgress'])->name('export.progress');

    require_once __DIR__.'/home.php';
    require_once __DIR__.'/qms.php';
    require_once __DIR__.'/hrm.php';
    require_once __DIR__.'/payroll.php';
    require_once __DIR__.'/account.php';
    require_once __DIR__.'/user.php';
    require_once __DIR__.'/customer.php';
    require_once __DIR__.'/vendor.php';
    require_once __DIR__.'/texrate.php';
    require_once __DIR__.'/tasks.php';
    require_once __DIR__.'/product.php';
    require_once __DIR__.'/logistics.php';
    require_once __DIR__.'/mass_mailer.php';
    require_once __DIR__.'/search.php';
    require_once __DIR__.'/project.php';
    require_once __DIR__.'/ticket.php';
    require_once __DIR__.'/ai.php';
});

require_once __DIR__ .'/employee-portal.php';

Route::get('/api/products/count', [\App\Http\Controllers\PublicProductController::class, 'getCountByTitle'])->name('public.api.products.count');
Route::get('/api/products/count3', [\App\Http\Controllers\PublicProductController::class, 'getCountByTitle3'])->name('public.api.products.count3');
Route::get('/api/products/ready-for-sale', [\App\Http\Controllers\PublicProductController::class, 'getReadyForSaleByTitle'])->name('public.api.products.ready-for-sale');
Route::get('/{uniqueId}', [\App\Http\Controllers\PublicProductController::class, 'show'])->name('public.product.show');

Route::get('/attendance', [ScannerController::class, 'index'])->name('attendance.index');
Route::post('/attendance/process', [ScannerController::class, 'processAttendance'])->name('attendance.process');
