<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\BankStatementLine;
use App\Models\Bill;
use App\Models\ChartOfAccount;
use App\Models\PayRun;
use App\Models\Sale;
use App\Models\Transaction;
use App\Models\UserDashboardPreference;
use App\Services\Account\AnomalyDetectionService;
use App\Services\PayrollService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    private const ACCOUNT_DASHBOARD_KEY = 'accounting_dashboard';

    private function accountDashboardWidgets(): array
    {
        return [
            'anomaly_alert' => ['label' => 'Anomaly Alert', 'size' => 'full'],
            'payroll_accounting_requests' => ['label' => 'Payroll Requests', 'size' => 'full'],
            'total_revenue' => ['label' => 'Total Revenue', 'size' => 'quarter'],
            'gross_profit' => ['label' => 'Gross Profit', 'size' => 'quarter'],
            'net_income' => ['label' => 'Net Income', 'size' => 'quarter'],
            'operating_expenses' => ['label' => 'Operating Expenses', 'size' => 'quarter'],
            'finance_tax_costs' => ['label' => 'Finance & Tax Costs', 'size' => 'quarter'],
            'total_expenses' => ['label' => 'Total Expenses', 'size' => 'quarter'],
            'cash_balance' => ['label' => 'Cash Balance', 'size' => 'third'],
            'ar_outstanding' => ['label' => 'AR Outstanding', 'size' => 'third'],
            'ap_outstanding' => ['label' => 'AP Outstanding', 'size' => 'third'],
            'dso' => ['label' => 'Days Sales Outstanding', 'size' => 'third'],
            'dpo' => ['label' => 'Days Payable Outstanding', 'size' => 'third'],
            'top_overdue_customers' => ['label' => 'Top Overdue Customers', 'size' => 'third'],
            'revenue_expenses' => ['label' => 'Revenue vs Expenses', 'size' => 'half'],
            'cash_flow' => ['label' => 'Cash In & Out', 'size' => 'half'],
            'invoices' => ['label' => 'Invoices', 'size' => 'half'],
            'bills' => ['label' => 'Bills', 'size' => 'half'],
            'bank_accounts' => ['label' => 'Bank Accounts', 'size' => 'full'],
        ];
    }

    private function dashboardPreferenceQuery()
    {
        return UserDashboardPreference::query()
            ->where('user_id', auth()->id())
            ->where('company_id', loggedCompanyId()['company_id'])
            ->where('dashboard_key', self::ACCOUNT_DASHBOARD_KEY);
    }

    private function resolveDashboardLayout(array $widgets, ?array $preferences = null): array
    {
        $defaultOrder = array_keys($widgets);
        $savedOrder = collect($preferences['order'] ?? [])
            ->filter(fn($id) => isset($widgets[$id]))
            ->values()
            ->all();
        $hidden = collect($preferences['hidden'] ?? [])
            ->filter(fn($id) => isset($widgets[$id]))
            ->unique()
            ->values()
            ->all();

        $ordered = collect($savedOrder)
            ->merge(collect($defaultOrder)->reject(fn($id) => in_array($id, $savedOrder, true)))
            ->unique()
            ->values()
            ->all();

        $visible = collect($ordered)
            ->reject(fn($id) => in_array($id, $hidden, true))
            ->values()
            ->all();

        return [
            'order' => $visible,
            'hidden' => $hidden,
            'render_order' => collect($visible)->merge($hidden)->unique()->values()->all(),
        ];
    }

    private function parseRange($range, $prefix = '')
    {
        if ($range === 'CUSTOM') {
            return [
                request($prefix . 'start_date') ?: null,
                request($prefix . 'end_date') ?: null,
            ];
        }

        if (!$range || !str_contains($range, '|')) {
            return [null, null];
        }

        [$start, $end] = explode('|', $range);

        return [
            trim($start) ?: null,
            trim($end) ?: null,
        ];
    }

    public function index(Request $request)
    {
        $data = [];
        $companyId = loggedCompanyId()['company_id'];

        [$saleStart, $saleEnd] = $this->parseRange($request->sale_per_date, 'sale_');
        [$billStart, $billEnd] = $this->parseRange($request->bill_per_date, 'bill_');
        [$startDate, $endDate] = $this->parseRange($request->per_date);

        // Default to current month if no date filter
        if (!$startDate || !$endDate) {
            $startDate = now()->startOfMonth()->toDateString();
            $endDate = now()->endOfMonth()->toDateString();
        }

        // Determine chart date range based on filter
        try {
            $chartStartDate = Carbon::parse($startDate)->startOfDay();
            $chartEndDate = Carbon::parse($endDate)->endOfDay();
        } catch (\Exception $e) {
            // Fallback to current month if parsing fails
            $chartStartDate = now()->startOfMonth();
            $chartEndDate = now()->endOfMonth();
        }

        // Ensure start <= end
        if ($chartStartDate > $chartEndDate) {
            [$chartStartDate, $chartEndDate] = [$chartEndDate, $chartStartDate];
        }

        // Limit to max 365 days to prevent too many data points
        $daysDiff = (int) $chartStartDate->diffInDays($chartEndDate);
        if ($daysDiff > 365) {
            $daysDiff = 365;
            $chartEndDate = $chartStartDate->copy()->addDays(365);
        }

        $txnQuery = Transaction::where('company_id', $companyId)
            ->where('status', 1)
            ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                $q->whereDate('date', '>=', $startDate)
                    ->whereDate('date', '<=', $endDate);
            });

        $profitLossAccounts = ChartOfAccount::forCompany($companyId)
            ->whereIn('category', ['Revenue', 'COGS', 'Expenses'])
            ->get();

        $otherIncomeGroupIds = $profitLossAccounts->filter(
            fn($account) =>
            $account->category === 'Revenue' &&
                $account->type === 'Group' &&
                ($account->sub_type === 'OTHER_INCOME' ||
                    str_contains(strtolower($account->name), 'other income') ||
                    str_contains(strtolower($account->name), 'other revenue'))
        )->pluck('id');

        $financeCostGroupIds = $profitLossAccounts->filter(
            fn($account) =>
            $account->category === 'Expenses' &&
                $account->type === 'Group' &&
                ($account->sub_type === 'FINANCE_COST' ||
                    str_contains(strtolower($account->name), 'finance') ||
                    str_contains(strtolower($account->name), 'interest') ||
                    str_contains(strtolower($account->name), 'bank fee') ||
                    str_contains(strtolower($account->name), 'bank charge'))
        )->pluck('id');

        $isOtherIncome = function ($account) use ($otherIncomeGroupIds) {
            return $account->sub_type === 'OTHER_INCOME' || $otherIncomeGroupIds->contains($account->parent_id);
        };

        $isFinanceCost = function ($account) use ($financeCostGroupIds) {
            $name = strtolower($account->name);

            return $account->sub_type === 'FINANCE_COST' ||
                $financeCostGroupIds->contains($account->parent_id) ||
                str_contains($name, 'interest expense') ||
                str_contains($name, 'finance cost') ||
                str_contains($name, 'finance charge') ||
                str_contains($name, 'bank fee') ||
                str_contains($name, 'bank charge') ||
                str_contains($name, 'foreign exchange loss');
        };

        $isTaxExpense = function ($account) {
            $name = strtolower($account->name);

            return $account->sub_type === 'TAX_EXPENSE' ||
                str_contains($name, 'income tax') ||
                str_contains($name, 'tax expense') ||
                str_contains($name, 'corporate tax') ||
                str_contains($name, 'provision for tax');
        };

        $revenueAccounts = $profitLossAccounts->where('category', 'Revenue')->where('type', 'Account');
        $expenseAccounts = $profitLossAccounts->where('category', 'Expenses')->where('type', 'Account');

        $revenueIds = $revenueAccounts->reject(fn($account) => $isOtherIncome($account))->pluck('id');
        $otherIncomeIds = $revenueAccounts->filter(fn($account) => $isOtherIncome($account))->pluck('id');
        $cogsIds = $profitLossAccounts->where('category', 'COGS')->where('type', 'Account')->pluck('id');
        $financeCostIds = $expenseAccounts->filter(fn($account) => $isFinanceCost($account))->pluck('id');
        $taxExpenseIds = $expenseAccounts
            ->reject(fn($account) => $isFinanceCost($account))
            ->filter(fn($account) => $isTaxExpense($account))
            ->pluck('id');
        $expenseIds = $expenseAccounts->reject(fn($account) => $isFinanceCost($account) || $isTaxExpense($account))->pluck('id');

        $overallAggregates = (clone $txnQuery)
            ->whereIn(
                'account_id',
                $revenueIds->merge($otherIncomeIds)
                    ->merge($cogsIds)
                    ->merge($expenseIds)
                    ->merge($financeCostIds)
                    ->merge($taxExpenseIds)
            )
            ->select(
                'account_id',
                DB::raw('SUM(debit) as total_debit'),
                DB::raw('SUM(credit) as total_credit')
            )
            ->groupBy('account_id')
            ->get();

        $creditNormalTotal = function ($rows, $accountIds) {
            return $rows->whereIn('account_id', $accountIds)
                ->sum(fn($row) => (float) $row->total_credit - (float) $row->total_debit);
        };

        $debitNormalTotal = function ($rows, $accountIds) {
            return $rows->whereIn('account_id', $accountIds)
                ->sum(fn($row) => (float) $row->total_debit - (float) $row->total_credit);
        };

        $totalRevenue = $creditNormalTotal($overallAggregates, $revenueIds);
        $totalOtherIncome = $creditNormalTotal($overallAggregates, $otherIncomeIds);
        $totalCOGS = $debitNormalTotal($overallAggregates, $cogsIds);
        $totalOperatingExpenses = $debitNormalTotal($overallAggregates, $expenseIds);
        $totalFinanceCosts = $debitNormalTotal($overallAggregates, $financeCostIds);
        $totalTaxExpenses = $debitNormalTotal($overallAggregates, $taxExpenseIds);
        $totalExpenses = $totalOperatingExpenses + $totalFinanceCosts + $totalTaxExpenses;

        $netIncome = ($totalRevenue + $totalOtherIncome)
            - ($totalCOGS + $totalExpenses);

        $data['totalRevenue'] = $totalRevenue;
        $data['totalOtherIncome'] = $totalOtherIncome;
        $data['totalCOGS'] = $totalCOGS;
        $data['totalExpenses'] = $totalExpenses;
        $data['totalOperatingExpenses'] = $totalOperatingExpenses;
        $data['totalFinanceCosts'] = $totalFinanceCosts;
        $data['totalTaxExpenses'] = $totalTaxExpenses;
        $data['netIncome'] = $netIncome;

        // $daysDiff already computed and capped at 365 above — no recalculation needed
        if ($daysDiff <= 0) {
            $days = collect([0]);
        } else {
            $days = collect(range(0, $daysDiff));
        }
        $data['chartLabels'] = $days->map(function ($d) use ($chartStartDate) {
            return $chartStartDate->copy()->addDays($d)->format('M d');
        })->values();

        // Optimization: Fetch all daily aggregates in one query
        $allChartAccountIds = $revenueIds->merge($otherIncomeIds)
            ->merge($cogsIds)
            ->merge($expenseIds)
            ->merge($financeCostIds)
            ->merge($taxExpenseIds);

        // Fetch daily sums grouped by date and account_id
        $dailyAggregates = Transaction::where('company_id', $companyId)
            ->where('status', 1)
            ->whereDate('date', '>=', $chartStartDate->toDateString())
            ->whereDate('date', '<=', $chartEndDate->toDateString())
            ->whereIn('account_id', $allChartAccountIds)
            ->select(
                DB::raw('CAST(date AS DATE) as txn_date'),
                'account_id',
                DB::raw('SUM(debit) as total_debit'),
                DB::raw('SUM(credit) as total_credit')
            )
            ->groupBy(DB::raw('CAST(date AS DATE)'), 'account_id')
            ->get()
            ->groupBy('txn_date');

        $data['revenueChartData'] = $days->map(function ($d) use ($dailyAggregates, $revenueIds, $chartStartDate, $creditNormalTotal) {
            $date = $chartStartDate->copy()->addDays($d)->format('Y-m-d');
            $dayData = $dailyAggregates->get($date, collect());
            return $creditNormalTotal($dayData, $revenueIds);
        });

        $data['expenseChartData'] = $days->map(function ($d) use ($dailyAggregates, $expenseIds, $financeCostIds, $taxExpenseIds, $chartStartDate, $debitNormalTotal) {
            $date = $chartStartDate->copy()->addDays($d)->format('Y-m-d');
            $dayData = $dailyAggregates->get($date, collect());

            return $debitNormalTotal($dayData, $expenseIds)
                + $debitNormalTotal($dayData, $financeCostIds)
                + $debitNormalTotal($dayData, $taxExpenseIds);
        });

        $data['operatingExpensesChartData'] = $days->map(function ($d) use ($dailyAggregates, $expenseIds, $chartStartDate, $debitNormalTotal) {
            $date = $chartStartDate->copy()->addDays($d)->format('Y-m-d');
            $dayData = $dailyAggregates->get($date, collect());

            return $debitNormalTotal($dayData, $expenseIds);
        });

        $data['financeTaxCostsChartData'] = $days->map(function ($d) use ($dailyAggregates, $financeCostIds, $taxExpenseIds, $chartStartDate, $debitNormalTotal) {
            $date = $chartStartDate->copy()->addDays($d)->format('Y-m-d');
            $dayData = $dailyAggregates->get($date, collect());

            return $debitNormalTotal($dayData, $financeCostIds)
                + $debitNormalTotal($dayData, $taxExpenseIds);
        });

        $data['incomeChartData'] = $days->map(function ($d) use ($dailyAggregates, $revenueIds, $otherIncomeIds, $cogsIds, $expenseIds, $financeCostIds, $taxExpenseIds, $chartStartDate, $creditNormalTotal, $debitNormalTotal) {
            $date = $chartStartDate->copy()->addDays($d)->format('Y-m-d');
            $dayData = $dailyAggregates->get($date, collect());

            $rev = $creditNormalTotal($dayData, $revenueIds);
            $other = $creditNormalTotal($dayData, $otherIncomeIds);
            $cogs = $debitNormalTotal($dayData, $cogsIds);
            $exp = $debitNormalTotal($dayData, $expenseIds);
            $finance = $debitNormalTotal($dayData, $financeCostIds);
            $tax = $debitNormalTotal($dayData, $taxExpenseIds);

            return ($rev + $other) - ($cogs + $exp + $finance + $tax);
        });

        // Gross Profit & Gross Margin
        $grossProfit = $totalRevenue - $totalCOGS;
        $grossMargin = ($totalRevenue > 0) ? round(($grossProfit / $totalRevenue) * 100, 1) : 0;
        $data['grossProfit'] = $grossProfit;
        $data['grossMargin'] = $grossMargin;

        // Gross Profit daily chart data (Revenue - COGS per day)
        $data['grossProfitChartData'] = $days->map(function ($d) use ($dailyAggregates, $revenueIds, $cogsIds, $chartStartDate, $creditNormalTotal, $debitNormalTotal) {
            $date = $chartStartDate->copy()->addDays($d)->format('Y-m-d');
            $dayData = $dailyAggregates->get($date, collect());
            $rev  = $creditNormalTotal($dayData, $revenueIds);
            $cogs = $debitNormalTotal($dayData, $cogsIds);
            return $rev - $cogs;
        });

        // Net Margin %
        $data['netMargin'] = ($totalRevenue > 0) ? round(($netIncome / $totalRevenue) * 100, 1) : 0;

        $saleQuery = Sale::with('customer')
            ->where('company_id', $companyId)
            ->latest();

        if ($saleStart && $saleEnd) {
            $saleQuery->whereDate('date', '>=', $saleStart)
                ->whereDate('date', '<=', $saleEnd);
        }

        $data['saleInvoices'] = $saleQuery->limit(10)->get();

        $billQuery = Bill::with('vendor')
            ->where('company_id', $companyId)
            ->latest();

        if ($billStart && $billEnd) {
            $billQuery->whereDate('date', '>=', $billStart)
                ->whereDate('date', '<=', $billEnd);
        }

        $data['billInvoices'] = $billQuery->limit(10)->get();

        // Fetch Bank Accounts — resolved by sub_type (Xero-style)
        $bankAccounts = ChartOfAccount::forCompany($companyId)
            ->where('sub_type', 'BANK')
            ->get();

        $bankIds = $bankAccounts->pluck('id');

        // Daily Cash Flow for filtered period
        $dailyCashAggregates = Transaction::where('company_id', $companyId)
            ->where('status', 1)
            ->whereDate('date', '>=', $chartStartDate->toDateString())
            ->whereDate('date', '<=', $chartEndDate->toDateString())
            ->whereIn('account_id', $bankIds)
            ->select(
                DB::raw('CAST(date AS DATE) as txn_date'),
                DB::raw('SUM(debit) as total_debit'),
                DB::raw('SUM(credit) as total_credit')
            )
            ->groupBy(DB::raw('CAST(date AS DATE)'))
            ->get()
            ->keyBy('txn_date');

        $cashFlowLabels = $data['chartLabels'];
        $cashInData = [];
        $cashOutData = [];

        foreach ($days as $d) {
            $date = $chartStartDate->copy()->addDays($d)->format('Y-m-d');
            $dayData = $dailyCashAggregates->get($date);
            $cashInData[] = $dayData ? $dayData->total_debit : 0;
            $cashOutData[] = $dayData ? $dayData->total_credit : 0;
        }

        $data['cashFlowLabels'] = $cashFlowLabels;
        $data['cashInData'] = $cashInData;
        $data['cashOutData'] = $cashOutData;



        if ($bankAccounts->isNotEmpty()) {
            $bankIds = $bankAccounts->pluck('id');

            // 1. Transaction Aggregates (Book Balance)
            $txnAggregates = Transaction::where('company_id', $companyId)
                ->where('status', 1)
                ->whereIn('account_id', $bankIds)
                ->select('account_id', DB::raw('SUM(debit) as total_debit'), DB::raw('SUM(credit) as total_credit'))
                ->groupBy('account_id')
                ->get()
                ->keyBy('account_id');

            // 2. Statement & Unreconciled Aggregates
            $statementAggregates = BankStatementLine::where('company_id', $companyId)
                ->whereIn('bank_account_id', $bankIds)
                ->select(
                    'bank_account_id',
                    DB::raw('SUM(amount) as total_amount'),
                    DB::raw("COUNT(CASE WHEN status = 'Unreconciled' THEN 1 END) as unreconciled_count")
                )
                ->groupBy('bank_account_id')
                ->get()
                ->keyBy('bank_account_id');

            // 4. Filtered Days Transactions (for Graph)
            // Get daily sums for each account
            $last30DaysGroups = Transaction::where('company_id', $companyId)
                ->where('status', 1)
                ->whereIn('account_id', $bankIds)
                ->whereDate('date', '>=', $chartStartDate->toDateString())
                ->whereDate('date', '<=', $chartEndDate->toDateString())
                ->select(
                    'account_id',
                    DB::raw('CAST(date AS DATE) as txn_date'),
                    DB::raw('SUM(debit) as day_debit'),
                    DB::raw('SUM(credit) as day_credit')
                )
                ->groupBy('account_id', DB::raw('CAST(date AS DATE)'))
                ->get()
                ->groupBy('account_id');

            foreach ($bankAccounts as $bank) {
                // Book Balance
                $txnStats = $txnAggregates->get($bank->id);
                $debits = $txnStats ? $txnStats->total_debit : 0;
                $credits = $txnStats ? $txnStats->total_credit : 0;
                $bank->book_balance = $debits - $credits;

                // Statement Balance
                $stmtStats = $statementAggregates->get($bank->id);
                $statementSum = $stmtStats ? $stmtStats->total_amount : 0;
                $bank->statement_balance = $statementSum;

                // Unreconciled Count
                $bank->unreconciled_count = $stmtStats ? $stmtStats->unreconciled_count : 0;

                // Graph Data
                $currentBalance = $bank->book_balance;

                // Get this bank's daily groups
                $bankDailyTxns = $last30DaysGroups->get($bank->id, collect());
                // Transform to simple lookup by date string
                $dateMap = $bankDailyTxns->keyBy('txn_date');

                $changeDebits = $bankDailyTxns->sum('day_debit');
                $changeCredits = $bankDailyTxns->sum('day_credit');

                // Start Balance = Current Balance - (Net Movement in 30 days)
                // Net Movement = Debits - Credits (for Assets)
                $startBalance = $currentBalance - ($changeDebits - $changeCredits);

                $runningBalance = $startBalance;
                $graphPoints = [];

                for ($i = 0; $i <= $daysDiff; $i++) {
                    $date = $chartStartDate->copy()->addDays($i)->format('Y-m-d');

                    $dayData = $dateMap->get($date);
                    $dayDebit = $dayData ? $dayData->day_debit : 0;
                    $dayCredit = $dayData ? $dayData->day_credit : 0;

                    $runningBalance = $runningBalance + $dayDebit - $dayCredit;
                    $graphPoints[] = $runningBalance;
                }

                $bank->graph_data = $graphPoints;
            }
        }

        $data['bankAccounts'] = $bankAccounts;

        // Total Cash Balance (sum of all bank account book balances)
        $data['totalCashBalance'] = $bankAccounts->sum('book_balance');

        // Accounts Receivable & Payable Outstanding
        $data['arOutstanding']    = Sale::where('company_id', $companyId)->where('approval_status', 'approved')->where('remaining_amount', '>', 0)->sum('remaining_amount');
        $data['apOutstanding']    = Bill::where('company_id', $companyId)->where('approval_status', 'approved')->where('remaining_amount', '>', 0)->sum('remaining_amount');
        $data['overdueCount']     = Sale::where('company_id', $companyId)->where('approval_status', 'approved')->where('remaining_amount', '>', 0)->whereDate('due_date', '<', today())->count();

        // ── DSO / DPO ─────────────────────────────────────────────────────────
        // Days Sales Outstanding = (AR / Revenue) × Days in period
        $periodDays = max(1, $chartStartDate->diffInDays($chartEndDate));
        $data['dso'] = $totalRevenue > 0
            ? round(($data['arOutstanding'] / $totalRevenue) * $periodDays, 1)
            : 0;

        // Days Payable Outstanding = (AP / COGS+Expenses) × Days in period
        $totalCosts = $totalCOGS + $totalExpenses;
        $data['dpo'] = $totalCosts > 0
            ? round(($data['apOutstanding'] / $totalCosts) * $periodDays, 1)
            : 0;

        // ── Top 5 Overdue Customers ───────────────────────────────────────────
        $data['topOverdueCustomers'] = Sale::with('customer')
            ->where('company_id', $companyId)
            ->where('approval_status', 'approved')
            ->where('remaining_amount', '>', 0)
            ->whereDate('due_date', '<', today())
            ->select('customer_id', DB::raw('SUM(remaining_amount) as total_overdue'), DB::raw('COUNT(*) as invoice_count'))
            ->groupBy('customer_id')
            ->orderByDesc('total_overdue')
            ->limit(5)
            ->get();

        // ── Anomaly Detection Summary ─────────────────────────────────────
        try {
            $anomalySummary = app(AnomalyDetectionService::class)->scan();
            $data['anomalySummary'] = $anomalySummary['summary'];
        } catch (\Throwable $e) {
            $data['anomalySummary'] = ['total' => 0, 'critical' => 0, 'warning' => 0, 'info' => 0];
        }

        $data['payrollAccountingRequests'] = PayRun::with(['createdBy', 'accountingNotifiedBy'])
            ->withCount('payslips')
            ->withSum('payslips as net_pay_total', 'net_pay')
            ->where('company_id', $companyId)
            ->where('status', 'draft')
            ->where('approval_status', 'approved')
            ->where('accounting_notification_status', 'notified')
            ->latest('accounting_notified_at')
            ->limit(5)
            ->get();

        $dashboardPreference = $this->dashboardPreferenceQuery()->first();
        $data['dashboardWidgetMeta'] = $this->accountDashboardWidgets();
        $data['dashboardLayout'] = $this->resolveDashboardLayout(
            $data['dashboardWidgetMeta'],
            $dashboardPreference?->preferences
        );

        return view('pages.account.dashboard', $data);
    }

    public function syncDocumentBalances()
    {
        $companyId = loggedCompanyId()['company_id'];

        Artisan::call('accounting:sync-document-balances', [
            '--company_id' => $companyId,
            '--user_id' => auth('web')->id(),
            '--source' => 'web',
        ]);

        $output = trim(Artisan::output());
        preg_match('/Sales updated:\s*(\d+)/', $output, $salesMatch);
        preg_match('/Bills updated:\s*(\d+)/', $output, $billsMatch);

        $salesUpdated = (int) ($salesMatch[1] ?? 0);
        $billsUpdated = (int) ($billsMatch[1] ?? 0);

        return back()->with(
            'success',
            "Document balances synced successfully. Sales updated: {$salesUpdated}. Bills updated: {$billsUpdated}."
        );
    }

    public function postPayrollToAccounting(Request $request, PayRun $payRun, PayrollService $payrollService)
    {
        abort_unless((int) $payRun->company_id === (int) loggedCompanyId()['company_id'], 403);

        try {
            $payrollService->postToAccounting($payRun->id, $payRun->company_id);
            $message = 'Payroll pay run posted to accounting successfully.';

            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => $message]);
            }

            return back()->with('success', $message);
        } catch (\Throwable $e) {
            $message = 'Error posting payroll to accounting: ' . $e->getMessage();

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return back()->with('error', $message);
        }
    }

    public function saveLayout(Request $request)
    {
        $validated = $request->validate([
            'order' => ['array'],
            'order.*' => ['string', 'max:80'],
            'hidden' => ['array'],
            'hidden.*' => ['string', 'max:80'],
            'sizes' => ['array'],
            'sizes.*' => ['string', 'max:30'],
        ]);

        $widgets = $this->accountDashboardWidgets();
        $preferences = [
            'order' => collect($validated['order'] ?? [])
                ->filter(fn($id) => isset($widgets[$id]))
                ->unique()
                ->values()
                ->all(),
            'hidden' => collect($validated['hidden'] ?? [])
                ->filter(fn($id) => isset($widgets[$id]))
                ->unique()
                ->values()
                ->all(),
            'sizes' => collect($validated['sizes'] ?? [])
                ->filter(fn($size, $id) => isset($widgets[$id]))
                ->all(),
        ];

        UserDashboardPreference::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'company_id' => loggedCompanyId()['company_id'],
                'dashboard_key' => self::ACCOUNT_DASHBOARD_KEY,
            ],
            ['preferences' => $preferences]
        );

        return response()->json([
            'success' => true,
            'layout' => $this->resolveDashboardLayout($widgets, $preferences),
        ]);
    }

    public function resetLayout()
    {
        $this->dashboardPreferenceQuery()->delete();

        return response()->json([
            'success' => true,
            'layout' => $this->resolveDashboardLayout($this->accountDashboardWidgets()),
        ]);
    }

    public function kpiData(Request $request)
    {
        $companyId = loggedCompanyId()['company_id'];
        $dateRange = $this->getDateRangeFromFilter($request);
        $startDate = $dateRange['start_date'];
        $endDate = $dateRange['end_date'];
        $previousPeriod = $this->getPreviousPeriod($startDate, $endDate);

        // Fetch COA Metadata needed for mapping
        // We need: id, category, name, account_number, initial_balance
        $accounts = ChartOfAccount::forCompany($companyId)->get()->keyBy('id');

        // Helper to get Account IDs by filter closure
        $getAccountIds = function ($filter) use ($accounts) {
            return $accounts->filter($filter)->pluck('id');
        };

        // --- 1. Fetch Aggregates for Current Period ---
        $currentAggregates = Transaction::where('company_id', $companyId)
            ->whereBetween('date', [$startDate, $endDate])
            ->select('account_id', DB::raw('SUM(debit) as debits, SUM(credit) as credits'))
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');

        // --- 2. Fetch Aggregates for Previous Period ---
        $prevAggregates = Transaction::where('company_id', $companyId)
            ->whereBetween('date', [$previousPeriod['start_date'], $previousPeriod['end_date']])
            ->select('account_id', DB::raw('SUM(debit) as debits, SUM(credit) as credits'))
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');

        // --- Calculation Logic ---
        // $aggregates is the collection of transaction sums.
        // $categoryFilter is a string (Category Name) or Closure (custom filter).
        // Returns the net value based on category normal side.
        $calculateTotal = function ($aggregates, $categoryFilter) use ($accounts) {
            $total = 0;
            $isClosure = $categoryFilter instanceof \Closure;

            foreach ($aggregates as $accId => $txn) {
                $account = $accounts->get($accId);
                if (!$account) continue;

                $match = $isClosure ? $categoryFilter($account) : $account->category === $categoryFilter;

                if ($match) {
                    $debit = $txn->debits ?? 0;
                    $credit = $txn->credits ?? 0;

                    // Determine Sign:
                    // Revenue, Equity, Liabilities: Credit - Debit
                    // Assets, Expenses, COGS: Debit - Credit
                    if (in_array($account->category, ['Revenue', 'Equity', 'Liabilities'])) {
                        $total += ($credit - $debit);
                    } else {
                        $total += ($debit - $credit);
                    }
                }
            }
            return $total;
        };

        // Filters
        $revenueFilter = 'Revenue';
        $cogsFilter = 'COGS';
        $expenseFilter = 'Expenses';

        // Add finance cost and tax expense filters (same as index function)
        $otherIncomeGroupIds = $accounts->filter(
            fn($acc) =>
                $acc->category === 'Revenue' &&
                $acc->type === 'Group' &&
                ($acc->sub_type === 'OTHER_INCOME' ||
                    str_contains(strtolower($acc->name), 'other income') ||
                    str_contains(strtolower($acc->name), 'other revenue'))
        )->pluck('id');

        $financeCostGroupIds = $accounts->filter(
            fn($acc) =>
                $acc->category === 'Expenses' &&
                $acc->type === 'Group' &&
                ($acc->sub_type === 'FINANCE_COST' ||
                    str_contains(strtolower($acc->name), 'finance') ||
                    str_contains(strtolower($acc->name), 'interest') ||
                    str_contains(strtolower($acc->name), 'bank fee') ||
                    str_contains(strtolower($acc->name), 'bank charge'))
        )->pluck('id');

        $isOtherIncome = function ($acc) use ($otherIncomeGroupIds) {
            return $acc->sub_type === 'OTHER_INCOME' || $otherIncomeGroupIds->contains($acc->parent_id);
        };

        $isFinanceCost = function ($acc) use ($financeCostGroupIds) {
            $name = strtolower($acc->name);
            return $acc->sub_type === 'FINANCE_COST' ||
                $financeCostGroupIds->contains($acc->parent_id) ||
                str_contains($name, 'interest expense') ||
                str_contains($name, 'finance cost') ||
                str_contains($name, 'finance charge') ||
                str_contains($name, 'bank fee') ||
                str_contains($name, 'bank charge') ||
                str_contains($name, 'foreign exchange loss');
        };

        $isTaxExpense = function ($acc) {
            $name = strtolower($acc->name);
            return $acc->sub_type === 'TAX_EXPENSE' ||
                str_contains($name, 'income tax') ||
                str_contains($name, 'tax expense') ||
                str_contains($name, 'corporate tax') ||
                str_contains($name, 'provision for tax');
        };

        // Custom filter functions
        $operatingExpenseFilter = function ($acc) use ($isFinanceCost, $isTaxExpense) {
            return $acc->category === 'Expenses' && !$isFinanceCost($acc) && !$isTaxExpense($acc);
        };

        $financeCostFilter = function ($acc) use ($isFinanceCost) {
            return $isFinanceCost($acc);
        };

        $taxExpenseFilter = function ($acc) use ($isTaxExpense) {
            return $isTaxExpense($acc);
        };

        $receivableFilter = function ($acc) {
            return $acc->sub_type === 'RECEIVABLE';
        };

        $payableFilter = function ($acc) {
            return $acc->sub_type === 'CURRENT_LIABILITY' &&
                str_contains(strtolower($acc->name), 'payable');
        };

        // Current Period Metrics
        $revCurrent = $calculateTotal($currentAggregates, $revenueFilter);
        $cogsCurrent = $calculateTotal($currentAggregates, $cogsFilter);
        $expCurrent = $calculateTotal($currentAggregates, $expenseFilter);
        $operatingExpensesCurrent = $calculateTotal($currentAggregates, $operatingExpenseFilter);
        $financeCostsCurrent = $calculateTotal($currentAggregates, $financeCostFilter);
        $taxExpensesCurrent = $calculateTotal($currentAggregates, $taxExpenseFilter);
        $financeTaxCostsCurrent = $financeCostsCurrent + $taxExpensesCurrent;

        $grossProfitCurrent = $revCurrent - $cogsCurrent;
        $netProfitCurrent = $revCurrent - ($expCurrent + $cogsCurrent);

        // Previous Period Metrics
        $revPrev = $calculateTotal($prevAggregates, $revenueFilter);
        $cogsPrev = $calculateTotal($prevAggregates, $cogsFilter);
        $expPrev = $calculateTotal($prevAggregates, $expenseFilter);
        $operatingExpensesPrev = $calculateTotal($prevAggregates, $operatingExpenseFilter);
        $financeCostsPrev = $calculateTotal($prevAggregates, $financeCostFilter);
        $taxExpensesPrev = $calculateTotal($prevAggregates, $taxExpenseFilter);
        $financeTaxCostsPrev = $financeCostsPrev + $taxExpensesPrev;

        $grossProfitPrev = $revPrev - $cogsPrev;
        $netProfitPrev = $revPrev - ($expPrev + $cogsPrev);


        // --- Ratios — resolved by sub_type (Xero-style) ---
        $currentAssetsFilter = function ($acc) {
            return $acc->category === 'Assets' &&
                in_array($acc->sub_type, ['BANK', 'CURRENT_ASSET', 'RECEIVABLE', 'INVENTORY']);
        };

        // Quick Ratio excludes Inventory
        $quickAssetsFilter = function ($acc) {
            return $acc->category === 'Assets' &&
                in_array($acc->sub_type, ['BANK', 'CURRENT_ASSET', 'RECEIVABLE']);
        };

        $currentLiabilitiesFilter = function ($acc) {
            return $acc->sub_type === 'CURRENT_LIABILITY';
        };

        $currentAssetsTotal = $calculateTotal($currentAggregates, $currentAssetsFilter);
        $quickAssetsTotal = $calculateTotal($currentAggregates, $quickAssetsFilter);
        $currentLiabilitiesTotal = $calculateTotal($currentAggregates, $currentLiabilitiesFilter);

        $currentRatio = $currentLiabilitiesTotal > 0 ? $currentAssetsTotal / $currentLiabilitiesTotal : 0;
        $quickRatio = $currentLiabilitiesTotal > 0 ? $quickAssetsTotal / $currentLiabilitiesTotal : 0;

        // Previous period ratios
        $currentAssetsTotalPrev = $calculateTotal($prevAggregates, $currentAssetsFilter);
        $quickAssetsTotalPrev = $calculateTotal($prevAggregates, $quickAssetsFilter);
        $currentLiabilitiesTotalPrev = $calculateTotal($prevAggregates, $currentLiabilitiesFilter);

        $currentRatioPrev = $currentLiabilitiesTotalPrev > 0 ? $currentAssetsTotalPrev / $currentLiabilitiesTotalPrev : 0;
        $quickRatioPrev = $currentLiabilitiesTotalPrev > 0 ? $quickAssetsTotalPrev / $currentLiabilitiesTotalPrev : 0;

        // --- End of Period Balances (for Ratios) ---
        // To get the balance at end_date, we need Sum(initial) + Sum(all txns up to end_date)
        $endPeriodAggregates = Transaction::where('date', '<=', $endDate)
            ->select('account_id', DB::raw('SUM(debit) as debits, SUM(credit) as credits'))
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');

        $calculateBalance = function ($aggregates) use ($accounts) {
            $total = 0;
            foreach ($aggregates as $accId => $txn) {
                $account = $accounts->get($accId);
                if (!$account) continue;
                $debit = $txn->debits ?? 0;
                $credit = $txn->credits ?? 0;
                if (in_array($account->category, ['Revenue', 'Equity', 'Liabilities'])) {
                    $total += ($credit - $debit);
                } else {
                    $total += ($debit - $credit);
                }
            }
            return $total;
        };

        $netMovementToEnd = $calculateBalance($endPeriodAggregates);
        $totalAccountBalance = $netMovementToEnd;

        // --- Additional KPIs (Advanced) ---

        // 1. Efficiency Ratios (AR Days / AP Days)
        $arCurrent = $calculateTotal($currentAggregates, $receivableFilter);
        $apCurrent = $calculateTotal($currentAggregates, $payableFilter);

        $daysInPeriod = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;

        // AR Days = (Average AR / Total Sales) * Days
        // Simplification: (Current AR / Period Revenue) * Days
        $arDays = ($revCurrent > 0) ? ($arCurrent / $revCurrent) * $daysInPeriod : 0;

        // AP Days = (Current AP / Cost of Sales or total expenses?)
        // Standard: (Current AP / COGS) * Days
        $apDays = ($cogsCurrent > 0) ? ($apCurrent / $cogsCurrent) * $daysInPeriod : 0;

        // 2. Financial Position (Debt to Equity / Debt Ratio)
        $totalLiabilitiesFilter = 'Liabilities';
        $totalEquityFilter = 'Equity';
        $totalAssetsFilter = 'Assets';

        $liabilitiesEnd = $calculateTotal($endPeriodAggregates, $totalLiabilitiesFilter);
        $equityEnd = $calculateTotal($endPeriodAggregates, $totalEquityFilter);
        $assetsEnd = $calculateTotal($endPeriodAggregates, $totalAssetsFilter);

        $debtToEquity = ($equityEnd != 0) ? $liabilitiesEnd / $equityEnd : 0;
        $debtRatio = ($assetsEnd > 0) ? ($liabilitiesEnd / $assetsEnd) * 100 : 0;

        // 3. Performance (ROA / Asset Turnover)
        // ROA = (Net Profit / Total Assets) * 100
        $roa = ($assetsEnd > 0) ? ($netProfitCurrent / $assetsEnd) * 100 : 0;

        // Asset Turnover = Net Sales / Total Assets
        $assetTurnover = ($assetsEnd > 0) ? ($revCurrent / $assetsEnd) : 0;


        // --- Monthly Data (Last 12 Months) ---
        $monthlyStart = Carbon::parse($endDate)->subMonths(11)->startOfMonth();
        $monthlyEnd = Carbon::parse($endDate)->endOfMonth();

        $driver = DB::getDriverName();
        $dateFormat = $driver === 'pgsql' ? "TO_CHAR(date, 'YYYY-MM')" : "DATE_FORMAT(date, '%Y-%m')";

        $monthlyAggregates = Transaction::whereBetween('date', [$monthlyStart, $monthlyEnd])
            ->whereIn('account_id', $accounts->pluck('id'))
            ->select(
                DB::raw("$dateFormat as month_key"),
                'account_id',
                DB::raw('SUM(debit) as debits'),
                DB::raw('SUM(credit) as credits')
            )
            ->groupBy(DB::raw($dateFormat), 'account_id')
            ->get()
            ->groupBy('month_key');

        $monthlyLabels = [];
        $monthlyRevenueData = [];
        $monthlyExpenseData = [];
        $monthlyBalanceData = [];

        $runningBalance = 0;

        for ($i = 11; $i >= 0; $i--) {
            $mDate = Carbon::parse($endDate)->subMonths($i);
            $key = $mDate->format('Y-m');
            $monthlyLabels[] = $mDate->format('M Y');

            $dataForMonth = $monthlyAggregates->get($key, collect());

            $mRev = 0;
            $mExp = 0;
            $mCOGS = 0;

            foreach ($dataForMonth as $txn) {
                $acc = $accounts->get($txn->account_id);
                if (!$acc) continue;

                if ($acc->category === 'Revenue') {
                    $mRev += ($txn->credits - $txn->debits);
                } elseif ($acc->category === 'Expenses') {
                    $mExp += ($txn->debits - $txn->credits);
                } elseif ($acc->category === 'COGS') {
                    $mCOGS += ($txn->debits - $txn->credits);
                }
            }

            $monthlyRevenueData[] = ['month' => $mDate->format('M'), 'year' => $mDate->format('Y'), 'value' => $mRev];
            $monthlyExpenseData[] = ['month' => $mDate->format('M'), 'year' => $mDate->format('Y'), 'value' => $mExp];

            $mNet = $mRev - ($mExp + $mCOGS);
            $runningBalance += $mNet;
            $monthlyBalanceData[] = ['month' => $mDate->format('M'), 'year' => $mDate->format('Y'), 'balance' => $runningBalance];
        }

        // Calculate account balances
        $accountBalances = [];
        foreach ($accounts as $account) {
            $txnAgg = $currentAggregates->get($account->id);
            if ($txnAgg) {
                $debit = $txnAgg->debits ?? 0;
                $credit = $txnAgg->credits ?? 0;
                if (in_array($account->category, ['Revenue', 'Equity', 'Liabilities'])) {
                    $balance = $credit - $debit;
                } else {
                    $balance = $debit - $credit;
                }
                $accountBalances[] = [
                    'name' => $account->name,
                    'balance' => $balance,
                    'category' => $account->category,
                ];
            }
        }

        // Sort by absolute balance descending
        usort($accountBalances, function ($a, $b) {
            return abs($b['balance']) <=> abs($a['balance']);
        });

        // Take top 10
        $topAccounts = array_slice($accountBalances, 0, 10);

        // Category breakdown
        $categoryTotals = [];
        foreach ($accountBalances as $acc) {
            $cat = $acc['category'];
            if (!isset($categoryTotals[$cat])) {
                $categoryTotals[$cat] = 0;
            }
            $categoryTotals[$cat] += $acc['balance'];
        }
        $categoryData = [];
        foreach ($categoryTotals as $cat => $total) {
            $categoryData[] = ['category' => $cat, 'total' => $total];
        }

        // Additional KPI calculations
        $workingCapital = $currentAssetsTotal - $currentLiabilitiesTotal;
        $workingCapitalPrev = $currentAssetsTotalPrev - $currentLiabilitiesTotalPrev;
        $roe = ($equityEnd > 0) ? ($netProfitCurrent / $equityEnd) * 100 : 0;
        $revenueGrowth = ($revPrev > 0) ? (($revCurrent - $revPrev) / abs($revPrev)) * 100 : 0;
        $expenseGrowth = (($expPrev + $cogsPrev) > 0) ? ((($expCurrent + $cogsCurrent) - ($expPrev + $cogsPrev)) / abs($expPrev + $cogsPrev)) * 100 : 0;
        // Inventory Days — resolved by sub_type
        $inventoryFilter = function ($acc) {
            return $acc->sub_type === 'INVENTORY';
        };
        $inventoryCurrent = $calculateTotal($currentAggregates, $inventoryFilter);
        $inventoryDays = ($cogsCurrent > 0) ? ($inventoryCurrent / $cogsCurrent) * $daysInPeriod : 0;
        // Cash & Cash Equivalents — resolved by sub_type
        $cashFilter = function ($acc) {
            return $acc->sub_type === 'BANK';
        };
        $cashBalance = $calculateTotal($endPeriodAggregates, $cashFilter);
        // Monthly burn rate (avg monthly expenses over last 3 months)
        $lastThreeMonthsExpenses = array_slice($monthlyExpenseData, -3);
        $burnRate = count($lastThreeMonthsExpenses) > 0
            ? array_sum(array_column($lastThreeMonthsExpenses, 'value')) / count($lastThreeMonthsExpenses)
            : 0;
        // Runway (months of cash remaining at current burn rate)
        $runway = ($burnRate > 0) ? $cashBalance / $burnRate : 0;
        // Interest coverage — resolved by sub_type
        $interestFilter = function ($acc) {
            return $acc->sub_type === 'FINANCE_COST' ||
                ($acc->category === 'Expenses' && str_contains(strtolower($acc->name), 'interest'));
        };
        $interestExpense = $calculateTotal($currentAggregates, $interestFilter);
        // EBIT = Revenue - COGS - OpEx + Interest (add back interest since it's in Expenses)
        $ebit = $revCurrent - $cogsCurrent - $expCurrent + $interestExpense;
        $interestCoverage = ($interestExpense > 0) ? $ebit / $interestExpense : 0;
        // Turnover ratios
        $arCurrent2 = $calculateTotal($currentAggregates, $receivableFilter);
        $apCurrent2 = $calculateTotal($currentAggregates, $payableFilter);
        $arTurnover = ($arCurrent2 > 0) ? $revCurrent / $arCurrent2 : 0;
        $apTurnover = ($apCurrent2 > 0) ? $cogsCurrent / $apCurrent2 : 0;
        $inventoryTurnover = ($inventoryCurrent > 0) ? $cogsCurrent / $inventoryCurrent : 0;
        $workingCapitalTurnover = ($workingCapital > 0) ? $revCurrent / $workingCapital : 0;
        // CCC = DSO + DIO - DPO
        $ccc = $arDays + $inventoryDays - $apDays;
        // Margin ratios
        $operatingMargin = ($revCurrent > 0) ? ($ebit / $revCurrent) * 100 : 0;
        $cogsRatio = ($revCurrent > 0) ? ($cogsCurrent / $revCurrent) * 100 : 0;
        $expenseRatio = ($revCurrent > 0) ? ($expCurrent / $revCurrent) * 100 : 0;
        // EBITDA — resolved by sub_type
        $depreciationFilter = function ($acc) {
            return $acc->sub_type === 'DEPRECIATION' ||
                ($acc->category === 'Expenses' && (
                    str_contains(strtolower($acc->name), 'depreciation') ||
                    str_contains(strtolower($acc->name), 'amortization')
                ));
        };
        $depreciation = $calculateTotal($currentAggregates, $depreciationFilter);
        $ebitda = $ebit + $depreciation;
        $ebitdaMargin = ($revCurrent > 0) ? ($ebitda / $revCurrent) * 100 : 0;
        // Fixed Assets Turnover — resolved by sub_type
        $fixedAssetsFilter = function ($acc) {
            return $acc->sub_type === 'FIXED_ASSET';
        };
        $fixedAssets = $calculateTotal($endPeriodAggregates, $fixedAssetsFilter);
        $fixedAssetTurnover = ($fixedAssets > 0) ? $revCurrent / $fixedAssets : 0;
        // Equity Multiplier (Total Assets / Equity) - for DuPont
        $equityMultiplier = ($equityEnd > 0) ? $assetsEnd / $equityEnd : 0;
        // DuPont ROE = Net Profit Margin × Asset Turnover × Equity Multiplier
        $dupontRoe = (($revCurrent > 0) ? ($netProfitCurrent / $revCurrent) : 0) * $assetTurnover * $equityMultiplier * 100;

        // ── NEW GLOBAL ACCOUNTING KPIs ────────────────────────────────────
        // Payroll / Wages
        $payrollFilter = function ($acc) {
            return $acc->category === 'Expenses' && (
                str_contains(strtolower($acc->name), 'payroll') ||
                str_contains(strtolower($acc->name), 'salary') ||
                str_contains(strtolower($acc->name), 'salaries') ||
                str_contains(strtolower($acc->name), 'wages') ||
                str_contains(strtolower($acc->name), 'remuneration')
            );
        };
        $payrollExpense = $calculateTotal($currentAggregates, $payrollFilter);
        $payrollPrev    = $calculateTotal($prevAggregates,    $payrollFilter);
        $payrollRatio   = ($revCurrent > 0) ? ($payrollExpense / $revCurrent) * 100 : 0;

        // Tax Expense & Effective Tax Rate
        $taxFilter = function ($acc) {
            return $acc->category === 'Expenses' && (
                str_contains(strtolower($acc->name), 'income tax') ||
                str_contains(strtolower($acc->name), 'corporate tax') ||
                str_contains(strtolower($acc->name), 'provision for tax') ||
                (str_contains(strtolower($acc->name), 'tax') &&
                    !str_contains(strtolower($acc->name), 'vat') &&
                    !str_contains(strtolower($acc->name), 'gst') &&
                    !str_contains(strtolower($acc->name), 'sales tax'))
            );
        };
        $taxExpense       = $calculateTotal($currentAggregates, $taxFilter);
        $effectiveTaxRate = ($ebit > 0) ? ($taxExpense / $ebit) * 100 : 0;

        // NPAT (Net Profit After Tax)
        $npat       = $netProfitCurrent - $taxExpense;
        $npatMargin = ($revCurrent > 0) ? ($npat / $revCurrent) * 100 : 0;

        // Contribution Margin = Revenue − Variable Costs (COGS)
        $contributionMargin      = $revCurrent - $cogsCurrent;
        $contributionMarginRatio = ($revCurrent > 0) ? ($contributionMargin / $revCurrent) * 100 : 0;

        // Breakeven Revenue = Fixed Costs / Contribution Margin Ratio
        $breakevenRevenue = ($contributionMarginRatio > 0)
            ? ($expCurrent / ($contributionMarginRatio / 100))
            : 0;

        // Safety Margin = (Revenue − Breakeven) / Revenue × 100
        $safetyMargin = ($revCurrent > 0 && $breakevenRevenue > 0)
            ? (($revCurrent - $breakevenRevenue) / $revCurrent) * 100
            : 0;

        // Free Cash Flow (simplified: EBITDA − Interest − Tax)
        $freeCashFlow = $ebitda - $interestExpense - $taxExpense;

        // Operating Cash Flow Ratio = OCF / Current Liabilities
        $operatingCFRatio = ($currentLiabilitiesTotal > 0)
            ? $netProfitCurrent / $currentLiabilitiesTotal
            : 0;

        // Cash Flow to Debt = OCF / Total Liabilities
        $cfToDebt = ($liabilitiesEnd > 0) ? $netProfitCurrent / $liabilitiesEnd : 0;

        // Debt Service Coverage Ratio = EBIT / Interest Expense
        $dscr = ($interestExpense > 0) ? $ebit / $interestExpense : 0;

        // Operating Leverage = Gross Profit / EBIT (measures fixed-cost sensitivity)
        $operatingLeverage = ($ebit != 0) ? $grossProfitCurrent / $ebit : 0;

        // Net Working Capital Ratio = Working Capital / Total Assets × 100
        $nwcRatio = ($assetsEnd > 0) ? ($workingCapital / $assetsEnd) * 100 : 0;

        // Revenue per Day
        $revenuePerDay = ($daysInPeriod > 0) ? $revCurrent / $daysInPeriod : 0;

        // Overhead (Opex excluding payroll)
        $overheadExpense = $expCurrent - $payrollExpense;
        $overheadRatio   = ($revCurrent > 0) ? ($overheadExpense / $revCurrent) * 100 : 0;

        // Bad Debt Ratio
        $badDebtFilter = function ($acc) {
            return $acc->category === 'Expenses' && (
                str_contains(strtolower($acc->name), 'bad debt') ||
                str_contains(strtolower($acc->name), 'doubtful') ||
                str_contains(strtolower($acc->name), 'uncollectible') ||
                str_contains(strtolower($acc->name), 'write-off') ||
                str_contains(strtolower($acc->name), 'written off')
            );
        };
        $badDebt      = $calculateTotal($currentAggregates, $badDebtFilter);
        $badDebtRatio = ($revCurrent > 0) ? ($badDebt / $revCurrent) * 100 : 0;

        // Retained Earnings (from Equity accounts)
        $retainedEarningsFilter = function ($acc) {
            return $acc->category === 'Equity' && (
                str_contains(strtolower($acc->name), 'retained') ||
                str_contains(strtolower($acc->name), 'accumulated') ||
                str_contains(strtolower($acc->name), 'undistributed')
            );
        };
        $retainedEarnings = $calculateTotal($endPeriodAggregates, $retainedEarningsFilter);

        // Capital Expenditure — resolved by sub_type
        $capexFilter = function ($acc) {
            return $acc->sub_type === 'FIXED_ASSET';
        };
        $capex = $calculateTotal($currentAggregates, $capexFilter);

        // Collection Effectiveness Index (simplified)
        // CEI = (Beginning AR + Sales − Ending AR) / (Beginning AR + Sales) × 100
        $arBegin = $calculateTotal($prevAggregates, $receivableFilter);
        $arEnd2  = $calculateTotal($currentAggregates, $receivableFilter);
        $cei     = ($arBegin + $revCurrent > 0)
            ? (($arBegin + $revCurrent - $arEnd2) / ($arBegin + $revCurrent)) * 100
            : 0;

        // Revenue Concentration (top account as % of total — rough proxy)
        $revenueConcentration = 0;
        if (!empty($topAccounts) && $revCurrent > 0) {
            $topBal = array_reduce($topAccounts, fn($c, $a) => $c + max(0, $a['balance']), 0);
            $revenueConcentration = ($topBal / max(1, array_sum(array_column($topAccounts, 'balance')))) * 100;
        }

        // ── Additional Global Accounting KPIs ────────────────────────

        // Altman Z-Score (manufacturing model): 1.2×A + 1.4×B + 3.3×C + 0.6×D + 1.0×E
        // A = Working Capital / Total Assets
        // B = Retained Earnings / Total Assets
        // C = EBIT / Total Assets
        // D = Equity / Total Liabilities
        // E = Revenue / Total Assets
        $altmanA = ($assetsEnd > 0) ? $workingCapital / $assetsEnd : 0;
        $altmanB = ($assetsEnd > 0) ? $retainedEarnings / $assetsEnd : 0;
        $altmanC = ($assetsEnd > 0) ? $ebit / $assetsEnd : 0;
        $altmanD = ($liabilitiesEnd > 0) ? $equityEnd / $liabilitiesEnd : 0;
        $altmanE = ($assetsEnd > 0) ? $revCurrent / $assetsEnd : 0;
        $altmanZScore = (1.2 * $altmanA) + (1.4 * $altmanB) + (3.3 * $altmanC) + (0.6 * $altmanD) + (1.0 * $altmanE);

        // Sustainable Growth Rate = ROE × Retention Ratio
        // Retention Ratio = 1 - (Dividends / Net Income) — assume no dividends tracked, so retention = 1
        $sustainableGrowthRate = $roe; // SGR = ROE × 1 (100% retention)

        // Gross Burn Rate (total monthly outflows — COGS + Expenses)
        $grossBurnRate = ($daysInPeriod > 0)
            ? (($cogsCurrent + $expCurrent) / $daysInPeriod) * 30
            : 0;

        // Operating Expense Ratio (Opex only, excluding COGS)
        $opexRatio = ($revCurrent > 0) ? (($expCurrent - $cogsCurrent) / $revCurrent) * 100 : 0;
        // Guard: if expCurrent doesn't include COGS (it shouldn't since they're separate filters)
        $opexRatio = ($revCurrent > 0) ? ($expCurrent / $revCurrent) * 100 : 0;

        // Equity Ratio = Equity / Total Assets
        $equityRatio = ($assetsEnd > 0) ? ($equityEnd / $assetsEnd) * 100 : 0;

        // Times Interest Earned (same as interest coverage but labeled differently)
        $timesInterestEarned = $interestCoverage;

        // Monthly COGS data for charts (must be computed before monthlyNetCashFlowData)
        $monthlyCOGSData = [];
        for ($i = 11; $i >= 0; $i--) {
            $mDate = Carbon::parse($endDate)->subMonths($i);
            $key = $mDate->format('Y-m');
            $dataForMonth = $monthlyAggregates->get($key, collect());
            $mCOGS = 0;
            foreach ($dataForMonth as $txn) {
                $acc = $accounts->get($txn->account_id);
                if ($acc && $acc->category === 'COGS') {
                    $mCOGS += ($txn->debits - $txn->credits);
                }
            }
            $monthlyCOGSData[] = ['month' => $mDate->format('M'), 'year' => $mDate->format('Y'), 'value' => $mCOGS];
        }

        // Monthly net cash flow array for charts
        $monthlyNetCashFlowData = [];
        for ($i = 0; $i < 12; $i++) {
            $mInc  = $monthlyRevenueData[$i]['value'] ?? 0;
            $mExp  = $monthlyExpenseData[$i]['value']  ?? 0;
            $mCogs = $monthlyCOGSData[$i]['value']     ?? 0;
            $monthlyNetCashFlowData[] = [
                'month' => $monthlyRevenueData[$i]['month'] ?? '',
                'value' => $mInc - $mExp - $mCogs,
            ];
        }

        $kpis = [
            'gross_profit_margin' => ($revCurrent > 0) ? ($grossProfitCurrent / $revCurrent) * 100 : 0,
            'gross_profit_margin_previous' => ($revPrev > 0) ? ($grossProfitPrev / $revPrev) * 100 : 0,
            'gross_profit' => $grossProfitCurrent,
            'gross_profit_previous' => $grossProfitPrev,

            'net_profit_margin' => ($revCurrent > 0) ? ($netProfitCurrent / $revCurrent) * 100 : 0,
            'net_profit_margin_previous' => ($revPrev > 0) ? ($netProfitPrev / $revPrev) * 100 : 0,

            'net_profit' => $netProfitCurrent,
            'net_profit_previous' => $netProfitPrev,

            'total_income' => $revCurrent,
            'total_income_previous' => $revPrev,
            'revenue_growth' => $revenueGrowth,

            'cogs' => $cogsCurrent,
            'cogs_previous' => $cogsPrev,

            'total_expenses' => $expCurrent + $cogsCurrent,
            'total_expenses_previous' => $expPrev + $cogsPrev,
            'expense_growth' => $expenseGrowth,

            // New expense breakdown metrics
            'operating_expenses' => $operatingExpensesCurrent,
            'operating_expenses_previous' => $operatingExpensesPrev,
            'finance_costs' => $financeCostsCurrent,
            'finance_costs_previous' => $financeCostsPrev,
            'tax_expenses_kpi' => $taxExpensesCurrent,
            'tax_expenses_kpi_previous' => $taxExpensesPrev,
            'finance_tax_costs' => $financeTaxCostsCurrent,
            'finance_tax_costs_previous' => $financeTaxCostsPrev,
            'total_expenses_excluding_cogs' => $expCurrent,
            'total_expenses_excluding_cogs_previous' => $expPrev,

            'accounts_receivable' => $calculateTotal($currentAggregates, $receivableFilter),
            'accounts_receivable_previous' => $calculateTotal($prevAggregates, $receivableFilter),

            'operating_cash_flow' => $netProfitCurrent,
            'operating_cash_flow_previous' => $netProfitPrev,

            'accounts_payable' => $calculateTotal($currentAggregates, $payableFilter),
            'accounts_payable_previous' => $calculateTotal($prevAggregates, $payableFilter),

            'working_capital' => $workingCapital,
            'working_capital_previous' => $workingCapitalPrev,

            'cash_balance' => $cashBalance,

            'quick_ratio' => $quickRatio,
            'quick_ratio_previous' => $quickRatioPrev,

            'current_ratio' => $currentRatio,
            'current_ratio_previous' => $currentRatioPrev,

            'account_balance' => $totalAccountBalance,

            'roe' => $roe,
            'ebit' => $ebit,
            'interest_coverage' => $interestCoverage,
            'burn_rate' => $burnRate,
            'runway' => $runway,

            'operating_margin' => $operatingMargin,
            'cogs_ratio' => $cogsRatio,
            'expense_ratio' => $expenseRatio,
            'ebitda' => $ebitda,
            'ebitda_margin' => $ebitdaMargin,
            'equity_multiplier' => $equityMultiplier,
            'dupont_roe' => $dupontRoe,

            // Operations & Tax KPIs
            'payroll_expense' => $payrollExpense,
            'payroll_expense_previous' => $payrollPrev,
            'payroll_ratio' => $payrollRatio,
            'tax_expense' => $taxExpense,
            'effective_tax_rate' => $effectiveTaxRate,
            'npat' => $npat,
            'npat_margin' => $npatMargin,
            'contribution_margin' => $contributionMargin,
            'contribution_margin_ratio' => $contributionMarginRatio,
            'breakeven_revenue' => $breakevenRevenue,
            'safety_margin' => $safetyMargin,
            'free_cash_flow' => $freeCashFlow,
            'operating_cf_ratio' => $operatingCFRatio,
            'cf_to_debt' => $cfToDebt,
            'dscr' => $dscr,
            'operating_leverage' => $operatingLeverage,
            'nwc_ratio' => $nwcRatio,
            'revenue_per_day' => $revenuePerDay,
            'overhead_expense' => $overheadExpense,
            'overhead_ratio' => $overheadRatio,
            'bad_debt' => $badDebt,
            'bad_debt_ratio' => $badDebtRatio,
            'retained_earnings' => $retainedEarnings,
            'capex' => $capex,
            'cei' => $cei,
            'monthly_net_cash_flow' => $monthlyNetCashFlowData,

            'efficiency_ratios' => [
                'ar_days' => $arDays,
                'ap_days' => $apDays,
                'asset_turnover' => $assetTurnover,
                'fixed_asset_turnover' => $fixedAssetTurnover,
                'inventory_days' => $inventoryDays,
                'inventory_turnover' => $inventoryTurnover,
                'ar_turnover' => $arTurnover,
                'ap_turnover' => $apTurnover,
                'working_capital_turnover' => $workingCapitalTurnover,
                'ccc' => $ccc,
            ],

            'financial_position' => [
                'debt_to_equity' => $debtToEquity,
                'debt_ratio' => $debtRatio,
                'total_assets' => $assetsEnd,
                'total_liabilities' => $liabilitiesEnd,
                'total_equity' => $equityEnd,
                'current_assets' => $currentAssetsTotal,
                'current_liabilities' => $currentLiabilitiesTotal,
            ],

            'roa' => $roa,

            'monthly_income' => $monthlyRevenueData,
            'monthly_expenses' => $monthlyExpenseData,
            'monthly_cogs' => $monthlyCOGSData,
            'monthly_balance' => $monthlyBalanceData,
            'monthly_labels' => $monthlyLabels,

            'profit_loss_data' => [
                'revenue' => $revCurrent,
                'cogs' => $cogsCurrent,
                'expenses' => $expCurrent,
                'gross_profit' => $grossProfitCurrent,
                'net_income' => $netProfitCurrent,
            ],

            'top_accounts' => $topAccounts,
            'category_data' => $categoryData,

            // Global Accounting KPIs
            'altman_z_score' => $altmanZScore,
            'sustainable_growth_rate' => $sustainableGrowthRate,
            'gross_burn_rate' => $grossBurnRate,
            'opex_ratio' => $opexRatio,
            'equity_ratio' => $equityRatio,
            'depreciation' => $depreciation,
            'interest_expense' => $interestExpense,

            'date_range' => [
                'start' => $startDate,
                'end' => $endDate,
                'formatted_start' => Carbon::parse($startDate)->format('M d, Y'),
                'formatted_end' => Carbon::parse($endDate)->format('M d, Y'),
            ],
        ];

        return view('pages.account.kpi', compact('kpis'));
        // return view('pages.account.kpi_old', compact('kpis'));
    }

    private function getDateRangeFromFilter(Request $request)
    {
        $perDate = $request->get('per_date');

        if ($perDate === 'CUSTOM') {
            return [
                'start_date' => $request->get('start_date'),
                'end_date' => $request->get('end_date'),
            ];
        }

        if ($perDate && strpos($perDate, '|') !== false) {
            [$startDate, $endDate] = explode('|', $perDate);
            return [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ];
        }

        return [
            'start_date' => Carbon::now()->startOfMonth()->toDateString(),
            'end_date' => Carbon::now()->endOfMonth()->toDateString(),
        ];
    }

    private function getPreviousPeriod($startDate, $endDate)
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $daysDiff = $start->diffInDays($end);

        return [
            'start_date' => $start->copy()->subDays($daysDiff + 1)->toDateString(),
            'end_date' => $start->copy()->subDay()->toDateString(),
        ];
    }
}
