<?php

use App\Http\Controllers\Product\DashboardController;
use App\Http\Controllers\Product\Warehouse\BarcodeController;
use App\Http\Controllers\Product\Warehouse\DashboardController as WarehouseDashboardController;
use App\Http\Controllers\Product\Warehouse\GatePassController;
use App\Http\Controllers\Product\Warehouse\MasterDataController;
use App\Http\Controllers\Product\Warehouse\PartsController;
use App\Http\Controllers\Product\Warehouse\ProductController;
use App\Http\Controllers\Product\Warehouse\ProductReportController;
use App\Http\Controllers\Product\Warehouse\ProductScanController;
use App\Http\Controllers\Product\Warehouse\ProgressSheetController;
use App\Http\Controllers\Product\Warehouse\ScrapController;
use App\Http\Controllers\Product\Warehouse\TechnicianController;
use App\Http\Controllers\Product\Warehouse\WarehouseActivityLogController;
use Illuminate\Support\Facades\Route;

// ── Product Scan Tracking ───────────────────────────────────────────────────
Route::prefix('warehouse/scan')->name('warehouse.scan.')->middleware('permission:warehouse.scan.view')->group(function () {
    Route::get('/location-a',  [ProductScanController::class, 'showA'])->name('location-a');
    Route::get('/location-b',  [ProductScanController::class, 'showB'])->name('location-b');
    Route::post('/',           [ProductScanController::class, 'scan'])->middleware('permission:warehouse.scan.process')->name('process');
    Route::get('/logs',        [ProductScanController::class, 'index'])->middleware('permission:warehouse.scan.logs')->name('logs');
});
// ───────────────────────────────────────────────────────────────────────────

Route::get('/product', [DashboardController::class, 'index'])
    ->middleware('permission:product.dashboard.view')
    ->name('products.dashboard');
// Dashboard
// Route::get('/product/warehouse', [WarehouseDashboardController::class, 'index'])
//     ->name('warehouse.dashboard');
// Add these routes to your web.php file

Route::prefix('product/warehouse')->name('warehouse.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [WarehouseDashboardController::class, 'index'])->middleware('permission:warehouse.dashboard.view')->name('dashboard');
    Route::get('/dashboard/stats', [WarehouseDashboardController::class, 'getStats'])->middleware('permission:warehouse.dashboard.stats')->name('dashboard.stats');
    Route::get('/dashboard/chart-data', [WarehouseDashboardController::class, 'getChartData'])->middleware('permission:warehouse.dashboard.chart-data')->name('dashboard.chart-data');
    Route::get('/activity-logs', [WarehouseActivityLogController::class, 'index'])->middleware('permission:warehouse.activity-logs.view')->name('activity-logs.index');

    // Other routes...
});

Route::get('/product/warehouse/barcode', [BarcodeController::class, 'index'])->middleware('permission:warehouse.barcode.view')->name('warehouse.barcode');
Route::post('/product/warehouse/barcode/generate', [BarcodeController::class, 'generate'])->middleware('permission:warehouse.barcode.generate')->name('warehouse.barcode.generate');
Route::delete('/product/warehouse/barcode/{barcode}', [BarcodeController::class, 'destroy'])->middleware('permission:warehouse.barcode.delete')->name('warehouse.barcode.destroy');
Route::post('/product/warehouse/barcode/bulk-delete', [BarcodeController::class, 'bulkDelete'])->middleware('permission:warehouse.barcode.bulk-delete')->name('warehouse.barcode.bulk-delete');

// Print routes
Route::post('/product/warehouse/barcode/print', [BarcodeController::class, 'printBarcodes'])->middleware('permission:warehouse.barcode.print')->name('warehouse.barcode.print');
Route::get('/product/warehouse/barcode/print/pdf', [BarcodeController::class, 'printSelectedPdf'])->middleware('permission:warehouse.barcode.print-pdf')->name('warehouse.barcode.print.pdf');

// Scan and image routes
Route::get('/product/warehouse/barcode/scan/{uniqueId}', [BarcodeController::class, 'scan'])->middleware('permission:warehouse.barcode.scan')->name('warehouse.barcode.scan');
Route::get('/product/warehouse/barcode/image/{uniqueId}', [BarcodeController::class, 'getBarcodeImage'])->middleware('permission:warehouse.barcode.scan')->name('warehouse.barcode.image');

