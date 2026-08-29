<?php

use App\Http\Controllers\Account\AccountingWorkspaceController;
use App\Http\Controllers\Account\AccountSuggestionController;
use App\Http\Controllers\Account\AnomalyDetectionController;
use App\Http\Controllers\Account\BankRuleController;
use App\Http\Controllers\Account\Bill\BillTransactionController;
use App\Http\Controllers\Account\BillController;
use App\Http\Controllers\Account\BillDownloadController;
use App\Http\Controllers\Account\BudgetController;
use App\Http\Controllers\Account\CashFlowForecastController;
use App\Http\Controllers\Account\CreditNoteController;
use App\Http\Controllers\Account\DashboardController;
use App\Http\Controllers\Account\DebitNoteController;
use App\Http\Controllers\Account\DuplicateDetectionController;
use App\Http\Controllers\Account\FixedAssetController;
use App\Http\Controllers\Account\InvoiceTemplateController;
use App\Http\Controllers\Account\InvoiceTermConditionController;
use App\Http\Controllers\Account\JournalEntry\JournalEntryController;
use App\Http\Controllers\Account\LiabilityPaymentController;
use App\Http\Controllers\Account\MonthEndAdjustmentController;
use App\Http\Controllers\Account\OpeningBalanceConversionController;
use App\Http\Controllers\Account\OrganizationSetting\AccountingPeriodController;
use App\Http\Controllers\Account\OrganizationSetting\ChartOfAccountController;
use App\Http\Controllers\Account\OrganizationSetting\OrganizationSettingController;
use App\Http\Controllers\Account\PurchaseOrderController;
use App\Http\Controllers\Account\PurchaseOrderQuotationController;
use App\Http\Controllers\Account\RecurringBillController;
use App\Http\Controllers\Account\RecurringInvoiceController;
use App\Http\Controllers\Account\Report\BalanceSheetController;
use App\Http\Controllers\Account\Report\CashFlowController;
use App\Http\Controllers\Account\Report\CashFlowDirectController;
use App\Http\Controllers\Account\Report\CustomerAgedReceivableController;
use App\Http\Controllers\Account\Report\JournalController;
use App\Http\Controllers\Account\Report\LedgerController;
use App\Http\Controllers\Account\Report\ProfitLossController;
use App\Http\Controllers\Account\Report\ReportAuditTrailController;
use App\Http\Controllers\Account\Report\ReportController;
use App\Http\Controllers\Account\Report\TrialBalanceController;
use App\Http\Controllers\Account\Report\VendorAgedPayableController;
use App\Http\Controllers\Account\Sale\SaleTransactionController;
use App\Http\Controllers\Account\SaleController;
use App\Http\Controllers\Account\SaleDownloadController;
use App\Http\Controllers\Account\TaxPaymentController;
use App\Http\Controllers\Account\TaxPeriodController;
use App\Http\Controllers\Account\TransactionToInvoice;
use Illuminate\Support\Facades\Route;

// Role authorization is handled by OrganizationSettingController. Keeping these
// routes outside account.dashboard.view lets Accountant Managers manage their
// accounting team without receiving access to the global Users module.
Route::prefix('account/organization-settings/users')
    ->name('account.organization-settings.')
    ->middleware('permission:account.organization-settings.users.view')
    ->group(function () {
        Route::get('', [OrganizationSettingController::class, 'accountantManagers'])->name('users');
        Route::get('/create', [OrganizationSettingController::class, 'createUser'])->middleware('permission:account.organization-settings.users.create')->name('users.create');
        Route::post('', [OrganizationSettingController::class, 'storeUser'])->middleware('permission:account.organization-settings.users.create')->name('users.store');
        Route::get('/export/pdf', [OrganizationSettingController::class, 'exportUsers'])->middleware('permission:account.organization-settings.users.export')->name('users.export.pdf');
        Route::get('/{user}/edit', [OrganizationSettingController::class, 'editUser'])->middleware('permission:account.organization-settings.users.edit')->name('users.edit');
        Route::put('/{user}', [OrganizationSettingController::class, 'updateUser'])->middleware('permission:account.organization-settings.users.edit')->name('users.update');
        Route::delete('/{user}', [OrganizationSettingController::class, 'destroyUser'])->middleware('permission:account.organization-settings.users.delete')->name('users.destroy');
    });

Route::prefix('account/organization-settings/roles')
    ->name('account.organization-settings.roles.')
    ->group(function () {
        Route::get('/create', [OrganizationSettingController::class, 'createRole'])->middleware('permission:account.organization-settings.roles.create')->name('create');
        Route::post('', [OrganizationSettingController::class, 'storeRole'])->middleware('permission:account.organization-settings.roles.create')->name('store');
        Route::get('/{role}/edit', [OrganizationSettingController::class, 'editRole'])->middleware('permission:account.organization-settings.roles.edit')->name('edit');
        Route::put('/{role}', [OrganizationSettingController::class, 'updateRole'])->middleware('permission:account.organization-settings.roles.edit')->name('update');
    });

