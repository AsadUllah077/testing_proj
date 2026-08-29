<?php

use App\Http\Controllers\Account\BillController;
use App\Http\Controllers\Account\CreditNoteController;
use App\Http\Controllers\Account\DashboardController;
use App\Http\Controllers\Account\InvoiceTemplateController;
use App\Http\Controllers\Account\JournalEntry\JournalEntryController;
use App\Http\Controllers\Account\OrganizationSetting\AccountingPeriodController;
use App\Http\Controllers\Account\OrganizationSetting\ChartOfAccountController;
use App\Http\Controllers\Account\PurchaseOrderController;
use App\Http\Controllers\Account\PurchaseOrderQuotationController;
use App\Http\Controllers\Account\Report\BalanceSheetController;
use App\Http\Controllers\Account\Report\CashFlowController;
use App\Http\Controllers\Account\Report\JournalController;
use App\Http\Controllers\Account\Report\LedgerController;
use App\Http\Controllers\Account\Report\ProfitLossController;
use App\Http\Controllers\Account\Report\ReportController;
use App\Http\Controllers\Account\Report\TrialBalanceController;
use App\Http\Controllers\Account\Bill\BillTransactionController;
use App\Http\Controllers\Account\Sale\SaleTransactionController;
use App\Http\Controllers\Account\SaleController;
use Illuminate\Support\Facades\Route;

Route::prefix('account')
    ->name('account.')
    ->controller(DashboardController::class)
    ->group(function () {
        // 👉 List all companies
        Route::get('/dashboard', 'index')->name('dashboard');
    });

Route::prefix('account')
    ->name('account.')
    ->controller(SaleController::class)
    ->group(function () {
        // 👉 List all companies
        Route::get('/sale', 'index')->name('sale-invoice.index');
        Route::get('/sale/create', 'create')->name('sale-invoice.create');
        Route::post('/sale/create', 'store')->name('sale-invoice.store');
        Route::put('/sale/update/{id}', 'update')->name('sale-invoice.update');
        Route::view('account/sale/report', 'pages.account.sale-transaction.index')->name('sale-transaction.index');

        Route::view('/sale/report', 'pages.account.sale.report.index')->name('sale.report.index');
        Route::view('/sale/credit-note', 'pages.account.sale.credit-note.index')->name('sale.credit-note.index');
        // Route::view('/sale/credit-note/create', 'pages.account.sale.credit-note.create')->name('sale.credit-note.create');
        Route::delete('/sale-invoice/delete/{id}', 'destroy')->name('sale-invoice.destroy');
        Route::get('/sale-invoice/edit/{id}', 'edit')->name('sale-invoice.edit');
        Route::get('/sale-invoice/show/{id}', 'show')->name('sale-invoice.show');
        // Route::view('/sale/customer', 'pages.account.sale.customer.index')->name('sale.customer.index');
        // Export routes
        Route::get('/sale-invoice/export/{format}', 'export')->name('sale-invoice.export');
        Route::get('/sale-invoice/{id}/export-pdf', 'exportPdf')->name('sale-invoice.export-pdf');
        Route::get('/sale-invoice/import', 'import')->name('sale-invoice.import');
        Route::post('/sale-invoice/import', 'import')->name('sale-invoice.import.post');
        Route::get('/sale-invoice/download-template', 'downloadTemplate')->name('sale-invoice.download-template');
        Route::get('/sale-invoice/{unique_no}/logs', 'logs')->name('sale-invoice.logs');
        // Add this to your web.php routes
        // Route::get('/sale-invoice/audit-log-details/{logId}',  'getLogDetails')->name('sale-invoice.audit-logs');
        Route::post('sale-invoice/record-payment', 'recordPayment')->name('sale-invoice.record-payment');
        Route::put('sale-invoice/transaction/{id}', 'updateTransaction')->name('sale-invoice.transaction.update');
    });
// Audit Logs Routes
Route::get('/sale-invoice/{id}/audit-logs', [SaleController::class, 'auditLogs'])->name('account.sale-invoice.audit-logs');
Route::get('/sale-invoice/audit-log-details/{logId}', [SaleController::class, 'getLogDetails'])->name('account.sale-invoice.audit-log-details');

Route::prefix('account/sale')
    ->as('account.')
    ->group(function () {
        Route::controller(SaleTransactionController::class)->group(function () {
            Route::get('/sale-transactions', 'index')->name('sale.sale-transaction.index');
        });
    });

Route::prefix('account/bill')
    ->as('account.')
    ->group(function () {
        Route::controller(BillTransactionController::class)->group(function () {
            Route::get('/bill-transactions', 'index')->name('bill.bill-transaction.index');
        });
    });

