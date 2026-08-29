<?php

namespace App\Http\Controllers\Account\Report;

use App\Http\Controllers\Controller;
use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\Transaction;
use App\Services\Account\FinancialNarrativeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfitLossController extends Controller
{
    use \App\Traits\LogsReportAccess, \App\Traits\ExportsToExcel;
    public function profitLoss(Request $request)
    {
        $companyId = loggedCompanyId()['company_id'];
        $comparePeriods = (int) $request->input('compare_periods', 0);
        $periodType = $request->input('period_type', 'month');
        $showZeroBalance = $request->input('show_zero_balance', false);

        $compareFromDate = $request->input('compare_from_date');
        $compareToDate = $request->input('compare_to_date');

        if ($periodType === 'custom_date') {
            $comparePeriods = 1;
        }

        [$startDate, $endDate] = resolveDateRange($request, $request->date_range ?? 'year');

        $profitLoss = $this->generateProfitLoss($companyId, $startDate, $endDate, $comparePeriods, $periodType, $showZeroBalance, $compareFromDate, $compareToDate);

        if ($request->ajax()) {
            $this->logReportAccess($request, 'Profit & Loss', 'filter');

            return response()->json([
                'html' => view('pages.account.reports.partials.profit-loss-table', compact(
                    'profitLoss',
                    'startDate',
                    'endDate',
                    'comparePeriods',
                    'periodType'
                ))->render(),
                'summary' => [
                    'total_revenue' => numberFormat($profitLoss['total_revenue']['current'] + $profitLoss['total_other_income']['current']),
                    'total_expenses' => numberFormat($profitLoss['total_expenses']['current'] + $profitLoss['total_finance_costs']['current'] + $profitLoss['total_tax_expenses']['current']),
                    'net_profit' => numberFormat($profitLoss['net_profit']['current']),
                ],
            ]);
        }

        $this->logReportAccess($request, 'Profit & Loss', 'view');

        $narrative = app(FinancialNarrativeService::class)->profitLoss($profitLoss);

        return view('pages.account.reports.profit-loss', compact(
            'profitLoss',
            'startDate',
            'endDate',
            'comparePeriods',
            'periodType',
            'narrative'
        ));
    }

    private function generateProfitLoss($companyId, $startDate, $endDate, $comparePeriods = 0, $periodType = 'month', $showZeroBalance = false, $compareFromDate = null, $compareToDate = null)
    {
        // Load all accounts (small dataset, usually < 100 records)
        $allAccounts = ChartOfAccount::forCompany(loggedCompanyId()['company_id'])->orderBy('account_number')->get()->keyBy('id');

        // Calculate periods
        $periods = $this->calculatePeriods($startDate, $endDate, $comparePeriods, $periodType, $compareFromDate, $compareToDate);

        // ============================================
        // OPTIMIZED: Get all balances in aggregated queries
        // ============================================
        // $balancesByPeriod = $this->getBalancesForAllPeriods($companyId, $allAccounts, $periods);
        ['balances' => $balancesByPeriod, 'activeIds' => $activeAccountIds] = $this->getBalancesForAllPeriods($companyId, $allAccounts, $periods);

        // Get all revenue (including other income), then split
        $allRevenue = $this->getProfitLossBalancesByCategory($allAccounts, $balancesByPeriod, 'Revenue', $periods, $activeAccountIds);
        [$operatingRevenue, $otherIncome] = $this->splitOtherIncome($allRevenue, $allAccounts);

        // Split expenses into operating expenses, finance costs, and tax
        $allExpenses = $this->getProfitLossBalancesByCategory($allAccounts, $balancesByPeriod, 'Expenses', $periods, $activeAccountIds);
        [$operatingExpenses, $financeCosts, $taxExpenses] = $this->splitExpenseTypes($allExpenses, $allAccounts);

        $data = [
            'revenue' => $operatingRevenue,
            'other_income' => $otherIncome,
            'cogs' => $this->getProfitLossBalancesByCategory($allAccounts, $balancesByPeriod, 'COGS', $periods, $activeAccountIds),
            'expenses' => $operatingExpenses,
            'finance_costs' => $financeCosts,
            'tax_expenses' => $taxExpenses,
            'periods' => $periods
        ];

        // Calculate totals
        $data['total_revenue'] = $this->calculateProfitLossTotal($data['revenue'], $periods);
        $data['total_other_income'] = $this->calculateProfitLossTotal($data['other_income'], $periods);
        $data['total_cogs'] = $this->calculateProfitLossTotal($data['cogs'], $periods);
        $data['total_expenses'] = $this->calculateProfitLossTotal($data['expenses'], $periods);
        $data['total_finance_costs'] = $this->calculateProfitLossTotal($data['finance_costs'], $periods);
        $data['total_tax_expenses'] = $this->calculateProfitLossTotal($data['tax_expenses'], $periods);

        // Gross Profit = Operating Revenue - COGS
        $data['gross_profit'] = $this->initializeEmptyTotals($periods);
        foreach ($periods as $index => $period) {
            $key = ($index === 0) ? 'current' : 'compare_' . $index;
            $data['gross_profit'][$key] = $data['total_revenue'][$key] - $data['total_cogs'][$key];
        }
        $data['gross_profit'] = $this->calculateVariancesForTotal($data['gross_profit'], $periods);

        // Operating Profit = Gross Profit - Operating Expenses
        $data['operating_profit'] = $this->initializeEmptyTotals($periods);
        foreach ($periods as $index => $period) {
            $key = ($index === 0) ? 'current' : 'compare_' . $index;
            $data['operating_profit'][$key] = $data['gross_profit'][$key] - $data['total_expenses'][$key];
        }
        $data['operating_profit'] = $this->calculateVariancesForTotal($data['operating_profit'], $periods);

        // Profit Before Tax = Operating Profit + Other Income - Finance Costs
        $data['profit_before_tax'] = $this->initializeEmptyTotals($periods);
        foreach ($periods as $index => $period) {
            $key = ($index === 0) ? 'current' : 'compare_' . $index;
            $data['profit_before_tax'][$key] = $data['operating_profit'][$key]
                + $data['total_other_income'][$key]
                - $data['total_finance_costs'][$key];
        }
        $data['profit_before_tax'] = $this->calculateVariancesForTotal($data['profit_before_tax'], $periods);

        // Net Profit = Profit Before Tax - Tax
        $data['net_profit'] = $this->initializeEmptyTotals($periods);
        foreach ($periods as $index => $period) {
            $key = ($index === 0) ? 'current' : 'compare_' . $index;
            $data['net_profit'][$key] = $data['profit_before_tax'][$key] - $data['total_tax_expenses'][$key];
        }
        $data['net_profit'] = $this->calculateVariancesForTotal($data['net_profit'], $periods);

        if (!$showZeroBalance) {
            $data = $this->filterProfitLossZeroBalances($data, $periods);
        }

        return $data;
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
            $startDate = $period['start'];
            $endDate = $period['end'];

            // Single aggregated query for ALL accounts in this date range
            // PostgreSQL compatible
            $transactionBalances = Transaction::where('company_id', $companyId)
                ->where('status', 1)
                ->whereDate('date', '>=', $startDate)
                ->whereDate('date', '<=', $endDate)
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
                    // Calculate based on account category
                    if ($account->category === 'Revenue') {
                        // Revenue: Credit - Debit
                        $balance = $txBalance->total_credit - $txBalance->total_debit;
                    } else {
                        // COGS, Expenses: Debit - Credit
                        $balance = $txBalance->total_debit - $txBalance->total_credit;
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

    private function calculatePeriods($startDate, $endDate, $comparePeriods, $periodType, $customFrom = null, $customTo = null)
    {
        $periods = [];
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        if ($periodType === 'custom_date' && $customFrom && $customTo) {
            $cStart = Carbon::parse($customFrom);
            $cEnd = Carbon::parse($customTo);

            $periods[] = [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
                'label' => $start->format('M Y') . ($start->toDateString() !== $end->toDateString() ? ' - ' . $end->format('M Y') : '')
            ];

            $periods[] = [
                'start' => $cStart->toDateString(),
                'end' => $cEnd->toDateString(),
                'label' => 'Custom (' . $cStart->format('d M Y') . ' - ' . $cEnd->format('d M Y') . ')'
            ];

            return $periods;
        }

        if ($periodType === 'fiscal_year') {
            $companyId = loggedCompanyId()['company_id'];
            $currentFiscalYear = AccountingPeriod::where('company_id', $companyId)
                ->where('start_date', '<=', $end->toDateString())
                ->where('end_date', '>=', $end->toDateString())
                ->orderByDesc('start_date')
                ->first()
                ?? AccountingPeriod::where('company_id', $companyId)
                    ->where('start_date', '<=', $start->toDateString())
                    ->where('end_date', '>=', $start->toDateString())
                    ->orderByDesc('start_date')
                    ->first();

            if ($currentFiscalYear) {
                $start = $currentFiscalYear->start_date->copy();
                $end = $currentFiscalYear->end_date->copy();
            }

            $periods[] = [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
                'label' => $currentFiscalYear?->name ?: ($start->format('M Y') . ($start->toDateString() !== $end->toDateString() ? ' - ' . $end->format('M Y') : ''))
            ];

            $fiscalYears = AccountingPeriod::where('company_id', $companyId)
                ->where('end_date', '<', $start->toDateString())
                ->orderBy('start_date', 'desc')
                ->take($comparePeriods)
                ->get();

            foreach ($fiscalYears as $fy) {
                $fyStart = $fy->start_date;
                $fyEnd = $fy->end_date;
                $periods[] = [
                    'start' => $fyStart->format('Y-m-d'),
                    'end' => $fyEnd->format('Y-m-d'),
                    'label' => $fy->name ?: ($fyStart->format('M Y') . ' - ' . $fyEnd->format('M Y'))
                ];
            }

            return $periods;
        }

        for ($i = 0; $i <= $comparePeriods; $i++) {
            if ($i === 0) {
                $pStart = clone $start;
                $pEnd = clone $end;
            } else {
                if ($periodType === 'month') {
                    $pStart = (clone $start)->subMonths($i);
                    $pEnd = (clone $end)->subMonths($i);
                } elseif ($periodType === 'quarter') {
                    $pStart = (clone $start)->subQuarters($i);
                    $pEnd = (clone $end)->subQuarters($i);
                } elseif ($periodType === 'year') {
                    $pStart = (clone $start)->subYears($i);
                    $pEnd = (clone $end)->subYears($i);
                }
            }

            $periods[] = [
                'start' => $pStart->toDateString(),
                'end' => $pEnd->toDateString(),
                'label' => $pStart->format('M Y') . ($pStart->toDateString() !== $pEnd->toDateString() ? ' - ' . $pEnd->format('M Y') : '')
            ];
        }

        return $periods;
    }

    private function initializeEmptyTotals($periods)
    {
        $totals = [];
        foreach ($periods as $index => $period) {
            $key = ($index === 0) ? 'current' : 'compare_' . $index;
            $totals[$key] = 0;
            if ($index > 0) {
                $totals['variance_' . $index] = 0;
                $totals['variance_pct_' . $index] = 0;
            }
        }
        return $totals;
    }

    /**
     * OPTIMIZED: Use pre-calculated balances instead of querying for each account
     */
    // private function getProfitLossBalancesByCategory($allAccounts, $balancesByPeriod, $category, $periods)
    private function getProfitLossBalancesByCategory($allAccounts, $balancesByPeriod, $category, $periods, array $activeAccountIds = [])
    {
        $accounts = $allAccounts->where('category', $category)->where('type', 'Account');
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

            // Calculate Variances
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
                $parent = $allAccounts[$account->parent_id] ?? null;
                if ($parent) {
                    if (!isset($groups[$parent->id])) {
                        $groups[$parent->id] = [
                            'group' => $parent,
                            'accounts' => [],
                            'total' => $this->initializeEmptyTotals($periods),
                        ];
                    }
                    $groups[$parent->id]['accounts'][] = $entry;
                    foreach ($periods as $index => $period) {
                        $key = ($index === 0) ? 'current' : 'compare_' . $index;
                        $groups[$parent->id]['total'][$key] += $periodBalances[$key];
                    }
                }
            } else {
                $balances[] = $entry;
            }
        }

        // Calculate variances for groups
        foreach ($groups as &$group) {
            $group['total'] = $this->calculateVariancesForTotal($group['total'], $periods);
        }

        return ['accounts' => $balances, 'groups' => array_values($groups)];
    }

    private function calculateVariancesForTotal($totals, $periods)
    {
        foreach ($periods as $index => $period) {
            if ($index === 0) continue;
            $key = 'compare_' . $index;
            $totals['variance_' . $index] = $totals['current'] - $totals[$key];
            $totals['variance_pct_' . $index] = $totals[$key] != 0
                ? ($totals['variance_' . $index] / abs($totals[$key])) * 100
                : 0;
        }
        return $totals;
    }

    private function calculateProfitLossTotal($categoryData, $periods)
    {
        $total = $this->initializeEmptyTotals($periods);

        foreach ($categoryData['accounts'] as $acc) {
            foreach ($periods as $index => $period) {
                $key = ($index === 0) ? 'current' : 'compare_' . $index;
                $total[$key] += $acc[$key];
            }
        }

        foreach ($categoryData['groups'] as $grp) {
            foreach ($periods as $index => $period) {
                $key = ($index === 0) ? 'current' : 'compare_' . $index;
                $total[$key] += $grp['total'][$key];
            }
        }

        return $this->calculateVariancesForTotal($total, $periods);
    }

    /**
     * Split revenue data into operating revenue and other income.
     * "Other Income" groups are identified by name (e.g. "Other Income", "Other Revenue").
     */
    private function splitOtherIncome($revenueData, $allAccounts)
    {
        // Identify "Other Income" group IDs
        $otherIncomeGroupIds = $allAccounts->filter(
            fn($a) =>
            $a->category === 'Revenue' &&
                $a->type === 'Group' &&
                ($a->sub_type === 'OTHER_INCOME' ||
                    str_contains(strtolower($a->name), 'other income') ||
                    str_contains(strtolower($a->name), 'other revenue'))
        )->pluck('id')->toArray();

        $operatingGroups = [];
        $otherIncomeGroups = [];

        foreach ($revenueData['groups'] as $group) {
            if ($group['group']->sub_type === 'OTHER_INCOME' || in_array($group['group']->id, $otherIncomeGroupIds)) {
                $otherIncomeGroups[] = $group;
            } else {
                $operatingGroups[] = $group;
            }
        }

        $operatingAccounts = [];
        $otherIncomeAccounts = [];

        foreach ($revenueData['accounts'] as $account) {
            if ($account['account']->sub_type === 'OTHER_INCOME' || in_array($account['account']->parent_id, $otherIncomeGroupIds)) {
                $otherIncomeAccounts[] = $account;
            } else {
                $operatingAccounts[] = $account;
            }
        }

        return [
            ['accounts' => $operatingAccounts, 'groups' => $operatingGroups],
            ['accounts' => $otherIncomeAccounts, 'groups' => $otherIncomeGroups],
        ];
    }

    /**
     * Split expenses into operating expenses, finance costs, and tax expenses.
     */
    private function splitExpenseTypes($expenseData, $allAccounts)
    {
        // Identify finance cost group IDs
        $financeCostGroupIds = $allAccounts->filter(
            fn($a) =>
            $a->category === 'Expenses' && $a->type === 'Group' &&
                ($a->sub_type === 'FINANCE_COST' ||
                    str_contains(strtolower($a->name), 'finance') || str_contains(strtolower($a->name), 'interest') ||
                    str_contains(strtolower($a->name), 'bank fee') || str_contains(strtolower($a->name), 'bank charge'))
        )->pluck('id')->toArray();

        // Finance cost account patterns (sub_type or name)
        $isFinanceCost = function ($account) use ($financeCostGroupIds) {
            $name = strtolower($account->name);
            return $account->sub_type === 'FINANCE_COST' ||
                in_array($account->parent_id, $financeCostGroupIds) ||
                str_contains($name, 'interest expense') || str_contains($name, 'finance cost') ||
                str_contains($name, 'finance charge') || str_contains($name, 'bank fee') ||
                str_contains($name, 'bank charge') || str_contains($name, 'foreign exchange loss');
        };

        // Tax expense patterns (sub_type or name)
        $isTaxExpense = function ($account) {
            $name = strtolower($account->name);
            return $account->sub_type === 'TAX_EXPENSE' ||
                str_contains($name, 'income tax') || str_contains($name, 'tax expense') ||
                str_contains($name, 'corporate tax') || str_contains($name, 'provision for tax');
        };

        $operatingGroups = [];
        $financeGroups = [];
        $taxGroups = [];

        foreach ($expenseData['groups'] as $group) {
            if ($group['group']->sub_type === 'FINANCE_COST' || in_array($group['group']->id, $financeCostGroupIds)) {
                $financeGroups[] = $group;
            } elseif ($group['group']->sub_type === 'TAX_EXPENSE') {
                $taxGroups[] = $group;
            } else {
                $operatingGroups[] = $group;
            }
        }

        $operatingAccounts = [];
        $financeAccounts = [];
        $taxAccounts = [];

        foreach ($expenseData['accounts'] as $account) {
            if ($isFinanceCost($account['account'])) {
                $financeAccounts[] = $account;
            } elseif ($isTaxExpense($account['account'])) {
                $taxAccounts[] = $account;
            } else {
                $operatingAccounts[] = $account;
            }
        }

        return [
            ['accounts' => $operatingAccounts, 'groups' => $operatingGroups],
            ['accounts' => $financeAccounts, 'groups' => $financeGroups],
            ['accounts' => $taxAccounts, 'groups' => $taxGroups],
        ];
    }

    private function filterProfitLossZeroBalances($data, $periods)
    {
        foreach (['revenue', 'other_income', 'cogs', 'expenses', 'finance_costs', 'tax_expenses'] as $category) {
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

    public function exportProfitLossToPdf(Request $request)
    {
        $this->logReportAccess($request, 'Profit & Loss', 'export', 'pdf');

        $companyId = loggedCompanyId()['company_id'];
        $comparePeriods = (int) $request->input('compare_periods', 0);
        $periodType = $request->input('period_type', 'month');
        $showZeroBalance = $request->input('show_zero_balance', false);
        $showVarianceAmount = $request->input('show_variance_amount', false);
        $showVariancePercentage = $request->input('show_variance_percentage', false);

        $compareFromDate = $request->input('compare_from_date');
        $compareToDate = $request->input('compare_to_date');

        if ($periodType === 'custom_date') {
            $comparePeriods = 1;
        }

        [$startDate, $endDate] = resolveDateRange($request, $request->date_range ?? 'year');
        $profitLoss = $this->generateProfitLoss($companyId, $startDate, $endDate, $comparePeriods, $periodType, $showZeroBalance, $compareFromDate, $compareToDate);
        $company = auth('web')->user()->company;
        $invoiceTemplate = \App\Models\InvoiceTemplate::where('company_id', $companyId)->where('is_default', true)->first();

        $narrative = app(FinancialNarrativeService::class)->profitLoss($profitLoss);

        $pdf = Pdf::loadView('pages.account.reports.pdf.profit-loss-pdf', compact(
            'profitLoss',
            'startDate',
            'endDate',
            'comparePeriods',
            'periodType',
            'company',
            'showVarianceAmount',
            'showVariancePercentage',
            'invoiceTemplate',
            'narrative'
        ));

        $pdf->setPaper($invoiceTemplate->page_size ?? 'a4', 'landscape');

        return $pdf->download('profit-loss-' . $startDate . '-to-' . $endDate . '.pdf');
    }

    public function exportProfitLossToCsv(Request $request)
    {
        $this->logReportAccess($request, 'Profit & Loss', 'export', 'csv');

        $companyId = loggedCompanyId()['company_id'];
        $comparePeriods = (int) $request->input('compare_periods', 0);
        $periodType = $request->input('period_type', 'month');
        $showZeroBalance = $request->input('show_zero_balance', false);

        $compareFromDate = $request->input('compare_from_date');
        $compareToDate = $request->input('compare_to_date');

        if ($periodType === 'custom_date') {
            $comparePeriods = 1;
        }

        [$startDate, $endDate] = resolveDateRange($request, $request->date_range ?? 'year');
        $profitLoss = $this->generateProfitLoss($companyId, $startDate, $endDate, $comparePeriods, $periodType, $showZeroBalance, $compareFromDate, $compareToDate);
        $periods = $profitLoss['periods'];

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
        $sheet->setTitle('Profit & Loss');
        $row = 1;

        $this->writeTitle($sheet, 'Profit & Loss Statement', $row, $colCount);
        $this->writeCompanyRow($sheet, $row, $colCount);
        $this->writeMeta($sheet, [
            ['Period:', $periods[0]['label']],
            ['Generated:', now()->format('d M Y, h:i A')],
        ], $row);

        $this->writeColumnHeaders($sheet, $colHeaders, $row);

        // REVENUE
        $this->writeSectionHeader($sheet, 'REVENUE', $row, $colCount);
        $this->writePLCategory($sheet, $profitLoss['revenue'], $periods, $row, $colCount);
        $this->writePLTotalRow($sheet, 'Total Revenue', $profitLoss['total_revenue'], $periods, $row, $colCount, false);
        $row++;

        // COGS
        if (!empty($profitLoss['cogs']['accounts']) || !empty($profitLoss['cogs']['groups'])) {
            $this->writeSectionHeader($sheet, 'COST OF GOODS SOLD', $row, $colCount);
            $this->writePLCategory($sheet, $profitLoss['cogs'], $periods, $row, $colCount);
            $this->writePLTotalRow($sheet, 'Total COGS', $profitLoss['total_cogs'], $periods, $row, $colCount, false);
            $row++;
        }

        $this->writePLTotalRow($sheet, 'GROSS PROFIT', $profitLoss['gross_profit'], $periods, $row, $colCount, false);
        $row++;

        // EXPENSES
        $this->writeSectionHeader($sheet, 'EXPENSES', $row, $colCount);
        $this->writePLCategory($sheet, $profitLoss['expenses'], $periods, $row, $colCount);
        $this->writePLTotalRow($sheet, 'Total Expenses', $profitLoss['total_expenses'], $periods, $row, $colCount, false);
        $row++;

        $this->writePLTotalRow($sheet, 'OPERATING PROFIT', $profitLoss['operating_profit'], $periods, $row, $colCount, false);
        $row++;

        // OTHER INCOME
        if (!empty($profitLoss['other_income']['accounts']) || !empty($profitLoss['other_income']['groups'])) {
            $this->writeSectionHeader($sheet, 'OTHER INCOME', $row, $colCount);
            $this->writePLCategory($sheet, $profitLoss['other_income'], $periods, $row, $colCount);
            $this->writePLTotalRow($sheet, 'Total Other Income', $profitLoss['total_other_income'], $periods, $row, $colCount, false);
            $row++;
        }

        // FINANCE COSTS
        if (!empty($profitLoss['finance_costs']['accounts']) || !empty($profitLoss['finance_costs']['groups'])) {
            $this->writeSectionHeader($sheet, 'FINANCE COSTS', $row, $colCount);
            $this->writePLCategory($sheet, $profitLoss['finance_costs'], $periods, $row, $colCount);
            $this->writePLTotalRow($sheet, 'Total Finance Costs', $profitLoss['total_finance_costs'], $periods, $row, $colCount, false);
            $row++;
        }

        $this->writePLTotalRow($sheet, 'PROFIT BEFORE TAX', $profitLoss['profit_before_tax'], $periods, $row, $colCount, false);
        $row++;

        // TAX EXPENSE
        if (!empty($profitLoss['tax_expenses']['accounts']) || !empty($profitLoss['tax_expenses']['groups'])) {
            $this->writeSectionHeader($sheet, 'TAX EXPENSE', $row, $colCount);
            $this->writePLCategory($sheet, $profitLoss['tax_expenses'], $periods, $row, $colCount);
            $this->writePLTotalRow($sheet, 'Total Tax Expense', $profitLoss['total_tax_expenses'], $periods, $row, $colCount, false);
            $row++;
        }

        $this->writePLTotalRow($sheet, 'NET PROFIT', $profitLoss['net_profit'], $periods, $row, $colCount, true);

        $this->autoSizeColumns($sheet, $colCount);

        $filename = 'profit-loss-' . $startDate . '-to-' . $endDate . '.xlsx';
        return $this->downloadExcel($spreadsheet, $filename);
    }

    private function writePLCategory($sheet, $categoryData, $periods, int &$row, int $colCount): void
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

    private function writePLTotalRow($sheet, string $label, $totals, $periods, int &$row, int $colCount, bool $grand): void
    {
        $vals = [$label];
        foreach ($periods as $index => $period) {
            $key = ($index === 0) ? 'current' : 'compare_' . $index;
            $vals[] = numberFormat($totals[$key]);
            if ($index > 0) {
                $vals[] = numberFormat($totals['variance_' . $index]);
                $vals[] = numberFormat($totals['variance_pct_' . $index]) . '%';
            }
        }
        $grand
            ? $this->writeGrandTotalRow($sheet, $vals, $row, $colCount)
            : $this->writeSubtotalRow($sheet, $vals, $row, $colCount);
    }
}
