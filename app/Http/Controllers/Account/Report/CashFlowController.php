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

class CashFlowController extends Controller
{
    use \App\Traits\LogsReportAccess, \App\Traits\ExportsToExcel;
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

    public function cashFlow(Request $request)
    {
        $companyId = loggedCompanyId()['company_id'];
        [$startDate, $endDate] = resolveDateRange($request, $request->date_range ?? 'year');
        $comparePeriods = $request->compare_periods ?? 0;
        $periodType = $request->period_type ?? 'month';
        $compareFromDate = $request->input('compare_from_date');
        $compareToDate = $request->input('compare_to_date');

        if ($periodType === 'custom_date') {
            $comparePeriods = 1;
        }

        $showVarianceAmount = $request->show_variance_amount === 'true' || $request->show_variance_amount === '1';
        $showVariancePercentage = $request->show_variance_percentage === 'true' || $request->show_variance_percentage === '1';

        $periods = $this->calculatePeriods($startDate, $endDate, $comparePeriods, $periodType, $compareFromDate, $compareToDate);
        $multiPeriodCashFlow = $this->getMultiPeriodCashFlowData($companyId, $periods);

        if ($request->ajax()) {
            $this->logReportAccess($request, 'Cash Flow', 'filter');

            return response()->json([
                'html' => view('pages.account.reports.partials.cash-flow-table', [
                    'cashFlow' => $multiPeriodCashFlow,
                    'showVarianceAmount' => $showVarianceAmount,
                    'showVariancePercentage' => $showVariancePercentage,
                    'periodType' => $periodType
                ])->render(),
                'summary' => [
                    'opening_balance' => numberFormat($multiPeriodCashFlow['data'][0]['opening_balance']),
                    'net_cash_from_operating' => numberFormat($multiPeriodCashFlow['data'][0]['net_cash_from_operating']),
                    'net_cash_from_investing' => numberFormat($multiPeriodCashFlow['data'][0]['net_cash_from_investing']),
                    'net_cash_from_financing' => numberFormat($multiPeriodCashFlow['data'][0]['net_cash_from_financing']),
                    'net_cash_movement' => numberFormat($multiPeriodCashFlow['data'][0]['net_cash_movement']),
                    'closing_balance' => numberFormat($multiPeriodCashFlow['data'][0]['closing_balance']),
                ],
            ]);
        }

        $this->logReportAccess($request, 'Cash Flow', 'view');

        $narrative = app(FinancialNarrativeService::class)->cashFlow($multiPeriodCashFlow);

        return view('pages.account.reports.cash-flow', [
            'cashFlow' => $multiPeriodCashFlow,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'comparePeriods' => $comparePeriods,
            'periodType' => $periodType,
            'showVarianceAmount' => $showVarianceAmount,
            'showVariancePercentage' => $showVariancePercentage,
            'narrative' => $narrative,
        ]);
    }

