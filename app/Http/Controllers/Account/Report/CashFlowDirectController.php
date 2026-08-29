<?php

namespace App\Http\Controllers\Account\Report;

use App\Http\Controllers\Controller;
use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CashFlowDirectController extends Controller
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
            $this->logReportAccess($request, 'Cash Flow Direct', 'filter');

            return response()->json([
                'html' => view('pages.account.reports.partials.cash-flow-direct-table', [
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

        $this->logReportAccess($request, 'Cash Flow Direct', 'view');

        return view('pages.account.reports.cash-flow-direct', [
            'cashFlow' => $multiPeriodCashFlow,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'comparePeriods' => $comparePeriods,
            'periodType' => $periodType,
            'showVarianceAmount' => $showVarianceAmount,
            'showVariancePercentage' => $showVariancePercentage
        ]);
    }

    private function getMultiPeriodCashFlowData($companyId, $periods)
    {
        $allAccounts = ChartOfAccount::forCompany(loggedCompanyId()['company_id'])->get()->keyBy('id');

        $selects = [];
        $bindings = [];

        foreach ($periods as $index => $period) {
            $selects[] = "COALESCE(SUM(CASE WHEN date < ? THEN debit - credit ELSE 0 END), 0) AS opening_{$index}";
            $bindings[] = $period['start'];

            $selects[] = "COALESCE(SUM(CASE WHEN date >= ? AND date <= ? THEN debit ELSE 0 END), 0) AS debit_{$index}";
            $bindings[] = $period['start'];
            $bindings[] = $period['end'];

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

        $periodData = [];

        foreach ($periods as $index => $period) {
            $aggregates = [];
            foreach ($allAccounts as $accId => $acc) {
                $row = $aggregatedResults[$accId] ?? null;
                $aggregates[$accId] = [
                    'opening' => $row->{"opening_{$index}"} ?? 0,
                    'debit' => $row->{"debit_{$index}"} ?? 0,
                    'credit' => $row->{"credit_{$index}"} ?? 0,
                ];
            }

            $cashAccounts = $allAccounts->filter(function ($acc) {
                $name = strtolower($acc->name);
                return $acc->category === 'Assets' &&
                    $acc->type === 'Account' &&
                    (
                        $acc->sub_type === 'BANK' ||
                        str_contains($name, 'cash') ||
                        str_contains($name, 'bank')
                    );
            });

            $openingBalance = 0;
            foreach ($cashAccounts as $acc) {
                $openingBalance += $aggregates[$acc->id]['opening'] ?? 0;
            }

            // DIRECT METHOD Logic
            $operatingActivities = $this->getDirectOperatingActivities($companyId, $cashAccounts, $period);
            $investingActivities = $this->getInvestingActivitiesOptimized($allAccounts, $aggregates);
            $financingActivities = $this->getFinancingActivitiesOptimized($allAccounts, $aggregates);

            $netCashFromOperating = array_sum(array_column($operatingActivities, 'amount'));
            $netCashFromInvesting = array_sum(array_column($investingActivities, 'amount'));
            $netCashFromFinancing = array_sum(array_column($financingActivities, 'amount'));

            $netCashMovement = $netCashFromOperating + $netCashFromInvesting + $netCashFromFinancing;
            $closingBalance = $openingBalance + $netCashMovement;

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
            'data' => $periodData,
            'reporting_method' => 'direct'
        ];
    }

    private function getDirectOperatingActivities($companyId, $cashAccounts, $period)
    {
        $cashAccountIds = $cashAccounts->pluck('id')->toArray();

        // Get all cash transactions in the period
        $cashTransactions = Transaction::where('company_id', $companyId)
            ->whereIn('account_id', $cashAccountIds)
            ->whereBetween('date', [$period['start'], $period['end']])
            ->where('status', 1)
            ->get();

        if ($cashTransactions->isEmpty()) {
            return [];
        }

        // Pre-fetch ALL offset transactions in ONE query (fixes N+1)
        $uniqueNos = $cashTransactions->pluck('unique_no')->unique()->toArray();
        $allOffsets = Transaction::where('company_id', $companyId)
            ->whereIn('unique_no', $uniqueNos)
            ->whereNotIn('account_id', $cashAccountIds)
            ->where('status', 1)
            ->with('account')
            ->get()
            ->groupBy('unique_no');

        $customers = 0;
        $suppliers = 0;
        $salaries = 0;
        $operatingExpenses = 0;

        $grouped = $cashTransactions->groupBy('unique_no');

        foreach ($grouped as $uniqueNo => $group) {
            $offsets = $allOffsets[$uniqueNo] ?? collect();

            foreach ($group as $cashTx) {
                $amount = $cashTx->debit - $cashTx->credit;

                if ($cashTx->type === 'Payroll' || $offsets->contains(fn($o) => str_contains(strtolower($o->account?->name ?? ''), 'salary') || str_contains(strtolower($o->account?->name ?? ''), 'wage'))) {
                    $salaries += $amount;
                    continue;
                }

                $isCustomer = false;
                $isSupplier = false;
                $isExpense = false;

                foreach ($offsets as $offset) {
                    $cat = $offset->account?->category;
                    if ($cat === 'Revenue' || str_contains(strtolower($offset->account?->name ?? ''), 'receivable')) {
                        $isCustomer = true;
                    } elseif ($cat === 'COGS' || str_contains(strtolower($offset->account?->name ?? ''), 'payable')) {
                        $isSupplier = true;
                    } elseif ($cat === 'Expenses') {
                        $isExpense = true;
                    }
                }

                if ($isCustomer) {
                    $customers += $amount;
                } elseif ($isSupplier) {
                    $suppliers += $amount;
                } elseif ($isExpense) {
                    $operatingExpenses += $amount;
                } else {
                    if ($amount > 0) {
                        $customers += $amount;
                    } else {
                        $operatingExpenses += $amount;
                    }
                }
            }
        }

        $activities = [];
        if ($customers != 0) $activities[] = ['description' => 'Cash received from customers', 'amount' => $customers, 'type' => 'inflow'];
        if ($suppliers != 0) $activities[] = ['description' => 'Cash paid to suppliers', 'amount' => $suppliers, 'type' => 'outflow'];
        if ($salaries != 0) $activities[] = ['description' => 'Cash paid for salaries & benefits', 'amount' => $salaries, 'type' => 'outflow'];
        if ($operatingExpenses != 0) $activities[] = ['description' => 'Cash paid for operating expenses', 'amount' => $operatingExpenses, 'type' => 'outflow'];

        return $activities;
    }

    private function getInvestingActivitiesOptimized($allAccounts, $aggregates)
    {
        $activities = [];
        // FIXED ASSETS (excluding accumulated depreciation)
        $fixedAssetAccounts = $allAccounts->filter(function ($acc) {
            $name = strtolower($acc->name);
            if (str_contains($name, 'accumulated depreciation') || str_contains($name, 'accum. dep') || $acc->sub_type === 'DEPRECIATION') {
                return false;
            }
            return $acc->category === 'Assets'
                && $acc->type === 'Account'
                && (
                    $acc->sub_type === 'FIXED_ASSET' ||
                    $acc->sub_type === 'NON_CURRENT_ASSET' ||
                    str_contains($name, 'fixed asset') ||
                    str_contains($name, 'equipment') ||
                    str_contains($name, 'property') ||
                    str_contains($name, 'vehicle') ||
                    str_contains($name, 'land') ||
                    str_contains($name, 'building') ||
                    str_contains($name, 'furniture') ||
                    str_contains($name, 'computer hardware') ||
                    str_contains($name, 'leasehold')
                );
        });

        foreach ($fixedAssetAccounts as $acc) {
            $purchases = $aggregates[$acc->id]['debit'] ?? 0;
            $sales = $aggregates[$acc->id]['credit'] ?? 0;

            if ($purchases > 0) {
                $activities[] = ['description' => 'Purchase of ' . $acc->name, 'amount' => -$purchases, 'type' => 'outflow'];
            }
            if ($sales > 0) {
                $activities[] = ['description' => 'Sale of ' . $acc->name, 'amount' => $sales, 'type' => 'inflow'];
            }
        }

        $investmentAccounts = $allAccounts->filter(function ($acc) {
            return $acc->category === 'Assets'
                && $acc->type === 'Account'
                && str_contains(strtolower($acc->name), 'investment');
        });

        foreach ($investmentAccounts as $acc) {
            $purchases = $aggregates[$acc->id]['debit'] ?? 0;
            $redemptions = $aggregates[$acc->id]['credit'] ?? 0;

            if ($purchases > 0) {
                $activities[] = ['description' => 'Investment Purchases', 'amount' => -$purchases, 'type' => 'outflow'];
            }
            if ($redemptions > 0) {
                $activities[] = ['description' => 'Investment Redemptions', 'amount' => $redemptions, 'type' => 'inflow'];
            }
        }

        return $activities;
    }

    private function getFinancingActivitiesOptimized($allAccounts, $aggregates)
    {
        $activities = [];
        $loanAccounts = $allAccounts->filter(function ($acc) {
            $name = strtolower($acc->name);
            return $acc->category === 'Liabilities'
                && $acc->type === 'Account'
                && (
                    $acc->sub_type === 'NON_CURRENT_LIABILITY' ||
                    str_contains($name, 'loan') ||
                    str_contains($name, 'borrowing') ||
                    str_contains($name, 'note payable')
                );
        });

        foreach ($loanAccounts as $acc) {
            $received = $aggregates[$acc->id]['credit'] ?? 0;
            $repaid = $aggregates[$acc->id]['debit'] ?? 0;

            if ($received > 0) {
                $activities[] = ['description' => 'Loans Received', 'amount' => $received, 'type' => 'inflow'];
            }
            if ($repaid > 0) {
                $activities[] = ['description' => 'Loan Repayments', 'amount' => -$repaid, 'type' => 'outflow'];
            }
        }

        // ALL EQUITY ACCOUNTS
        $allEquityAccounts = $allAccounts->filter(function ($acc) {
            return $acc->category === 'Equity' && $acc->type === 'Account';
        });

        foreach ($allEquityAccounts as $acc) {
            $debit = $aggregates[$acc->id]['debit'] ?? 0;
            $credit = $aggregates[$acc->id]['credit'] ?? 0;
            $name = strtolower($acc->name);

            if ($debit == 0 && $credit == 0) continue;

            if (str_contains($name, 'drawing')) {
                if ($debit > 0) $activities[] = ['description' => 'Owner Drawings', 'amount' => -$debit, 'type' => 'outflow'];
                if ($credit > 0) $activities[] = ['description' => 'Drawings Returned', 'amount' => $credit, 'type' => 'inflow'];
            } elseif (str_contains($name, 'dividend')) {
                if ($debit > 0) $activities[] = ['description' => 'Dividends Paid', 'amount' => -$debit, 'type' => 'outflow'];
            } elseif (
                str_contains($name, 'share capital') || str_contains($name, 'common stock') ||
                str_contains($name, 'equity') || str_contains($name, 'capital')
            ) {
                if ($credit > 0) $activities[] = ['description' => 'Capital Invested (' . $acc->name . ')', 'amount' => $credit, 'type' => 'inflow'];
                if ($debit > 0) $activities[] = ['description' => 'Capital Withdrawn (' . $acc->name . ')', 'amount' => -$debit, 'type' => 'outflow'];
            } elseif (str_contains($name, 'retained earning')) {
                $net = $credit - $debit;
                if ($net != 0) $activities[] = ['description' => 'Retained Earnings Adjustment', 'amount' => $net, 'type' => $net > 0 ? 'inflow' : 'outflow'];
            } else {
                $net = $credit - $debit;
                if ($net != 0) $activities[] = ['description' => 'Other Equity Movement (' . $acc->name . ')', 'amount' => $net, 'type' => $net > 0 ? 'inflow' : 'outflow'];
            }
        }

        return $activities;
    }

    public function exportCashFlowToPdf(Request $request)
    {
        $this->logReportAccess($request, 'Cash Flow Direct', 'export', 'pdf');

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

        $pdf = Pdf::loadView('pages.account.reports.pdf.cash-flow-direct-pdf', [
            'cashFlow' => $multiPeriodCashFlow,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'company' => $company,
            'showVarianceAmount' => $showVarianceAmount,
            'showVariancePercentage' => $showVariancePercentage,
            'periodType' => $periodType,
            'invoiceTemplate' => $invoiceTemplate,
            'reportingMethod' => 'direct'
        ]);

        $orientation = $comparePeriods > 0 ? 'landscape' : 'portrait';
        $pdf->setPaper($invoiceTemplate->page_size ?? 'A4', $orientation);

        return $pdf->download('cash-flow-direct-' . $startDate . '-to-' . $endDate . '.pdf');
    }

    public function exportCashFlowToCsv(Request $request)
    {
        $this->logReportAccess($request, 'Cash Flow Direct', 'export', 'csv');

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
        $sheet->setTitle('Cash Flow Direct');
        $row = 1;

        $this->writeTitle($sheet, 'Cash Flow Statement (Direct Method)', $row, $colCount);
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

        $getDescriptions = function ($type) use ($multiPeriodCashFlow) {
            $descs = [];
            foreach ($multiPeriodCashFlow['data'] as $pData) {
                foreach ($pData[$type] as $activity) {
                    $descs[$activity['description']] = true;
                }
            }
            return array_keys($descs);
        };

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

        $vals = ['Net Increase/Decrease in Cash'];
        foreach ($multiPeriodCashFlow['data'] as $pData) {
            $vals[] = numberFormat($pData['net_cash_movement']);
        }
        $this->writeSubtotalRow($sheet, $vals, $row, $colCount);

        $vals = ['Closing Cash Balance'];
        foreach ($multiPeriodCashFlow['data'] as $pData) {
            $vals[] = numberFormat($pData['closing_balance']);
        }
        $this->writeGrandTotalRow($sheet, $vals, $row, $colCount);

        $this->autoSizeColumns($sheet, $colCount);

        $filename = 'cash-flow-direct-' . $startDate . '-to-' . $endDate . '.xlsx';
        return $this->downloadExcel($spreadsheet, $filename);
    }
}
