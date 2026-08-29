<?php

use App\Http\Controllers\Payroll\EmployeePayController;
use App\Http\Controllers\Payroll\PayItemController;
use App\Http\Controllers\Payroll\PayrollControlController;
use App\Http\Controllers\Payroll\PayrollDashboardController;
use App\Http\Controllers\Payroll\PayrollLiabilityController;
use App\Http\Controllers\Payroll\PayrollOperationsController;
use App\Http\Controllers\Payroll\PayrollReportController;
use App\Http\Controllers\Payroll\PayrollUserAccessController;
use App\Http\Controllers\Payroll\PayRunController;
use App\Http\Controllers\Payroll\PayslipController;
use App\Http\Controllers\Payroll\PayslipDownloadController;
use App\Http\Controllers\Payroll\SalaryAdvanceController;
use App\Http\Controllers\Payroll\TaxBracketController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'permission:payroll.organization-settings.users.view'])
    ->prefix('payroll/organization-settings/users')
    ->name('payroll.organization-settings.users.')
    ->group(function () {
        Route::get('/', [PayrollUserAccessController::class, 'index'])->name('index');
        Route::get('/create', [PayrollUserAccessController::class, 'create'])->middleware('permission:payroll.organization-settings.users.create')->name('create');
        Route::post('/', [PayrollUserAccessController::class, 'store'])->middleware('permission:payroll.organization-settings.users.create')->name('store');
        Route::get('/export/pdf', [PayrollUserAccessController::class, 'export'])->middleware('permission:payroll.organization-settings.users.export')->name('export');
        Route::get('/{user}/edit', [PayrollUserAccessController::class, 'edit'])->middleware('permission:payroll.organization-settings.users.edit')->name('edit');
        Route::put('/{user}', [PayrollUserAccessController::class, 'update'])->middleware('permission:payroll.organization-settings.users.edit')->name('update');
        Route::delete('/{user}', [PayrollUserAccessController::class, 'destroy'])->middleware('permission:payroll.organization-settings.users.delete')->name('destroy');
    });

Route::middleware('auth')
    ->prefix('payroll/organization-settings/roles')
    ->name('payroll.organization-settings.roles.')
    ->group(function () {
        Route::get('/create', [PayrollUserAccessController::class, 'createRole'])->middleware('permission:payroll.organization-settings.roles.create')->name('create');
        Route::post('/', [PayrollUserAccessController::class, 'storeRole'])->middleware('permission:payroll.organization-settings.roles.create')->name('store');
        Route::get('/{role}/edit', [PayrollUserAccessController::class, 'editRole'])->middleware('permission:payroll.organization-settings.roles.edit')->name('edit');
        Route::put('/{role}', [PayrollUserAccessController::class, 'updateRole'])->middleware('permission:payroll.organization-settings.roles.edit')->name('update');
    });