Route::prefix('account')
    ->name('account.')
    ->middleware('permission:account.dashboard.view')
    ->group(function () {
        // Dashboard
        Route::controller(DashboardController::class)->group(function () {
            Route::get('/dashboard', 'index')->name('dashboard');
            Route::post('/dashboard/layout', 'saveLayout')->middleware('permission:account.dashboard.layout.edit')->name('dashboard.layout.save');
            Route::delete('/dashboard/layout', 'resetLayout')->middleware('permission:account.dashboard.layout.edit')->name('dashboard.layout.reset');
            Route::post('/dashboard/sync-document-balances', 'syncDocumentBalances')->middleware('permission:account.dashboard.balance-sync')->name('dashboard.sync-document-balances');
            Route::post('/payroll-accounting/{payRun}/post', 'postPayrollToAccounting')->middleware('permission:account.dashboard.payroll-post')->name('payroll-accounting.post');
            Route::get('kpi', 'kpiData')->middleware('permission:account.kpi.view')->name('kpi');
        });

        // Accounting control center and month-end workflow
        Route::middleware('permission:account.reports.view')->group(function () {
            Route::get('/control-center', [AccountingWorkspaceController::class, 'commandCenter'])->name('control-center.index');
            Route::get('/health-center', [AccountingWorkspaceController::class, 'health'])->name('health-center.index');
            Route::get('/balance-sync-history', [AccountingWorkspaceController::class, 'balanceSyncHistory'])->name('balance-sync.index');
            Route::get('/balance-sync-history/{balanceSyncRun}', [AccountingWorkspaceController::class, 'balanceSyncShow'])->name('balance-sync.show');
            Route::get('/period-close', [AccountingWorkspaceController::class, 'periodClose'])->name('period-close.index');
            Route::post('/period-close/{period}/task', [AccountingWorkspaceController::class, 'updateCloseTask'])->middleware('permission:account.accounting-period.edit')->name('period-close.task');
            Route::post('/period-close/{period}/close', [AccountingWorkspaceController::class, 'closePeriod'])->middleware('permission:account.accounting-period.update-status')->name('period-close.close');
            Route::get('/document-trace', [AccountingWorkspaceController::class, 'documentTrace'])->name('document-trace.index');
        });

        Route::prefix('month-end-adjustments')
            ->name('month-end-adjustments.')
            ->middleware('permission:account.journal-entries.view')
            ->controller(MonthEndAdjustmentController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->middleware('permission:account.journal-entries.create')->name('create');
                Route::post('/', 'store')->middleware('permission:account.journal-entries.create')->name('store');
                Route::get('/{monthEndAdjustment}', 'show')->middleware('permission:account.journal-entries.show')->name('show');
                Route::post('/{monthEndAdjustment}/post', 'post')->middleware('permission:account.journal-entries.create')->name('post');
                Route::delete('/{monthEndAdjustment}', 'destroy')->middleware('permission:account.journal-entries.delete')->name('destroy');
            });

        Route::prefix('bank-rules')
            ->name('bank-rules.')
            ->middleware('permission:account.bank-rules.view')
            ->controller(BankRuleController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/', 'store')->middleware('permission:account.bank-rules.create')->name('store');
                Route::post('/{bankRule}/feedback', 'feedback')->middleware('permission:account.bank-rules.edit')->name('feedback');
                Route::put('/{bankRule}', 'update')->middleware('permission:account.bank-rules.edit')->name('update');
                Route::delete('/{bankRule}', 'destroy')->middleware('permission:account.bank-rules.delete')->name('destroy');
            });

        // AI Account Suggestion
        Route::get('/suggest-account', [AccountSuggestionController::class, 'suggest'])->middleware('permission:account.ai-suggestion.view')->name('suggest-account');

        Route::prefix('conversion/opening-balances')
            ->name('conversion.opening-balances.')
            ->middleware('permission:account.opening-balance-conversion.view')
            ->controller(OpeningBalanceConversionController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->middleware('permission:account.opening-balance-conversion.create')->name('create');
                Route::post('/', 'store')->middleware('permission:account.opening-balance-conversion.create')->name('store');
                Route::get('/template', 'downloadTemplate')->middleware('permission:account.opening-balance-conversion.import')->name('template');
                Route::post('/import', 'import')->middleware('permission:account.opening-balance-conversion.import')->name('import');
                Route::post('/legacy/{account}/adjustment', 'createLegacyAdjustment')->middleware('permission:account.opening-balance-conversion.create')->name('legacy.adjustment');
                Route::get('/{openingBalanceBatch}', 'show')->name('show');
                Route::get('/{openingBalanceBatch}/edit', 'edit')->middleware('permission:account.opening-balance-conversion.edit')->name('edit');
                Route::put('/{openingBalanceBatch}', 'update')->middleware('permission:account.opening-balance-conversion.edit')->name('update');
                Route::post('/{openingBalanceBatch}/validate', 'validateBatch')->middleware('permission:account.opening-balance-conversion.validate')->name('validate');
                Route::post('/{openingBalanceBatch}/approve', 'approve')->middleware('permission:account.opening-balance-conversion.validate')->name('approve');
                Route::post('/{openingBalanceBatch}/post', 'post')->middleware('permission:account.opening-balance-conversion.post')->name('post');
                Route::post('/{openingBalanceBatch}/reverse', 'reverse')->middleware('permission:account.opening-balance-conversion.reverse')->name('reverse');
                Route::post('/{openingBalanceBatch}/correct-posting-date', 'correctPostingDate')->middleware('permission:account.opening-balance-conversion.reverse')->name('correct-posting-date');
                Route::post('/{openingBalanceBatch}/copy-reversed-batch', 'copyReversedBatch')->middleware('permission:account.opening-balance-conversion.create')->name('copy-reversed-batch');
                Route::post('/{openingBalanceBatch}/revise', 'revise')->middleware('permission:account.opening-balance-conversion.edit')->name('revise');
            });

        // Duplicate Detection
        Route::get('/check-duplicates', [DuplicateDetectionController::class, 'check'])->middleware('permission:account.duplicate-detection.view')->name('check-duplicates');

        // Anomaly Detection
        Route::get('/anomaly-detection', [AnomalyDetectionController::class, 'index'])->middleware('permission:account.anomaly-detection.view')->name('anomaly-detection');
        Route::get('/anomaly-detection/summary', [AnomalyDetectionController::class, 'summary'])->middleware('permission:account.anomaly-detection.view')->name('anomaly-detection.summary');
        Route::get('/anomaly-detection/history', [AnomalyDetectionController::class, 'history'])->middleware('permission:account.anomaly-detection.view')->name('anomaly-detection.history');
        Route::post('/anomaly-detection/execute', [AnomalyDetectionController::class, 'executeAction'])->middleware('permission:account.anomaly-detection.execute')->name('anomaly-detection.execute');

        // Sale Routes
        Route::prefix('sale-downloads')
            ->name('sale-download.')
            ->middleware('permission:account.sale.view')
            ->group(function () {
                Route::get('/', [SaleDownloadController::class, 'index'])->name('index');
                Route::get('/pdf', [SaleDownloadController::class, 'pdf'])->middleware('permission:account.sale.export-pdf')->name('pdf');
                Route::get('/separate-pdfs', [SaleDownloadController::class, 'separatePdfs'])->middleware('permission:account.sale.export-pdf')->name('separate-pdfs');
                Route::get('/csv', [SaleDownloadController::class, 'csv'])->middleware('permission:account.sale.export')->name('csv');
            });

        // Sale Routes
        Route::controller(SaleController::class)->group(function () {
            Route::prefix('sale')
                ->name('sale-invoice.')
                ->middleware('permission:account.sale.view')
                ->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('/create', 'create')->middleware('permission:account.sale.create')->name('create');
                    Route::post('/create', 'store')->middleware('permission:account.sale.create')->name('store');
                    Route::post('/extract', 'extractInvoice')->middleware('permission:account.sale.create')->name('extract');
                    Route::put('/update/{id}', 'update')->middleware('permission:account.sale.edit')->name('update');
                    Route::view('/report', 'pages.account.sale.report.index')->middleware('permission:account.reports.view')->name('report.index');
                    Route::view('/credit-note', 'pages.account.sale.credit-note.index')->middleware('permission:account.credit-note.view')->name('credit-note.index');
                    Route::delete('/delete/{id}', 'destroy')->middleware('permission:account.sale.delete')->name('destroy');
                    Route::post('/{id}/void', 'void')->middleware('permission:account.sale.delete')->name('void');
                    Route::post('/{id}/resend-email', 'resendEmail')->middleware('permission:account.sale.show')->name('resend-email');
                    Route::post('/{id}/duplicate', 'duplicate')->middleware('permission:account.sale.create')->name('duplicate');
                    Route::get('/attachment/{id}', 'downloadAttachment')->middleware('permission:account.sale.show')->name('attachment.download');
                    Route::get('/{id}/version/{version}/pdf', 'downloadVersion')->middleware('permission:account.sale.show')->name('version.pdf');
                    Route::get('/edit/{id}', 'edit')->middleware('permission:account.sale.edit')->name('edit');
                    Route::get('/show/{id}', 'show')->middleware('permission:account.sale.show')->name('show');
                    Route::get('/export/{format}', 'export')->middleware('permission:account.sale.export')->name('export');
                    Route::get('/{id}/export-pdf', 'exportPdf')->middleware('permission:account.sale.export-pdf')->name('export-pdf');
                    Route::get('/{id}/export-excel', 'exportExcel')->middleware('permission:account.sale.export')->name('export-excel');
                    Route::get('/export-columns', 'getExportColumns')->middleware('permission:account.sale.export')->name('export-columns');
                    Route::get('/import', 'import')->middleware('permission:account.sale.import')->name('import');
                    Route::post('/import', 'import')->middleware('permission:account.sale.import')->name('import.post');
                    Route::post('/import-chunk', 'importChunk')->middleware('permission:account.sale.import')->name('import.chunk');
                    Route::get('/download-template', 'downloadTemplate')->middleware('permission:account.sale.import')->name('download-template');
                    Route::get('/{unique_no}/logs', 'logs')->middleware('permission:account.sale.show')->name('logs');
                    Route::post('/record-payment', 'recordPayment')->middleware('permission:account.sale.record-payment')->name('record-payment');
                    Route::put('/transaction/{id}', 'updateTransaction')->middleware('permission:account.sale.transaction.update')->name('transaction.update');
                });

            // Audit Logs Routes
            Route::get('/sale-invoice/{id}/audit-logs', 'auditLogs')->middleware('permission:account.sale.show')->name('sale-invoice.audit-logs');
            Route::get('/sale-invoice/audit-log-details/{logId}', 'getLogDetails')->middleware('permission:account.sale.show')->name('sale-invoice.audit-log-details');
        });

        // Sale Transactions
        Route::prefix('sale')
            ->name('sale.')
            ->group(function () {
                Route::controller(SaleTransactionController::class)->group(function () {
                    Route::get('/sale-transactions', 'index')->middleware('permission:account.sale-transaction.view')->name('sale-transaction.index');
                });
            });

        // Bill Transactions
        Route::prefix('bill')
            ->name('bill.')
            ->group(function () {
                Route::controller(BillTransactionController::class)->group(function () {
                    Route::get('/bill-transactions', 'index')->middleware('permission:account.bill-transaction.view')->name('bill-transaction.index');
                });
            });

        // Purchase Order Routes
        Route::prefix('purchase-orders')
            ->name('purchase-order.')
            ->middleware('permission:account.purchase-order.view')
            ->group(function () {
                Route::get('/list', [PurchaseOrderController::class, 'list'])->name('order.list');
                Route::get('/', [PurchaseOrderController::class, 'index'])->name('index');
                Route::get('/create', [PurchaseOrderController::class, 'create'])->middleware('permission:account.purchase-order.create')->name('create');
                Route::post('/store', [PurchaseOrderController::class, 'store'])->middleware('permission:account.purchase-order.create')->name('store');
                Route::get('/{id}', [PurchaseOrderController::class, 'show'])->middleware('permission:account.purchase-order.show')->name('show');
                Route::get('/{id}/edit', [PurchaseOrderController::class, 'edit'])->middleware('permission:account.purchase-order.edit')->name('edit');
                Route::put('/{id}/update', [PurchaseOrderController::class, 'update'])->middleware('permission:account.purchase-order.edit')->name('update');
                Route::delete('/{id}/destroy', [PurchaseOrderController::class, 'destroy'])->middleware('permission:account.purchase-order.delete')->name('destroy');
                Route::get('/{id}/audit-logs', [PurchaseOrderController::class, 'auditLogs'])->middleware('permission:account.purchase-order.show')->name('audit-logs');
                Route::get('/audit-log/{logId}/details', [PurchaseOrderController::class, 'getLogDetails'])->middleware('permission:account.purchase-order.show')->name('log-details');
                Route::get('/export/{format}', [PurchaseOrderController::class, 'export'])->middleware('permission:account.purchase-order.export')->name('export');
                Route::get('/{id}/export-pdf', [PurchaseOrderController::class, 'exportPdf'])->middleware('permission:account.purchase-order.export-pdf')->name('export-pdf');
            });

        // Purchase Order Quotation Routes
        Route::prefix('purchase-orders-quotation')
            ->name('purchase-order-quotation.')
            ->middleware('permission:account.purchase-order-quotation.view')
            ->group(function () {
                Route::get('/list', [PurchaseOrderQuotationController::class, 'list'])->name('list');
                Route::get('/', [PurchaseOrderQuotationController::class, 'index'])->name('index');
                Route::get('/reports', [PurchaseOrderQuotationController::class, 'report'])->middleware('permission:account.reports.view')->name('reports');
                Route::get('/create', [PurchaseOrderQuotationController::class, 'create'])->middleware('permission:account.purchase-order-quotation.create')->name('create');
                Route::get('/good-recieve', [PurchaseOrderQuotationController::class, 'good_receive'])->middleware('permission:account.purchase-order-quotation.show')->name('good-recieve');
                Route::post('/store', [PurchaseOrderQuotationController::class, 'store'])->middleware('permission:account.purchase-order-quotation.create')->name('store');
                Route::get('/{id}', [PurchaseOrderQuotationController::class, 'show'])->middleware('permission:account.purchase-order-quotation.show')->name('show');
                Route::get('/{id}/edit', [PurchaseOrderQuotationController::class, 'edit'])->middleware('permission:account.purchase-order-quotation.edit')->name('edit');
                Route::get('/{id}/gd_edit/pur', [PurchaseOrderQuotationController::class, 'gd_edit'])->middleware('permission:account.purchase-order-quotation.edit')->name('gd_edit');
                Route::put('/{id}/gd_edit/pur', [PurchaseOrderQuotationController::class, 'gd_update'])->middleware('permission:account.purchase-order-quotation.edit')->name('gd_update');
                Route::put('/{id}/update', [PurchaseOrderQuotationController::class, 'update'])->middleware('permission:account.purchase-order-quotation.edit')->name('update');
                Route::delete('/{id}/destroy', [PurchaseOrderQuotationController::class, 'destroy'])->middleware('permission:account.purchase-order-quotation.delete')->name('destroy');
                Route::get('/{id}/audit-logs', [PurchaseOrderQuotationController::class, 'auditLogs'])->middleware('permission:account.purchase-order-quotation.show')->name('audit-logs');
                Route::get('/audit-log/{logId}/details', [PurchaseOrderQuotationController::class, 'getLogDetails'])->middleware('permission:account.purchase-order-quotation.show')->name('log-details');
                Route::put('/purchase-order-quotation/gd_update/{id}', [PurchaseOrderQuotationController::class, 'gd_update'])->middleware('permission:account.purchase-order-quotation.edit')->name('account.purchase-order-quotation.gd_update');
                Route::get('/export/{format}', [PurchaseOrderQuotationController::class, 'export'])->middleware('permission:account.purchase-order-quotation.export')->name('export');
                Route::get('/{id}/export-pdf', [PurchaseOrderQuotationController::class, 'exportPdf'])->middleware('permission:account.purchase-order-quotation.export-pdf')->name('export-pdf');
                Route::post('/purchase-order-invoice/record-payment', [PurchaseOrderQuotationController::class, 'recordPayment'])->middleware('permission:account.purchase-order-quotation.record-payment')->name('record-payment');
                Route::put('/payment/{payment}', [PurchaseOrderQuotationController::class, 'updatePayment'])->middleware('permission:account.purchase-order-quotation.record-payment')->name('payment.update');
            });

        // Credit Note Routes
        Route::prefix('credit-notes')
            ->name('sale.credit-notes.')
            ->group(function () {
                Route::get('/create', [CreditNoteController::class, 'create'])->middleware('permission:account.credit-note.create')->name('create');
                Route::get('/', [CreditNoteController::class, 'index'])->middleware('permission:account.credit-note.view')->name('index');
                Route::post('/store', [CreditNoteController::class, 'store'])->middleware('permission:account.credit-note.create')->name('store');
                Route::get('/get-customer-sales/{customerId}', [CreditNoteController::class, 'getCustomerSales'])->middleware('permission:account.credit-note.create')->name('get-customer-sales');
                Route::get('/get-sale-details/{saleId}', [CreditNoteController::class, 'getSaleDetails'])->middleware('permission:account.credit-note.create')->name('get-sale-details');
                Route::get('/show/{Id}', [CreditNoteController::class, 'show'])->middleware('permission:account.credit-note.show')->name('show');
                Route::get('/{Id}/audit-logs', [CreditNoteController::class, 'auditLogs'])->middleware('permission:account.credit-note.show')->name('audit-logs');
                Route::get('/log-details/{logId}', [CreditNoteController::class, 'getLogDetails'])->middleware('permission:account.credit-note.show')->name('log-details');
                Route::get('/export/{Id}', [CreditNoteController::class, 'exportPdf'])->middleware('permission:account.credit-note.export')->name('export-pdf');
                Route::get('/export-excel/{Id}', [CreditNoteController::class, 'exportExcel'])->middleware('permission:account.credit-note.export')->name('export-excel');
                Route::get('/export-columns', [CreditNoteController::class, 'getExportColumns'])->middleware('permission:account.credit-note.export')->name('export-columns');
                Route::delete('/delete/{Id}', [CreditNoteController::class, 'destroy'])->middleware('permission:account.credit-note.delete')->name('destroy');
                Route::get('/get-accounts', [CreditNoteController::class, 'getAccounts'])->middleware('permission:account.credit-note.create')->name('get-accounts');
                Route::get('/get-taxes', [CreditNoteController::class, 'getTaxes'])->middleware('permission:account.credit-note.create')->name('get-taxes');
                Route::get('/get-currencies', [CreditNoteController::class, 'getCurrencies'])->middleware('permission:account.credit-note.create')->name('get-currencies');
                Route::post('/credit-invoice/record-payment', [CreditNoteController::class, 'recordPayment'])->middleware('permission:account.credit-note.record-payment')->name('record-payment');
                Route::post('/{creditNote}/allocate', [CreditNoteController::class, 'allocate'])->middleware('permission:account.credit-note.record-payment')->name('allocate');
                Route::post('/{creditNote}/refund', [CreditNoteController::class, 'refund'])->middleware('permission:account.credit-note.record-payment')->name('refund');
                Route::put('/transaction/{id}', [CreditNoteController::class, 'updateTransaction'])->middleware('permission:account.credit-note.transaction.update')->name('transaction.update');
                Route::get('/{Id}/edit', [CreditNoteController::class, 'edit'])->middleware('permission:account.credit-note.edit')->name('edit');
                Route::put('/update/{id}', [CreditNoteController::class, 'update'])->middleware('permission:account.credit-note.edit')->name('update');
            });

        // Bill Routes - Fixed version
        Route::prefix('bill-downloads')
            ->name('bill-download.')
            ->middleware('permission:account.bill-invoice.view')
            ->group(function () {
                Route::get('/', [BillDownloadController::class, 'index'])->name('index');
                Route::get('/pdf', [BillDownloadController::class, 'pdf'])->middleware('permission:account.bill-invoice.export-pdf')->name('pdf');
                Route::get('/separate-pdfs', [BillDownloadController::class, 'separatePdfs'])->middleware('permission:account.bill-invoice.export-pdf')->name('separate-pdfs');
                Route::get('/csv', [BillDownloadController::class, 'csv'])->middleware('permission:account.bill-invoice.export')->name('csv');
            });

        // Bill Routes - Fixed version
        Route::prefix('bill-invoice')
            ->name('bill-invoice.')
            ->middleware('permission:account.bill-invoice.view')
            ->group(function () {
                Route::get('/', [BillController::class, 'index'])->name('index');
                Route::get('/report', [BillController::class, 'index'])->middleware('permission:account.reports.view')->name('report.index');
                Route::get('/create', [BillController::class, 'create'])->middleware('permission:account.bill-invoice.create')->name('create');
                Route::post('/store', [BillController::class, 'store'])->middleware('permission:account.bill-invoice.create')->name('store');
                Route::post('/extract', [BillController::class, 'extractInvoice'])->middleware('permission:account.bill-invoice.create')->name('extract');
                Route::get('/export-columns', [BillController::class, 'getExportColumns'])->middleware('permission:account.bill-invoice.export')->name('export-columns');
                Route::get('/{id}', [BillController::class, 'show'])->middleware('permission:account.bill-invoice.show')->name('show');
                Route::get('/{id}/edit', [BillController::class, 'edit'])->middleware('permission:account.bill-invoice.edit')->name('edit');
                Route::put('/{id}/update', [BillController::class, 'update'])->middleware('permission:account.bill-invoice.edit')->name('update');
                Route::delete('/{id}/delete', [BillController::class, 'destroy'])->middleware('permission:account.bill-invoice.delete')->name('destroy');
                Route::get('/{id}/audit-logs', [BillController::class, 'auditLogs'])->middleware('permission:account.bill-invoice.show')->name('audit-logs');
                Route::get('/log-details/{logId}', [BillController::class, 'getLogDetails'])->middleware('permission:account.bill-invoice.show')->name('log-details');
                Route::get('/export/{format}', [BillController::class, 'export'])->middleware('permission:account.bill-invoice.export')->name('export');
                Route::get('/{id}/export-pdf', [BillController::class, 'exportPdf'])->middleware('permission:account.bill-invoice.export-pdf')->name('export-pdf');
                Route::get('/{id}/export-excel', [BillController::class, 'exportExcel'])->middleware('permission:account.bill-invoice.export')->name('export-excel');
                Route::get('/import', [BillController::class, 'import'])->middleware('permission:account.bill-invoice.import')->name('import');
                Route::post('/import', [BillController::class, 'import'])->middleware('permission:account.bill-invoice.import')->name('import.post');
                Route::post('/import-chunk', [BillController::class, 'importChunk'])->middleware('permission:account.bill-invoice.import')->name('import.chunk');
                Route::get('/download/template', [BillController::class, 'downloadTemplate'])->middleware('permission:account.bill-invoice.import')->name('download.template');
                Route::post('/record-payment', [BillController::class, 'recordPayment'])->middleware('permission:account.bill-invoice.record-payment')->name('record-payment');
                Route::post('/{id}/void', [BillController::class, 'void'])->middleware('permission:account.bill-invoice.delete')->name('void');
                Route::post('/{id}/duplicate', [BillController::class, 'duplicate'])->middleware('permission:account.bill-invoice.create')->name('duplicate');
                Route::post('/{id}/resend-email', [BillController::class, 'resendEmail'])->middleware('permission:account.bill-invoice.show')->name('resend-email');
                Route::get('/attachment/{id}', [BillController::class, 'downloadAttachment'])->middleware('permission:account.bill-invoice.show')->name('attachment.download');
                Route::get('/{id}/version/{version}/pdf', [BillController::class, 'downloadVersionPdf'])->middleware('permission:account.bill-invoice.show')->name('version.pdf');
                Route::put('/transaction/{id}', [BillController::class, 'updateTransaction'])->middleware('permission:account.bill-invoice.transaction.update')->name('transaction.update');
            });

        // Organization Settings
        Route::prefix('organization-settings')
            ->name('organization-settings.')
            ->middleware('permission:account.organization-settings.view')
            ->group(function () {
                Route::get('/invoice-templates', [InvoiceTemplateController::class, 'index'])->middleware('permission:account.invoice-setting.view')->name('invoice-templates.index');
                Route::get('/invoice-templates/create', [InvoiceTemplateController::class, 'create'])->middleware('permission:account.invoice-setting.create')->name('invoice-templates.create');
                Route::post('/invoice-templates', [InvoiceTemplateController::class, 'store'])->middleware('permission:account.invoice-setting.create')->name('invoice-templates.store');
                Route::get('/invoice-templates/{id}', [InvoiceTemplateController::class, 'show'])->middleware('permission:account.invoice-setting.view')->name('invoice-templates.show');
                Route::get('/invoice-templates/{id}/edit', [InvoiceTemplateController::class, 'edit'])->middleware('permission:account.invoice-setting.edit')->name('invoice-templates.edit');
                Route::put('/invoice-templates/{id}', [InvoiceTemplateController::class, 'update'])->middleware('permission:account.invoice-setting.edit')->name('invoice-templates.update');
                Route::delete('/invoice-templates/{id}', [InvoiceTemplateController::class, 'destroy'])->middleware('permission:account.invoice-setting.delete')->name('invoice-templates.destroy');
                Route::post('/invoice-templates/{id}/duplicate', [InvoiceTemplateController::class, 'duplicate'])->middleware('permission:account.invoice-setting.create')->name('invoice-templates.duplicate');
                Route::get('/invoice-term-conditions', [InvoiceTermConditionController::class, 'index'])->middleware('permission:account.invoice-setting.view')->name('invoice-term-conditions.index');
                Route::post('/invoice-term-conditions', [InvoiceTermConditionController::class, 'store'])->middleware('permission:account.invoice-setting.create')->name('invoice-term-conditions.store');
                Route::put('/invoice-term-conditions/{invoiceTermCondition}', [InvoiceTermConditionController::class, 'update'])->middleware('permission:account.invoice-setting.edit')->name('invoice-term-conditions.update');
                Route::delete('/invoice-term-conditions/{invoiceTermCondition}', [InvoiceTermConditionController::class, 'destroy'])->middleware('permission:account.invoice-setting.delete')->name('invoice-term-conditions.destroy');
                Route::get('', [OrganizationSettingController::class, 'index'])->name('index');
                Route::get('/company/{id}', [OrganizationSettingController::class, 'company'])->middleware('permission:account.organization-profile.view')->name('company');
                Route::get('/company/{id}/edit', [OrganizationSettingController::class, 'companyEdit'])->middleware('permission:account.organization-profile.edit')->name('company.edit');
                Route::view('/currency', 'pages.account.organization-settings.currency.index')->middleware('permission:account.currency-setting.view')->name('currency.index');

                // Accounting Period
                Route::controller(AccountingPeriodController::class)
                    ->prefix('accounting-period')
                    ->as('accounting-period.')
                    ->middleware('permission:account.accounting-period.view')
                    ->group(function () {
                        Route::get('/', 'index')->name('index');
                        Route::get('/create', 'create')->middleware('permission:account.accounting-period.create')->name('create');
                        Route::post('/store', 'store')->middleware('permission:account.accounting-period.create')->name('store');
                        Route::get('/edit/{accounting_period}', 'edit')->middleware('permission:account.accounting-period.edit')->name('edit');
                        Route::put('/update/{id}', 'update')->middleware('permission:account.accounting-period.edit')->name('update');
                        Route::put('/{accounting_period}/allowed-users', 'updateAllowedUsers')->middleware('permission:account.accounting-period.edit')->name('allowed-users.update');
                        Route::delete('/delete/{id}', 'destroy')->middleware('permission:account.accounting-period.delete')->name('destroy');
                        Route::get('/detail/{id}', 'show')->middleware('permission:account.accounting-period.show')->name('detail');
                        Route::put('/status/{id}', 'updateStatus')->middleware('permission:account.accounting-period.update-status')->name('updateStatus');
                        Route::get('/{id}/logs', 'logs')->middleware('permission:account.accounting-period.show')->name('logs');
                        Route::get('/all-logs', 'allLogs')->middleware('permission:account.accounting-period.show')->name('all.logs');
                    });

                // Chart of Accounts
                Route::controller(ChartOfAccountController::class)
                    ->prefix('account')
                    ->as('account.')
                    ->middleware('permission:account.chart-of-account.view')
                    ->group(function () {
                        Route::get('/', 'index')->name('index');
                        Route::get('/create', 'create')->middleware('permission:account.chart-of-account.create')->name('create');
                        Route::post('/store', 'store')->middleware('permission:account.chart-of-account.create')->name('store');
                        Route::get('/edit/{id}', 'edit')->middleware('permission:account.chart-of-account.edit')->name('edit');
                        Route::put('/update/{id}', 'update')->middleware('permission:account.chart-of-account.edit')->name('update');
                        Route::delete('/delete/{id}', 'destroy')->middleware('permission:account.chart-of-account.delete')->name('destroy');
                        Route::get('/detail/{id}', 'show')->middleware('permission:account.chart-of-account.show')->name('detail');
                        Route::get('/export', 'export')->middleware('permission:account.chart-of-account.export')->name('export');
                        Route::get('/download-template', 'downloadTemplate')->middleware('permission:account.chart-of-account.import')->name('download-template');
                        Route::post('/import', 'import')->middleware('permission:account.chart-of-account.import')->name('import');
                        Route::get('/{id}/logs', 'logs')->middleware('permission:account.chart-of-account.show')->name('logs');
                        Route::get('/all-logs', 'allLogs')->middleware('permission:account.chart-of-account.show')->name('all.logs');
                        Route::get('/search', 'search')->name('search');
                        Route::post('/bulk-delete', 'bulkDelete')->middleware('permission:account.chart-of-account.bulk-delete')->name('bulk-delete');
                        Route::post('/bulk-archive', 'bulkArchive')->middleware('permission:account.chart-of-account.bulk-archive')->name('bulk-archive');
                        Route::post('/bulk-restore', 'bulkRestore')->middleware('permission:account.chart-of-account.bulk-restore')->name('bulk-restore');
                        Route::post('/bulk-change-tax-rate', 'bulkChangeTaxRate')->middleware('permission:account.chart-of-account.bulk-edit')->name('bulk-change-tax-rate');
                    });

                Route::view('currency', 'pages.account.organization-settings.currency.index')->middleware('permission:account.currency-setting.view')->name('currency.index');
            });

        // Recurring Invoices
        Route::prefix('recurring-invoices')
            ->name('recurring-invoice.')
            ->middleware('permission:account.recurring-invoice.view')
            ->controller(RecurringInvoiceController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->middleware('permission:account.recurring-invoice.create')->name('create');
                Route::post('/', 'store')->middleware('permission:account.recurring-invoice.create')->name('store');
                Route::get('/{recurringInvoice}/edit', 'edit')->middleware('permission:account.recurring-invoice.edit')->name('edit');
                Route::put('/{recurringInvoice}', 'update')->middleware('permission:account.recurring-invoice.edit')->name('update');
                Route::delete('/{recurringInvoice}', 'destroy')->middleware('permission:account.recurring-invoice.delete')->name('destroy');
                Route::patch('/{recurringInvoice}/toggle-status', 'toggleStatus')->middleware('permission:account.recurring-invoice.toggle-status')->name('toggle-status');
            });

        // Recurring Bills
        Route::prefix('recurring-bills')
            ->name('recurring-bill.')
            ->middleware('permission:account.recurring-bill.view')
            ->controller(RecurringBillController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->middleware('permission:account.recurring-bill.create')->name('create');
                Route::post('/', 'store')->middleware('permission:account.recurring-bill.create')->name('store');
                Route::get('/{recurringBill}/edit', 'edit')->middleware('permission:account.recurring-bill.edit')->name('edit');
                Route::put('/{recurringBill}', 'update')->middleware('permission:account.recurring-bill.edit')->name('update');
                Route::delete('/{recurringBill}', 'destroy')->middleware('permission:account.recurring-bill.delete')->name('destroy');
                Route::patch('/{recurringBill}/toggle-status', 'toggleStatus')->middleware('permission:account.recurring-bill.toggle-status')->name('toggle-status');
            });

        // Debit Notes (purchase returns to suppliers)
        Route::prefix('debit-notes')
            ->name('debit-note.')
            ->middleware('permission:account.debit-note.view')
            ->controller(DebitNoteController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->middleware('permission:account.debit-note.create')->name('create');
                Route::post('/', 'store')->middleware('permission:account.debit-note.create')->name('store');
                Route::get('/export-columns', 'getExportColumns')->middleware('permission:account.debit-note.export')->name('export-columns');
                Route::get('/{debitNote}', 'show')->middleware('permission:account.debit-note.show')->name('show');
                Route::get('/{debitNote}/edit', 'edit')->middleware('permission:account.debit-note.edit')->name('edit');
                Route::put('/{debitNote}', 'update')->middleware('permission:account.debit-note.edit')->name('update');
                Route::delete('/{debitNote}', 'destroy')->middleware('permission:account.debit-note.delete')->name('destroy');
                Route::post('/{debitNote}/allocate', 'allocate')->middleware('permission:account.debit-note.allocate')->name('allocate');
                Route::post('/{debitNote}/refund', 'receiveRefund')->middleware('permission:account.debit-note.refund')->name('refund');
                Route::get('/vendor/{vendor}/bills', 'getVendorBills')->name('vendor-bills');
                Route::get('/bill/{bill}/items', 'getBillItems')->name('bill-items');
                Route::get('/{id}/export-pdf', 'exportPdf')->middleware('permission:account.debit-note.export')->name('export-pdf');
                Route::get('/{id}/export-excel', 'exportExcel')->middleware('permission:account.debit-note.export')->name('export-excel');
            });

        // Budget Management & Report
        Route::prefix('budget')->name('budget.')->middleware('permission:account.budget.view')->controller(BudgetController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/upsert', 'upsert')->middleware('permission:account.budget.edit')->name('upsert');
            Route::delete('/{budget}', 'destroy')->middleware('permission:account.budget.delete')->name('destroy');
            Route::get('/report', 'report')->middleware('permission:account.budget.report')->name('report');
        });

        // Fixed Asset Register
        Route::prefix('fixed-assets')->name('fixed-asset.')->middleware('permission:account.fixed-asset.view')->controller(FixedAssetController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->middleware('permission:account.fixed-asset.create')->name('create');
            Route::post('/', 'store')->middleware('permission:account.fixed-asset.create')->name('store');
            Route::get('/{fixedAsset}', 'show')->middleware('permission:account.fixed-asset.show')->name('show');
            Route::get('/{fixedAsset}/edit', 'edit')->middleware('permission:account.fixed-asset.edit')->name('edit');
            Route::put('/{fixedAsset}', 'update')->middleware('permission:account.fixed-asset.edit')->name('update');
            Route::delete('/{fixedAsset}', 'destroy')->middleware('permission:account.fixed-asset.delete')->name('destroy');
        });

        // Cash Flow Forecast
        Route::get('/cash-flow-forecast', [CashFlowForecastController::class, 'index'])
            ->middleware('permission:account.cash-flow-forecast.view')
            ->name('cash-flow-forecast');
        Route::get('/cash-flow-forecast/items', [CashFlowForecastController::class, 'items'])
            ->middleware('permission:account.cash-flow-forecast.view')
            ->name('cash-flow-forecast.items');

        // Journal Entries
        Route::prefix('journal-entries')
            ->name('journal-entries.')
            ->group(function () {
                Route::get('/', [JournalEntryController::class, 'index'])->middleware('permission:account.journal-entries.view')->name('index');
                Route::get('/create', [JournalEntryController::class, 'create'])->middleware('permission:account.journal-entries.create')->name('create');
                Route::post('/', [JournalEntryController::class, 'store'])->middleware('permission:account.journal-entries.create')->name('store');
                // Export routes must be before parameterized routes
                Route::get('/export-columns', [JournalEntryController::class, 'getExportColumns'])->middleware('permission:account.journal-entries.view')->name('export-columns');
                Route::get('/export-pdf', [JournalEntryController::class, 'exportPdf'])->middleware('permission:account.journal-entries.view')->name('export-pdf');
                Route::get('/export-excel', [JournalEntryController::class, 'exportExcel'])->middleware('permission:account.journal-entries.view')->name('export-excel');
                Route::get('/{unique_no}/correct', [JournalEntryController::class, 'correct'])->middleware(['permission:account.journal-entries.create', 'permission:account.journal-entries.reverse'])->name('correct');
                Route::get('/{unique_no}/copy-replacement', [JournalEntryController::class, 'copyReplacement'])->middleware(['permission:account.journal-entries.create', 'permission:account.journal-entries.reverse'])->name('copy-replacement');
                Route::get('/{unique_no}', [JournalEntryController::class, 'show'])->middleware('permission:account.journal-entries.show')->name('show');
                Route::get('/{unique_no}/edit', [JournalEntryController::class, 'edit'])->middleware('permission:account.journal-entries.edit')->name('edit');
                Route::put('/{unique_no}', [JournalEntryController::class, 'update'])->middleware('permission:account.journal-entries.edit')->name('update');
                Route::post('/{unique_no}/reverse', [JournalEntryController::class, 'reverse'])->middleware('permission:account.journal-entries.reverse')->name('reverse');
                Route::delete('/{unique_no}', [JournalEntryController::class, 'destroy'])->middleware('permission:account.journal-entries.delete')->name('destroy');
                Route::get('/{id}/logs', [JournalEntryController::class, 'logs'])->middleware('permission:account.journal-entries.show')->name('logs');
            });

        // Liability Payments
        Route::prefix('liabilities')
            ->name('liabilities.')
            ->middleware('permission:account.liability.view')
            ->controller(LiabilityPaymentController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/history', 'history')->name('history');
                Route::get('/history/{id}/attachment', 'downloadAttachment')->whereNumber('id')->name('history.attachment');
                Route::post('/store', 'store')->middleware('permission:account.liability.create')->name('store');
            });

        // Tax Settlement & Periods
        Route::prefix('tax-periods')
            ->name('tax-periods.')
            ->middleware('permission:account.tax-period.view')
            ->group(function () {
                Route::controller(TaxPeriodController::class)->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('/authority-ledger', 'authorityLedger')->name('authority-ledger');
                    Route::get('/authority-ledger/export/{format}', 'exportAuthorityLedger')->middleware('permission:account.tax-period.export')->name('authority-ledger.export');
                    Route::get('/create', 'create')->middleware('permission:account.tax-period.create')->name('create');
                    Route::post('/store', 'store')->middleware('permission:account.tax-period.create')->name('store');
                    Route::get('/show/{id}', 'show')->middleware('permission:account.tax-period.show')->name('show');
                    Route::get('/show/{id}/export/{format}', 'exportPeriod')->middleware('permission:account.tax-period.export')->name('show.export');
                    Route::post('/settle/{id}', 'settle')->middleware('permission:account.tax-period.settle')->name('settle');
                    Route::post('/{id}/submit', 'submit')->middleware('permission:account.tax-period.settle')->name('submit');
                    Route::post('/{id}/review', 'review')->middleware('permission:account.tax-period.settle')->name('review');
                    Route::post('/{id}/approve', 'approve')->middleware('permission:account.tax-period.settle')->name('approve');
                    Route::post('/{id}/reverse', 'reverse')->middleware('permission:account.tax-period.settle')->name('reverse');
                    Route::put('/{id}/filing', 'updateFiling')->middleware('permission:account.tax-period.settle')->name('filing.update');
                    Route::delete('/delete/{id}', 'destroy')->middleware('permission:account.tax-period.delete')->name('destroy');
                });

                Route::controller(TaxPaymentController::class)->group(function () {
                    Route::get('/{id}/payment', 'create')->middleware('permission:account.tax-period.payment')->name('payment.create');
                    Route::post('/{id}/payment', 'store')->middleware('permission:account.tax-period.payment')->name('payment.store');
                });
            });

        // Bank Reconciliation
        Route::prefix('bank-reconciliation')
            ->name('bank-reconciliation.')
            ->middleware('permission:account.bank-reconciliation.view')
            ->controller(\App\Http\Controllers\Account\BankReconciliationController::class)
            ->group(function () {
                Route::get('/template/download', 'downloadTemplate')->middleware('permission:account.bank-reconciliation.upload')->name('template.download');

                Route::prefix('{accountId}')->where(['accountId' => '[0-9]+'])->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('/get-statement-lines', 'getStatementLines')->name('get-statement-lines');
                    Route::get('/get-all-statement-lines', 'getAllStatementLines')->name('get-all-statement-lines');
                    Route::get('/get-account-transactions', 'getAccountTransactions')->name('get-account-transactions');
                    Route::get('/statement/export-excel', 'exportStatementExcel')->middleware('permission:account.bank-reconciliation.export')->name('statement.export-excel');
                    Route::post('/upload', 'store')->middleware('permission:account.bank-reconciliation.upload')->name('upload');
                    Route::post('/import-chunk', 'importChunk')->middleware('permission:account.bank-reconciliation.upload')->name('import-chunk');
                    Route::get('/report', 'reconciliationReport')->middleware('permission:account.bank-reconciliation.report')->name('report');
                    Route::get('/report/export-pdf', 'exportPdf')->middleware('permission:account.bank-reconciliation.report')->name('report.export-pdf');
                    Route::get('/spend-money', 'spendMoney')->middleware('permission:account.bank-reconciliation.spend-money')->name('spend-money');
                    Route::post('/spend-money', 'storeSpendMoney')->middleware('permission:account.bank-reconciliation.spend-money')->name('spend-money.store');
                    Route::get('/receive-money', 'receiveMoney')->middleware('permission:account.bank-reconciliation.receive-money')->name('receive-money');
                    Route::post('/receive-money', 'storeReceiveMoney')->middleware('permission:account.bank-reconciliation.receive-money')->name('receive-money.store');
                    Route::get('/transfer-money', 'transferMoney')->middleware('permission:account.bank-reconciliation.transfer')->name('transfer-money');
                    Route::post('/transfer-money', 'storeTransferMoney')->middleware('permission:account.bank-reconciliation.transfer')->name('transfer-money.store');
                    Route::get('/create-line', 'createLine')->middleware('permission:account.bank-reconciliation.create-line')->name('create-line');
                    Route::post('/store-line', 'storeLine')->middleware('permission:account.bank-reconciliation.create-line')->name('store-line');
                    Route::get('/search-transactions', 'searchTransactions')->name('search-transactions');
                    Route::post('/unreconcile-all', 'unreconcileAll')->middleware('permission:account.bank-reconciliation.unreconcile')->name('unreconcile-all');
                    Route::post('/statements/{statementId}/review', 'reviewStatement')->middleware('permission:account.bank-reconciliation.report')->name('statements.review');
                    Route::post('/statements/{statementId}/finalize', 'finalizeStatement')->middleware('permission:account.bank-reconciliation.report')->name('statements.finalize');
                    Route::post('/statements/{statementId}/reopen', 'reopenStatement')->middleware('permission:account.bank-reconciliation.unreconcile')->name('statements.reopen');
                    Route::get('/statements/{statementId}/exceptions', 'statementExceptions')->middleware('permission:account.bank-reconciliation.report')->name('statements.exceptions');
                });

                Route::post('/match', 'match')->middleware('permission:account.bank-reconciliation.match')->name('match');
                Route::post('/match-invoice', 'matchInvoice')->middleware('permission:account.bank-reconciliation.match')->name('match-invoice');
                Route::post('/create-transaction', 'create')->middleware('permission:account.bank-reconciliation.create-transaction')->name('create-transaction');
                Route::post('/transfer', 'transfer')->middleware('permission:account.bank-reconciliation.transfer')->name('transfer');
                Route::post('/update-note', 'updateNote')->middleware('permission:account.bank-reconciliation.edit')->name('update-note');
                Route::post('/unreconcile', 'unreconcile')->middleware('permission:account.bank-reconciliation.unreconcile')->name('unreconcile');
                Route::post('/bulk-unreconcile', 'bulkUnreconcile')->middleware('permission:account.bank-reconciliation.unreconcile')->name('bulk-unreconcile');
                Route::get('/transaction/{unique_no}', 'showTransaction')->name('show-transaction');
                Route::delete('/line/{id}', 'destroyLine')->middleware('permission:account.bank-reconciliation.delete')->name('line.destroy');
                Route::delete('/txn/{uniqueNo}', 'destroyTxn')->middleware('permission:account.bank-reconciliation.delete')->name('txn.destroy');
                Route::put('/line/{id}', 'updateLine')->middleware('permission:account.bank-reconciliation.edit')->name('line.update');
            });

        // Reports
        Route::prefix('reports')
            ->name('report.')
            ->middleware('permission:account.reports.view')
            ->group(function () {
                Route::get('journal', [JournalController::class, 'journal'])->middleware('permission:account.reports.journal')->name('journal');
                Route::get('journal/export/csv', [JournalController::class, 'exportJournalCsv'])->middleware('permission:account.reports.journal')->name('journal.export.csv');
                Route::get('journal/export/pdf', [JournalController::class, 'exportJournalPdf'])->middleware('permission:account.reports.journal')->name('journal.export.pdf');
                Route::get('journal/export-columns', [JournalController::class, 'getExportColumns'])->middleware('permission:account.reports.journal')->name('journal.export-columns');

                Route::get('ledger', [LedgerController::class, 'ledger'])->middleware('permission:account.reports.ledger')->name('ledger');
                Route::get('ledger/export/pdf', [LedgerController::class, 'exportLedgerPdf'])->middleware('permission:account.reports.ledger')->name('ledger.export.pdf');
                Route::get('ledger/export/csv', [LedgerController::class, 'exportLedgerCsv'])->middleware('permission:account.reports.ledger')->name('ledger.export.csv');
                Route::get('ledger/export-columns', [LedgerController::class, 'getExportColumns'])->middleware('permission:account.reports.ledger')->name('ledger.export-columns');

                Route::get('trial-balance', [TrialBalanceController::class, 'trialBalance'])->middleware('permission:account.reports.trial-balance')->name('trail-balance');
                Route::get('trial-balance/pdf', [TrialBalanceController::class, 'exportToPDF'])->middleware('permission:account.reports.trial-balance')->name('trail-balance.pdf');
                Route::get('trial-balance/csv', [TrialBalanceController::class, 'exportToCSV'])->middleware('permission:account.reports.trial-balance')->name('trail-balance.csv');

                Route::get('balance-sheet', [BalanceSheetController::class, 'balanceSheet'])->middleware('permission:account.reports.balance-sheet')->name('balance-sheet');
                Route::get('balance-sheet/export', [BalanceSheetController::class, 'export'])->middleware('permission:account.reports.balance-sheet')->name('balance-sheet.export');
                Route::get('balance-sheet/pdf', [BalanceSheetController::class, 'exportToPdf'])->middleware('permission:account.reports.balance-sheet')->name('balance-sheet.pdf');
                Route::get('balance-sheet/csv', [BalanceSheetController::class, 'exportToCsv'])->middleware('permission:account.reports.balance-sheet')->name('balance-sheet.csv');

                Route::get('profit-loss', [ProfitLossController::class, 'profitLoss'])->middleware('permission:account.reports.profit-loss')->name('profit-loss');
                Route::get('profit-loss/pdf', [ProfitLossController::class, 'exportProfitLossToPdf'])->middleware('permission:account.reports.profit-loss')->name('profit-loss.pdf');
                Route::get('profit-loss/csv', [ProfitLossController::class, 'exportProfitLossToCsv'])->middleware('permission:account.reports.profit-loss')->name('profit-loss.csv');

                Route::get('cash-flow', [CashFlowController::class, 'cashFlow'])->middleware('permission:account.reports.cash-flow')->name('cash-flow');
                Route::get('/cash-flow/pdf', [CashFlowController::class, 'exportCashFlowToPdf'])->middleware('permission:account.reports.cash-flow')->name('cash-flow.pdf');
                Route::get('/cash-flow/csv', [CashFlowController::class, 'exportCashFlowToCsv'])->middleware('permission:account.reports.cash-flow')->name('cash-flow.csv');

                Route::get('cash-flow-direct', [CashFlowDirectController::class, 'cashFlow'])->middleware('permission:account.reports.cash-flow')->name('cash-flow-direct');
                Route::get('/cash-flow-direct/pdf', [CashFlowDirectController::class, 'exportCashFlowToPdf'])->middleware('permission:account.reports.cash-flow')->name('cash-flow-direct.pdf');
                Route::get('/cash-flow-direct/csv', [CashFlowDirectController::class, 'exportCashFlowToCsv'])->middleware('permission:account.reports.cash-flow')->name('cash-flow-direct.csv');

                Route::get('aged-payable', [ReportController::class, 'agedPayable'])->middleware('permission:account.reports.aged-payable')->name('aged-payable');
                Route::get('aged-payable/pdf', [ReportController::class, 'exportAgedPayableToPdf'])->middleware('permission:account.reports.aged-payable')->name('aged-payable.pdf');
                Route::get('aged-payable/csv', [ReportController::class, 'exportAgedPayableToCsv'])->middleware('permission:account.reports.aged-payable')->name('aged-payable.csv');
                Route::get('aged-receivable', [ReportController::class, 'agedReceivable'])->middleware('permission:account.reports.aged-receivable')->name('aged-receivable');
                Route::get('aged-receivable/pdf', [ReportController::class, 'exportAgedReceivableToPdf'])->middleware('permission:account.reports.aged-receivable')->name('aged-receivable.pdf');
                Route::get('aged-receivable/csv', [ReportController::class, 'exportAgedReceivableToCsv'])->middleware('permission:account.reports.aged-receivable')->name('aged-receivable.csv');

                // Global-standard Aged Reports (trade vendors / customers only — no tax, no payroll)
                Route::get('vendor-aged-payable', [VendorAgedPayableController::class, 'index'])->middleware('permission:account.reports.aged-payable')->name('vendor-aged-payable');
                Route::get('vendor-aged-payable/pdf', [VendorAgedPayableController::class, 'exportPdf'])->middleware('permission:account.reports.aged-payable')->name('vendor-aged-payable.pdf');
                Route::get('vendor-aged-payable/csv', [VendorAgedPayableController::class, 'exportCsv'])->middleware('permission:account.reports.aged-payable')->name('vendor-aged-payable.csv');

                Route::get('customer-aged-receivable', [CustomerAgedReceivableController::class, 'index'])->middleware('permission:account.reports.aged-receivable')->name('customer-aged-receivable');
                Route::get('customer-aged-receivable/pdf', [CustomerAgedReceivableController::class, 'exportPdf'])->middleware('permission:account.reports.aged-receivable')->name('customer-aged-receivable.pdf');
                Route::get('customer-aged-receivable/csv', [CustomerAgedReceivableController::class, 'exportCsv'])->middleware('permission:account.reports.aged-receivable')->name('customer-aged-receivable.csv');

                Route::get('audit-trail', [ReportAuditTrailController::class, 'auditTrail'])->middleware('permission:account.reports.audit-trail')->name('audit-trail');
            });

        Route::get('transaction-to-invoice/{unique_no}', TransactionToInvoice::class)->middleware('permission:account.transaction-to-invoice.view')->name('transaction-to-invoice');

        Route::get('/paginate', action: [TransactionToInvoice::class, 'paginate'])->middleware('permission:account.transaction-to-invoice.view')->name('invoice.paginate');

        // ── Payment Reminders ─────────────────────────────────────────────────
        Route::prefix('payment-reminders')->name('payment-reminders.')->middleware('permission:account.payment-reminder.view')->controller(\App\Http\Controllers\Account\PaymentReminderController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/{sale}/send', 'sendOne')->middleware('permission:account.payment-reminder.send')->name('send-one');
            Route::post('/send-bucket', 'sendBucket')->middleware('permission:account.payment-reminder.send')->name('send-bucket');
        });

        // ── Partial Payment Allocation ─────────────────────────────────────────
        Route::prefix('partial-payment')->name('partial-payment.')->middleware('permission:account.partial-payment.view')->controller(\App\Http\Controllers\Account\PartialPaymentController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->middleware('permission:account.partial-payment.create')->name('create');
            Route::post('/', 'store')->middleware('permission:account.partial-payment.create')->name('store');
            Route::get('/{customerPayment}', 'show')->middleware('permission:account.partial-payment.show')->name('show');
            Route::post('/{id}/confirm', 'confirm')->middleware('permission:account.partial-payment.confirm')->name('confirm');
            Route::get('/{id}/receipt', 'downloadReceipt')->middleware('permission:account.partial-payment.export')->name('receipt');
        });

        // ── Invoice Allocation (bulk, cross-customer/vendor) ──────────────────
        Route::prefix('invoice-allocation')->name('invoice-allocation.')->middleware('permission:account.invoice-allocation.view')->controller(\App\Http\Controllers\Account\InvoiceAllocationController::class)->group(function () {
            Route::get('/sales', 'sales')->name('sales');
            Route::post('/sales', 'storeSales')->middleware('permission:account.invoice-allocation.create')->name('sales.store');
            Route::get('/bills', 'bills')->name('bills');
            Route::post('/bills', 'storeBills')->middleware('permission:account.invoice-allocation.create')->name('bills.store');
        });

        // ── Invoice Approval Workflow ──────────────────────────────────────────
        Route::prefix('approvals')->name('approvals.')->middleware('permission:account.approval.view')->controller(\App\Http\Controllers\Account\InvoiceApprovalController::class)->group(function () {
            Route::get('/sales', 'salesIndex')->name('sales.index');
            Route::post('/sales/{sale}/submit', 'submitSale')->middleware('permission:account.approval.submit')->name('sales.submit');
            Route::post('/sales/{sale}/approve', 'approveSale')->middleware('permission:account.approval.approve')->name('sales.approve');
            Route::post('/sales/{sale}/reject', 'rejectSale')->middleware('permission:account.approval.reject')->name('sales.reject');
            Route::post('/sales/bulk-submit', 'bulkSubmitSales')->middleware('permission:account.approval.submit')->name('sales.bulk-submit');
            Route::post('/sales/bulk-approve', 'bulkApproveSales')->middleware('permission:account.approval.approve')->name('sales.bulk-approve');
            Route::post('/sales/bulk-reject', 'bulkRejectSales')->middleware('permission:account.approval.reject')->name('sales.bulk-reject');

            Route::get('/bills', 'billsIndex')->name('bills.index');
            Route::post('/bills/{bill}/submit', 'submitBill')->middleware('permission:account.approval.submit')->name('bills.submit');
            Route::post('/bills/{bill}/approve', 'approveBill')->middleware('permission:account.approval.approve')->name('bills.approve');
            Route::post('/bills/{bill}/reject', 'rejectBill')->middleware('permission:account.approval.reject')->name('bills.reject');
            Route::post('/bills/bulk-submit', 'bulkSubmitBills')->middleware('permission:account.approval.submit')->name('bills.bulk-submit');
            Route::post('/bills/bulk-approve', 'bulkApproveBills')->middleware('permission:account.approval.approve')->name('bills.bulk-approve');
            Route::post('/bills/bulk-reject', 'bulkRejectBills')->middleware('permission:account.approval.reject')->name('bills.bulk-reject');
        });

        // ── Customer Statements ────────────────────────────────────────────────
        Route::prefix('customer-statements')->name('customer-statements.')->middleware('permission:account.customer-statement.view')->controller(\App\Http\Controllers\Account\CustomerStatementController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/bulk-pdf', 'bulkPdf')->middleware('permission:account.customer-statement.export')->name('bulk-pdf');
            Route::get('/separate-pdfs', 'bulkSeparatePdfs')->middleware('permission:account.customer-statement.export')->name('separate-pdfs');
            Route::post('/selected-email', 'bulkSendSelectedEmail')->middleware('permission:account.customer-statement.email')->name('selected-email');
            Route::get('/{customer}', 'show')->name('show');
            Route::get('/{customer}/pdf', 'exportPdf')->middleware('permission:account.customer-statement.export')->name('pdf');
            Route::post('/{customer}/email', 'sendEmail')->middleware('permission:account.customer-statement.email')->name('email');
            Route::post('/bulk-email', 'bulkSendEmail')->middleware('permission:account.customer-statement.email')->name('bulk-email');
        });

        // ── Vendor Statements ──────────────────────────────────────────────────
        Route::prefix('vendor-statements')->name('vendor-statements.')->middleware('permission:account.vendor-statement.view')->controller(\App\Http\Controllers\Account\VendorStatementController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/bulk-pdf', 'bulkPdf')->middleware('permission:account.vendor-statement.export')->name('bulk-pdf');
            Route::get('/separate-pdfs', 'bulkSeparatePdfs')->middleware('permission:account.vendor-statement.export')->name('separate-pdfs');
            Route::post('/selected-email', 'bulkSendSelectedEmail')->middleware('permission:account.vendor-statement.email')->name('selected-email');
            Route::get('/{vendor}', 'show')->name('show');
            Route::get('/{vendor}/pdf', 'exportPdf')->middleware('permission:account.vendor-statement.export')->name('pdf');
            Route::post('/{vendor}/email', 'sendEmail')->middleware('permission:account.vendor-statement.email')->name('email');
            Route::post('/bulk-email', 'bulkSendEmail')->middleware('permission:account.vendor-statement.email')->name('bulk-email');
        });

        // ── Vendor Payment Runs ────────────────────────────────────────────────
        Route::prefix('vendor-payment-runs')->name('vendor-payment-run.')->middleware('permission:account.vendor-payment-run.view')->controller(\App\Http\Controllers\Account\VendorPaymentRunController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->middleware('permission:account.vendor-payment-run.create')->name('create');
            Route::post('/', 'store')->middleware('permission:account.vendor-payment-run.create')->name('store');
            Route::get('/{vendorPaymentRun}', 'show')->middleware('permission:account.vendor-payment-run.show')->name('show');
            Route::post('/{vendorPaymentRun}/confirm', 'confirm')->middleware('permission:account.vendor-payment-run.confirm')->name('confirm');
            Route::delete('/{vendorPaymentRun}', 'destroy')->middleware('permission:account.vendor-payment-run.delete')->name('destroy');
            Route::get('/{vendorPaymentRun}/remittance-pdf', 'remittancePdf')->middleware('permission:account.vendor-payment-run.export')->name('remittance-pdf');
        });
    });