// Export routes
Route::get('/product/warehouse/barcode/export/pdf', [BarcodeController::class, 'exportPdf'])->middleware('permission:warehouse.barcode.export-pdf')->name('warehouse.barcode.export.pdf');
Route::get('/product/warehouse/barcode/export/excel', [BarcodeController::class, 'exportExcel'])->middleware('permission:warehouse.barcode.export-excel')->name('warehouse.barcode.export.excel');
Route::post('warehouse/barcode/print-view', [BarcodeController::class, 'printView'])->middleware('permission:warehouse.barcode.print-view')->name('warehouse.barcode.print-view');
Route::get('warehouse/barcode/print-view', [BarcodeController::class, 'printViewGet'])->middleware('permission:warehouse.barcode.print-view')->name('warehouse.barcode.print-view.get');
Route::prefix('api')->group(function () {
    Route::get('/barcode/scan/{uniqueId}', [BarcodeController::class, 'scan'])
        ->middleware('permission:warehouse.barcode.scan')
        ->name('api.barcode.scan');
});
Route::post('/warehouse/barcode/{barcode}/mark-as-used', [BarcodeController::class, 'markAsUsed'])
    ->middleware('permission:warehouse.barcode.mark-as-used')
    ->name('warehouse.barcode.mark-as-used');
Route::post('/warehouse/barcode/bulk-mark-as-used', [BarcodeController::class, 'bulkMarkAsUsed'])
    ->middleware('permission:warehouse.barcode.bulk-mark-as-used')
    ->name('warehouse.barcode.bulk-mark-as-used');




// Master Data
Route::get('/product/warehouse/master-data', [MasterDataController::class, 'index'])
    ->middleware('permission:warehouse.master-data.view')
    ->name('warehouse.master-data');

Route::prefix('warehouse/master-data/category')->name('warehouse.master-data.category.')->middleware('permission:warehouse.master-data.category.view')->group(function () {
    Route::get('/', [\App\Http\Controllers\Product\Warehouse\MasterData\CategoryController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\Product\Warehouse\MasterData\CategoryController::class, 'create'])->middleware('permission:warehouse.master-data.category.create')->name('create');
    Route::post('/store', [\App\Http\Controllers\Product\Warehouse\MasterData\CategoryController::class, 'store'])->middleware('permission:warehouse.master-data.category.create')->name('store');
    Route::get('/{id}/edit', [\App\Http\Controllers\Product\Warehouse\MasterData\CategoryController::class, 'edit'])->middleware('permission:warehouse.master-data.category.edit')->name('edit');
    Route::put('/{id}/update', [\App\Http\Controllers\Product\Warehouse\MasterData\CategoryController::class, 'update'])->middleware('permission:warehouse.master-data.category.edit')->name('update');
    Route::delete('/{id}/delete', [\App\Http\Controllers\Product\Warehouse\MasterData\CategoryController::class, 'destroy'])->middleware('permission:warehouse.master-data.category.delete')->name('destroy');
    Route::post('/bulk-delete', [\App\Http\Controllers\Product\Warehouse\MasterData\CategoryController::class, 'bulkDelete'])->middleware('permission:warehouse.master-data.category.bulk-delete')->name('bulk-delete');
});


Route::prefix('warehouse/master-data/locations')->name('warehouse.master-data.locations.')->middleware('permission:warehouse.master-data.locations.view')->group(function () {
    Route::get('/', [\App\Http\Controllers\Product\Warehouse\MasterData\LocationController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\Product\Warehouse\MasterData\LocationController::class, 'create'])->middleware('permission:warehouse.master-data.locations.create')->name('create');
    Route::post('/store', [\App\Http\Controllers\Product\Warehouse\MasterData\LocationController::class, 'store'])->middleware('permission:warehouse.master-data.locations.create')->name('store');
    Route::get('/{id}/edit', [\App\Http\Controllers\Product\Warehouse\MasterData\LocationController::class, 'edit'])->middleware('permission:warehouse.master-data.locations.edit')->name('edit');
    Route::put('/{id}/update', [\App\Http\Controllers\Product\Warehouse\MasterData\LocationController::class, 'update'])->middleware('permission:warehouse.master-data.locations.edit')->name('update');
    Route::delete('/{id}/delete', [\App\Http\Controllers\Product\Warehouse\MasterData\LocationController::class, 'destroy'])->middleware('permission:warehouse.master-data.locations.delete')->name('destroy');
    Route::post('/bulk-delete', [\App\Http\Controllers\Product\Warehouse\MasterData\LocationController::class, 'bulkDelete'])->middleware('permission:warehouse.master-data.locations.bulk-delete')->name('bulk-delete');
});
Route::prefix('warehouse/master-data/dispositions')->name('warehouse.master-data.dispositions.')->middleware('permission:warehouse.master-data.dispositions.view')->group(function () {
    Route::get('/', [\App\Http\Controllers\Product\Warehouse\MasterData\DispositionController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\Product\Warehouse\MasterData\DispositionController::class, 'create'])->middleware('permission:warehouse.master-data.dispositions.create')->name('create');
    Route::post('/store', [\App\Http\Controllers\Product\Warehouse\MasterData\DispositionController::class, 'store'])->middleware('permission:warehouse.master-data.dispositions.create')->name('store');
    Route::get('/{id}/edit', [\App\Http\Controllers\Product\Warehouse\MasterData\DispositionController::class, 'edit'])->middleware('permission:warehouse.master-data.dispositions.edit')->name('edit');
    Route::put('/{id}/update', [\App\Http\Controllers\Product\Warehouse\MasterData\DispositionController::class, 'update'])->middleware('permission:warehouse.master-data.dispositions.edit')->name('update');
    Route::delete('/{id}/delete', [\App\Http\Controllers\Product\Warehouse\MasterData\DispositionController::class, 'destroy'])->middleware('permission:warehouse.master-data.dispositions.delete')->name('destroy');
    Route::post('/bulk-delete', [\App\Http\Controllers\Product\Warehouse\MasterData\DispositionController::class, 'bulkDelete'])->middleware('permission:warehouse.master-data.dispositions.bulk-delete')->name('bulk-delete');
});

