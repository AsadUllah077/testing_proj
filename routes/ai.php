<?php

use App\Http\Controllers\AI\FinancialAssistantController;
use App\Http\Controllers\AI\DocumentIngestionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::prefix('ai-assistant')->group(function () {
        Route::get('/', [FinancialAssistantController::class, 'index'])->name('ai.assistant.index');
        Route::get('/context', [FinancialAssistantController::class, 'getContext'])->name('ai.assistant.context');
        Route::get('/suggestions', [FinancialAssistantController::class, 'getSuggestions'])->name('ai.assistant.suggestions');
        Route::post('/ask', [FinancialAssistantController::class, 'ask'])->name('ai.assistant.ask');
    });

    Route::prefix('document-ingestion')->group(function () {
        Route::get('/', [DocumentIngestionController::class, 'index'])->name('ai.document.ingestion.index');
        Route::post('/upload', [DocumentIngestionController::class, 'upload'])->name('ai.document.ingestion.upload');
        Route::post('/{id}/confirm', [DocumentIngestionController::class, 'confirm'])->name('ai.document.ingestion.confirm');
        Route::delete('/{id}', [DocumentIngestionController::class, 'delete'])->name('ai.document.ingestion.delete');
    });
});