    private function getMultiPeriodCashFlowData($companyId, $periods)
    {
        // 1. Fetch Accounts
        $allAccounts = ChartOfAccount::forCompany(loggedCompanyId()['company_id'])->get()->keyBy('id');

        // 2. Build Aggregated Query
        $selects = [];
        $bindings = [];

        foreach ($periods as $index => $period) {
            // Opening Balance for this period (Everything before start date)
            $selects[] = "COALESCE(SUM(CASE WHEN date < ? THEN debit - credit ELSE 0 END), 0) AS opening_{$index}";
            $bindings[] = $period['start'];

            // Period Debits
            $selects[] = "COALESCE(SUM(CASE WHEN date >= ? AND date <= ? THEN debit ELSE 0 END), 0) AS debit_{$index}";
            $bindings[] = $period['start'];
            $bindings[] = $period['end'];

            // Period Credits
            $selects[] = "COALESCE(SUM(CASE WHEN date >= ? AND date <= ? THEN credit ELSE 0 END), 0) AS credit_{$index}";
            $bindings[] = $period['start'];
            $bindings[] = $period['end'];
        }

        $aggregatedResults = Transaction::where('company_id', $companyId)
            ->where('status', 1)
            ->selectRaw('account_id, ' . implode(', ', $selects), $bindings)
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');

        // 3. Process Data for Each Period
        $periodData = [];

        foreach ($periods as $index => $period) {

            // Map aggregated results to a predictable structure for this period index
            $aggregates = [];
            foreach ($allAccounts as $accId => $acc) {
                $row = $aggregatedResults[$accId] ?? null;
                $aggregates[$accId] = [
                    'opening' => $row->{"opening_{$index}"} ?? 0,
                    'debit' => $row->{"debit_{$index}"} ?? 0,
                    'credit' => $row->{"credit_{$index}"} ?? 0,
                ];
            }

            // Calculation Logic (using aggregates instead of Transaction queries)

            // Filter cash/bank accounts (sub_type BANK)
            $cashAccounts = $allAccounts->filter(function ($acc) {
                return $acc->sub_type === 'BANK';
            });

            // Opening balance — OBE journal entries are included in transaction aggregates
            $openingBalance = 0;
            foreach ($cashAccounts as $acc) {
                $openingBalance += $aggregates[$acc->id]['opening'] ?? 0;
            }

            // Compute ACTUAL cash change from cash account transactions (source of truth)
            $cashAccountIds = $cashAccounts->pluck('id')->toArray();
            $actualCashChange = 0;
            foreach ($cashAccounts as $acc) {
                $actualCashChange += ($aggregates[$acc->id]['debit'] ?? 0) - ($aggregates[$acc->id]['credit'] ?? 0);
            }

            // Activities detail
            $operatingActivities = $this->getOperatingActivitiesOptimized($allAccounts, $aggregates, $cashAccountIds);
            $investingActivities = $this->getInvestingActivitiesOptimized($allAccounts, $aggregates);
            $financingActivities = $this->getFinancingActivitiesOptimized($allAccounts, $aggregates);

            $netCashFromInvesting = array_sum(array_column($investingActivities, 'amount'));
            $netCashFromFinancing = array_sum(array_column($financingActivities, 'amount'));

            // Operating = Actual Cash Change - Investing - Financing (guaranteed correct)
            $actualNetOperating = $actualCashChange - $netCashFromInvesting - $netCashFromFinancing;
            $computedNetOperating = array_sum(array_column($operatingActivities, 'amount'));

            // If there's a reconciliation gap, show it as "Other Adjustments"
            $reconGap = round($actualNetOperating - $computedNetOperating, 2);
            if (abs($reconGap) > 0.01) {
                $operatingActivities[] = [
                    'description' => 'Other Adjustments',
                    'amount' => $reconGap,
                    'type' => 'adjustment'
                ];
            }

            $netCashFromOperating = $actualNetOperating;
            $netCashMovement = $actualCashChange;
            $closingBalance = $openingBalance + $actualCashChange;

            $periodData[$index] = [
                'opening_balance' => $openingBalance,
                'operating_activities' => $operatingActivities,
                'net_cash_from_operating' => $netCashFromOperating,
                'investing_activities' => $investingActivities,
                'net_cash_from_investing' => $netCashFromInvesting,
                'financing_activities' => $financingActivities,
                'net_cash_from_financing' => $netCashFromFinancing,
                'net_cash_movement' => $netCashMovement,
                'closing_balance' => $closingBalance,
            ];
        }

        return [
            'periods' => $periods,
            'data' => $periodData
        ];
    }

    // Refactored Helper Methods needing $aggregates instead of $transactions

    private function getOperatingActivitiesOptimized($allAccounts, $aggregates, $cashAccountIds = [])
    {
        $activities = [];

        // Net Profit
        $netProfit = $this->computeNetProfitOptimized($allAccounts, $aggregates);
        $activities[] = ['description' => 'Net Profit for the Period', 'amount' => $netProfit, 'type' => 'income'];

        // Non-cash expenses (add back depreciation/amortization/bad debts)
        $nonCash = $this->computeNonCashExpensesOptimized($allAccounts, $aggregates);
        if ($nonCash > 0) {
            $activities[] = ['description' => 'Depreciation & Amortization', 'amount' => $nonCash, 'type' => 'adjustment'];
        }

        // Working capital changes (comprehensive)
        $wcChanges = $this->getWorkingCapitalChangesOptimized($allAccounts, $aggregates, $cashAccountIds);
        foreach ($wcChanges as $row) {
            $activities[] = $row;
        }

        return $activities;
    }

