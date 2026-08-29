<?php

namespace App\Http\Controllers\Account\Report;

use App\Http\Controllers\Controller;
use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\Transaction;
use App\Services\Account\FinancialNarrativeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class BalanceSheetController extends Controller
{
    use \App\Traits\LogsReportAccess, \App\Traits\ExportsToExcel;
    public function balanceSheet(Request $request)
    {
        $companyId = loggedCompanyId()['company_id'];

        // Fiscal year filter: use period's end_date as as_of_date
        // if ($request->fiscal_year) {
        //     $fiscalPeriod = \App\Models\AccountingPeriod::find($request->fiscal_year);
        //     $asOfDate = $fiscalPeriod ? $fiscalPeriod->end_date->format('Y-m-d') : now()->format('Y-m-d');
        // } else {
        //     $asOfDate = $request->input('as_of_date', now()->format('Y-m-d'));
        // }

        $comparePeriods = (int) $request->input('compare_periods', 0);
        $periodType = $request->input('period_type', 'month');
        $asOfDate = $this->resolveAsOfDate($request, $companyId, $periodType);
        $compareDate = $request->input('compare_date');
        $showZeroBalance = $request->input('show_zero_balance', false);
        $showVarianceAmount = $request->input('show_variance_amount', false);
        $showVariancePercentage = $request->input('show_variance_percentage', false);

        if ($periodType === 'custom_date') {
            $comparePeriods = 1;
        }

        $balanceSheet = $this->generateBalanceSheet(
            $companyId,
            $asOfDate,
            $comparePeriods,
            $periodType,
            $showZeroBalance,
            $compareDate
        );

        if ($request->ajax()) {
            $this->logReportAccess($request, 'Balance Sheet', 'filter');

            return response()->json([
                'html' => view('pages.account.reports.partials.balance-sheet-table', compact(
                    'balanceSheet',
                    'asOfDate',
                    'comparePeriods',
                    'periodType',
                    'showVarianceAmount',
                    'showVariancePercentage'
                ))->render(),
                'summary' => [
                    'total_assets' => numberFormat($balanceSheet['total_assets']['current']),
                    'total_liabilities' => numberFormat($balanceSheet['total_liabilities']['current']),
                    'total_equity' => numberFormat($balanceSheet['total_equity']['current']),
                    'total_liabilities_equity' => numberFormat($balanceSheet['total_liabilities_equity']['current']),
                ],
            ]);
        }

        $this->logReportAccess($request, 'Balance Sheet', 'view');

        $narrative = app(FinancialNarrativeService::class)->balanceSheet($balanceSheet);

        return view('pages.account.reports.balance-sheet', compact(
            'balanceSheet',
            'asOfDate',
            'comparePeriods',
            'periodType',
            'narrative'
        ));
    }

    private function generateBalanceSheet($companyId, $asOfDate, $comparePeriods = 0, $periodType = 'month', $showZeroBalance = false, $compareDate = null)
    {
        // Load all accounts (this is fine, usually small dataset)
        $allAccounts = ChartOfAccount::forCompany(loggedCompanyId()['company_id'])->orderBy('account_number')->get()->keyBy('id');

        // Calculate periods
        $periods = $this->calculatePeriods($asOfDate, $comparePeriods, $periodType, $compareDate);

        // ============================================
        // OPTIMIZED: Get all balances in ONE query per period
        // ============================================
        // $balancesByPeriod = $this->getBalancesForAllPeriods($companyId, $allAccounts, $periods);
        ['balances' => $balancesByPeriod, 'activeIds' => $activeAccountIds] = $this->getBalancesForAllPeriods($companyId, $allAccounts, $periods);

        // Split assets and liabilities into current/non-current (IAS 1)
        $allAssets = $this->getBalancesByCategory($allAccounts, $balancesByPeriod, 'Assets', $periods, $activeAccountIds);
        [$currentAssets, $nonCurrentAssets] = $this->splitCurrentNonCurrent($allAssets, $allAccounts, 'Assets');

        $allLiabilities = $this->getBalancesByCategory($allAccounts, $balancesByPeriod, 'Liabilities', $periods, $activeAccountIds);
        [$currentLiabilities, $nonCurrentLiabilities] = $this->splitCurrentNonCurrent($allLiabilities, $allAccounts, 'Liabilities');

        $data = [
            'current_assets' => $currentAssets,
            'non_current_assets' => $nonCurrentAssets,
            'assets' => $allAssets, // kept for backward compatibility (totals, exports)
            'current_liabilities' => $currentLiabilities,
            'non_current_liabilities' => $nonCurrentLiabilities,
            'liabilities' => $allLiabilities,
            'equity' => $this->getBalancesByCategory($allAccounts, $balancesByPeriod, 'Equity', $periods, $activeAccountIds),
            'periods' => $periods
        ];

        // Calculate totals
        $data['total_current_assets'] = $this->calculateGrandTotal($data['current_assets'], $periods);
        $data['total_non_current_assets'] = $this->calculateGrandTotal($data['non_current_assets'], $periods);
        $data['total_assets'] = $this->calculateGrandTotal($data['assets'], $periods);
        $data['total_current_liabilities'] = $this->calculateGrandTotal($data['current_liabilities'], $periods);
        $data['total_non_current_liabilities'] = $this->calculateGrandTotal($data['non_current_liabilities'], $periods);
        $data['total_liabilities'] = $this->calculateGrandTotal($data['liabilities'], $periods);
        $data['total_equity'] = $this->calculateGrandTotal($data['equity'], $periods);

        // Compute earnings: all-time P&L and current fiscal year P&L
        // Both are needed because books may not have been closed for prior years
        $allTimeEarnings = $this->calculateAllTimeEarnings($allAccounts, $balancesByPeriod, $periods);
        $currentYearEarnings = $this->calculateCurrentYearEarnings($companyId, $allAccounts, $periods);

        // Prior year retained earnings = all-time P&L minus current FY P&L
        // (represents unclosed prior years' earnings not yet in Retained Earnings GL)
        $priorYearRetained = [];
        foreach ($periods as $index => $period) {
            $key = ($index === 0) ? 'current' : 'compare_' . $index;

            $priorYearRetained[$key] = $allTimeEarnings[$key] - $currentYearEarnings[$key];
        }
        foreach ($periods as $index => $period) {
            if ($index === 0) continue;
            $key = 'compare_' . $index;
            $priorYearRetained['variance_' . $index] = $priorYearRetained['current'] - $priorYearRetained[$key];
            $priorYearRetained['variance_pct_' . $index] = $priorYearRetained[$key] != 0
                ? ($priorYearRetained['variance_' . $index] / abs($priorYearRetained[$key])) * 100
                : 0;
        }

        $data['prior_year_retained'] = $priorYearRetained;
        $data['current_year_earnings'] = $currentYearEarnings;

        // Add ALL earnings (prior + current) to equity — ensures BS always balances
        foreach ($periods as $index => $period) {
            $key = ($index === 0) ? 'current' : 'compare_' . $index;
            $data['total_equity'][$key] += $allTimeEarnings[$key];

            if ($index > 0) {
                $data['total_equity']['variance_' . $index] = $data['total_equity']['current'] - $data['total_equity'][$key];
                $data['total_equity']['variance_pct_' . $index] = $data['total_equity'][$key] != 0
                    ? ($data['total_equity']['variance_' . $index] / abs($data['total_equity'][$key])) * 100
                    : 0;
            }
        }

        // Total Liabilities + Equity
        $data['total_liabilities_equity'] = [
            'current' => $data['total_liabilities']['current'] + $data['total_equity']['current']
        ];

        foreach ($periods as $index => $period) {
            if ($index === 0) continue;
            $key = 'compare_' . $index;
            $data['total_liabilities_equity'][$key] = $data['total_liabilities'][$key] + $data['total_equity'][$key];
            $data['total_liabilities_equity']['variance_' . $index] =
                $data['total_liabilities_equity']['current'] - $data['total_liabilities_equity'][$key];
            $data['total_liabilities_equity']['variance_pct_' . $index] = $data['total_liabilities_equity'][$key] != 0
                ? ($data['total_liabilities_equity']['variance_' . $index] / abs($data['total_liabilities_equity'][$key])) * 100
                : 0;
        }

        return $showZeroBalance ? $data : $this->filterZeroBalances($data, $periods);
    }

    /**
     * OPTIMIZED: Get all account balances for all periods in minimal queries
     */
    private function getBalancesForAllPeriods($companyId, $allAccounts, $periods): array
    {
        $balancesByPeriod = [];
        $activeAccountIds = [];

        foreach ($periods as $index => $period) {
            $periodKey = ($index === 0) ? 'current' : 'compare_' . $index;
            $asOfDate = $period['date'];

            // Single aggregated query for ALL accounts up to this date
            // PostgreSQL compatible
            $transactionBalances = Transaction::where('company_id', $companyId)
                ->where('status', 1)
                ->whereDate('date', '<=', $asOfDate)
                ->groupBy('account_id')
                ->select(
                    'account_id',
                    DB::raw('SUM(debit) as total_debit'),
                    DB::raw('SUM(credit) as total_credit')
                )
                ->get()
                ->keyBy('account_id');

            // Track accounts that have any transaction activity
            foreach ($transactionBalances->keys() as $accountId) {
                $activeAccountIds[$accountId] = true;
            }

            // Build balances for this period
            $periodBalances = [];
            foreach ($allAccounts as $accountId => $account) {
                $txBalance = $transactionBalances[$accountId] ?? null;

                if ($txBalance) {
                    // Calculate based on account category normal balance side
                    if (in_array($account->category, ['Assets', 'Expenses', 'COGS'])) {
                        $balance = $txBalance->total_debit - $txBalance->total_credit;
                    } else {
                        $balance = $txBalance->total_credit - $txBalance->total_debit;
                    }
                } else {
                    $balance = 0;
                }

                $periodBalances[$accountId] = $balance;
            }

            $balancesByPeriod[$periodKey] = $periodBalances;
        }
        // return $balancesByPeriod;
        return ['balances' => $balancesByPeriod, 'activeIds' => array_keys($activeAccountIds)];
    }

    /**
     * Split assets or liabilities into current and non-current per IAS 1.
     * Current assets: Cash, Receivables, Inventory, Prepayments, Other Current (account# < 160 or < 1600)
     * Non-current assets: Fixed Assets, Intangibles, Investments (account# >= 160 or >= 1600)
     * Current liabilities: Payables, Accrued, Tax, Credit Cards, Other Current (account# < 250 or < 2500)
     * Non-current liabilities: Loans, Long-term borrowings (account# >= 250 or >= 2500)
     */
    private function splitCurrentNonCurrent($categoryData, $allAccounts, $category)
    {
        if ($category === 'Assets') {
            $isNonCurrent = function ($account) use ($allAccounts) {
                $subType = $account->sub_type;
                $name = strtolower($account->name);
                // Check parent group sub_type too
                $parentSubType = $account->parent_id ? ($allAccounts[$account->parent_id]->sub_type ?? null) : null;

                return in_array($subType, ['FIXED_ASSET', 'NON_CURRENT_ASSET']) ||
                    in_array($parentSubType, ['FIXED_ASSET', 'NON_CURRENT_ASSET']) ||
                    str_contains($name, 'fixed asset') || str_contains($name, 'equipment') ||
                    str_contains($name, 'property') || str_contains($name, 'vehicle') ||
                    str_contains($name, 'building') || str_contains($name, 'land') ||
                    str_contains($name, 'furniture') || str_contains($name, 'intangible') ||
                    str_contains($name, 'patent') || str_contains($name, 'software license') ||
                    str_contains($name, 'accumulated depreciation') || str_contains($name, 'investment') ||
                    str_contains($name, 'leasehold') || str_contains($name, 'computer hardware');
            };
        } else {
            $isNonCurrent = function ($account) use ($allAccounts) {
                $subType = $account->sub_type;
                $name = strtolower($account->name);
                $parentSubType = $account->parent_id ? ($allAccounts[$account->parent_id]->sub_type ?? null) : null;

                return $subType === 'NON_CURRENT_LIABILITY' ||
                    $parentSubType === 'NON_CURRENT_LIABILITY' ||
                    str_contains($name, 'long term') || str_contains($name, 'long-term') ||
                    str_contains($name, 'loan') || str_contains($name, 'borrowing') ||
                    str_contains($name, 'note payable') || str_contains($name, 'mortgage');
            };
        }

        $currentGroups = [];
        $nonCurrentGroups = [];

        foreach ($categoryData['groups'] as $group) {
            if ($isNonCurrent($group['group'])) {
                $nonCurrentGroups[] = $group;
            } else {
                $currentGroups[] = $group;
            }
        }

        $currentAccounts = [];
        $nonCurrentAccounts = [];

        foreach ($categoryData['accounts'] as $account) {
            if ($isNonCurrent($account['account'])) {
                $nonCurrentAccounts[] = $account;
            } else {
                $currentAccounts[] = $account;
            }
        }

        return [
            ['accounts' => $currentAccounts, 'groups' => $currentGroups],
            ['accounts' => $nonCurrentAccounts, 'groups' => $nonCurrentGroups],
        ];
    }

    private function calculatePeriods($asOfDate, $comparePeriods, $periodType, $customDate = null)
    {
        $periods = [];
        $baseDate = \Carbon\Carbon::parse($asOfDate);

        if ($periodType === 'custom_date' && $customDate) {
            $periods[] = [
                'date' => $baseDate->format('Y-m-d'),
                'label' => $baseDate->format('d M Y')
            ];

            $cDate = \Carbon\Carbon::parse($customDate);
            $periods[] = [
                'date' => $cDate->format('Y-m-d'),
                'label' => 'Custom (' . $cDate->format('d M Y') . ')'
            ];

            return $periods;
        }

        if ($periodType === 'fiscal_year') {
            $companyId = loggedCompanyId()['company_id'];
            $currentFiscalYear = AccountingPeriod::where('company_id', $companyId)
                ->where('start_date', '<=', $baseDate->toDateString())
                ->where('end_date', '>=', $baseDate->toDateString())
                ->orderByDesc('start_date')
                ->first();

            if ($currentFiscalYear) {
                $baseDate = $currentFiscalYear->end_date->copy();
            }

            $periods[] = [
                'date' => $baseDate->format('Y-m-d'),
                'label' => $currentFiscalYear?->name ?: $baseDate->format('d M Y')
            ];

            $fiscalYears = AccountingPeriod::where('company_id', $companyId)
                ->where('end_date', '<', $baseDate->toDateString())
                ->orderBy('start_date', 'desc')
                ->take($comparePeriods)
                ->get();

            foreach ($fiscalYears as $fy) {
                $periods[] = [
                    'date' => $fy->end_date->format('Y-m-d'),
                    'label' => $fy->name ?: ($fy->start_date->format('d M Y') . ' - ' . $fy->end_date->format('d M Y'))
                ];
            }

            return $periods;
        }

        for ($i = 0; $i <= $comparePeriods; $i++) {
            $date = clone $baseDate;
            if ($i > 0) {
                if ($periodType === 'month') {
                    $date->subMonths($i)->endOfMonth();
                } elseif ($periodType === 'quarter') {
                    $date->subQuarters($i)->endOfQuarter();
                } elseif ($periodType === 'year') {
                    $date->subYears($i)->endOfYear();
                }
            }
            $periods[] = [
                'date' => $date->format('Y-m-d'),
                'label' => $date->format('d M Y')
            ];
        }

        return $periods;
    }

    private function resolveAsOfDate(Request $request, int $companyId, string $periodType): string
    {
        if ($request->fiscal_year) {
            $fiscalPeriod = AccountingPeriod::where('company_id', $companyId)
                ->whereKey($request->fiscal_year)
                ->first();

            if ($fiscalPeriod) {
                return $fiscalPeriod->end_date->format('Y-m-d');
            }
        }

        $asOfDate = $request->input('as_of_date', now()->format('Y-m-d'));

        if ($periodType === 'fiscal_year') {
            $fiscalPeriod = AccountingPeriod::where('company_id', $companyId)
                ->where('start_date', '<=', $asOfDate)
                ->where('end_date', '>=', $asOfDate)
                ->orderByDesc('start_date')
                ->first();

            if ($fiscalPeriod) {
                return $fiscalPeriod->end_date->format('Y-m-d');
            }
        }

        return $asOfDate;
    }

    /**
     * OPTIMIZED: Use pre-calculated balances instead of querying for each account
     */
    // private function getBalancesByCategory($allAccounts, $balancesByPeriod, $category, $periods)
    private function getBalancesByCategory($allAccounts, $balancesByPeriod, $category, $periods, array $activeAccountIds = [])
    {
        $accounts = $allAccounts->where('category', $category)->where('type', 'Account');
        if ($category === 'Equity') {
            // Current Year Earnings is a calculated report line. Excluding a
            // legacy physical account prevents duplicate presentation while
            // preserving its posted history for the production audit.
            $accounts = $accounts->reject(fn ($account) =>
                $account->system_account_type === 'CURRENT_YEAR_EARNINGS'
                || strcasecmp(trim((string) $account->name), 'Current Year Earnings') === 0
            );
        }

        $balances = [];
        $groups = [];

        foreach ($accounts as $account) {
            if (!empty($activeAccountIds) && !in_array($account->id, $activeAccountIds)) {
                continue;
            }
            $periodBalances = [];

            // Get pre-calculated balances for each period
            foreach ($periods as $index => $period) {
                $key = ($index === 0) ? 'current' : 'compare_' . $index;
                $periodBalances[$key] = $balancesByPeriod[$key][$account->id] ?? 0;
            }

            // Calculate variances
            foreach ($periods as $index => $period) {
                if ($index === 0) continue;
                $key = 'compare_' . $index;
                $periodBalances['variance_' . $index] = $periodBalances['current'] - $periodBalances[$key];
                $periodBalances['variance_pct_' . $index] = $periodBalances[$key] != 0
                    ? ($periodBalances['variance_' . $index] / abs($periodBalances[$key])) * 100
                    : 0;
            }

            $entry = array_merge(['account' => $account], $periodBalances);

            if ($account->parent_id) {
                if (!isset($groups[$account->parent_id])) {
                    $groups[$account->parent_id] = [
                        'group' => $allAccounts[$account->parent_id],
                        'accounts' => [],
                        'total' => $this->initializeEmptyTotals($periods)
                    ];
                }

                $groups[$account->parent_id]['accounts'][] = $entry;
                foreach ($periods as $index => $period) {
                    $key = ($index === 0) ? 'current' : 'compare_' . $index;
                    $groups[$account->parent_id]['total'][$key] += $periodBalances[$key];
                }
            } else {
                $balances[] = $entry;
            }
        }

        // Calculate variance for groups
        foreach ($groups as &$grp) {
            foreach ($periods as $index => $period) {
                if ($index === 0) continue;
                $grp['total']['variance_' . $index] = $grp['total']['current'] - $grp['total']['compare_' . $index];
                $grp['total']['variance_pct_' . $index] = $grp['total']['compare_' . $index] != 0
                    ? ($grp['total']['variance_' . $index] / abs($grp['total']['compare_' . $index])) * 100
                    : 0;
            }
        }

        return [
            'accounts' => $balances,
            'groups' => array_values($groups)
        ];
    }

    private function initializeEmptyTotals($periods)
    {
        $totals = [];
        foreach ($periods as $index => $period) {
            $key = ($index === 0) ? 'current' : 'compare_' . $index;
            $totals[$key] = 0;
        }
        return $totals;
    }

    /**
     * Calculate all-time cumulative earnings (Revenue - COGS - Expenses).
     * Used to ensure the Balance Sheet always balances regardless of whether closing entries exist.
     */
    private function calculateAllTimeEarnings($allAccounts, $balancesByPeriod, $periods)
    {
        $revenueAccounts = $allAccounts->where('category', 'Revenue')->pluck('id')->toArray();
        $expenseAccounts = $allAccounts->where('category', 'Expenses')->pluck('id')->toArray();
        $cogsAccounts = $allAccounts->where('category', 'COGS')->pluck('id')->toArray();

        $earnings = [];

        foreach ($periods as $index => $period) {
            $key = ($index === 0) ? 'current' : 'compare_' . $index;
            $periodBalances = $balancesByPeriod[$key];

            $revenue = 0;
            $expenses = 0;
            $cogs = 0;

            foreach ($revenueAccounts as $accId) {
                $revenue += $periodBalances[$accId] ?? 0;
            }
            foreach ($expenseAccounts as $accId) {
                $expenses += $periodBalances[$accId] ?? 0;
            }
            foreach ($cogsAccounts as $accId) {
                $cogs += $periodBalances[$accId] ?? 0;
            }

            $earnings[$key] = $revenue - $cogs - $expenses;
        }

        // Calculate variances
        foreach ($periods as $index => $period) {
            if ($index === 0) continue;
            $key = 'compare_' . $index;
            $earnings['variance_' . $index] = $earnings['current'] - $earnings[$key];
            $earnings['variance_pct_' . $index] = $earnings[$key] != 0
                ? ($earnings['variance_' . $index] / abs($earnings[$key])) * 100
                : 0;
        }

        return $earnings;
    }

    /**
     * Calculate current fiscal year earnings only (Revenue - COGS - Expenses within the FY).
     * Shown as a separate line on the Balance Sheet for proper disclosure.
     */
    private function calculateCurrentYearEarnings($companyId, $allAccounts, $periods)
    {
        $revenueIds = $allAccounts->where('category', 'Revenue')->pluck('id')->toArray();
        $expenseIds = $allAccounts->where('category', 'Expenses')->pluck('id')->toArray();
        $cogsIds = $allAccounts->where('category', 'COGS')->pluck('id')->toArray();
        $incomeStatementIds = array_merge($revenueIds, $expenseIds, $cogsIds);

        $earnings = [];

        foreach ($periods as $index => $period) {
            $key = ($index === 0) ? 'current' : 'compare_' . $index;
            $asOfDate = $period['date'];

            if (empty($incomeStatementIds)) {
                $earnings[$key] = 0;
                continue;
            }

            // Find fiscal year containing this date
            $fiscalYear = \App\Models\AccountingPeriod::where('company_id', $companyId)
                ->where('start_date', '<=', $asOfDate)
                ->where('end_date', '>=', $asOfDate)
                ->first();

            // Never invent a calendar fiscal year. Missing configuration is
            // surfaced by the accounting audit and contributes no calculated CYE.
            if (! $fiscalYear) {
                $earnings[$key] = 0;
                continue;
            }
            $fyStart = $fiscalYear->start_date->format('Y-m-d');

            // Query income statement accounts within fiscal year only
            $fyBalances = Transaction::where('company_id', $companyId)
                ->where('status', 1)
                ->whereIn('account_id', $incomeStatementIds)
                ->whereDate('date', '>=', $fyStart)
                ->whereDate('date', '<=', $asOfDate)
                ->groupBy('account_id')
                ->select(
                    'account_id',
                    DB::raw('SUM(debit) as total_debit'),
                    DB::raw('SUM(credit) as total_credit')
                )
                ->get()
                ->keyBy('account_id');

            $revenue = 0;
            $expenses = 0;
            $cogs = 0;

            foreach ($revenueIds as $accId) {
                $tx = $fyBalances[$accId] ?? null;
                if ($tx) $revenue += $tx->total_credit - $tx->total_debit;
            }
            foreach ($expenseIds as $accId) {
                $tx = $fyBalances[$accId] ?? null;
                if ($tx) $expenses += $tx->total_debit - $tx->total_credit;
            }
            foreach ($cogsIds as $accId) {
                $tx = $fyBalances[$accId] ?? null;
                if ($tx) $cogs += $tx->total_debit - $tx->total_credit;
            }

            $earnings[$key] = $revenue - $cogs - $expenses;
        }

        // Calculate variances
        foreach ($periods as $index => $period) {
            if ($index === 0) continue;
            $key = 'compare_' . $index;
            $earnings['variance_' . $index] = $earnings['current'] - $earnings[$key];
            $earnings['variance_pct_' . $index] = $earnings[$key] != 0
                ? ($earnings['variance_' . $index] / abs($earnings[$key])) * 100
                : 0;
        }

        return $earnings;
    }

    private function calculateGrandTotal($categoryData, $periods)
    {
        $grandTotal = $this->initializeEmptyTotals($periods);

        foreach ($categoryData['accounts'] as $acc) {
            foreach ($periods as $index => $period) {
                $key = ($index === 0) ? 'current' : 'compare_' . $index;
                $grandTotal[$key] += $acc[$key];
            }
        }

        foreach ($categoryData['groups'] as $grp) {
            foreach ($periods as $index => $period) {
                $key = ($index === 0) ? 'current' : 'compare_' . $index;
                $grandTotal[$key] += $grp['total'][$key];
            }
        }

        foreach ($periods as $index => $period) {
            if ($index === 0) continue;
            $key = 'compare_' . $index;
            $grandTotal['variance_' . $index] = $grandTotal['current'] - $grandTotal[$key];
            $grandTotal['variance_pct_' . $index] = $grandTotal[$key] != 0
                ? ($grandTotal['variance_' . $index] / abs($grandTotal[$key])) * 100
                : 0;
        }

        return $grandTotal;
    }

    private function filterZeroBalances($data, $periods)
    {
        foreach (['assets', 'liabilities', 'equity', 'current_assets', 'non_current_assets', 'current_liabilities', 'non_current_liabilities'] as $category) {
            if (!isset($data[$category])) continue;
            $data[$category]['accounts'] = array_filter($data[$category]['accounts'], function ($item) use ($periods) {
                foreach ($periods as $index => $period) {
                    $key = ($index === 0) ? 'current' : 'compare_' . $index;
                    if ($item[$key] != 0) return true;
                }
                return false;
            });

            $data[$category]['groups'] = array_filter($data[$category]['groups'], function ($group) use ($periods) {
                $group['accounts'] = array_filter($group['accounts'], function ($item) use ($periods) {
                    foreach ($periods as $index => $period) {
                        $key = ($index === 0) ? 'current' : 'compare_' . $index;
                        if ($item[$key] != 0) return true;
                    }
                    return false;
                });
                return count($group['accounts']) > 0;
            });
        }

        return $data;
    }

    public function export(Request $request)
    {
        $format = $request->input('format', 'pdf');
        $this->logReportAccess($request, 'Balance Sheet', 'export', $format);

        switch ($format) {
            case 'csv':
                return $this->exportToCsv($request);
            default:
                return $this->exportToPdf($request);
        }
    }

    public function exportToPdf($request)
    {
        $companyId = loggedCompanyId()['company_id'];
        $comparePeriods = (int) $request->input('compare_periods', 0);
        $periodType = $request->input('period_type', 'month');
        $asOfDate = $this->resolveAsOfDate($request, $companyId, $periodType);
        $compareDate = $request->input('compare_date');
        $showZeroBalance = $request->input('show_zero_balance', false);
        $showVarianceAmount = $request->input('show_variance_amount', false);
        $showVariancePercentage = $request->input('show_variance_percentage', false);

        if ($periodType === 'custom_date') {
            $comparePeriods = 1;
        }

        $balanceSheet = $this->generateBalanceSheet($companyId, $asOfDate, $comparePeriods, $periodType, $showZeroBalance, $compareDate);
        $company = auth('web')->user()->company;
        $invoiceTemplate = \App\Models\InvoiceTemplate::where('company_id', $companyId)->where('is_default', true)->first();

        $narrative = app(FinancialNarrativeService::class)->balanceSheet($balanceSheet);

        $pdf = Pdf::loadView('pages.account.reports.pdf.balance-sheet-pdf', compact(
            'balanceSheet',
            'asOfDate',
            'comparePeriods',
            'periodType',
            'company',
            'showVarianceAmount',
            'showVariancePercentage',
            'invoiceTemplate',
            'narrative'
        ));

        return $pdf->download('balance-sheet-' . $asOfDate . '.pdf');
    }

    public function exportToCsv($request)
    {
        $companyId = loggedCompanyId()['company_id'];
        $comparePeriods = (int) $request->input('compare_periods', 0);
        $periodType = $request->input('period_type', 'month');
        $asOfDate = $this->resolveAsOfDate($request, $companyId, $periodType);
        $compareDate = $request->input('compare_date');
        $showZeroBalance = $request->input('show_zero_balance', false);

        if ($periodType === 'custom_date') {
            $comparePeriods = 1;
        }

        $balanceSheet = $this->generateBalanceSheet($companyId, $asOfDate, $comparePeriods, $periodType, $showZeroBalance, $compareDate);
        $periods = $balanceSheet['periods'];

        // Build column headers
        $colHeaders = ['Account'];
        foreach ($periods as $index => $period) {
            $colHeaders[] = $period['label'];
            if ($index > 0) {
                $colHeaders[] = 'Variance';
                $colHeaders[] = 'Variance %';
            }
        }
        $colCount = count($colHeaders);

        $spreadsheet = $this->createReportSpreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Balance Sheet');
        $row = 1;

        $this->writeTitle($sheet, 'Balance Sheet', $row, $colCount);
        $this->writeCompanyRow($sheet, $row, $colCount);
        $this->writeMeta($sheet, [
            ['As of Date:', $periods[0]['label']],
            ['Generated:', now()->format('d M Y, h:i A')],
        ], $row);

        $this->writeColumnHeaders($sheet, $colHeaders, $row);

        // ASSETS
        $this->writeSectionHeader($sheet, 'ASSETS', $row, $colCount);
        $this->writeExcelCategory($sheet, $balanceSheet['assets'], $periods, $row, $colCount);
        $this->writeExcelTotalRow($sheet, 'Total Assets', $balanceSheet['total_assets'], $periods, $row, $colCount, false);
        $row++;

        // LIABILITIES
        $this->writeSectionHeader($sheet, 'LIABILITIES', $row, $colCount);
        $this->writeExcelCategory($sheet, $balanceSheet['liabilities'], $periods, $row, $colCount);
        $this->writeExcelTotalRow($sheet, 'Total Liabilities', $balanceSheet['total_liabilities'], $periods, $row, $colCount, false);
        $row++;

        // EQUITY
        $this->writeSectionHeader($sheet, 'EQUITY', $row, $colCount);
        $this->writeExcelCategory($sheet, $balanceSheet['equity'], $periods, $row, $colCount);
        if (($balanceSheet['prior_year_retained']['current'] ?? 0) != 0) {
            $this->writeExcelTotalRow($sheet, 'Prior Year Retained Earnings', $balanceSheet['prior_year_retained'], $periods, $row, $colCount, false);
        }
        $this->writeExcelTotalRow($sheet, 'Current Year Earnings', $balanceSheet['current_year_earnings'], $periods, $row, $colCount, false);
        $this->writeExcelTotalRow($sheet, 'Total Equity', $balanceSheet['total_equity'], $periods, $row, $colCount, false);
        $row++;

        // GRAND TOTAL
        $this->writeExcelTotalRow($sheet, 'TOTAL LIABILITIES & EQUITY', $balanceSheet['total_liabilities_equity'], $periods, $row, $colCount, true);

        $this->autoSizeColumns($sheet, $colCount);

        $filename = 'balance-sheet-' . $asOfDate . '.xlsx';
        return $this->downloadExcel($spreadsheet, $filename);
    }

    private function writeExcelCategory($sheet, $categoryData, $periods, int &$row, int $colCount): void
    {
        foreach ($categoryData['groups'] as $group) {
            $vals = [$group['group']->name];
            foreach ($periods as $index => $period) {
                $key = ($index === 0) ? 'current' : 'compare_' . $index;
                $vals[] = numberFormat($group['total'][$key]);
                if ($index > 0) {
                    $vals[] = numberFormat($group['total']['variance_' . $index]);
                    $vals[] = numberFormat($group['total']['variance_pct_' . $index]) . '%';
                }
            }
            $this->writeSubtotalRow($sheet, $vals, $row, $colCount);

            foreach ($group['accounts'] as $account) {
                $vals = [$account['account']->name];
                foreach ($periods as $index => $period) {
                    $key = ($index === 0) ? 'current' : 'compare_' . $index;
                    $vals[] = numberFormat($account[$key]);
                    if ($index > 0) {
                        $vals[] = numberFormat($account['variance_' . $index]);
                        $vals[] = numberFormat($account['variance_pct_' . $index]) . '%';
                    }
                }
                $this->writeDataRow($sheet, $vals, $row, $colCount, true);
            }
        }

        foreach ($categoryData['accounts'] as $account) {
            $vals = [$account['account']->name];
            foreach ($periods as $index => $period) {
                $key = ($index === 0) ? 'current' : 'compare_' . $index;
                $vals[] = numberFormat($account[$key]);
                if ($index > 0) {
                    $vals[] = numberFormat($account['variance_' . $index]);
                    $vals[] = numberFormat($account['variance_pct_' . $index]) . '%';
                }
            }
            $this->writeDataRow($sheet, $vals, $row, $colCount);
        }
    }

    private function writeExcelTotalRow($sheet, string $label, $totalData, $periods, int &$row, int $colCount, bool $grand): void
    {
        $vals = [$label];
        foreach ($periods as $index => $period) {
            $key = ($index === 0) ? 'current' : 'compare_' . $index;
            $vals[] = numberFormat($totalData[$key]);
            if ($index > 0) {
                $vals[] = numberFormat($totalData['variance_' . $index]);
                $vals[] = numberFormat($totalData['variance_pct_' . $index]) . '%';
            }
        }
        $grand
            ? $this->writeGrandTotalRow($sheet, $vals, $row, $colCount)
            : $this->writeSubtotalRow($sheet, $vals, $row, $colCount);
    }
}