Route::prefix('warehouse/master-data/workflow-stages')->name('warehouse.master-data.workflow-stages.')->middleware('permission:warehouse.master-data.workflow-stages.view')->group(function () {
    Route::get('/', [\App\Http\Controllers\Product\Warehouse\MasterData\WorkflowStageController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\Product\Warehouse\MasterData\WorkflowStageController::class, 'create'])->middleware('permission:warehouse.master-data.workflow-stages.create')->name('create');
    Route::post('/store', [\App\Http\Controllers\Product\Warehouse\MasterData\WorkflowStageController::class, 'store'])->middleware('permission:warehouse.master-data.workflow-stages.create')->name('store');
    Route::get('/{id}/edit', [\App\Http\Controllers\Product\Warehouse\MasterData\WorkflowStageController::class, 'edit'])->middleware('permission:warehouse.master-data.workflow-stages.edit')->name('edit');
    Route::put('/{id}/update', [\App\Http\Controllers\Product\Warehouse\MasterData\WorkflowStageController::class, 'update'])->middleware('permission:warehouse.master-data.workflow-stages.edit')->name('update');
    Route::delete('/{id}/delete', [\App\Http\Controllers\Product\Warehouse\MasterData\WorkflowStageController::class, 'destroy'])->middleware('permission:warehouse.master-data.workflow-stages.delete')->name('destroy');
    Route::post('/bulk-delete', [\App\Http\Controllers\Product\Warehouse\MasterData\WorkflowStageController::class, 'bulkDelete'])->middleware('permission:warehouse.master-data.workflow-stages.bulk-delete')->name('bulk-delete');
});

Route::prefix('warehouse/master-data/scrap-types')->name('warehouse.master-data.scrap-types.')->middleware('permission:warehouse.master-data.scrap-types.view')->group(function () {
    Route::get('/', [\App\Http\Controllers\Product\Warehouse\MasterData\ScrapTypeController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\Product\Warehouse\MasterData\ScrapTypeController::class, 'create'])->middleware('permission:warehouse.master-data.scrap-types.create')->name('create');
    Route::post('/store', [\App\Http\Controllers\Product\Warehouse\MasterData\ScrapTypeController::class, 'store'])->middleware('permission:warehouse.master-data.scrap-types.create')->name('store');
    Route::get('/{id}/edit', [\App\Http\Controllers\Product\Warehouse\MasterData\ScrapTypeController::class, 'edit'])->middleware('permission:warehouse.master-data.scrap-types.edit')->name('edit');
    Route::put('/{id}/update', [\App\Http\Controllers\Product\Warehouse\MasterData\ScrapTypeController::class, 'update'])->middleware('permission:warehouse.master-data.scrap-types.edit')->name('update');
    Route::delete('/{id}/delete', [\App\Http\Controllers\Product\Warehouse\MasterData\ScrapTypeController::class, 'destroy'])->middleware('permission:warehouse.master-data.scrap-types.delete')->name('destroy');
    Route::post('/bulk-delete', [\App\Http\Controllers\Product\Warehouse\MasterData\ScrapTypeController::class, 'bulkDelete'])->middleware('permission:warehouse.master-data.scrap-types.bulk-delete')->name('bulk-delete');
});

