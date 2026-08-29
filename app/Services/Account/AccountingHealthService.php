<?php

namespace App\Services\Account;

use App\Models\AccountingPeriod;
use App\Models\BankStatementLine;
use App\Models\Bill;
use App\Models\FixedAsset;
use App\Models\PayItem;
use App\Models\PayRun;
use App\Models\Sale;
use App\Models\TaxPeriod;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class AccountingHealthService
{
    public function inspect(int $companyId, ?string $startDate = null, ?string $endDate = null): array
    {
        $end = Carbon::parse($endDate ?: now())->endOfDay();
        $start = Carbon::parse($startDate ?: $end->copy()->startOfMonth())->startOfDay();
        $checks = [];

        $ledgerTotals = $this->transactions($companyId, $start, $end)
            ->selectRaw('COALESCE(SUM(debit), 0) AS debit, COALESCE(SUM(credit), 0) AS credit')
            ->first();
        $ledgerDifference = round((float) $ledgerTotals->debit - (float) $ledgerTotals->credit, 2);
        $checks[] = $this->check(
            'ledger_balance',
            'General ledger balance',
            abs($ledgerDifference) <= 0.01 ? 'pass' : 'critical',
            abs($ledgerDifference) <= 0.01 ? 'Debits and credits agree.' : 'The ledger is out of balance by ' . number_format(abs($ledgerDifference), 2) . '.',
            abs($ledgerDifference) <= 0.01 ? 0 : 1,
            route('account.report.trail-balance')
        );

        $unbalancedDocuments = DB::query()->fromSub(
            $this->transactions($companyId, $start, $end)
                ->whereNotNull('unique_no')
                ->select('unique_no')
                ->groupBy('unique_no')
                ->havingRaw('ABS(COALESCE(SUM(debit), 0) - COALESCE(SUM(credit), 0)) > 0.01'),
            'unbalanced_documents'
        )->count();
        $checks[] = $this->countCheck('unbalanced_documents', 'Unbalanced posting batches', $unbalancedDocuments, 'critical', 'Every posting reference balances.', route('account.document-trace.index'));

        $companyMismatches = DB::table('transactions as t')
            ->join('chart_of_accounts as coa', 'coa.id', '=', 't.account_id')
            ->where('t.company_id', $companyId)
            ->where('coa.company_id', '<>', $companyId)
            ->whereNull('t.deleted_at')
            ->whereNull('coa.deleted_at')
            ->count();
        $checks[] = $this->countCheck('account_company_mismatch', 'Cross-company account references', $companyMismatches, 'critical', 'All ledger rows use this company\'s chart of accounts.');

        $saleBalanceMismatches = Sale::where('company_id', $companyId)
            ->whereBetween('date', [$start, $end])
            ->whereRaw('ABS(
                COALESCE(sales.remaining_amount, 0) - GREATEST(0,
                    COALESCE(sales.total_after_discount, sales.total, 0)
                    - COALESCE(sales.paid_amount, 0)
                    - COALESCE((
                        SELECT SUM(credit_note_allocations.amount)
                        FROM credit_note_allocations
                        WHERE credit_note_allocations.sale_id = sales.id
                          AND credit_note_allocations.company_id = sales.company_id
                    ), 0)
                )
            ) > 0.01')
            ->count();
        $checks[] = $this->countCheck('sale_balance_mismatch', 'Sales document balances', $saleBalanceMismatches, 'critical', 'Invoice remaining balances agree with total less payments and allocated credit notes.', route('account.sale-invoice.index'));

        $billBalanceMismatches = Bill::where('company_id', $companyId)
            ->whereBetween('date', [$start, $end])
            ->whereRaw('ABS(
                COALESCE(bills.remaining_amount, 0) - GREATEST(0,
                    COALESCE(bills.total_after_discount, bills.total, 0)
                    - COALESCE(bills.paid_amount, 0)
                    - COALESCE((
                        SELECT SUM(debit_note_allocations.amount)
                        FROM debit_note_allocations
                        WHERE debit_note_allocations.bill_id = bills.id
                          AND debit_note_allocations.company_id = bills.company_id
                    ), 0)
                )
            ) > 0.01')
            ->count();
        $checks[] = $this->countCheck('bill_balance_mismatch', 'Bill document balances', $billBalanceMismatches, 'critical', 'Bill remaining balances agree with total less payments and allocated debit notes.', route('account.bill-invoice.index'));

        $approvedSalesWithoutLedger = $this->documentsWithoutLedger('sales', $companyId, $start, $end);
        $checks[] = $this->countCheck('sales_without_ledger', 'Approved sales missing ledger rows', $approvedSalesWithoutLedger, 'critical', 'Approved and paid sales have ledger postings.', route('account.sale-invoice.index', ['approval_status' => 'approved']));

        $approvedBillsWithoutLedger = $this->documentsWithoutLedger('bills', $companyId, $start, $end);
        $checks[] = $this->countCheck('bills_without_ledger', 'Approved bills missing ledger rows', $approvedBillsWithoutLedger, 'critical', 'Approved and paid bills have ledger postings.', route('account.bill-invoice.index', ['approval_status' => 'approved']));

        $draftSales = Sale::where('company_id', $companyId)->whereBetween('date', [$start, $end])->whereIn('approval_status', ['draft', 'submitted'])->count();
        $checks[] = $this->countCheck('draft_sales', 'Sales awaiting approval', $draftSales, 'warning', 'No sales remain in draft or submitted status.', route('account.sale-invoice.index'));

        $draftBills = Bill::where('company_id', $companyId)->whereBetween('date', [$start, $end])->whereIn('approval_status', ['draft', 'submitted'])->count();
        $checks[] = $this->countCheck('draft_bills', 'Bills awaiting approval', $draftBills, 'warning', 'No bills remain in draft or submitted status.', route('account.bill-invoice.index'));

        $unreconciled = BankStatementLine::where('company_id', $companyId)
            ->whereBetween('date', [$start, $end])
            ->whereIn('status', ['Unreconciled', 'Partially Reconciled'])
            ->count();
        $checks[] = $this->countCheck('unreconciled_bank', 'Unreconciled bank lines', $unreconciled, 'warning', 'All imported bank lines are reconciled.');

        $unpaidTax = TaxPeriod::where('company_id', $companyId)->where('end_date', '<=', $end)->whereNotIn('status', ['paid'])->count();
        $checks[] = $this->countCheck('unpaid_tax', 'Tax periods not paid', $unpaidTax, 'warning', 'All due tax periods are settled.', route('account.tax-periods.index'));

        $unpaidPayroll = PayRun::where('company_id', $companyId)->whereBetween('payment_date', [$start, $end])->where('status', 'posted')->count();
        $checks[] = $this->countCheck('unpaid_payroll', 'Posted payroll awaiting payment', $unpaidPayroll, 'warning', 'Posted payroll runs are paid.');

        $missingPayrollMappings = PayItem::query()
            ->leftJoin('chart_of_accounts as coa', 'coa.id', '=', 'pay_items.account_id')
            ->where('pay_items.company_id', $companyId)
            ->whereNull('pay_items.deleted_at')
            ->where(function ($query) use ($companyId) {
                $query->whereNull('pay_items.account_id')
                    ->orWhereNull('coa.id')
                    ->orWhere('coa.company_id', '<>', $companyId);
            })
            ->count();
        $checks[] = $this->countCheck('payroll_mappings', 'Payroll account mappings', $missingPayrollMappings, 'warning', 'Every pay item maps to this company\'s chart of accounts.');

        $suspenseBalance = abs((float) DB::table('transactions as t')
            ->join('chart_of_accounts as coa', 'coa.id', '=', 't.account_id')
            ->where('t.company_id', $companyId)
            ->whereNull('t.deleted_at')
            ->whereNull('coa.deleted_at')
            ->whereRaw("LOWER(coa.name) LIKE '%suspense%'")
            ->whereDate('t.date', '<=', $end)
            ->selectRaw('COALESCE(SUM(t.debit - t.credit), 0) AS balance')
            ->value('balance'));
        $checks[] = $this->check('suspense_balance', 'Suspense account balance', $suspenseBalance <= 0.01 ? 'pass' : 'warning', $suspenseBalance <= 0.01 ? 'No unresolved suspense balance.' : 'Suspense accounts contain ' . number_format($suspenseBalance, 2) . '.', $suspenseBalance <= 0.01 ? 0 : 1, route('account.report.ledger'));

        $depreciationDue = FixedAsset::where('company_id', $companyId)->where('status', 'active')
            ->whereDate('in_service_date', '<=', $end)
            ->where(function ($query) use ($end) {
                $query->whereNull('last_depreciation_date')->orWhereDate('last_depreciation_date', '<', $end->copy()->startOfMonth());
            })
            ->count();
        $checks[] = $this->countCheck('depreciation_due', 'Fixed assets awaiting depreciation', $depreciationDue, 'warning', 'All active assets are depreciated through the period.', route('account.month-end-adjustments.index'));

        $period = AccountingPeriod::where('company_id', $companyId)->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $end->copy()->startOfDay())
            ->latest('start_date')
            ->first();
        $checks[] = $this->check('accounting_period', 'Accounting period coverage', $period ? 'pass' : 'critical', $period ? ($period->is_locked ? 'The selected period is locked.' : 'The selected period is open.') : 'No accounting period covers the selected end date.', $period ? 0 : 1, route('account.organization-settings.accounting-period.index'));

        $critical = collect($checks)->where('status', 'critical')->sum('count');
        $warnings = collect($checks)->where('status', 'warning')->sum('count');
        $passed = collect($checks)->where('status', 'pass')->count();
        $score = max(0, 100 - min(70, $critical * 15) - min(30, $warnings * 2));

        return compact('checks', 'critical', 'warnings', 'passed', 'score', 'start', 'end', 'period');
    }

    private function transactions(int $companyId, Carbon $start, Carbon $end): Builder
    {
        return DB::table('transactions')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->whereBetween('date', [$start, $end]);
    }

    private function documentsWithoutLedger(string $table, int $companyId, Carbon $start, Carbon $end): int
    {
        return DB::table($table . ' as document')
            ->where('document.company_id', $companyId)
            ->whereNull('document.deleted_at')
            ->whereBetween('document.date', [$start, $end])
            ->whereIn('document.approval_status', ['approved', 'paid'])
            ->whereNotNull('document.unique_no')
            ->whereNotExists(function ($query) use ($companyId) {
                $query->selectRaw('1')
                    ->from('transactions as transaction')
                    ->whereColumn('transaction.unique_no', 'document.unique_no')
                    ->where('transaction.company_id', $companyId)
                    ->whereNull('transaction.deleted_at');
            })
            ->count();
    }

    private function countCheck(string $key, string $label, int $count, string $failureStatus, string $passMessage, ?string $url = null): array
    {
        return $this->check($key, $label, $count === 0 ? 'pass' : $failureStatus, $count === 0 ? $passMessage : $count . ' item' . ($count === 1 ? '' : 's') . ' need attention.', $count, $url);
    }

    private function check(string $key, string $label, string $status, string $message, int $count = 0, ?string $url = null): array
    {
        return compact('key', 'label', 'status', 'message', 'count', 'url');
    }
}