    private function computeNetProfitOptimized($allAccounts, $aggregates)
    {
        $revenue = 0;
        $cogs = 0;
        $expenses = 0;

        foreach ($allAccounts as $acc) {
            if ($acc->type !== 'Account') continue;

            $debit = $aggregates[$acc->id]['debit'] ?? 0;
            $credit = $aggregates[$acc->id]['credit'] ?? 0;

            if ($acc->category === 'Revenue') {
                $revenue += $credit - $debit;
            } elseif ($acc->category === 'COGS') {
                $cogs += $debit - $credit;
            } elseif ($acc->category === 'Expenses') {
                $expenses += $debit - $credit;
            }
        }

        return $revenue - $cogs - $expenses;
    }

    private function computeNonCashExpensesOptimized($allAccounts, $aggregates)
    {
        $total = 0;
        foreach ($allAccounts as $acc) {
            if (
                $acc->type === 'Account' &&
                ($acc->sub_type === 'DEPRECIATION' ||
                    ($acc->category === 'Expenses' &&
                        (str_contains(strtolower($acc->name), 'depreciation') ||
                            str_contains(strtolower($acc->name), 'amortization') ||
                            str_contains(strtolower($acc->name), 'bad debt'))))
            ) {
                $debit = $aggregates[$acc->id]['debit'] ?? 0;
                $credit = $aggregates[$acc->id]['credit'] ?? 0;
                $total += $debit - $credit;
            }
        }
        return $total;
    }