Route::prefix('account')
    ->as('account.')
    ->group(function () {
        // Route::view('purchase-order', 'pages.account.purchase-order.index')->name('purchase-order.index');
        // Route::view('purchase-order/list', 'pages.account.purchase-order.order.list')->name('purchase-order.list');
        // Route::view('purchase-order/good-recieve', 'pages.account.purchase-order.good-recieve')->name('purchase-order.good-recieve');
        // Route::view('purchase-order/create', 'pages.account.purchase-order.create')->name('purchase-order.create');
        // Route::view('purchase-order/reports', 'pages.account.purchase-order.reports')->name('purchase-order.reports');
    });
// Purchase Order Routes
Route::prefix('purchase-orders')->name('account.purchase-order.')->group(function () {
    Route::get('/list', [PurchaseOrderController::class, 'list'])->name('order.list');
    Route::get('/', [PurchaseOrderController::class, 'index'])->name('index');
    Route::get('/create', [PurchaseOrderController::class, 'create'])->name('create');
    Route::post('/store', [PurchaseOrderController::class, 'store'])->name('store');
    Route::get('/{id}', [PurchaseOrderController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [PurchaseOrderController::class, 'edit'])->name('edit');
    Route::put('/{id}/update', [PurchaseOrderController::class, 'update'])->name('update');
    Route::delete('/{id}/destroy', [PurchaseOrderController::class, 'destroy'])->name('destroy');
    Route::get('/{id}/audit-logs', [PurchaseOrderController::class, 'auditLogs'])->name('audit-logs');
    Route::get('/audit-log/{logId}/details', [PurchaseOrderController::class, 'getLogDetails'])->name('log-details');
    // Export routes
    Route::get('/purchase-orders/export/{format}', [PurchaseOrderController::class, 'export'])->name('export');
    Route::get('/purchase-orders/{id}/export-pdf', [PurchaseOrderController::class, 'exportPdf'])->name('export-pdf');
});

Route::prefix('purchase-orders-quotation')->name('account.purchase-order-quotation.')->group(function () {
    Route::get('/list', [PurchaseOrderQuotationController::class, 'list'])->name('list');
    Route::get('/', [PurchaseOrderQuotationController::class, 'index'])->name('index');
    Route::get('/reports', [PurchaseOrderQuotationController::class, 'report'])->name('reports');
    Route::get('/create', [PurchaseOrderQuotationController::class, 'create'])->name('create');
    Route::get('/good-recieve', [PurchaseOrderQuotationController::class, 'good_receive'])->name('good-recieve');
    Route::post('/store', [PurchaseOrderQuotationController::class, 'store'])->name('store');
    Route::get('/{id}', [PurchaseOrderQuotationController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [PurchaseOrderQuotationController::class, 'edit'])->name('edit');
    Route::get('/{id}/gd_edit/pur', [PurchaseOrderQuotationController::class, 'gd_edit'])->name('gd_edit');
    Route::put('/{id}/gd_edit/pur', [PurchaseOrderQuotationController::class, 'gd_update'])->name('gd_update');
    Route::put('/{id}/update', [PurchaseOrderQuotationController::class, 'update'])->name('update');
    Route::delete('/{id}/destroy', [PurchaseOrderQuotationController::class, 'destroy'])->name('destroy');
    Route::get('/{id}/audit-logs', [PurchaseOrderQuotationController::class, 'auditLogs'])->name('audit-logs');
    Route::get('/audit-log/{logId}/details', [PurchaseOrderQuotationController::class, 'getLogDetails'])->name('log-details');
    Route::put('/purchase-order-quotation/gd_update/{id}', [PurchaseOrderQuotationController::class, 'gd_update'])->name('account.purchase-order-quotation.gd_update');
    // Export routes
    Route::get('/purchase-orders-quotation/export/{format}', [PurchaseOrderQuotationController::class, 'export'])->name('export');
    Route::get('/purchase-orders-quotation/{id}/export-pdf', [PurchaseOrderQuotationController::class, 'exportPdf'])->name('export-pdf');
    Route::post('/purchase-order-invoice/record-payment', [PurchaseOrderQuotationController::class, 'recordPayment'])->name('record-payment');
    Route::put('/payment/{payment}', [PurchaseOrderQuotationController::class, 'updatePayment'])
        ->name('payment.update');
});
// Route::prefix('account')
//     ->as('account.')
//     ->group(function () {
//         Route::get('/invoice-setting/create', [InvoiceTemplateController::class, 'edit'])->name('invoice-setting.create');
//         Route::put('/invoice-template/{id}', [InvoiceTemplateController::class, 'update'])->name('invoice-setting.update');
//         Route::get('/invoice-template/index', [InvoiceTemplateController::class, 'index'])->name('invoice-setting.index');
//     });

// Route::prefix('account')
//     ->as('account.')
//     ->group(function () {
//         Route::view('bill', 'pages.account.bill.index')->name('bill-invoice.index');
//         Route::view('bill/create', 'pages.account.bill.create')->name('bill-invoice.create');
//         Route::view('bill/report', 'pages.account.bill.report.index')->name('bill-report.index');
//         Route::get('vendor', [VendorController::class, 'index'])->name('vendor.index');
//     });
// Bill Routes
// Credit Note Routes
Route::prefix('credit-notes')->group(function () {
    Route::get('/create', [CreditNoteController::class, 'create'])->name('account.sale.credit-notes.create');
    Route::get('/', [CreditNoteController::class, 'index'])->name('account.sale.credit-notes.index');
    Route::post('/store', [CreditNoteController::class, 'store'])->name('account.sale.credit-notes.store');
    Route::get('/get-customer-sales/{customerId}', [CreditNoteController::class, 'getCustomerSales'])->name('account.sale.credit-notes.get-customer-sales');
    Route::get('/get-sale-details/{saleId}', [CreditNoteController::class, 'getSaleDetails'])->name('account.sale.credit-notes.get-sale-details');
    Route::get('/show/{Id}', [CreditNoteController::class, 'show'])->name('account.sale.credit-notes.show');
    Route::get('/{Id}/audit-logs', [CreditNoteController::class, 'auditLogs'])->name('account.sale.credit-notes.audit-logs');
    Route::get('/log-details/{logId}', [CreditNoteController::class, 'getLogDetails'])->name('account.sale.credit-notes.audit-logs');
    Route::get('/export/{Id}', [CreditNoteController::class, 'getSaleDetails'])->name('account.sale.credit-notes.export-pdf');
    Route::get('/delete/{Id}', [CreditNoteController::class, 'getSaleDetails'])->name('account.sale.credit-notes.destroy');
    Route::get('/get-accounts', [CreditNoteController::class, 'getAccounts'])->name('account.sale.credit-notes.get-accounts');
    Route::get('/get-taxes', [CreditNoteController::class, 'getTaxes'])->name('account.sale.credit-notes.get-taxes');
    Route::get('/get-currencies', [CreditNoteController::class, 'getCurrencies'])->name('account.sale.credit-notes.get-currencies');
    Route::post('/credit-invoice/record-payment', [CreditNoteController::class, 'recordPayment'])->name('account.credit-note.record-payment');
    Route::put('/transaction/{id}', [CreditNoteController::class, 'updateTransaction'])->name('account.credit-note.transaction.update');
});

Route::prefix('account')->group(function () {
    Route::get('bill-invoice', [BillController::class, 'index'])->name('account.bill-invoice.index');
    Route::get('bill-invoice/report', [BillController::class, 'index'])->name('account.bill-report.index');
    Route::get('bill-invoice/create', [BillController::class, 'create'])->name('account.bill-invoice.create');
    Route::post('bill-invoice/store', [BillController::class, 'store'])->name('account.bill-invoice.store');
    Route::get('bill-invoice/{id}', [BillController::class, 'show'])->name('account.bill-invoice.show');
    Route::get('bill-invoice/{id}/edit', [BillController::class, 'edit'])->name('account.bill-invoice.edit');
    Route::put('bill-invoice/{id}/update', [BillController::class, 'update'])->name('account.bill-invoice.update');
    Route::delete('bill-invoice/{id}/delete', [BillController::class, 'destroy'])->name('account.bill-invoice.destroy');
    Route::get('bill-invoice/{id}/audit-logs', [BillController::class, 'auditLogs'])->name('account.bill-invoice.audit-logs');
    Route::get('/bill-invoice/log-details/{logId}', [BillController::class, 'getLogDetails'])->name('account.bill-invoice.log-details');
    // Export routes
    Route::get('bill-invoice/export/{format}', [BillController::class, 'export'])->name('account.bill-invoice.export');
    Route::get('bill-invoice/{id}/export-pdf', [BillController::class, 'exportPdf'])->name('account.bill-invoice.export-pdf');
    Route::get('bill-invoice/import', [BillController::class, 'import'])->name('account.bill-invoice.import');
    Route::post('bill-invoice/import', [BillController::class, 'import'])->name('account.bill-invoice.import.post');
    Route::get('download-template', [BillController::class, 'downloadTemplate'])->name('account.bill-invoice.download.template');
    Route::post('bill-invoice/record-payment', [BillController::class, 'recordPayment'])->name('account.bill-invoice.record-payment');
    Route::put('bill-invoice/transaction/{id}', [BillController::class, 'updateTransaction'])->name('account.bill-invoice.transaction.update');
});

Route::prefix('account/organization-settings')
    ->as('account.organization-settings.')
    ->group(function () {
        Route::get('/invoice-setting/create', [InvoiceTemplateController::class, 'edit'])->name('invoice-setting.create');
        Route::put('/invoice-template/{id}', [InvoiceTemplateController::class, 'update'])->name('invoice-setting.update');
        Route::get('/invoice-template/index', [InvoiceTemplateController::class, 'index'])->name('invoice-setting.index');
        Route::view('', 'pages.account.organization-settings.index')->name('index');
        Route::view('/currency', 'pages.account.organization-settings.currency.index')->name('currency.index');
        Route::view('/invoice-setting', 'pages.account.organization-settings.invoice-setting.index')->name('invoice-setting.index');
        // Route::view('/invoice-setting/create', 'pages.account.organization-settings.invoice-setting.create')->name('invoice-setting.create');

        Route::controller(AccountingPeriodController::class)->prefix('accounting-period')->as('accounting-period.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/store', 'store')->name('store');
            Route::get('/edit/{accounting_period}', 'edit')->name('edit');
            Route::put('/update/{id}', 'update')->name('update');
            Route::delete('/delete/{id}', 'destroy')->name('destroy');
            Route::get('/detail/{id}', 'show')->name('detail');
            Route::put('/status/{id}', 'updateStatus')->name('updateStatus');
            Route::get('/{id}/logs', 'logs')->name('logs');
            Route::get('/all-logs', 'allLogs')->name('all.logs');
        });
        Route::controller(ChartOfAccountController::class)->prefix('account')->as('account.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/store', 'store')->name('store');
            Route::get('/edit/{id}', 'edit')->name('edit');
            Route::put('/update/{id}', 'update')->name('update');
            Route::delete('/delete/{id}', 'destroy')->name('destroy');
            Route::get('/detail/{id}', 'show')->name('detail');
            Route::get('/{id}/logs', 'logs')->name('logs');
            Route::get('/all-logs', 'allLogs')->name('all.logs');
        });

        Route::view('currency', 'pages.account.organization-settings.currency.index')->name('currency.index');
        // Route::view('tax-rate', 'pages.account.organization-settings.tax-rate.index')->name('tax_rate.index');

        // Route::view('invoice-setting', 'pages.account.organization-settings.invoice-setting.index')->name('invoice-setting.index');
    });
