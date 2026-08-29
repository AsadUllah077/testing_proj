<?php

namespace App\Services\AI;

use App\Models\Bill;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class FinancialIntelligenceService
{
    /**
     * Get approved revenue for a period.
     */
    public function getRevenue($startDate, $endDate, $companyId): float
    {
        $ledgerRevenue = $this->getRevenueFromTransactions($startDate, $endDate, $companyId);
        if ($ledgerRevenue['line_count'] > 0) {
            return $ledgerRevenue['amount'];
        }

        return (float) Sale::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('approval_status', 'approved')
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('COALESCE(SUM(COALESCE(total_after_discount, total, 0)), 0) as total_revenue')
            ->value('total_revenue');
    }

    public function getRevenueFromTransactions($startDate, $endDate, int $companyId): array
    {
        return $this->getCategoryActivityFromTransactions($startDate, $endDate, $companyId, ['Revenue']);
    }

    /**
     * Get payroll gross earnings for posted/paid pay runs in a period.
     */
    public function getLaborCost($startDate, $endDate, $companyId): float
    {
        return $this->getPayrollSummary($startDate, $endDate, $companyId)['gross_pay'];
    }

    /**
     * Get statutory deductions for posted/paid pay runs in a period.
     */
    public function getStatutoryLiabilities($startDate, $endDate, $companyId): float
    {
        return (float) DB::table('payslip_items')
            ->join('payslips', 'payslip_items.payslip_id', '=', 'payslips.id')
            ->join('pay_runs', 'payslips.pay_run_id', '=', 'pay_runs.id')
            ->join('pay_items', 'payslip_items.pay_item_id', '=', 'pay_items.id')
            ->where('payslips.company_id', $companyId)
            ->whereIn('pay_runs.status', ['posted', 'paid'])
            ->whereNotNull('pay_items.statutory_body_id')
            ->whereBetween('pay_runs.period_end', [$startDate, $endDate])
            ->sum('payslip_items.amount');
    }

    /**
     * Get approved expenses for a period.
     */
    public function getExpenses($startDate, $endDate, $companyId): float
    {
        $ledgerExpenses = $this->getExpensesFromTransactions($startDate, $endDate, $companyId);
        if ($ledgerExpenses['line_count'] > 0) {
            return $ledgerExpenses['amount'];
        }

        return (float) Bill::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('approval_status', 'approved')
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('COALESCE(SUM(COALESCE(total_after_discount, total, 0)), 0) as total_expenses')
            ->value('total_expenses');
    }

    public function getExpensesFromTransactions($startDate, $endDate, int $companyId): array
    {
        return $this->getCategoryActivityFromTransactions($startDate, $endDate, $companyId, ['Expenses']);
    }

    public function getCOGS($startDate, $endDate, int $companyId): float
    {
        return $this->getCOGSFromTransactions($startDate, $endDate, $companyId)['amount'];
    }

    public function getCOGSFromTransactions($startDate, $endDate, int $companyId): array
    {
        return $this->getCategoryActivityFromTransactions($startDate, $endDate, $companyId, ['COGS']);
    }

    public function getReceivablesOutstanding($endDate, int $companyId): float
    {
        $ledgerReceivables = $this->getSystemAccountBalanceFromTransactions($endDate, $companyId, ['ACCOUNTS_RECEIVABLE']);
        if ($ledgerReceivables['line_count'] > 0) {
            return $ledgerReceivables['balance'];
        }

        return (float) Sale::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('approval_status', 'approved')
            ->where('date', '<=', $endDate)
            ->whereRaw('COALESCE(remaining_amount, 0) > 0')
            ->sum('remaining_amount');
    }

    public function getPayablesOutstanding($endDate, int $companyId): float
    {
        $ledgerPayables = $this->getSystemAccountBalanceFromTransactions($endDate, $companyId, ['ACCOUNTS_PAYABLE']);
        if ($ledgerPayables['line_count'] > 0) {
            return $ledgerPayables['balance'];
        }

        return (float) Bill::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('approval_status', 'approved')
            ->where('date', '<=', $endDate)
            ->whereRaw('COALESCE(remaining_amount, 0) > 0')
            ->sum('remaining_amount');
    }

    public function getCashBalance($endDate, int $companyId): float
    {
        return $this->getFilteredBalanceFromTransactions($endDate, $companyId, function ($q) {
            $q->where('chart_of_accounts.sub_type', 'BANK')
                ->orWhere('chart_of_accounts.system_account_type', 'BANK')
                ->orWhere('chart_of_accounts.name', 'like', '%bank%')
                ->orWhere('chart_of_accounts.name', 'like', '%cash%');
        })['balance'];
    }

    public function getAssetsBalance($endDate, int $companyId): float
    {
        return $this->getCategoryBalanceFromTransactions($endDate, $companyId, ['Assets'])['balance'];
    }

    public function getLiabilitiesBalance($endDate, int $companyId): float
    {
        return $this->getCategoryBalanceFromTransactions($endDate, $companyId, ['Liabilities'])['balance'];
    }

    public function getEquityBalance($endDate, int $companyId): float
    {
        return $this->getCategoryBalanceFromTransactions($endDate, $companyId, ['Equity'])['balance'];
    }

    public function getFinancialActivitySummary($startDate, $endDate, int $companyId): array
    {
        $revenue = $this->getRevenue($startDate, $endDate, $companyId);
        $expenses = $this->getExpenses($startDate, $endDate, $companyId);
        $cogs = $this->getCOGS($startDate, $endDate, $companyId);

        return [
            'revenue' => $revenue,
            'cogs' => $cogs,
            'gross_profit' => $revenue - $cogs,
            'expenses' => $expenses,
            'net_profit' => $revenue - $cogs - $expenses,
            'cash_balance' => $this->getCashBalance($endDate, $companyId),
            'accounts_receivable' => $this->getReceivablesOutstanding($endDate, $companyId),
            'accounts_payable' => $this->getPayablesOutstanding($endDate, $companyId),
            'assets' => $this->getAssetsBalance($endDate, $companyId),
            'liabilities' => $this->getLiabilitiesBalance($endDate, $companyId),
            'equity' => $this->getEquityBalance($endDate, $companyId),
        ];
    }

    public function getPayrollSummary($startDate, $endDate, int $companyId): array
    {
        $summary = DB::table('payslips')
            ->join('pay_runs', 'payslips.pay_run_id', '=', 'pay_runs.id')
            ->where('payslips.company_id', $companyId)
            ->whereIn('pay_runs.status', ['posted', 'paid'])
            ->whereBetween('pay_runs.period_end', [$startDate, $endDate])
            ->selectRaw('
                COUNT(payslips.id) as payslip_count,
                COUNT(DISTINCT payslips.employee_id) as employee_count,
                COALESCE(SUM(payslips.gross_earnings), 0) as gross_pay,
                COALESCE(SUM(payslips.total_deductions), 0) as deductions,
                COALESCE(SUM(payslips.net_pay), 0) as net_pay
            ')
            ->first();

        $grossPay = (float) ($summary->gross_pay ?? 0);
        $employeeCount = (int) ($summary->employee_count ?? 0);

        return [
            'payslip_count' => (int) ($summary->payslip_count ?? 0),
            'employee_count' => $employeeCount,
            'gross_pay' => $grossPay,
            'deductions' => (float) ($summary->deductions ?? 0),
            'net_pay' => (float) ($summary->net_pay ?? 0),
            'average_gross_per_employee' => $employeeCount > 0 ? $grossPay / $employeeCount : 0,
        ];
    }

    public function getDepartmentLaborCost($startDate, $endDate, int $companyId)
    {
        return DB::table('payslips')
            ->join('pay_runs', 'payslips.pay_run_id', '=', 'pay_runs.id')
            ->join('employees', 'payslips.employee_id', '=', 'employees.id')
            ->leftJoin('departments', 'employees.department_id', '=', 'departments.id')
            ->where('payslips.company_id', $companyId)
            ->whereIn('pay_runs.status', ['posted', 'paid'])
            ->whereBetween('pay_runs.period_end', [$startDate, $endDate])
            ->selectRaw("
                COALESCE(departments.department, 'Unassigned') as department,
                COUNT(DISTINCT payslips.employee_id) as employees,
                COALESCE(SUM(payslips.gross_earnings), 0) as gross_pay,
                COALESCE(SUM(payslips.total_deductions), 0) as deductions,
                COALESCE(SUM(payslips.net_pay), 0) as net_pay
            ")
            ->groupBy('departments.department')
            ->orderByDesc('gross_pay')
            ->get();
    }

    public function getStatutoryBreakdown($startDate, $endDate, int $companyId)
    {
        return DB::table('payslip_items')
            ->join('payslips', 'payslip_items.payslip_id', '=', 'payslips.id')
            ->join('pay_runs', 'payslips.pay_run_id', '=', 'pay_runs.id')
            ->join('pay_items', 'payslip_items.pay_item_id', '=', 'pay_items.id')
            ->leftJoin('statutory_bodies', 'pay_items.statutory_body_id', '=', 'statutory_bodies.id')
            ->where('payslips.company_id', $companyId)
            ->whereIn('pay_runs.status', ['posted', 'paid'])
            ->whereNotNull('pay_items.statutory_body_id')
            ->whereBetween('pay_runs.period_end', [$startDate, $endDate])
            ->selectRaw("
                COALESCE(statutory_bodies.name, pay_items.name) as statutory_body,
                COUNT(DISTINCT payslips.employee_id) as employees,
                COALESCE(SUM(payslip_items.amount), 0) as amount
            ")
            ->groupBy('statutory_bodies.name', 'pay_items.name')
            ->orderByDesc('amount')
            ->get();
    }

    public function getTopReceivables(int $companyId, int $limit = 10)
    {
        $ledgerRows = DB::table('transactions')
            ->join('chart_of_accounts', 'transactions.account_id', '=', 'chart_of_accounts.id')
            ->leftJoin('customers', 'transactions.customer_id', '=', 'customers.id')
            ->where('transactions.company_id', $companyId)
            ->where('transactions.status', 1)
            ->whereNull('transactions.deleted_at')
            ->where('chart_of_accounts.system_account_type', 'ACCOUNTS_RECEIVABLE')
            ->selectRaw("
                NULL as invoice_no,
                MAX(transactions.date) as date,
                COALESCE(customers.name, 'Unknown customer') as customer,
                COALESCE(SUM(transactions.debit), 0) - COALESCE(SUM(transactions.credit), 0) as outstanding
            ")
            ->groupBy('customers.name')
            ->havingRaw('COALESCE(SUM(transactions.debit), 0) - COALESCE(SUM(transactions.credit), 0) > 0')
            ->orderByDesc('outstanding')
            ->limit($limit)
            ->get();

        if ($ledgerRows->isNotEmpty()) {
            return $ledgerRows;
        }

        return DB::table('sales')
            ->leftJoin('customers', 'sales.customer_id', '=', 'customers.id')
            ->where('sales.company_id', $companyId)
            ->where('sales.approval_status', 'approved')
            ->whereRaw('COALESCE(sales.remaining_amount, 0) > 0')
            ->selectRaw("
                sales.invoice_no,
                sales.date,
                COALESCE(customers.name, 'Unknown customer') as customer,
                COALESCE(sales.remaining_amount, 0) as outstanding
            ")
            ->orderByDesc('outstanding')
            ->limit($limit)
            ->get();
    }

    public function getTopPayables(int $companyId, int $limit = 10)
    {
        $ledgerRows = DB::table('transactions')
            ->join('chart_of_accounts', 'transactions.account_id', '=', 'chart_of_accounts.id')
            ->leftJoin('vendors', 'transactions.vendor_id', '=', 'vendors.id')
            ->where('transactions.company_id', $companyId)
            ->where('transactions.status', 1)
            ->whereNull('transactions.deleted_at')
            ->where('chart_of_accounts.system_account_type', 'ACCOUNTS_PAYABLE')
            ->selectRaw("
                NULL as invoice_no,
                MAX(transactions.date) as date,
                COALESCE(vendors.name, 'Unknown vendor') as vendor,
                COALESCE(SUM(transactions.credit), 0) - COALESCE(SUM(transactions.debit), 0) as outstanding
            ")
            ->groupBy('vendors.name')
            ->havingRaw('COALESCE(SUM(transactions.credit), 0) - COALESCE(SUM(transactions.debit), 0) > 0')
            ->orderByDesc('outstanding')
            ->limit($limit)
            ->get();

        if ($ledgerRows->isNotEmpty()) {
            return $ledgerRows;
        }

        return DB::table('bills')
            ->leftJoin('vendors', 'bills.vendor_id', '=', 'vendors.id')
            ->where('bills.company_id', $companyId)
            ->where('bills.approval_status', 'approved')
            ->whereRaw('COALESCE(bills.remaining_amount, 0) > 0')
            ->selectRaw("
                bills.invoice_no,
                bills.date,
                COALESCE(vendors.name, 'Unknown vendor') as vendor,
                COALESCE(bills.remaining_amount, 0) as outstanding
            ")
            ->orderByDesc('outstanding')
            ->limit($limit)
            ->get();
    }

    public function getRecentPayRuns(int $companyId, int $limit = 6)
    {
        return DB::table('pay_runs')
            ->leftJoin('payslips', 'pay_runs.id', '=', 'payslips.pay_run_id')
            ->where('pay_runs.company_id', $companyId)
            ->whereIn('pay_runs.status', ['posted', 'paid'])
            ->selectRaw('
                pay_runs.unique_no,
                pay_runs.period_start,
                pay_runs.period_end,
                pay_runs.payment_date,
                pay_runs.status,
                COUNT(payslips.id) as payslips,
                COALESCE(SUM(payslips.gross_earnings), 0) as gross_pay,
                COALESCE(SUM(payslips.net_pay), 0) as net_pay
            ')
            ->groupBy('pay_runs.id', 'pay_runs.unique_no', 'pay_runs.period_start', 'pay_runs.period_end', 'pay_runs.payment_date', 'pay_runs.status')
            ->orderByDesc('pay_runs.period_end')
            ->limit($limit)
            ->get();
    }

    public function getMonthlyAccountingPayrollTrend(int $companyId, int $months = 12): array
    {
        $rows = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $start = now()->subMonths($i)->startOfMonth()->toDateString();
            $end = now()->subMonths($i)->endOfMonth()->toDateString();

            $rows[] = [
                'label' => now()->subMonths($i)->format('M Y'),
                'revenue' => $this->getRevenue($start, $end, $companyId),
                'expenses' => $this->getExpenses($start, $end, $companyId),
                'payroll' => $this->getLaborCost($start, $end, $companyId),
            ];
        }

        return $rows;
    }

    public function getTransactionSummary($startDate, $endDate, int $companyId): array
    {
        $summary = DB::table('transactions')
            ->where('company_id', $companyId)
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('
                COUNT(*) as transaction_count,
                COUNT(DISTINCT unique_no) as journal_count,
                COALESCE(SUM(debit), 0) as total_debit,
                COALESCE(SUM(credit), 0) as total_credit
            ')
            ->first();

        return [
            'transaction_count' => (int) ($summary->transaction_count ?? 0),
            'journal_count' => (int) ($summary->journal_count ?? 0),
            'total_debit' => (float) ($summary->total_debit ?? 0),
            'total_credit' => (float) ($summary->total_credit ?? 0),
            'variance' => (float) (($summary->total_debit ?? 0) - ($summary->total_credit ?? 0)),
        ];
    }

    public function getTransactionProfitLoss($startDate, $endDate, int $companyId): array
    {
        $rows = DB::table('transactions')
            ->join('chart_of_accounts', 'transactions.account_id', '=', 'chart_of_accounts.id')
            ->where('transactions.company_id', $companyId)
            ->where('transactions.status', 1)
            ->whereNull('transactions.deleted_at')
            ->whereBetween('transactions.date', [$startDate, $endDate])
            ->whereIn('chart_of_accounts.category', ['Revenue', 'Expenses', 'COGS'])
            ->selectRaw("
                chart_of_accounts.category,
                COALESCE(SUM(transactions.debit), 0) as debit,
                COALESCE(SUM(transactions.credit), 0) as credit
            ")
            ->groupBy('chart_of_accounts.category')
            ->get()
            ->keyBy('category');

        $revenue = (float) (($rows['Revenue']->credit ?? 0) - ($rows['Revenue']->debit ?? 0));
        $expenses = (float) (($rows['Expenses']->debit ?? 0) - ($rows['Expenses']->credit ?? 0));
        $cogs = (float) (($rows['COGS']->debit ?? 0) - ($rows['COGS']->credit ?? 0));

        return [
            'revenue' => $revenue,
            'expenses' => $expenses,
            'cogs' => $cogs,
            'gross_profit' => $revenue - $cogs,
            'net_profit' => $revenue - $cogs - $expenses,
        ];
    }

    public function getAccountBalancesFromTransactions($startDate, $endDate, int $companyId, int $limit = 20)
    {
        return DB::table('transactions')
            ->join('chart_of_accounts', 'transactions.account_id', '=', 'chart_of_accounts.id')
            ->where('transactions.company_id', $companyId)
            ->where('transactions.status', 1)
            ->whereNull('transactions.deleted_at')
            ->whereBetween('transactions.date', [$startDate, $endDate])
            ->selectRaw("
                chart_of_accounts.account_number,
                chart_of_accounts.name as account_name,
                chart_of_accounts.category,
                chart_of_accounts.sub_type,
                COALESCE(SUM(transactions.debit), 0) as debit,
                COALESCE(SUM(transactions.credit), 0) as credit,
                CASE
                    WHEN chart_of_accounts.category IN ('Assets', 'Expenses', 'COGS')
                    THEN COALESCE(SUM(transactions.debit), 0) - COALESCE(SUM(transactions.credit), 0)
                    ELSE COALESCE(SUM(transactions.credit), 0) - COALESCE(SUM(transactions.debit), 0)
                END as balance
            ")
            ->groupBy('chart_of_accounts.id', 'chart_of_accounts.account_number', 'chart_of_accounts.name', 'chart_of_accounts.category', 'chart_of_accounts.sub_type')
            ->orderByRaw('ABS(balance) DESC')
            ->limit($limit)
            ->get();
    }

    public function getRecentTransactions(int $companyId, int $limit = 20)
    {
        return DB::table('transactions')
            ->leftJoin('chart_of_accounts', 'transactions.account_id', '=', 'chart_of_accounts.id')
            ->leftJoin('vendors', 'transactions.vendor_id', '=', 'vendors.id')
            ->leftJoin('customers', 'transactions.customer_id', '=', 'customers.id')
            ->where('transactions.company_id', $companyId)
            ->where('transactions.status', 1)
            ->whereNull('transactions.deleted_at')
            ->selectRaw("
                transactions.date,
                transactions.unique_no,
                transactions.invoice_no,
                transactions.type,
                transactions.description,
                COALESCE(chart_of_accounts.name, transactions.account_label) as account,
                chart_of_accounts.category,
                COALESCE(vendors.name, customers.name) as counterparty,
                COALESCE(transactions.debit, 0) as debit,
                COALESCE(transactions.credit, 0) as credit
            ")
            ->orderByDesc('transactions.date')
            ->orderByDesc('transactions.id')
            ->limit($limit)
            ->get();
    }

    public function getTransactionsByAccount(string $accountSearch, int $companyId, int $limit = 20)
    {
        return DB::table('transactions')
            ->leftJoin('chart_of_accounts', 'transactions.account_id', '=', 'chart_of_accounts.id')
            ->leftJoin('vendors', 'transactions.vendor_id', '=', 'vendors.id')
            ->leftJoin('customers', 'transactions.customer_id', '=', 'customers.id')
            ->where('transactions.company_id', $companyId)
            ->where('transactions.status', 1)
            ->whereNull('transactions.deleted_at')
            ->where(function ($q) use ($accountSearch) {
                $q->where('chart_of_accounts.name', 'like', "%{$accountSearch}%")
                    ->orWhere('chart_of_accounts.account_number', 'like', "%{$accountSearch}%")
                    ->orWhere('transactions.account_label', 'like', "%{$accountSearch}%");
            })
            ->selectRaw("
                transactions.date,
                transactions.unique_no,
                transactions.invoice_no,
                transactions.type,
                transactions.description,
                COALESCE(chart_of_accounts.name, transactions.account_label) as account,
                COALESCE(vendors.name, customers.name) as counterparty,
                COALESCE(transactions.debit, 0) as debit,
                COALESCE(transactions.credit, 0) as credit
            ")
            ->orderByDesc('transactions.date')
            ->orderByDesc('transactions.id')
            ->limit($limit)
            ->get();
    }

    public function getCashBankTransactions(int $companyId, int $limit = 20)
    {
        return DB::table('transactions')
            ->join('chart_of_accounts', 'transactions.account_id', '=', 'chart_of_accounts.id')
            ->leftJoin('vendors', 'transactions.vendor_id', '=', 'vendors.id')
            ->leftJoin('customers', 'transactions.customer_id', '=', 'customers.id')
            ->where('transactions.company_id', $companyId)
            ->where('transactions.status', 1)
            ->whereNull('transactions.deleted_at')
            ->where(function ($q) {
                $q->where('chart_of_accounts.sub_type', 'BANK')
                    ->orWhere('chart_of_accounts.system_account_type', 'BANK')
                    ->orWhere('chart_of_accounts.name', 'like', '%bank%')
                    ->orWhere('chart_of_accounts.name', 'like', '%cash%');
            })
            ->selectRaw("
                transactions.date,
                transactions.unique_no,
                transactions.type,
                transactions.description,
                chart_of_accounts.name as account,
                COALESCE(vendors.name, customers.name) as counterparty,
                COALESCE(transactions.debit, 0) as debit,
                COALESCE(transactions.credit, 0) as credit
            ")
            ->orderByDesc('transactions.date')
            ->orderByDesc('transactions.id')
            ->limit($limit)
            ->get();
    }

    protected function getCategoryActivityFromTransactions($startDate, $endDate, int $companyId, array $categories): array
    {
        $summary = $this->baseTransactionAccountQuery($companyId)
            ->whereIn('chart_of_accounts.category', $categories)
            ->whereBetween('transactions.date', [$startDate, $endDate])
            ->selectRaw("
                COUNT(*) as line_count,
                COALESCE(SUM(transactions.debit), 0) as debit,
                COALESCE(SUM(transactions.credit), 0) as credit,
                COALESCE(SUM(
                    CASE
                        WHEN chart_of_accounts.category IN ('Assets', 'Expenses', 'COGS')
                        THEN COALESCE(transactions.debit, 0) - COALESCE(transactions.credit, 0)
                        ELSE COALESCE(transactions.credit, 0) - COALESCE(transactions.debit, 0)
                    END
                ), 0) as amount
            ")
            ->first();

        return [
            'line_count' => (int) ($summary->line_count ?? 0),
            'debit' => (float) ($summary->debit ?? 0),
            'credit' => (float) ($summary->credit ?? 0),
            'amount' => (float) ($summary->amount ?? 0),
        ];
    }

    protected function getCategoryBalanceFromTransactions($endDate, int $companyId, array $categories): array
    {
        return $this->getFilteredBalanceFromTransactions($endDate, $companyId, function ($q) use ($categories) {
            $q->whereIn('chart_of_accounts.category', $categories);
        });
    }

    protected function getSystemAccountBalanceFromTransactions($endDate, int $companyId, array $systemTypes): array
    {
        return $this->getFilteredBalanceFromTransactions($endDate, $companyId, function ($q) use ($systemTypes) {
            $q->whereIn('chart_of_accounts.system_account_type', $systemTypes);
        });
    }

    protected function getFilteredBalanceFromTransactions($endDate, int $companyId, callable $accountFilter): array
    {
        $query = $this->baseTransactionAccountQuery($companyId)
            ->where('transactions.date', '<=', $endDate);

        $query->where(function ($q) use ($accountFilter) {
            $accountFilter($q);
        });

        $summary = $query
            ->selectRaw("
                COUNT(*) as line_count,
                COALESCE(SUM(transactions.debit), 0) as debit,
                COALESCE(SUM(transactions.credit), 0) as credit,
                COALESCE(SUM(
                    CASE
                        WHEN chart_of_accounts.category IN ('Assets', 'Expenses', 'COGS')
                        THEN COALESCE(transactions.debit, 0) - COALESCE(transactions.credit, 0)
                        ELSE COALESCE(transactions.credit, 0) - COALESCE(transactions.debit, 0)
                    END
                ), 0) as balance
            ")
            ->first();

        return [
            'line_count' => (int) ($summary->line_count ?? 0),
            'debit' => (float) ($summary->debit ?? 0),
            'credit' => (float) ($summary->credit ?? 0),
            'balance' => (float) ($summary->balance ?? 0),
        ];
    }

    protected function baseTransactionAccountQuery(int $companyId)
    {
        return DB::table('transactions')
            ->join('chart_of_accounts', 'transactions.account_id', '=', 'chart_of_accounts.id')
            ->where('transactions.company_id', $companyId)
            ->where('transactions.status', 1)
            ->whereNull('transactions.deleted_at');
    }

    /**
     * Compare metrics over time.
     */
    public function getGrowthTrend($metric, $companyId, $months = 12): array
    {
        $data = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $start = now()->subMonths($i)->startOfMonth()->toDateString();
            $end = now()->subMonths($i)->endOfMonth()->toDateString();
            $label = now()->subMonths($i)->format('M Y');

            $value = match ($metric) {
                'revenue' => $this->getRevenue($start, $end, $companyId),
                'labor_cost' => $this->getLaborCost($start, $end, $companyId),
                'expenses' => $this->getExpenses($start, $end, $companyId),
                'statutory' => $this->getStatutoryLiabilities($start, $end, $companyId),
                default => 0,
            };

            $data[] = ['label' => $label, 'value' => $value];
        }

        return $data;
    }

    /**
     * Build a comprehensive source-of-truth context string for the AI assistant.
     */
    public function buildFullContext($companyId): string
    {
        $now = now();
        $thisMonthStart = $now->copy()->startOfMonth()->toDateString();
        $thisMonthEnd = $now->copy()->endOfMonth()->toDateString();
        $lastMonthStart = $now->copy()->subMonth()->startOfMonth()->toDateString();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth()->toDateString();
        $ytdStart = $now->copy()->startOfYear()->toDateString();

        $revThisMonth = $this->getRevenue($thisMonthStart, $thisMonthEnd, $companyId);
        $revLastMonth = $this->getRevenue($lastMonthStart, $lastMonthEnd, $companyId);
        $revYTD = $this->getRevenue($ytdStart, $thisMonthEnd, $companyId);
        $expenseThisMonth = $this->getExpenses($thisMonthStart, $thisMonthEnd, $companyId);
        $expenseYTD = $this->getExpenses($ytdStart, $thisMonthEnd, $companyId);
        $payrollThisMonth = $this->getPayrollSummary($thisMonthStart, $thisMonthEnd, $companyId);
        $payrollYTD = $this->getPayrollSummary($ytdStart, $thisMonthEnd, $companyId);
        $statYTD = $this->getStatutoryLiabilities($ytdStart, $thisMonthEnd, $companyId);
        $transactionSummary = $this->getTransactionSummary($thisMonthStart, $thisMonthEnd, $companyId);
        $transactionPL = $this->getTransactionProfitLoss($thisMonthStart, $thisMonthEnd, $companyId);
        $financialSummary = $this->getFinancialActivitySummary($thisMonthStart, $thisMonthEnd, $companyId);

        $context = [];
        $context[] = "=== FINANCIAL INTELLIGENCE CONTEXT ===";
        $context[] = "Company ID: {$companyId}";
        $context[] = "Report Date: " . $now->format('M d, Y');
        $context[] = "";

        $context[] = "=== ACCOUNTING OVERVIEW ===";
        $context[] = "Revenue (This Month): " . number_format($revThisMonth, 2);
        $context[] = "Revenue (Last Month): " . number_format($revLastMonth, 2);
        $context[] = "Revenue (YTD): " . number_format($revYTD, 2);
        $context[] = "Expenses (This Month): " . number_format($expenseThisMonth, 2);
        $context[] = "Expenses (YTD): " . number_format($expenseYTD, 2);
        $context[] = "Net Operating Result (This Month): " . number_format($revThisMonth - $expenseThisMonth, 2);
        $context[] = "Outstanding Receivables: " . number_format($this->getReceivablesOutstanding($thisMonthEnd, $companyId), 2);
        $context[] = "Outstanding Payables: " . number_format($this->getPayablesOutstanding($thisMonthEnd, $companyId), 2);
        $context[] = "";

        $context[] = "=== FINANCIAL POSITION FROM TRANSACTIONS ===";
        $context[] = "Cash/Bank Balance: " . number_format($financialSummary['cash_balance'], 2);
        $context[] = "Accounts Receivable: " . number_format($financialSummary['accounts_receivable'], 2);
        $context[] = "Accounts Payable: " . number_format($financialSummary['accounts_payable'], 2);
        $context[] = "Assets: " . number_format($financialSummary['assets'], 2);
        $context[] = "Liabilities: " . number_format($financialSummary['liabilities'], 2);
        $context[] = "Equity: " . number_format($financialSummary['equity'], 2);
        $context[] = "";

        $context[] = "=== TRANSACTION LEDGER OVERVIEW (Current Month) ===";
        $context[] = "Transaction Lines: " . $transactionSummary['transaction_count'];
        $context[] = "Journal References: " . $transactionSummary['journal_count'];
        $context[] = "Total Debits: " . number_format($transactionSummary['total_debit'], 2);
        $context[] = "Total Credits: " . number_format($transactionSummary['total_credit'], 2);
        $context[] = "Debit/Credit Variance: " . number_format($transactionSummary['variance'], 2);
        $context[] = "Revenue from Transactions: " . number_format($transactionPL['revenue'], 2);
        $context[] = "Expenses from Transactions: " . number_format($transactionPL['expenses'], 2);
        $context[] = "COGS from Transactions: " . number_format($transactionPL['cogs'], 2);
        $context[] = "Net Profit from Transactions: " . number_format($transactionPL['net_profit'], 2);
        $context[] = "";

        $context[] = "=== PAYROLL OVERVIEW ===";
        $context[] = "Gross Payroll (This Month): " . number_format($payrollThisMonth['gross_pay'], 2);
        $context[] = "Net Payroll (This Month): " . number_format($payrollThisMonth['net_pay'], 2);
        $context[] = "Payroll Deductions (This Month): " . number_format($payrollThisMonth['deductions'], 2);
        $context[] = "Payroll Headcount (This Month): " . $payrollThisMonth['employee_count'];
        $context[] = "Gross Payroll (YTD): " . number_format($payrollYTD['gross_pay'], 2);
        $context[] = "Net Payroll (YTD): " . number_format($payrollYTD['net_pay'], 2);
        $context[] = "Statutory Deductions (YTD): " . number_format($statYTD, 2);
        $context[] = "Labor % of Revenue (This Month): " . ($revThisMonth > 0 ? round(($payrollThisMonth['gross_pay'] / $revThisMonth) * 100, 2) : 0) . "%";
        $context[] = "";

        $context[] = "=== DEPARTMENTAL LABOR COST (Current Month) ===";
        foreach ($this->getDepartmentLaborCost($thisMonthStart, $thisMonthEnd, $companyId) as $dept) {
            $context[] = "- {$dept->department}: " . number_format((float) $dept->gross_pay, 2)
                . " gross across {$dept->employees} employee(s)";
        }
        $context[] = "";

        $context[] = "=== RECENT PAY RUNS ===";
        foreach ($this->getRecentPayRuns($companyId, 5) as $run) {
            $context[] = "- " . ($run->unique_no ?: 'Pay run')
                . " {$run->period_start} to {$run->period_end}: "
                . number_format((float) $run->gross_pay, 2)
                . " gross, " . number_format((float) $run->net_pay, 2)
                . " net, status {$run->status}";
        }

        return implode("\n", $context);
    }

    public function answerDetailedQuery(string $query, int $companyId): ?array
    {
        $parsed = (new QueryIntentParser())->parse($query);
        $queryLower = strtolower($query);
        $start = $parsed['start_date'];
        $end = $parsed['end_date'];
        $label = $parsed['date_range_label'] ?? $parsed['date_label'];

        if (str_contains($queryLower, 'financial summary') || str_contains($queryLower, 'financial activity') || str_contains($queryLower, 'financial activities')) {
            return [
                'message' => "Financial activity summary for {$label}, using active transaction lines where available.",
                'type' => 'summary_card',
                'data' => $this->getFinancialActivitySummary($start, $end, $companyId),
                'columns' => ['revenue', 'cogs', 'gross_profit', 'expenses', 'net_profit', 'cash_balance', 'accounts_receivable', 'accounts_payable', 'assets', 'liabilities', 'equity'],
            ];
        }

        if (str_contains($queryLower, 'cogs') || str_contains($queryLower, 'cost of goods')) {
            return [
                'message' => "COGS for {$label} from active transaction lines.",
                'type' => 'stat',
                'value' => $this->getCOGS($start, $end, $companyId),
            ];
        }

        if (str_contains($queryLower, 'asset')) {
            return [
                'message' => "Total assets as of {$end}, from active transaction lines.",
                'type' => 'stat',
                'value' => $this->getAssetsBalance($end, $companyId),
            ];
        }

        if (str_contains($queryLower, 'liabilit')) {
            return [
                'message' => "Total liabilities as of {$end}, from active transaction lines.",
                'type' => 'stat',
                'value' => $this->getLiabilitiesBalance($end, $companyId),
            ];
        }

        if (str_contains($queryLower, 'equity')) {
            return [
                'message' => "Total equity as of {$end}, from active transaction lines.",
                'type' => 'stat',
                'value' => $this->getEquityBalance($end, $companyId),
            ];
        }

        if (str_contains($queryLower, 'account balance') || str_contains($queryLower, 'trial balance')) {
            return [
                'message' => "Account balances from active transaction lines for {$label}.",
                'type' => 'query_result',
                'visualization' => 'table',
                'data' => $this->getAccountBalancesFromTransactions($start, $end, $companyId),
                'columns' => ['account_number', 'account_name', 'category', 'sub_type', 'debit', 'credit', 'balance'],
            ];
        }

        if (str_contains($queryLower, 'transaction') || str_contains($queryLower, 'journal') || str_contains($queryLower, 'ledger')) {
            if (str_contains($queryLower, 'summary') || str_contains($queryLower, 'balance') || str_contains($queryLower, 'account')) {
                return [
                    'message' => "Account balances from active transaction lines for {$label}.",
                    'type' => 'query_result',
                    'visualization' => 'table',
                    'data' => $this->getAccountBalancesFromTransactions($start, $end, $companyId),
                    'columns' => ['account_number', 'account_name', 'category', 'sub_type', 'debit', 'credit', 'balance'],
                ];
            }

            return [
                'message' => 'Recent active transaction lines from the ledger.',
                'type' => 'query_result',
                'visualization' => 'table',
                'data' => $this->getRecentTransactions($companyId),
                'columns' => ['date', 'unique_no', 'invoice_no', 'type', 'description', 'account', 'category', 'counterparty', 'debit', 'credit'],
            ];
        }

        if (str_contains($queryLower, 'profit') || str_contains($queryLower, 'p&l') || str_contains($queryLower, 'profit and loss')) {
            return [
                'message' => "Profit and loss from active transaction lines for {$label}.",
                'type' => 'summary_card',
                'data' => $this->getTransactionProfitLoss($start, $end, $companyId),
                'columns' => ['revenue', 'cogs', 'gross_profit', 'expenses', 'net_profit'],
            ];
        }

        if ((str_contains($queryLower, 'cash') || str_contains($queryLower, 'bank')) && (str_contains($queryLower, 'total') || str_contains($queryLower, 'balance'))) {
            return [
                'message' => "Cash/bank balance as of {$end}, from active transaction lines.",
                'type' => 'stat',
                'value' => $this->getCashBalance($end, $companyId),
            ];
        }

        if (str_contains($queryLower, 'cash') || str_contains($queryLower, 'bank')) {
            return [
                'message' => 'Recent active cash/bank transaction lines.',
                'type' => 'query_result',
                'visualization' => 'table',
                'data' => $this->getCashBankTransactions($companyId),
                'columns' => ['date', 'unique_no', 'type', 'description', 'account', 'counterparty', 'debit', 'credit'],
            ];
        }

        if (
            (str_contains($queryLower, 'trend') || str_contains($queryLower, 'compare') || str_contains($queryLower, 'over time'))
            && (str_contains($queryLower, 'revenue') || str_contains($queryLower, 'expense') || str_contains($queryLower, 'payroll'))
        ) {
            return [
                'message' => 'Revenue, expense, and payroll trend over the past 12 months.',
                'type' => 'chart',
                'chart_type' => 'line',
                'visualization' => 'line_chart',
                'data' => $this->getMonthlyAccountingPayrollTrend($companyId),
                'columns' => ['label', 'revenue', 'expenses', 'payroll'],
            ];
        }

        if (str_contains($queryLower, 'department') && (str_contains($queryLower, 'labor') || str_contains($queryLower, 'payroll'))) {
            $rows = $this->getDepartmentLaborCost($start, $end, $companyId);

            return [
                'message' => "Department labor cost for {$label}, based on posted/paid payroll.",
                'type' => 'query_result',
                'visualization' => 'bar_chart',
                'data' => $rows,
                'columns' => ['department', 'employees', 'gross_pay', 'deductions', 'net_pay'],
            ];
        }

        if (str_contains($queryLower, 'statutory') || str_contains($queryLower, 'deduction')) {
            $rows = $this->getStatutoryBreakdown($start, $end, $companyId);

            return [
                'message' => "Statutory deduction breakdown for {$label}.",
                'type' => 'query_result',
                'visualization' => 'bar_chart',
                'data' => $rows,
                'columns' => ['statutory_body', 'employees', 'amount'],
            ];
        }

        if (str_contains($queryLower, 'pay run') || str_contains($queryLower, 'payroll run')) {
            return [
                'message' => 'Recent posted/paid pay runs.',
                'type' => 'query_result',
                'visualization' => 'table',
                'data' => $this->getRecentPayRuns($companyId),
                'columns' => ['unique_no', 'period_start', 'period_end', 'payment_date', 'status', 'payslips', 'gross_pay', 'net_pay'],
            ];
        }

        if ((str_contains($queryLower, 'receivable') || str_contains($queryLower, 'unpaid sale') || str_contains($queryLower, 'unpaid invoice')) && (str_contains($queryLower, 'total') || str_contains($queryLower, 'balance') || str_contains($queryLower, 'outstanding'))) {
            return [
                'message' => "Accounts receivable outstanding as of {$end}, using active transaction lines where available.",
                'type' => 'stat',
                'value' => $this->getReceivablesOutstanding($end, $companyId),
            ];
        }

        if (str_contains($queryLower, 'receivable') || str_contains($queryLower, 'unpaid sale') || str_contains($queryLower, 'unpaid invoice')) {
            return [
                'message' => 'Top outstanding customer receivables from approved sales.',
                'type' => 'query_result',
                'visualization' => 'table',
                'data' => $this->getTopReceivables($companyId),
                'columns' => ['invoice_no', 'date', 'customer', 'outstanding'],
            ];
        }

        if ((str_contains($queryLower, 'payable') || str_contains($queryLower, 'unpaid bill')) && (str_contains($queryLower, 'total') || str_contains($queryLower, 'balance') || str_contains($queryLower, 'outstanding'))) {
            return [
                'message' => "Accounts payable outstanding as of {$end}, using active transaction lines where available.",
                'type' => 'stat',
                'value' => $this->getPayablesOutstanding($end, $companyId),
            ];
        }

        if (str_contains($queryLower, 'payable') || str_contains($queryLower, 'unpaid bill') || str_contains($queryLower, 'vendor')) {
            return [
                'message' => 'Top outstanding vendor payables from approved bills.',
                'type' => 'query_result',
                'visualization' => 'table',
                'data' => $this->getTopPayables($companyId),
                'columns' => ['invoice_no', 'date', 'vendor', 'outstanding'],
            ];
        }

        if (str_contains($queryLower, 'headcount') || str_contains($queryLower, 'how many employee')) {
            $summary = $this->getPayrollSummary($start, $end, $companyId);

            return [
                'message' => "Payroll headcount for {$label} was {$summary['employee_count']} employee(s) across {$summary['payslip_count']} payslip(s).",
                'type' => 'summary_card',
                'data' => $summary,
                'columns' => ['employee_count', 'payslip_count', 'gross_pay', 'net_pay'],
            ];
        }

        return null;
    }

    /**
     * Generate dynamic suggestion questions based on current data.
     */
    public function generateSuggestions($companyId): array
    {
        $now = now();
        $thisMonthStart = $now->copy()->startOfMonth()->toDateString();
        $thisMonthEnd = $now->copy()->endOfMonth()->toDateString();
        $lastMonthStart = $now->copy()->subMonth()->startOfMonth()->toDateString();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth()->toDateString();

        $revThisMonth = $this->getRevenue($thisMonthStart, $thisMonthEnd, $companyId);
        $laborThisMonth = $this->getLaborCost($thisMonthStart, $thisMonthEnd, $companyId);
        $revLastMonth = $this->getRevenue($lastMonthStart, $lastMonthEnd, $companyId);

        $suggestions = [];

        if ($revThisMonth > 0) {
            $ratio = ($laborThisMonth / $revThisMonth) * 100;
            if ($ratio > 30) {
                $suggestions[] = "Why is our labor cost high (" . round($ratio, 1) . "%) relative to revenue this month?";
            } else {
                $suggestions[] = "Analyze our labor cost efficiency for " . $now->format('F');
            }
        }

        if ($revThisMonth > 0 && $revLastMonth > 0) {
            $growth = (($revThisMonth - $revLastMonth) / $revLastMonth) * 100;
            if ($growth > 5) {
                $suggestions[] = "What drove our " . round($growth, 1) . "% revenue growth since last month?";
            } elseif ($growth < -5) {
                $suggestions[] = "Why did revenue decline by " . round(abs($growth), 1) . "% compared to last month?";
            }
        }

        if ($this->getStatutoryLiabilities($thisMonthStart, $thisMonthEnd, $companyId) > 0) {
            $suggestions[] = "Break down our statutory liabilities for " . $now->format('F');
        }

        $suggestions[] = "Show department labor cost this month";
        $suggestions[] = "List top outstanding receivables";
        $suggestions[] = "Show recent pay runs";

        shuffle($suggestions);

        return array_slice($suggestions, 0, 3);
    }
}