    /**
     * Comprehensive working capital changes for the indirect method.
     * Covers ALL current assets (except cash) and ALL current liabilities (except loans).
     * This ensures the indirect method reconciles with the direct method.
     */
    private function getWorkingCapitalChangesOptimized($allAccounts, $aggregates, $cashAccountIds = [])
    {
        $changes = [];

        // Build sets of account IDs to EXCLUDE from working capital
        // (already handled in investing/financing sections or not working capital)
        $excludedAssetIds = collect($cashAccountIds);

        // Exclude fixed assets (investing section)
        $excludedAssetIds = $excludedAssetIds->merge(
            $allAccounts->filter(function ($a) {
                $name = strtolower($a->name);
                if (str_contains($name, 'accumulated depreciation') || str_contains($name, 'accum. dep')) {
                    return true; // non-cash, already added back via depreciation
                }
                return $a->type === 'Account' && (
                    $a->sub_type === 'FIXED_ASSET' ||
                    $a->sub_type === 'NON_CURRENT_ASSET' ||
                    ($a->category === 'Assets' && (
                        str_contains($name, 'fixed asset') || str_contains($name, 'equipment') ||
                        str_contains($name, 'property') || str_contains($name, 'vehicle') ||
                        str_contains($name, 'land') || str_contains($name, 'building') ||
                        str_contains($name, 'furniture') || str_contains($name, 'computer hardware') ||
                        str_contains($name, 'leasehold') || str_contains($name, 'patent') ||
                        str_contains($name, 'software license') || str_contains($name, 'intangible') ||
                        str_contains($name, 'investment')
                    ))
                );
            })->pluck('id')
        );

        // Exclude loans from liabilities (financing section)
        $excludedLiabilityIds = $allAccounts->filter(function ($a) {
            return $a->type === 'Account' && (
                $a->sub_type === 'NON_CURRENT_LIABILITY' ||
                ($a->category === 'Liabilities' && (
                    str_contains(strtolower($a->name), 'loan') ||
                    str_contains(strtolower($a->name), 'borrowing') ||
                    str_contains(strtolower($a->name), 'note payable')
                ))
            );
        })->pluck('id');

        // --- CURRENT ASSET CHANGES (increase in assets = cash outflow) ---

        $workingCapitalAssets = [
            'Change in Accounts Receivable' => function ($a) {
                return $a->sub_type === 'RECEIVABLE' || str_contains(strtolower($a->name), 'receivable');
            },
            'Change in Inventory' => function ($a) {
                return $a->sub_type === 'INVENTORY' || str_contains(strtolower($a->name), 'inventory');
            },
            'Change in Prepayments' => function ($a) {
                return $a->sub_type === 'PREPAYMENT' ||
                    str_contains(strtolower($a->name), 'prepaid') ||
                    str_contains(strtolower($a->name), 'prepayment');
            },
            'Change in Other Current Assets' => function ($a) {
                $name = strtolower($a->name);
                return $a->sub_type === 'CURRENT_ASSET' ||
                    str_contains($name, 'advance') || str_contains($name, 'tax refund') ||
                    str_contains($name, 'undeposited') || str_contains($name, 'deposit');
            },
        ];

        $matchedAssetIds = collect();

        foreach ($workingCapitalAssets as $label => $filter) {
            $accounts = $allAccounts->filter(function ($a) use ($filter, $excludedAssetIds) {
                return $a->category === 'Assets' && $a->type === 'Account'
                    && !$excludedAssetIds->contains($a->id) && $filter($a);
            });
            $matchedAssetIds = $matchedAssetIds->merge($accounts->pluck('id'));
            $change = $this->computeAccountGroupChange($accounts, $aggregates);
            if ($change != 0) {
                $changes[] = [
                    'description' => $label,
                    'amount' => -$change, // increase in asset = cash outflow
                    'type' => $change > 0 ? 'outflow' : 'inflow'
                ];
            }
        }

        // Catch-all: any remaining current asset accounts not matched above
        $unmatchedAssets = $allAccounts->filter(function ($a) use ($excludedAssetIds, $matchedAssetIds) {
            return $a->category === 'Assets' && $a->type === 'Account'
                && !$excludedAssetIds->contains($a->id) && !$matchedAssetIds->contains($a->id);
        });
        $unmatchedAssetChange = $this->computeAccountGroupChange($unmatchedAssets, $aggregates);
        if ($unmatchedAssetChange != 0) {
            $changes[] = [
                'description' => 'Change in Other Assets',
                'amount' => -$unmatchedAssetChange,
                'type' => $unmatchedAssetChange > 0 ? 'outflow' : 'inflow'
            ];
        }

        // --- CURRENT LIABILITY CHANGES (increase in liabilities = cash inflow) ---

        $workingCapitalLiabilities = [
            'Change in Accounts Payable' => function ($a) {
                $name = strtolower($a->name);
                return ($a->sub_type === 'CURRENT_LIABILITY' && str_contains($name, 'payable') && !str_contains($name, 'tax payable') && !str_contains($name, 'note payable')) ||
                    (str_contains($name, 'payable') && !str_contains($name, 'tax payable') && !str_contains($name, 'note payable'));
            },
            'Change in Credit Cards' => function ($a) {
                return str_contains(strtolower($a->name), 'credit card');
            },
            'Change in Accrued Liabilities' => function ($a) {
                return str_contains(strtolower($a->name), 'accrued');
            },
            'Change in Tax Liabilities' => function ($a) {
                return ($a->sub_type === 'CURRENT_LIABILITY' && (str_contains(strtolower($a->name), 'tax payable') || str_contains(strtolower($a->name), 'tax liabilit'))) ||
                    (str_contains(strtolower($a->name), 'tax payable') || str_contains(strtolower($a->name), 'tax liabilit'));
            },
            'Change in Other Liabilities' => function ($a) {
                $name = strtolower($a->name);
                return $a->sub_type === 'CURRENT_LIABILITY' ||
                    str_contains($name, 'customer deposit') || str_contains($name, 'unearned') ||
                    str_contains($name, 'deferred revenue');
            },
        ];

        $matchedLiabilityIds = collect();

        foreach ($workingCapitalLiabilities as $label => $filter) {
            $accounts = $allAccounts->filter(function ($a) use ($filter, $excludedLiabilityIds) {
                return $a->category === 'Liabilities' && $a->type === 'Account'
                    && !$excludedLiabilityIds->contains($a->id) && $filter($a);
            });
            $matchedLiabilityIds = $matchedLiabilityIds->merge($accounts->pluck('id'));
            $change = $this->computeAccountGroupChange($accounts, $aggregates);
            if ($change != 0) {
                $changes[] = [
                    'description' => $label,
                    'amount' => $change, // increase in liability = cash inflow
                    'type' => $change > 0 ? 'inflow' : 'outflow'
                ];
            }
        }

        // Catch-all: any remaining liability accounts not matched above
        $unmatchedLiabilities = $allAccounts->filter(function ($a) use ($excludedLiabilityIds, $matchedLiabilityIds) {
            return $a->category === 'Liabilities' && $a->type === 'Account'
                && !$excludedLiabilityIds->contains($a->id) && !$matchedLiabilityIds->contains($a->id);
        });
        $unmatchedLiabilityChange = $this->computeAccountGroupChange($unmatchedLiabilities, $aggregates);
        if ($unmatchedLiabilityChange != 0) {
            $changes[] = [
                'description' => 'Change in Other Liabilities',
                'amount' => $unmatchedLiabilityChange,
                'type' => $unmatchedLiabilityChange > 0 ? 'inflow' : 'outflow'
            ];
        }

        // --- EQUITY CHANGES that affect operating cash (e.g., drawings) ---
        // Equity changes are normally financing, but owner drawings reduce cash operationally
        // These are handled in financing section, so no adjustment needed here.

        return $changes;
    }