Route::prefix('account')
    ->name('account.')
    ->group(function () {
        Route::get('kpi', [DashboardController::class, 'kpiData'])->name('kpi');
    });

Route::prefix('account')
    ->name('account.')
    ->group(function () {
        Route::get('/journal-entries', [JournalEntryController::class, 'index'])->name('journal-entries.index');
        Route::get('/journal-entries/create', [JournalEntryController::class, 'create'])->name('journal-entries.create');
        Route::post('/journal-entries', [JournalEntryController::class, 'store'])->name('journal-entries.store');
        Route::get('/journal-entries/{unique_no}', [JournalEntryController::class, 'show'])->name('journal-entries.show');
        Route::get('/journal-entries/{unique_no}/edit', [JournalEntryController::class, 'edit'])->name('journal-entries.edit');
        Route::put('/journal-entries/{unique_no}', [JournalEntryController::class, 'update'])->name('journal-entries.update');
        Route::delete('/journal-entries/{unique_no}', [JournalEntryController::class, 'destroy'])->name('journal-entries.destroy');
        Route::get(
            '/{id}/logs',
            [JournalEntryController::class, 'logs']
        )->name('journal-entries.logs');
    });

Route::prefix('account/bank-reconciliation')
    ->name('account.bank-reconciliation.')
    ->controller(\App\Http\Controllers\Account\BankReconciliationController::class)
    ->group(function () {
        Route::get('/template/download', 'downloadTemplate')->name('template.download');
        Route::get('/{accountId}', 'index')->name('index');
        Route::get('/{accountId}/get-statement-lines', 'getStatementLines')->name('get-statement-lines');
        Route::get('/{accountId}/get-account-transactions', 'getAccountTransactions')->name('get-account-transactions');
        Route::post('/{accountId}/upload', 'store')->name('upload');
        Route::post('/match', 'match')->name('match');
        Route::post('/create-transaction', 'create')->name('create-transaction');
        Route::post('/transfer', 'transfer')->name('transfer');
        Route::post('/update-note', 'updateNote')->name('update-note');

        Route::get('/{accountId}/report', 'reconciliationReport')->name('report');
        Route::get('/{accountId}/spend-money', 'spendMoney')->name('spend-money');
        Route::post('/{accountId}/spend-money', 'storeSpendMoney')->name('spend-money.store');

        Route::get('/{accountId}/receive-money', 'receiveMoney')->name('receive-money');
        Route::post('/{accountId}/receive-money', 'storeReceiveMoney')->name('receive-money.store');

        Route::get('/{accountId}/transfer-money', 'transferMoney')->name('transfer-money');
        Route::post('/{accountId}/transfer-money', 'storeTransferMoney')->name('transfer-money.store');

        Route::get('/{accountId}/create-line', 'createLine')->name('create-line');
        Route::post('/{accountId}/store-line', 'storeLine')->name('store-line');
        Route::get('/{accountId}/search-transactions', 'searchTransactions')->name('search-transactions');
        Route::post('/unreconcile', 'unreconcile')->name('unreconcile');
        Route::post('/bulk-unreconcile', 'bulkUnreconcile')->name('bulk-unreconcile');
        Route::post('/{accountId}/unreconcile-all', 'unreconcileAll')->name('unreconcile-all');
        Route::delete('/line/{id}', 'destroyLine')->name('line.destroy');
        Route::put('/line/{id}', 'updateLine')->name('line.update');
    });

