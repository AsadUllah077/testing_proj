<?php

use App\Http\Controllers\GlobalSearchController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/global-search', [GlobalSearchController::class, 'search'])->name('global.search');
});