    private function computeAccountGroupChange($accounts, $aggregates)
    {
        $closing = 0;

        foreach ($accounts as $acc) {
            $debit = $aggregates[$acc->id]['debit'] ?? 0;
            $credit = $aggregates[$acc->id]['credit'] ?? 0;

            if (in_array($acc->category, ['Assets', 'Expenses'])) {
                $closing += $debit - $credit;
            } else {
                $closing += $credit - $debit;
            }
        }

        return $closing;
    }

    private function getInvestingActivitiesOptimized($allAccounts, $aggregates)
    {
        $activities = [];

        // FIXED ASSETS (sub_type FIXED_ASSET or name match)
        $fixedAssetAccounts = $allAccounts->filter(function ($acc) {
            $name = strtolower($acc->name);
            // Exclude accumulated depreciation accounts
            if (str_contains($name, 'accumulated depreciation') || str_contains($name, 'accum. dep')) {
                return false;
            }
            return $acc->type === 'Account'
                && (
                    $acc->sub_type === 'FIXED_ASSET' ||
                    ($acc->category === 'Assets' && (
                        str_contains($name, 'fixed asset') ||
                        str_contains($name, 'equipment') ||
                        str_contains($name, 'property') ||
                        str_contains($name, 'vehicle') ||
                        str_contains($name, 'land') ||
                        str_contains($name, 'building') ||
                        str_contains($name, 'furniture') ||
                        str_contains($name, 'computer hardware') ||
                        str_contains($name, 'leasehold')
                    ))
                );
        });

        foreach ($fixedAssetAccounts as $acc) {
            $purchases = $aggregates[$acc->id]['debit'] ?? 0;
            $sales = $aggregates[$acc->id]['credit'] ?? 0;

            if ($purchases > 0) {
                $activities[] = [
                    'description' => 'Purchase of ' . $acc->name,
                    'amount' => -$purchases,
                    'type' => 'outflow'
                ];
            }
            if ($sales > 0) {
                $activities[] = [
                    'description' => 'Sale of ' . $acc->name,
                    'amount' => $sales,
                    'type' => 'inflow'
                ];
            }
        }

        // INTANGIBLE ASSETS (sub_type NON_CURRENT_ASSET or name match)
        $intangibleAccounts = $allAccounts->filter(function ($acc) {
            $name = strtolower($acc->name);
            return $acc->type === 'Account'
                && (
                    ($acc->sub_type === 'NON_CURRENT_ASSET' && (str_contains($name, 'patent') || str_contains($name, 'license') || str_contains($name, 'intangible'))) ||
                    ($acc->category === 'Assets' && (
                        str_contains($name, 'patent') ||
                        str_contains($name, 'software license') ||
                        str_contains($name, 'intangible')
                    ))
                );
        });

        foreach ($intangibleAccounts as $acc) {
            $purchases = $aggregates[$acc->id]['debit'] ?? 0;
            if ($purchases > 0) {
                $activities[] = [
                    'description' => 'Purchase of ' . $acc->name,
                    'amount' => -$purchases,
                    'type' => 'outflow'
                ];
            }
        }

        // INVESTMENTS
        $investmentAccounts = $allAccounts->filter(function ($acc) {
            return $acc->category === 'Assets'
                && $acc->type === 'Account'
                && str_contains(strtolower($acc->name), 'investment');
        });

        foreach ($investmentAccounts as $acc) {
            $purchases = $aggregates[$acc->id]['debit'] ?? 0;
            $redemptions = $aggregates[$acc->id]['credit'] ?? 0;

            if ($purchases > 0) {
                $activities[] = [
                    'description' => 'Investment Purchases',
                    'amount' => -$purchases,
                    'type' => 'outflow'
                ];
            }
            if ($redemptions > 0) {
                $activities[] = [
                    'description' => 'Investment Redemptions',
                    'amount' => $redemptions,
                    'type' => 'inflow'
                ];
            }
        }

        return $activities;
    }