Route::prefix('account/reports')
    ->name('account.report.')
    ->group(function () {
        Route::get('journal', [JournalController::class, 'journal'])->name('journal');
        Route::get('journal/export/csv', [JournalController::class, 'exportJournalCsv'])->name('journal.export.csv');
        Route::get('journal/export/pdf', [JournalController::class, 'exportJournalPdf'])->name('journal.export.pdf');

        Route::get('ledger', [LedgerController::class, 'ledger'])->name('ledger');
        Route::get('ledger/export/pdf', [LedgerController::class, 'exportLedgerPdf'])->name('ledger.export.pdf');
        Route::get('ledger/export/csv', [LedgerController::class, 'exportLedgerCsv'])->name('ledger.export.csv');

        Route::get('trial-balance', [TrialBalanceController::class, 'trialBalance'])->name('trail-balance');
        Route::get('trial-balance/pdf', [TrialBalanceController::class, 'exportToPDF'])->name('trail-balance.pdf');
        Route::get('trial-balance/csv', [TrialBalanceController::class, 'exportToCSV'])->name('trail-balance.csv');

        Route::get('balance-sheet', [BalanceSheetController::class, 'balanceSheet'])->name('balance-sheet');
        Route::get('balance-sheet/export', [BalanceSheetController::class, 'export'])->name('balance-sheet.export');
        Route::get('balance-sheet/pdf', [BalanceSheetController::class, 'exportToPdf'])->name('balance-sheet.pdf');
        Route::get('balance-sheet/csv', [BalanceSheetController::class, 'exportToCsv'])->name('balance-sheet.csv');

        Route::get('profit-loss', [ProfitLossController::class, 'profitLoss'])->name('profit-loss');
        Route::get('profit-loss/pdf', [ProfitLossController::class, 'exportProfitLossToPdf'])->name('profit-loss.pdf');
        Route::get('profit-loss/csv', [ProfitLossController::class, 'exportProfitLossToCsv'])->name('profit-loss.csv');

        Route::get('cash-flow', [CashFlowController::class, 'cashFlow'])->name('cash-flow');
        Route::get('/cash-flow/pdf', [CashFlowController::class, 'exportCashFlowToPdf'])->name('cash-flow.pdf');
        Route::get('/cash-flow/csv', [CashFlowController::class, 'exportCashFlowToCsv'])->name('cash-flow.csv');

        Route::get('aged-payable', [ReportController::class, 'agedPayable'])->name('aged-payable');
        Route::get('aged-payable/pdf', [ReportController::class, 'exportAgedPayableToPdf'])->name('aged-payable.pdf');
        Route::get('aged-payable/csv', [ReportController::class, 'exportAgedPayableToCsv'])->name('aged-payable.csv');
        Route::get('aged-receivable', [ReportController::class, 'agedReceivable'])->name('aged-receivable');
        Route::get('aged-receivable/pdf', [ReportController::class, 'exportAgedReceivableToPdf'])->name('aged-receivable.pdf');
        Route::get('aged-receivable/csv', [ReportController::class, 'exportAgedReceivableToCsv'])->name('aged-receivable.csv');

        Route::view('audit-trail', 'pages.account.reports.audit-trail')->name('audit-trail');
    });
