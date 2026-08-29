<?php

use App\Http\Controllers\QMS\DashboardController;
use App\Http\Controllers\QMS\DocumentManagement\DocumentManagementController;
use App\Http\Controllers\QMS\DocumentManagement\QmsTemplateController;
use App\Http\Controllers\QMS\DocumentManagement\QmsExternalDocumentController;
use App\Http\Controllers\QMS\DocumentManagement\QmsDocumentRequestController;
use App\Http\Controllers\QMS\Policy\PolicyController;
use App\Http\Controllers\QMS\Sop\SopController;
use App\Http\Controllers\QMS\Training\TrainingController;
use App\Http\Controllers\QMS\Capa\CapaController;
use App\Http\Controllers\QMS\ManagementReview\ManagementReviewController;
use Illuminate\Support\Facades\Route;

Route::prefix('')
    ->name('qms.')
    ->controller(DashboardController::class)
    ->group(function () {
        // 👉 List all companies
        Route::get('/qms', 'index')->name('index');
       
    });

    Route::prefix('qms')
    ->name('qms.')
    ->controller(DocumentManagementController::class)
    ->group(function () {
        // 👉 List all documents
        Route::get('/documentmanagement', 'index')->name('documentmanagement.index');
        Route::get('/documentmanagement/create', 'create')->name('documentmanagement.create');
        Route::post('/documentmanagement', 'store')->name('documentmanagement.store');
        Route::get('/documentmanagement/{id}', 'show')->name('documentmanagement.show');
        Route::get('/documentmanagement/{id}/edit', 'edit')->name('documentmanagement.edit');
        Route::put('/documentmanagement/{id}', 'update')->name('documentmanagement.update');
        Route::delete('/documentmanagement/{id}', 'destroy')->name('documentmanagement.destroy');
    });

    // Templates Library
    Route::prefix('qms')->name('qms.')->group(function () {
        Route::post('/documentmanagement/templates', [QmsTemplateController::class, 'store'])->name('documentmanagement.templates.store');
        Route::get('/documentmanagement/templates/{id}/download', [QmsTemplateController::class, 'download'])->name('documentmanagement.templates.download');
        Route::delete('/documentmanagement/templates/{id}', [QmsTemplateController::class, 'destroy'])->name('documentmanagement.templates.destroy');
    });

    // External Documents
    Route::prefix('qms')->name('qms.')->group(function () {
        Route::post('/documentmanagement/external', [QmsExternalDocumentController::class, 'store'])->name('documentmanagement.external.store');
        Route::get('/documentmanagement/external/{id}/download', [QmsExternalDocumentController::class, 'download'])->name('documentmanagement.external.download');
        Route::delete('/documentmanagement/external/{id}', [QmsExternalDocumentController::class, 'destroy'])->name('documentmanagement.external.destroy');
    });

    // Document Requests
    Route::prefix('qms')->name('qms.')->group(function () {
        Route::post('/documentmanagement/requests', [QmsDocumentRequestController::class, 'store'])->name('documentmanagement.requests.store');
        Route::patch('/documentmanagement/requests/{id}/status', [QmsDocumentRequestController::class, 'updateStatus'])->name('documentmanagement.requests.status');
        Route::delete('/documentmanagement/requests/{id}', [QmsDocumentRequestController::class, 'destroy'])->name('documentmanagement.requests.destroy');

    });


    Route::prefix('qms')
    ->name('qms.')
    ->controller(PolicyController::class)
    ->group(function () {
        Route::get('/policy', 'index')->name('policy.index');
        Route::get('/policy/create', 'create')->name('policy.create');
        Route::post('/policy', 'store')->name('policy.store');
        Route::get('/policy/{id}', 'show')->name('policy.show');
        Route::get('/policy/{id}/edit', 'edit')->name('policy.edit');
        Route::put('/policy/{id}', 'update')->name('policy.update');
        Route::delete('/policy/{id}', 'destroy')->name('policy.destroy');
        Route::delete('/policy/attachment/{id}', 'deleteAttachment')->name('policy.attachment.destroy');
        Route::patch('/policy/{id}/submit-review', 'submitForReview')->name('policy.submit-review');
        Route::patch('/policy/{id}/qa-approve', 'qaApprove')->name('policy.qa-approve');
        Route::patch('/policy/{id}/qa-reject', 'qaReject')->name('policy.qa-reject');
        Route::patch('/policy/{id}/final-approve', 'finalApprove')->name('policy.final-approve');
        Route::patch('/policy/{id}/final-reject', 'finalReject')->name('policy.final-reject');
    });

    Route::prefix('qms')
    ->name('qms.')
    ->controller(SopController::class)
    ->group(function () {
        Route::get('/sop', 'index')->name('sop.index');
        Route::get('/sop/create', 'create')->name('sop.create');
        Route::post('/sop', 'store')->name('sop.store');
        Route::get('/sop/{id}', 'show')->name('sop.show');
        Route::get('/sop/{id}/edit', 'edit')->name('sop.edit');
        Route::put('/sop/{id}', 'update')->name('sop.update');
        Route::delete('/sop/{id}', 'destroy')->name('sop.destroy');
    });

    Route::prefix('qms')
    ->name('qms.')
    ->controller(TrainingController::class)
    ->group(function () {
        Route::get('/training', 'index')->name('training.index');
        Route::get('/training/create', 'create')->name('training.create');
        Route::post('/training', 'store')->name('training.store');
        Route::get('/training/{id}', 'show')->name('training.show');
        Route::get('/training/{id}/edit', 'edit')->name('training.edit');
        Route::put('/training/{id}', 'update')->name('training.update');
        Route::delete('/training/{id}', 'destroy')->name('training.destroy');
    });

    Route::prefix('qms')
    ->name('qms.')
    ->controller(CapaController::class)
    ->group(function () {
        Route::get('/capa', 'index')->name('capa.index');
        Route::get('/capa/create', 'create')->name('capa.create');
        Route::post('/capa', 'store')->name('capa.store');
        Route::get('/capa/{id}', 'show')->name('capa.show');
        Route::get('/capa/{id}/edit', 'edit')->name('capa.edit');
        Route::put('/capa/{id}', 'update')->name('capa.update');
        Route::delete('/capa/{id}', 'destroy')->name('capa.destroy');
        Route::patch('/capa/{id}/status', 'updateStatus')->name('capa.update-status');
    });

    Route::prefix('qms')
    ->name('qms.')
    ->controller(ManagementReviewController::class)
    ->group(function () {
        Route::get('/management-review', 'index')->name('management-review.index');
    });