    private function getFinancingActivitiesOptimized($allAccounts, $aggregates)
    {
        $activities = [];

        // LOANS (sub_type NON_CURRENT_LIABILITY or name match)
        $loanAccounts = $allAccounts->filter(function ($acc) {
            $name = strtolower($acc->name);
            return $acc->type === 'Account'
                && (
                    $acc->sub_type === 'NON_CURRENT_LIABILITY' ||
                    ($acc->category === 'Liabilities' && (
                        str_contains($name, 'loan') ||
                        str_contains($name, 'borrowing') ||
                        str_contains($name, 'note payable')
                    ))
                );
        });

        foreach ($loanAccounts as $acc) {
            $received = $aggregates[$acc->id]['credit'] ?? 0;
            $repaid = $aggregates[$acc->id]['debit'] ?? 0;

            if ($received > 0) {
                $activities[] = [
                    'description' => 'Loans Received',
                    'amount' => $received,
                    'type' => 'inflow'
                ];
            }
            if ($repaid > 0) {
                $activities[] = [
                    'description' => 'Loan Repayments',
                    'amount' => -$repaid,
                    'type' => 'outflow'
                ];
            }
        }

        // ALL EQUITY ACCOUNTS (captures owner's equity, retained earnings, drawings, share capital, dividends)
        $allEquityAccounts = $allAccounts->filter(function ($acc) {
            return $acc->category === 'Equity' && $acc->type === 'Account';
        });

        foreach ($allEquityAccounts as $acc) {
            $debit = $aggregates[$acc->id]['debit'] ?? 0;
            $credit = $aggregates[$acc->id]['credit'] ?? 0;
            $name = strtolower($acc->name);

            if ($debit == 0 && $credit == 0) continue;

            // Owner Drawings (debit-normal: debit = drawings taken out)
            if (str_contains($name, 'drawing')) {
                if ($debit > 0) {
                    $activities[] = ['description' => 'Owner Drawings', 'amount' => -$debit, 'type' => 'outflow'];
                }
                if ($credit > 0) {
                    $activities[] = ['description' => 'Drawings Returned', 'amount' => $credit, 'type' => 'inflow'];
                }
            }
            // Dividends
            elseif (str_contains($name, 'dividend')) {
                if ($debit > 0) {
                    $activities[] = ['description' => 'Dividends Paid', 'amount' => -$debit, 'type' => 'outflow'];
                }
            }
            // Share Capital / Common Stock / Owner's Equity
            elseif (
                str_contains($name, 'share capital') || str_contains($name, 'common stock') ||
                str_contains($name, 'equity') || str_contains($name, 'capital')
            ) {
                if ($credit > 0) {
                    $activities[] = ['description' => 'Capital Invested (' . $acc->name . ')', 'amount' => $credit, 'type' => 'inflow'];
                }
                if ($debit > 0) {
                    $activities[] = ['description' => 'Capital Withdrawn (' . $acc->name . ')', 'amount' => -$debit, 'type' => 'outflow'];
                }
            }
            // Retained Earnings (manual entries only — computed earnings are in P&L)
            elseif (str_contains($name, 'retained earning')) {
                $net = $credit - $debit;
                if ($net != 0) {
                    $activities[] = [
                        'description' => 'Retained Earnings Adjustment',
                        'amount' => $net,
                        'type' => $net > 0 ? 'inflow' : 'outflow'
                    ];
                }
            }
            // Catch-all for any other equity account with activity
            else {
                $net = $credit - $debit;
                if ($net != 0) {
                    $activities[] = [
                        'description' => 'Other Equity Movement (' . $acc->name . ')',
                        'amount' => $net,
                        'type' => $net > 0 ? 'inflow' : 'outflow'
                    ];
                }
            }
        }

        return $activities;
    }