Route::prefix('warehouse/master-data/scrap-category')->name('warehouse.master-data.scrap-category.')->middleware('permission:warehouse.master-data.scrap-category.view')->group(function () {
    Route::get('/', [\App\Http\Controllers\Product\Warehouse\MasterData\ScrapCategoryController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\Product\Warehouse\MasterData\ScrapCategoryController::class, 'create'])->middleware('permission:warehouse.master-data.scrap-category.create')->name('create');
    Route::post('/store', [\App\Http\Controllers\Product\Warehouse\MasterData\ScrapCategoryController::class, 'store'])->middleware('permission:warehouse.master-data.scrap-category.create')->name('store');
    Route::get('/{id}/edit', [\App\Http\Controllers\Product\Warehouse\MasterData\ScrapCategoryController::class, 'edit'])->middleware('permission:warehouse.master-data.scrap-category.edit')->name('edit');
    Route::put('/{id}/update', [\App\Http\Controllers\Product\Warehouse\MasterData\ScrapCategoryController::class, 'update'])->middleware('permission:warehouse.master-data.scrap-category.edit')->name('update');
    Route::delete('/{id}/delete', [\App\Http\Controllers\Product\Warehouse\MasterData\ScrapCategoryController::class, 'destroy'])->middleware('permission:warehouse.master-data.scrap-category.delete')->name('destroy');
    Route::post('/bulk-delete', [\App\Http\Controllers\Product\Warehouse\MasterData\ScrapCategoryController::class, 'bulkDelete'])->middleware('permission:warehouse.master-data.scrap-category.bulk-delete')->name('bulk-delete');
});

// Technicians Routes
Route::prefix('/warehouse/technicians')->name('warehouse.technicians.')->middleware('permission:warehouse.technicians.view')->group(function () {
    Route::get('/', [TechnicianController::class, 'index'])->name('index');
    Route::post('/bulk-assign', [TechnicianController::class, 'bulkAssign'])->middleware('permission:warehouse.technicians.bulk-assign')->name('bulk.assign');
    Route::post('/{id}/start-task', [TechnicianController::class, 'startTask'])->middleware('permission:warehouse.technicians.start-task')->name('start.task');
    Route::get('/{id}/view-result', [TechnicianController::class, 'viewResult'])->middleware('permission:warehouse.technicians.view-result')->name('view.result');
    Route::get('/submit/{id}/{type}', [TechnicianController::class, 'submitResult'])->middleware('permission:warehouse.technicians.submit-result')->name('submit.result');
    Route::post('/{id}/update-status', [TechnicianController::class, 'updateStatus'])->middleware('permission:warehouse.technicians.complete-task')->name('update.status');
});

Route::get('/scrap/warehouse/scrap', [ScrapController::class, 'index'])
    ->middleware('permission:warehouse.scrap.view')
    ->name('warehouse.scrap.index');
Route::get('/scrap/warehouse/scrap/create', [ScrapController::class, 'create'])
    ->middleware('permission:warehouse.scrap.create')
    ->name('warehouse.scrap.create');

Route::get('/gatepass/warehouse/gatepass', [GatePassController::class, 'index'])
    ->middleware('permission:warehouse.gate-pass.view')
    ->name('warehouse.gatepass.index');

Route::get('/gatepass/warehouse/gatepass/create', [GatePassController::class, 'create'])
    ->middleware('permission:warehouse.gate-pass.create')
    ->name('warehouse.gatepass.create');
Route::get('/gatepass/warehouse/gatepass/detail', [GatePassController::class, 'details'])
    ->middleware('permission:warehouse.gate-pass.show')
    ->name('warehouse.gatepass.show');

Route::get('/part/warehouse/parts', [PartsController::class, 'index'])
    ->middleware('permission:warehouse.parts.view')
    ->name('warehouse.parts.index');
Route::get('/part/warehouse/parts/details', [PartsController::class, 'details'])
    ->middleware('permission:warehouse.parts.show')
    ->name('warehouse.parts.detail');

Route::get('/part/warehouse/parts/create', [PartsController::class, 'create'])
    ->middleware('permission:warehouse.parts.create')
    ->name('warehouse.parts.create');