Route::middleware(['auth', 'permission:payroll.dashboard.view'])
    ->prefix('payroll')
    ->name('payroll.')
    ->group(function () {

        // Dashboard
        Route::get('dashboard', [PayrollDashboardController::class, 'index'])->name('dashboard');

        Route::prefix('control-center')->name('control-center.')->middleware('permission:payroll.control-center.view')->group(function () {
            Route::get('/', [PayrollControlController::class, 'index'])->name('index');
            Route::post('/pay-runs/{payRun}/run', [PayrollControlController::class, 'run'])->middleware('permission:payroll.pay-run.edit')->name('run');
            Route::post('/pay-runs/{payRun}/override', [PayrollControlController::class, 'overrideWarning'])->middleware('permission:payroll.pay-run.approve')->name('override');
            Route::post('/pay-runs/{payRun}/override-all', [PayrollControlController::class, 'overrideWarnings'])->middleware('permission:payroll.pay-run.approve')->name('override-all');
        });

        Route::prefix('operations')->name('operations.')->middleware('permission:payroll.operations.view')->group(function () {
            Route::get('/', [PayrollOperationsController::class, 'index'])->name('index');
            Route::post('/settlements', [PayrollOperationsController::class, 'storeSettlement'])->middleware('permission:payroll.pay-run.create')->name('settlements.store');
            Route::post('/settlements/{settlement}/approve', [PayrollOperationsController::class, 'approveSettlement'])->middleware('permission:payroll.pay-run.approve')->name('settlements.approve');
            Route::post('/settlements/{settlement}/post', [PayrollOperationsController::class, 'postSettlement'])->middleware('permission:payroll.pay-run.edit')->name('settlements.post');
            Route::post('/allocations', [PayrollOperationsController::class, 'storeAllocations'])->middleware('permission:payroll.employee-pay.edit')->name('allocations.store');
            Route::post('/scenarios', [PayrollOperationsController::class, 'storeScenario'])->middleware('permission:payroll.operations.scenario.create')->name('scenarios.store');
            Route::post('/reviews', [PayrollOperationsController::class, 'storeReview'])->middleware('permission:payroll.employee-pay.edit')->name('reviews.store');
            Route::post('/reviews/{review}/approve', [PayrollOperationsController::class, 'approveReview'])->middleware('permission:payroll.pay-run.approve')->name('reviews.approve');
            Route::post('/reviews/{review}/apply', [PayrollOperationsController::class, 'applyReview'])->middleware('permission:payroll.employee-pay.edit')->name('reviews.apply');
            Route::get('/reviews/{review}/increment-letter', [PayrollOperationsController::class, 'incrementLetter'])->middleware('permission:payroll.operations.increment-letter.export')->name('reviews.increment-letter');
            Route::post('/variance/{payRun}/comment', [PayrollOperationsController::class, 'storeVarianceComment'])->middleware('permission:payroll.pay-run.edit')->name('variance.comment');
            Route::post('/bank-requests/{bankRequest}/review', [PayrollOperationsController::class, 'reviewBankRequest'])->middleware('permission:payroll.employee-pay.edit')->name('bank-requests.review');
            Route::get('/audit-pack/{payRun}', [PayrollOperationsController::class, 'auditPack'])->middleware('permission:payroll.pay-run.export')->name('audit-pack');
        });

        // Pay Items
        Route::prefix('pay-items')->name('pay-items.')->middleware('permission:payroll.pay-item.view')->group(function () {
            Route::get('/', [PayItemController::class, 'index'])->name('index');
            Route::get('/create', [PayItemController::class, 'create'])->middleware('permission:payroll.pay-item.create')->name('create');
            Route::post('/', [PayItemController::class, 'store'])->middleware('permission:payroll.pay-item.create')->name('store');
            Route::get('/{pay_item}/edit', [PayItemController::class, 'edit'])->middleware('permission:payroll.pay-item.edit')->name('edit');
            Route::put('/{pay_item}', [PayItemController::class, 'update'])->middleware('permission:payroll.pay-item.edit')->name('update');
            Route::delete('/{pay_item}', [PayItemController::class, 'destroy'])->middleware('permission:payroll.pay-item.delete')->name('destroy');
        });

        // Salary Advances
        Route::prefix('salary-advances')->name('salary-advances.')->middleware('permission:payroll.salary-advance.view')->group(function () {
            Route::get('/', [SalaryAdvanceController::class, 'index'])->name('index');
            Route::post('/', [SalaryAdvanceController::class, 'store'])->middleware('permission:payroll.salary-advance.create')->name('store');
            Route::put('/{id}', [SalaryAdvanceController::class, 'update'])->middleware('permission:payroll.salary-advance.edit')->name('update');
            Route::post('/{id}/approve', [SalaryAdvanceController::class, 'approve'])->middleware('permission:payroll.salary-advance.approve')->name('approve');
            Route::post('/{id}/issue', [SalaryAdvanceController::class, 'issue'])->middleware('permission:payroll.salary-advance.issue')->name('issue');
            Route::post('/{id}/reject', [SalaryAdvanceController::class, 'reject'])->middleware('permission:payroll.salary-advance.approve')->name('reject');
            Route::post('/{id}/toggle-pause', [SalaryAdvanceController::class, 'togglePause'])->middleware('permission:payroll.salary-advance.edit')->name('toggle-pause');
            Route::put('/{id}/reschedule', [SalaryAdvanceController::class, 'reschedule'])->middleware('permission:payroll.salary-advance.edit')->name('reschedule');
            Route::post('/{id}/settle-early', [SalaryAdvanceController::class, 'settleEarly'])->middleware('permission:payroll.salary-advance.edit')->name('settle-early');
            Route::delete('/{id}', [SalaryAdvanceController::class, 'destroy'])->middleware('permission:payroll.salary-advance.delete')->name('destroy');
        });

        // Salary Benchmarks
        Route::prefix('salary-benchmarks')->name('salary-benchmarks.')->middleware('permission:payroll.salary-benchmark.view')->group(function () {
            Route::get('/', [App\Http\Controllers\Payroll\SalaryBenchmarkController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Payroll\SalaryBenchmarkController::class, 'create'])->middleware('permission:payroll.salary-benchmark.create')->name('create');
            Route::post('/', [App\Http\Controllers\Payroll\SalaryBenchmarkController::class, 'store'])->middleware('permission:payroll.salary-benchmark.create')->name('store');
            Route::get('/{salary_benchmark}/edit', [App\Http\Controllers\Payroll\SalaryBenchmarkController::class, 'edit'])->middleware('permission:payroll.salary-benchmark.edit')->name('edit');
            Route::put('/{salary_benchmark}', [App\Http\Controllers\Payroll\SalaryBenchmarkController::class, 'update'])->middleware('permission:payroll.salary-benchmark.edit')->name('update');
            Route::delete('/{salary_benchmark}', [App\Http\Controllers\Payroll\SalaryBenchmarkController::class, 'destroy'])->middleware('permission:payroll.salary-benchmark.delete')->name('destroy');
        });

        // Statutory Bodies
        Route::prefix('statutory-bodies')->name('statutory-bodies.')->middleware('permission:payroll.statutory-body.view')->group(function () {
            Route::get('/', [App\Http\Controllers\Payroll\StatutoryBodyController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Payroll\StatutoryBodyController::class, 'create'])->middleware('permission:payroll.statutory-body.create')->name('create');
            Route::post('/', [App\Http\Controllers\Payroll\StatutoryBodyController::class, 'store'])->middleware('permission:payroll.statutory-body.create')->name('store');
            Route::get('/{statutory_body}/edit', [App\Http\Controllers\Payroll\StatutoryBodyController::class, 'edit'])->middleware('permission:payroll.statutory-body.edit')->name('edit');
            Route::put('/{statutory_body}', [App\Http\Controllers\Payroll\StatutoryBodyController::class, 'update'])->middleware('permission:payroll.statutory-body.edit')->name('update');
            Route::delete('/{statutory_body}', [App\Http\Controllers\Payroll\StatutoryBodyController::class, 'destroy'])->middleware('permission:payroll.statutory-body.delete')->name('destroy');
        });

        // Pay Runs
        Route::prefix('pay-runs')->name('pay-runs.')->middleware('permission:payroll.pay-run.view')->group(function () {
            Route::get('/', [PayRunController::class, 'index'])->name('index');
            Route::get('/create', [PayRunController::class, 'create'])->middleware('permission:payroll.pay-run.create')->name('create');
            Route::post('/', [PayRunController::class, 'store'])->middleware('permission:payroll.pay-run.create')->name('store');
            Route::get('/transactions', [PayRunController::class, 'allTransactions'])->middleware('permission:payroll.pay-run.transactions.view')->name('transactions');
            Route::get('/transactions/export-pdf', [PayRunController::class, 'exportAllTransactionsPdf'])->middleware('permission:payroll.pay-run.export')->name('transactions.export-pdf');
            Route::get('/transactions/export-csv', [PayRunController::class, 'exportAllTransactionsCsv'])->middleware('permission:payroll.pay-run.export')->name('transactions.export-csv');
            Route::get('/{id}', [PayRunController::class, 'show'])->middleware('permission:payroll.pay-run.show')->name('show');
            Route::get('/{uniqueNo}/view-transaction', [PayRunController::class, 'viewTransaction'])->middleware('permission:payroll.pay-run.transactions.view')->name('view-transaction');
            Route::get('/{uniqueNo}/export-transaction-pdf', [PayRunController::class, 'exportTransactionPdf'])->middleware('permission:payroll.pay-run.export')->name('export-transaction-pdf');
            Route::get('/{uniqueNo}/export-transaction-csv', [PayRunController::class, 'exportTransactionCsv'])->middleware('permission:payroll.pay-run.export')->name('export-transaction-csv');
            Route::get('/{id}/payslips/filter', [PayRunController::class, 'filterPayslips'])->middleware('permission:payroll.pay-run.show')->name('filter-payslips');
            Route::post('/{id}/post', [PayRunController::class, 'post'])->middleware('permission:payroll.pay-run.post')->name('post');
            Route::post('/{id}/supplementary', [PayRunController::class, 'supplementary'])->middleware('permission:payroll.pay-run.create')->name('supplementary');
            Route::post('/{id}/reverse', [PayRunController::class, 'reverse'])->middleware('permission:payroll.pay-run.approve')->name('reverse');
            Route::post('/{id}/notify-accounting', [PayRunController::class, 'notifyAccounting'])->middleware('permission:payroll.pay-run.notify-accounting')->name('notify-accounting');
            Route::post('/{id}/pay', [PayRunController::class, 'pay'])->middleware('permission:payroll.pay-run.pay')->name('pay');
            Route::delete('/{id}', [PayRunController::class, 'destroy'])->middleware('permission:payroll.pay-run.delete')->name('destroy');
            Route::post('/{id}/pay-deduction', [PayRunController::class, 'payDeduction'])->middleware('permission:payroll.pay-run.pay')->name('pay-deduction');

            // Approval Workflow
            Route::post('/{id}/approve', [PayRunController::class, 'approve'])->middleware('permission:payroll.pay-run.approve')->name('approve');
            Route::post('/{id}/reject', [PayRunController::class, 'reject'])->middleware('permission:payroll.pay-run.approve')->name('reject');

            // Bank File Export
            Route::get('/{id}/bank-file', [PayRunController::class, 'bankFile'])->middleware('permission:payroll.pay-run.export')->name('bank-file');

            // Anomaly Detection
            Route::get('/{id}/validate', [PayRunController::class, 'validatePayRun'])->middleware('permission:payroll.pay-run.validate')->name('validate');

            // Bulk Email
            Route::post('/{payRun}/email-all', [PayslipController::class, 'emailAll'])->middleware('permission:payroll.payslip.email')->name('email-all');
        });

        // Payslips
        Route::prefix('pay-runs/{payRun}/payslips/{payslip}')->name('payslips.')->middleware('permission:payroll.payslip.view')->group(function () {
            Route::get('/pdf', [PayslipController::class, 'downloadPdf'])->middleware('permission:payroll.payslip.export')->name('pdf');
            Route::get('/doc', [PayslipController::class, 'downloadDoc'])->middleware('permission:payroll.payslip.export')->name('doc');
            Route::post('/email', [PayslipController::class, 'email'])->middleware('permission:payroll.payslip.email')->name('email');
        });

        // Payslip Downloads
        Route::prefix('download-payslip')->name('download-payslip.')->middleware('permission:payroll.payslip.view')->group(function () {
            Route::get('/', [PayslipDownloadController::class, 'index'])->name('index');
            Route::get('/bulk-pdf', [PayslipDownloadController::class, 'bulkPdf'])->middleware('permission:payroll.payslip.export')->name('bulk-pdf');
            Route::post('/bulk-email', [PayslipDownloadController::class, 'bulkEmail'])->middleware('permission:payroll.payslip.email')->name('bulk-email');
        });

        // Payslip Adjustments
        Route::middleware('permission:payroll.payslip.edit')->group(function () {
            Route::post('payslips/items/{itemId}/update', [PayslipController::class, 'updateItem'])->name('payslips.update-item');
            Route::post('payslips/{payslipId}/items/add', [PayslipController::class, 'addItem'])->name('payslips.add-item');
            Route::delete('payslips/items/{itemId}', [PayslipController::class, 'removeItem'])->name('payslips.remove-item');
        });

        // Employee Pay Structure
        Route::prefix('employee-pay')->name('employee-pay.')->middleware('permission:payroll.employee-pay.view')->group(function () {
            Route::get('/', [EmployeePayController::class, 'index'])->name('index');
            Route::post('/bulk-update', [EmployeePayController::class, 'bulkUpdate'])->middleware('permission:payroll.employee-pay.edit')->name('bulk-update');
            Route::get('/payslips/bulk-pdf', [EmployeePayController::class, 'bulkDownloadPayslips'])->middleware('permission:payroll.employee-pay.export')->name('bulk-payslips');
            Route::get('/{employee_id}/edit', [EmployeePayController::class, 'edit'])->middleware('permission:payroll.employee-pay.edit')->name('edit');
            Route::post('/{employee_id}', [EmployeePayController::class, 'update'])->middleware('permission:payroll.employee-pay.edit')->name('update');
            Route::get('/{employee_id}/tax-optimize', [EmployeePayController::class, 'taxOptimize'])->middleware('permission:payroll.employee-pay.show')->name('tax-optimize');
        });

        // Payroll Liabilities
        Route::prefix('liabilities')->name('liabilities.')->middleware('permission:payroll.liability.view')->group(function () {
            Route::get('/', [PayrollLiabilityController::class, 'index'])->name('index');
            Route::get('/report', [PayrollLiabilityController::class, 'report'])->name('report');
            Route::get('/by-statutory-body', [PayrollLiabilityController::class, 'byStatutoryBody'])->name('by-statutory-body');
            Route::post('/pay', [PayrollLiabilityController::class, 'store'])->middleware('permission:payroll.liability.create')->name('store');
            Route::patch('/remittances/{id}/payment-date', [PayrollLiabilityController::class, 'updatePaymentDate'])->middleware('permission:payroll.liability.create')->name('payment-date.update');
            Route::post('/remittances/{id}/approve', [PayrollLiabilityController::class, 'approve'])->middleware('permission:payroll.liability.approve')->name('approve');
            Route::post('/remittances/{id}/post', [PayrollLiabilityController::class, 'post'])->middleware('permission:payroll.liability.approve')->name('post');
            Route::post('/remittances/{id}/reverse', [PayrollLiabilityController::class, 'reverse'])->middleware('permission:payroll.liability.reverse')->name('reverse');
            Route::get('/remittances/{id}/receipt', [PayrollLiabilityController::class, 'receipt'])->middleware('permission:payroll.liability.export')->name('receipt');
        });

        // FBR Tax Brackets
        Route::prefix('tax-brackets')->name('tax-brackets.')->middleware('permission:payroll.tax-bracket.view')->group(function () {
            Route::get('/', [TaxBracketController::class, 'index'])->name('index');
            Route::get('/template', [TaxBracketController::class, 'downloadTemplate'])->middleware('permission:payroll.tax-bracket.import')->name('template');
            Route::post('/import', [TaxBracketController::class, 'import'])->middleware('permission:payroll.tax-bracket.import')->name('import');
            Route::post('/', [TaxBracketController::class, 'store'])->middleware('permission:payroll.tax-bracket.create')->name('store');
            Route::put('/{taxBracket}', [TaxBracketController::class, 'update'])->middleware('permission:payroll.tax-bracket.edit')->name('update');
            Route::delete('/{taxBracket}', [TaxBracketController::class, 'destroy'])->middleware('permission:payroll.tax-bracket.delete')->name('destroy');
            Route::post('/seed-fbr', [TaxBracketController::class, 'seedFBR'])->middleware('permission:payroll.tax-bracket.create')->name('seed-fbr');
        });

        // Statutory Compliance
        Route::prefix('compliance')->name('compliance.')->middleware('permission:payroll.compliance.view')->group(function () {
            Route::get('dashboard', [App\Http\Controllers\StatutoryComplianceController::class, 'dashboard'])->name('dashboard');
            Route::get('calendar', [App\Http\Controllers\StatutoryComplianceController::class, 'calendar'])->name('calendar');
            Route::get('alerts', [App\Http\Controllers\StatutoryComplianceController::class, 'alerts'])->name('alerts');
            Route::post('generate-events', [App\Http\Controllers\StatutoryComplianceController::class, 'generateEvents'])->middleware('permission:payroll.compliance.manage')->name('generate-events');
            Route::post('check-regulatory-changes', [App\Http\Controllers\StatutoryComplianceController::class, 'checkRegulatoryChanges'])->middleware('permission:payroll.compliance.manage')->name('check-regulatory-changes');
            Route::post('alerts/{alertId}/acknowledge', [App\Http\Controllers\StatutoryComplianceController::class, 'acknowledgeAlert'])->middleware('permission:payroll.compliance.manage')->name('alerts.acknowledge');
            Route::post('events/{eventId}/complete', [App\Http\Controllers\StatutoryComplianceController::class, 'completeEvent'])->middleware('permission:payroll.compliance.manage')->name('events.complete');
            Route::get('statistics', [App\Http\Controllers\StatutoryComplianceController::class, 'statistics'])->name('statistics');
        });

        // Payroll Reports
        Route::prefix('reports')->name('reports.')->middleware('permission:payroll.reports.view')->group(function () {
            Route::get('summary', [PayrollReportController::class, 'summary'])->name('summary');
            Route::get('summary/export', [PayrollReportController::class, 'export'])->middleware('permission:payroll.reports.export')->name('summary.export');
            Route::get('employee-history', [PayrollReportController::class, 'employeeHistory'])->name('employee-history');
            Route::get('employee-comparison', [PayrollReportController::class, 'employeeComparison'])->name('employee-comparison');
            Route::get('calendar', [PayrollReportController::class, 'calendar'])->name('calendar');
            Route::get('analytics', [PayrollReportController::class, 'analytics'])->name('analytics');
            Route::get('overtime', [PayrollReportController::class, 'overtime'])->name('overtime');
            Route::get('export', [PayrollReportController::class, 'export'])->middleware('permission:payroll.reports.export')->name('export');
        });
    });
