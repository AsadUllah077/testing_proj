<?php

use App\Http\Controllers\MassMailer\DashboardController;
use App\Http\Controllers\MassMailer\TagController;
use App\Http\Controllers\MassMailer\ContactController;
use App\Http\Controllers\MassMailer\TemplateController;
use App\Http\Controllers\MassMailer\SenderController;
use Illuminate\Support\Facades\Route;

Route::prefix('mass-mailer')->name('mass-mailer.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('tags', TagController::class);
    Route::resource('contacts', ContactController::class);
    Route::resource('templates', TemplateController::class);

    Route::controller(SenderController::class)->group(function () {
        Route::get('/sender', 'create')->name('sender.create');
        Route::post('/sender', 'store')->name('sender.store');
        Route::get('/sender/progress/{id}', 'progress')->name('sender.progress');
    });
});