// Warehouse Products Routes
Route::prefix('warehouse/products')->name('warehouse.products.')->middleware('permission:warehouse.products.view')->group(function () {
    // List products
    Route::get('/', [ProductController::class, 'index'])->name('index');

    // Product reporting (static routes must remain before /{product})
    Route::get('/reports', [ProductReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export/pdf', [ProductReportController::class, 'exportPdf'])
        ->middleware('permission:warehouse.products.export-pdf')
        ->name('reports.export.pdf');
    Route::get('/reports/export/csv', [ProductReportController::class, 'exportCsv'])
        ->middleware('permission:warehouse.products.export-excel')
        ->name('reports.export.csv');

    // Existing register exports
    Route::get('/export/pdf', [ProductController::class, 'exportPdf'])->middleware('permission:warehouse.products.export-pdf')->name('export.pdf');
    Route::get('/export/excel', [ProductController::class, 'exportExcel'])->middleware('permission:warehouse.products.export-excel')->name('export.excel');

    // Separate Excel workflow for updating selected products (kept independent from register exports)
    Route::post('/bulk-update/template', [ProductController::class, 'downloadBulkUpdateTemplate'])
        ->middleware('permission:warehouse.products.edit')
        ->name('bulk-update.template');
    Route::post('/bulk-update/import', [ProductController::class, 'importBulkUpdate'])
        ->middleware('permission:warehouse.products.edit')
        ->name('bulk-update.import');

    // Bulk specification label printing. Store the selection before opening the configurable print page.
    Route::post('/bulk-print-specifications', [ProductController::class, 'prepareBulkSpecificationPrint'])
        ->middleware('permission:warehouse.products.show')
        ->name('bulk-print-specifications');
    Route::get('/bulk-print-specifications', [ProductController::class, 'printBulkSpecifications'])
        ->middleware('permission:warehouse.products.show')
        ->name('bulk-print-specifications.show');

    // Catalog Proxy Search
    Route::get('/catalog-search', [ProductController::class, 'catalogSearch'])->middleware('permission:warehouse.products.catalog-search')->name('catalog-search');

    // Resolve a scanned product barcode for bulk selection (must remain before /{product})
    Route::get('/scan-selection', [ProductController::class, 'scanSelection'])->name('scan-selection');

    // Create product manually (must be before /{product})
    Route::get('/create', [ProductController::class, 'create'])->middleware('permission:warehouse.products.create')->name('create');
    Route::post('/', [ProductController::class, 'store'])->middleware('permission:warehouse.products.create')->name('store');

    // AJAX uniqueness check
    Route::get('/check-unique-id', [ProductController::class, 'checkUniqueId'])->middleware('permission:warehouse.products.check-unique-id')->name('check-unique-id');

    // Show single product
    Route::get('/{product}', [ProductController::class, 'show'])->middleware('permission:warehouse.products.show')->name('show');

    // View product history
    Route::get('/{product}/history', [ProductController::class, 'history'])->middleware('permission:warehouse.products.history')->name('history');

    // Printable product specification label
    Route::get('/{product}/print-specification', [ProductController::class, 'printSpecification'])
        ->middleware('permission:warehouse.products.show')
        ->name('print-specification');

    // Edit product
    Route::get('/{product}/edit', [ProductController::class, 'edit'])->middleware('permission:warehouse.products.edit')->name('edit');

    // Update product
    Route::put('/{product}', [ProductController::class, 'update'])->middleware('permission:warehouse.products.edit')->name('update');

    // Delete single product
    Route::delete('/{product}', [ProductController::class, 'destroy'])->middleware('permission:warehouse.products.delete')->name('destroy');

    // Bulk Operations
    Route::post('/bulk-delete', [ProductController::class, 'bulkDelete'])->middleware('permission:warehouse.products.bulk-delete')->name('bulk-delete');
    Route::post('/bulk-status-change', [ProductController::class, 'bulkStatusChange'])->middleware('permission:warehouse.products.bulk-status-change')->name('bulk-status-change');
    Route::post('/bulk-update-title', [ProductController::class, 'bulkUpdateTitle'])->middleware('permission:warehouse.products.edit')->name('bulk-update-title');
    Route::post('/bulk-update-specifications', [ProductController::class, 'bulkUpdateSpecifications'])->middleware('permission:warehouse.products.edit')->name('bulk-update-specifications');
    Route::post('/bulk-add-part', [ProductController::class, 'bulkAddPart'])->middleware('permission:warehouse.products.bulk-add-part')->name('bulk-add-part');
    Route::post('/bulk-extract-part', [ProductController::class, 'bulkExtractPart'])->middleware('permission:warehouse.products.bulk-extract-part')->name('bulk-extract-part');
    Route::post('/bulk-move-location', [ProductController::class, 'bulkMoveLocation'])->middleware('permission:warehouse.products.bulk-move-location')->name('bulk-move-location');
    Route::post('/bulk-assign-tech', [ProductController::class, 'bulkAssignTech'])->middleware('permission:warehouse.products.bulk-assign-tech')->name('bulk-assign-tech');

    Route::post('/{product}/restore', [ProductController::class, 'restore'])->middleware('permission:warehouse.products.restore')->name('restore');
    // QR Code generation (JSON) for manual products
    Route::get('/{product}/qrcode', [ProductController::class, 'generateQrCode'])->middleware('permission:warehouse.products.qrcode')->name('qrcode');

    // QR Code full print page for manual products
    Route::get('/{product}/print-qrcode', [ProductController::class, 'printQrView'])->middleware('permission:warehouse.products.print-qrcode')->name('print-qrcode');
});
Route::post('/warehouse/products/{product}/action', [ProductController::class, 'action'])
    ->middleware('permission:warehouse.products.action')
    ->name('warehouse.products.action');

// Product Issue to Employee
Route::prefix('warehouse/product-issue')->name('warehouse.product-issue.')->middleware('permission:warehouse.product-issue.view')->group(function () {
    Route::get('/', [\App\Http\Controllers\Product\Warehouse\ProductIssueController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\Product\Warehouse\ProductIssueController::class, 'create'])->middleware('permission:warehouse.product-issue.create')->name('create');
    Route::post('/', [\App\Http\Controllers\Product\Warehouse\ProductIssueController::class, 'store'])->middleware('permission:warehouse.product-issue.create')->name('store');
    Route::get('/{product_issue}/edit', [\App\Http\Controllers\Product\Warehouse\ProductIssueController::class, 'edit'])->middleware('permission:warehouse.product-issue.edit')->name('edit');
    Route::put('/{product_issue}', [\App\Http\Controllers\Product\Warehouse\ProductIssueController::class, 'update'])->middleware('permission:warehouse.product-issue.edit')->name('update');
    Route::post('/{product_issue}/return', [\App\Http\Controllers\Product\Warehouse\ProductIssueController::class, 'returnProduct'])->middleware('permission:warehouse.product-issue.return')->name('return');
    Route::delete('/{product_issue}', [\App\Http\Controllers\Product\Warehouse\ProductIssueController::class, 'destroy'])->middleware('permission:warehouse.product-issue.delete')->name('destroy');
});

// Products
// Route::get('/product/warehouse/products', [ProductController::class, 'index'])
//     ->name('warehouse.products.index');

// // Add this route
// Edit route
Route::get('/warehouse/products/{id}/edit', [ProductController::class, 'edit'])
    ->middleware('permission:warehouse.products.edit')
    ->name('warehouse.products.edit');

// Extract part page (GET)
Route::get('/warehouse/products/{id}/extract-part', [ProductController::class, 'extractPartPage'])
    ->middleware('permission:warehouse.products.extract-part')
    ->name('warehouse.products.extract-part');

// Process extract part (POST)
Route::post('/warehouse/products/{id}/extract-part', [ProductController::class, 'processExtractPart'])
    ->middleware('permission:warehouse.products.process-extract-part')
    ->name('warehouse.products.process-extract-part');
// Technicians
// Technician routes
// routes/web.php

Route::prefix('warehouse')->name('warehouse.')->group(function () {
    // Main index route
    Route::get('/technicians', [TechnicianController::class, 'index'])->middleware('permission:warehouse.technicians.view')->name('technicians.index');

    // Bulk operations
    Route::post('/technicians/bulk/assign', [TechnicianController::class, 'bulkAssign'])->middleware('permission:warehouse.technicians.bulk-assign')->name('technicians.bulk.assign');
    Route::post('/technicians/bulk/delete', [TechnicianController::class, 'bulkDelete'])->middleware('permission:warehouse.technicians.bulk-delete')->name('technicians.bulk.delete');
    Route::post('/technicians/bulk/complete', [TechnicianController::class, 'bulkComplete'])->middleware('permission:warehouse.technicians.bulk-complete')->name('technicians.bulk.complete');

    // Task operations - USE CONSISTENT PARAMETER NAMES (id)
    Route::post('/technicians/start-task/{id}', [TechnicianController::class, 'startTask'])->middleware('permission:warehouse.technicians.start-task')->name('technicians.start-task');
    Route::post('/technicians/retry-task/{id}', [TechnicianController::class, 'retryTask'])->middleware('permission:warehouse.technicians.retry-task')->name('technicians.retry-task');
    Route::get('/technicians/submit-result/{id}/{type}', [TechnicianController::class, 'submitResult'])->middleware('permission:warehouse.technicians.submit-result')->name('technicians.submit.result');
    Route::get('/technicians/view-result/{id}', [TechnicianController::class, 'viewResult'])->middleware('permission:warehouse.technicians.view-result')->name('technicians.view.result');

    // Submission POST routes
    Route::post('/technicians/submit-visual/{id}', [TechnicianController::class, 'submitVisualInspection'])->middleware('permission:warehouse.technicians.submit-visual')->name('technicians.submit.visual');
    Route::post('/technicians/submit-functional/{id}', [TechnicianController::class, 'submitFunctionalTesting'])->middleware('permission:warehouse.technicians.submit-functional')->name('technicians.submit.functional');
    Route::post('/technicians/submit-general/{id}', [TechnicianController::class, 'submitGeneralTask'])->middleware('permission:warehouse.technicians.submit-general')->name('technicians.submit.general');
    Route::post('/technicians/complete-task/{id}', [TechnicianController::class, 'completeTask'])->middleware('permission:warehouse.technicians.complete-task')->name('technicians.complete-task');

    // Export routes
    Route::get('/technicians/export/pdf', [TechnicianController::class, 'exportPDF'])->middleware('permission:warehouse.technicians.export-pdf')->name('technicians.export.pdf');
    Route::get('/technicians/export/excel', [TechnicianController::class, 'exportExcel'])->middleware('permission:warehouse.technicians.export-excel')->name('technicians.export.excel');

    // Resource routes (except index which we already defined)
    Route::resource('technicians', TechnicianController::class)->except(['index'])->middleware('permission:warehouse.technicians.view');
});

// Parts
Route::prefix('warehouse')->name('warehouse.')->group(function () {
    // Parts Management Routes
    Route::get('/parts', [PartsController::class, 'index'])->middleware('permission:warehouse.parts.view')->name('parts.index');
    Route::get('/parts/create', [PartsController::class, 'create'])->middleware('permission:warehouse.parts.create')->name('parts.create');
    Route::post('/parts', [PartsController::class, 'store'])->middleware('permission:warehouse.parts.create')->name('parts.store');
    Route::get('/parts/{id}', [PartsController::class, 'show'])->middleware('permission:warehouse.parts.show')->name('parts.show');
    Route::get('/parts/{id}/edit', [PartsController::class, 'edit'])->middleware('permission:warehouse.parts.edit')->name('parts.edit');
    Route::put('/parts/{id}', [PartsController::class, 'update'])->middleware('permission:warehouse.parts.edit')->name('parts.update');
    Route::delete('/parts/{id}', [PartsController::class, 'destroy'])->middleware('permission:warehouse.parts.delete')->name('parts.destroy');

    // Part actions
    Route::post('/parts/add-to-product', [PartsController::class, 'addToProduct'])->middleware('permission:warehouse.parts.add-to-product')->name('parts.add-to-product');
    Route::post('/parts/{id}/transfer', [PartsController::class, 'transfer'])->middleware('permission:warehouse.parts.transfer')->name('parts.transfer');
    Route::get('/parts/{id}/movements', [PartsController::class, 'movements'])->middleware('permission:warehouse.parts.movements')->name('parts.movements');

    // Bulk operations
    Route::post('/parts/bulk-delete', [PartsController::class, 'bulkDelete'])->middleware('permission:warehouse.parts.bulk-delete')->name('parts.bulk-delete');

    // Export routes
    Route::get('/parts/export/pdf', [PartsController::class, 'exportPDF'])->middleware('permission:warehouse.parts.export-pdf')->name('parts.export.pdf');
    Route::get('/parts/export/excel', [PartsController::class, 'exportExcel'])->middleware('permission:warehouse.parts.export-excel')->name('parts.export.excel');
});

// Gate Pass
// Route::get('/product/warehouse/gate-pass', [GatePassController::class, 'index'])
//     ->name('warehouse.gate-pass.index');

// Scrap
// Route::get('/product/warehouse/scrap', [ScrapController::class, 'index'])
//     ->name('warehouse.scrap.index');
Route::prefix('warehouse')->name('warehouse.')->group(function () {

    // Gate Pass Routes
    Route::prefix('gate-pass')->name('gate-pass.')->group(function () {

        // -------------------------------------------------------
        // IMPORTANT: static routes MUST come before {id} wildcards
        // -------------------------------------------------------

        // List
        Route::get('/', [GatePassController::class, 'index'])->middleware('permission:warehouse.gate-pass.view')->name('index');

        // Create & Store
        Route::get('/create', [GatePassController::class, 'create'])->middleware('permission:warehouse.gate-pass.create')->name('create');
        Route::post('/', [GatePassController::class, 'store'])->middleware('permission:warehouse.gate-pass.create')->name('store');

        // Bulk operations  (static — must be before /{id})
        Route::post('/bulk-delete', [GatePassController::class, 'bulkDelete'])->middleware('permission:warehouse.gate-pass.bulk-delete')->name('bulk-delete');

        // Search  (static — must be before /{id})
        Route::get('/search/products', [GatePassController::class, 'searchProducts'])->middleware('permission:warehouse.gate-pass.search-products')->name('search.products');

        // Export  (static — must be before /{id})
        Route::get('/export/pdf', [GatePassController::class, 'exportPDF'])->middleware('permission:warehouse.gate-pass.export-pdf')->name('export.pdf');
        Route::get('/export/excel', [GatePassController::class, 'exportExcel'])->middleware('permission:warehouse.gate-pass.export-excel')->name('export.excel');

        // -------------------------------------------------------
        // Wildcard / resource routes — always LAST
        // -------------------------------------------------------

        // Show, Edit, Update, Delete
        Route::get('/{id}/print', [GatePassController::class, 'print'])->middleware('permission:warehouse.gate-pass.show')->name('print');
        Route::get('/{id}', [GatePassController::class, 'show'])->middleware('permission:warehouse.gate-pass.show')->name('show');
        Route::get('/{id}/edit', [GatePassController::class, 'edit'])->middleware('permission:warehouse.gate-pass.edit')->name('edit');
        Route::put('/{id}', [GatePassController::class, 'update'])->middleware('permission:warehouse.gate-pass.edit')->name('update');
        Route::delete('/{id}', [GatePassController::class, 'destroy'])->middleware('permission:warehouse.gate-pass.delete')->name('destroy');

        // Approval routes
        Route::post('/{id}/qc-approval', [GatePassController::class, 'qcApproval'])->middleware('permission:warehouse.gate-pass.qc-approval')->name('qc-approval');
        Route::post('/{id}/security-approval', [GatePassController::class, 'securityApproval'])->middleware('permission:warehouse.gate-pass.security-approval')->name('security-approval');
    });

});

Route::prefix('product/warehouse')->name('warehouse.')->group(function () {

    // Scrap Material Routes
    Route::prefix('scrap')->name('scrap.')->group(function () {

        // -------------------------------------------------------
        // Static routes MUST come before {id} wildcards
        // -------------------------------------------------------

        // List
        Route::get('/', [ScrapController::class, 'index'])->middleware('permission:warehouse.scrap.view')->name('index');

        // Create & Store
        Route::get('/create', [ScrapController::class, 'create'])->middleware('permission:warehouse.scrap.create')->name('create');
        Route::post('/', [ScrapController::class, 'store'])->middleware('permission:warehouse.scrap.create')->name('store');

        // Bulk operations (static — before /{id})
        Route::delete('/bulk-delete', [ScrapController::class, 'bulkDelete'])->middleware('permission:warehouse.scrap.bulk-delete')->name('bulk-delete');

        // Export (static — before /{id})
        Route::get('/export/pdf', [ScrapController::class, 'exportPDF'])->middleware('permission:warehouse.scrap.export-pdf')->name('export.pdf');
        Route::get('/export/excel', [ScrapController::class, 'exportExcel'])->middleware('permission:warehouse.scrap.export-excel')->name('export.excel');
        Route::get('/export/csv', [ScrapController::class, 'exportCSV'])->middleware('permission:warehouse.scrap.export-csv')->name('export.csv');

        // -------------------------------------------------------
        // Wildcard / resource routes — always LAST
        // -------------------------------------------------------

        Route::get('/{id}', [ScrapController::class, 'details'])->middleware('permission:warehouse.scrap.show')->name('details');
        Route::get('/{id}/edit', [ScrapController::class, 'edit'])->middleware('permission:warehouse.scrap.edit')->name('edit');
        Route::put('/{id}', [ScrapController::class, 'update'])->middleware('permission:warehouse.scrap.edit')->name('update');
        Route::delete('/{id}', [ScrapController::class, 'destroy'])->middleware('permission:warehouse.scrap.delete')->name('destroy');
    });

    // Progress Sheet Routes
    Route::prefix('progress-sheet')->name('progress-sheet.')->group(function () {

        Route::get('/', [ProgressSheetController::class, 'index'])->middleware('permission:warehouse.progress-sheet.view')->name('index');
        Route::get('/create', [ProgressSheetController::class, 'create'])->middleware('permission:warehouse.progress-sheet.create')->name('create');
        Route::post('/', [ProgressSheetController::class, 'store'])->middleware('permission:warehouse.progress-sheet.create')->name('store');
        Route::delete('/bulk-delete', [ProgressSheetController::class, 'bulkDelete'])->middleware('permission:warehouse.progress-sheet.bulk-delete')->name('bulk-delete');

        // Export (static — before /{id})
        Route::get('/export/pdf', [ProgressSheetController::class, 'exportPdf'])->middleware('permission:warehouse.progress-sheet.export-pdf')->name('export.pdf');
        Route::get('/export/csv', [ProgressSheetController::class, 'exportCsv'])->middleware('permission:warehouse.progress-sheet.export-csv')->name('export.csv');

        Route::get('/{id}/edit', [ProgressSheetController::class, 'edit'])->middleware('permission:warehouse.progress-sheet.edit')->name('edit');
        Route::put('/{id}', [ProgressSheetController::class, 'update'])->middleware('permission:warehouse.progress-sheet.edit')->name('update');
        Route::delete('/{id}', [ProgressSheetController::class, 'destroy'])->middleware('permission:warehouse.progress-sheet.delete')->name('destroy');
    });

});