    public function exportCashFlowToPdf(Request $request)
    {
        $this->logReportAccess($request, 'Cash Flow', 'export', 'pdf');

        $companyId = loggedCompanyId()['company_id'];
        [$startDate, $endDate] = resolveDateRange($request, $request->date_range ?? 'year');
        $comparePeriods = $request->compare_periods ?? 0;
        $periodType = $request->period_type ?? 'month';
        $compareFromDate = $request->input('compare_from_date');
        $compareToDate = $request->input('compare_to_date');

        if ($periodType === 'custom_date') {
            $comparePeriods = 1;
        }

        $showVarianceAmount = $request->show_variance_amount === 'true' || $request->show_variance_amount === '1';
        $showVariancePercentage = $request->show_variance_percentage === 'true' || $request->show_variance_percentage === '1';

        $periods = $this->calculatePeriods($startDate, $endDate, $comparePeriods, $periodType, $compareFromDate, $compareToDate);
        $multiPeriodCashFlow = $this->getMultiPeriodCashFlowData($companyId, $periods);

        $company = auth('web')->user()->company;
        $invoiceTemplate = \App\Models\InvoiceTemplate::where('company_id', $companyId)->where('is_default', true)->first();

        $narrative = app(FinancialNarrativeService::class)->cashFlow($multiPeriodCashFlow);

        $pdf = Pdf::loadView('pages.account.reports.pdf.cash-flow-pdf', [
            'cashFlow' => $multiPeriodCashFlow,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'company' => $company,
            'showVarianceAmount' => $showVarianceAmount,
            'showVariancePercentage' => $showVariancePercentage,
            'periodType' => $periodType,
            'invoiceTemplate' => $invoiceTemplate,
            'narrative' => $narrative,
        ]);

        $orientation = $comparePeriods > 0 ? 'landscape' : 'portrait';
        $pdf->setPaper($invoiceTemplate?->page_size ?? 'A4', $orientation);

        return $pdf->download('cash-flow-' . $startDate . '-to-' . $endDate . '.pdf');
    }

