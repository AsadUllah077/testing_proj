<?php

use App\Http\Controllers\Employee\{EmployeeAuthController, PayslipChatController, PayslipPortalController};
use Illuminate\Support\Facades\Route;

// Employee Portal - Public auth routes
Route::prefix('my')->name('employee.')->group(function () {
    Route::get('login', [EmployeeAuthController::class, 'showLoginForm'])->name('login');
    Route::post('login', [EmployeeAuthController::class, 'login'])->name('login.submit');
    Route::post('logout', [EmployeeAuthController::class, 'logout'])->name('logout');
});

// Employee Self-Service (employee auth guard)
Route::middleware(['auth:employee'])->prefix('my')->name('employee.')->group(function () {

    // Payslip Chatbot - AI assistant for payslip Q&A
    Route::prefix('payslips/chat')->name('payslips.chat.')->group(function () {
        Route::post('/', [PayslipChatController::class, 'sendMessage'])->name('send');
        Route::get('context', [PayslipChatController::class, 'getContext'])->name('context');
        Route::get('suggestions/{payslip}', [PayslipChatController::class, 'getSuggestions'])->name('suggestions');
    });

    Route::get('payslips', [PayslipPortalController::class, 'index'])->name('payslips.index');
    Route::post('bank-change-request', [PayslipPortalController::class, 'requestBankChange'])->name('bank-change-request');
    Route::get('tax-certificate/{fiscalYear?}', [PayslipPortalController::class, 'taxCertificate'])->name('tax-certificate');
    Route::get('payslips/{payslip}', [PayslipPortalController::class, 'show'])
        ->whereNumber('payslip')
        ->name('payslips.show');
    Route::get('payslips/{payslip}/pdf', [PayslipPortalController::class, 'downloadPdf'])
        ->whereNumber('payslip')
        ->name('payslips.pdf');
    Route::get('payslips/{payslip}/doc', [PayslipPortalController::class, 'downloadDoc'])
        ->whereNumber('payslip')
        ->name('payslips.doc');
});