    public function exportCashFlowToCsv(Request $request)
    {
        $this->logReportAccess($request, 'Cash Flow', 'export', 'csv');

        $companyId = loggedCompanyId()['company_id'];
        [$startDate, $endDate] = resolveDateRange($request, $request->date_range ?? 'year');
        $comparePeriods = $request->compare_periods ?? 0;
        $periodType = $request->period_type ?? 'month';
        $compareFromDate = $request->input('compare_from_date');
        $compareToDate = $request->input('compare_to_date');

        if ($periodType === 'custom_date') {
            $comparePeriods = 1;
        }

        $periods = $this->calculatePeriods($startDate, $endDate, $comparePeriods, $periodType, $compareFromDate, $compareToDate);
        $multiPeriodCashFlow = $this->getMultiPeriodCashFlowData($companyId, $periods);

        $colHeaders = ['Category'];
        foreach ($multiPeriodCashFlow['periods'] as $period) {
            $colHeaders[] = $period['label'];
        }
        $colCount = count($colHeaders);

        $spreadsheet = $this->createReportSpreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Cash Flow');
        $row = 1;

        $this->writeTitle($sheet, 'Cash Flow Statement (Indirect Method)', $row, $colCount);
        $this->writeCompanyRow($sheet, $row, $colCount);
        $this->writeMeta($sheet, [
            ['Period:', Carbon::parse($startDate)->format('d M Y') . ' to ' . Carbon::parse($endDate)->format('d M Y')],
            ['Generated:', now()->format('d M Y, h:i A')],
        ], $row);

        $this->writeColumnHeaders($sheet, $colHeaders, $row);

        // Opening Balance
        $vals = ['Opening Cash Balance'];
        foreach ($multiPeriodCashFlow['data'] as $pData) {
            $vals[] = numberFormat($pData['opening_balance']);
        }
        $this->writeSubtotalRow($sheet, $vals, $row, $colCount);
        $row++;

        // Helper
        $getDescriptions = function ($type) use ($multiPeriodCashFlow) {
            $descs = [];
            foreach ($multiPeriodCashFlow['data'] as $pData) {
                foreach ($pData[$type] as $activity) {
                    $descs[$activity['description']] = true;
                }
            }
            return array_keys($descs);
        };

        // Activity sections
        $sections = [
            ['CASH FLOWS FROM OPERATING ACTIVITIES', 'operating_activities', 'net_cash_from_operating', 'Net Cash from Operating Activities'],
            ['CASH FLOWS FROM INVESTING ACTIVITIES', 'investing_activities', 'net_cash_from_investing', 'Net Cash from Investing Activities'],
            ['CASH FLOWS FROM FINANCING ACTIVITIES', 'financing_activities', 'net_cash_from_financing', 'Net Cash from Financing Activities'],
        ];

        foreach ($sections as [$sectionTitle, $activityKey, $netKey, $netLabel]) {
            $this->writeSectionHeader($sheet, $sectionTitle, $row, $colCount);

            foreach ($getDescriptions($activityKey) as $desc) {
                $vals = [$desc];
                foreach ($multiPeriodCashFlow['data'] as $pData) {
                    $found = 0;
                    foreach ($pData[$activityKey] as $act) {
                        if ($act['description'] === $desc) {
                            $found = $act['amount'];
                            break;
                        }
                    }
                    $vals[] = numberFormat($found);
                }
                $this->writeDataRow($sheet, $vals, $row, $colCount, true);
            }

            $vals = [$netLabel];
            foreach ($multiPeriodCashFlow['data'] as $pData) {
                $vals[] = numberFormat($pData[$netKey]);
            }
            $this->writeSubtotalRow($sheet, $vals, $row, $colCount);
            $row++;
        }

        // Net movement
        $vals = ['Net Increase/Decrease in Cash'];
        foreach ($multiPeriodCashFlow['data'] as $pData) {
            $vals[] = numberFormat($pData['net_cash_movement']);
        }
        $this->writeSubtotalRow($sheet, $vals, $row, $colCount);

        // Closing Balance
        $vals = ['Closing Cash Balance'];
        foreach ($multiPeriodCashFlow['data'] as $pData) {
            $vals[] = numberFormat($pData['closing_balance']);
        }
        $this->writeGrandTotalRow($sheet, $vals, $row, $colCount);

        $this->autoSizeColumns($sheet, $colCount);

        $filename = 'cash-flow-' . $startDate . '-to-' . $endDate . '.xlsx';
        return $this->downloadExcel($spreadsheet, $filename);
    }
}
