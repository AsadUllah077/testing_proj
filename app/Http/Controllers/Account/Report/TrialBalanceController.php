<?php

namespace App\Http\Controllers\Account\Report;

use App\Http\Controllers\Controller;
use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TrialBalanceController extends Controller
{
    use \App\Traits\LogsReportAccess, \App\Traits\ExportsToExcel;
    private function getComparisons($accountId, $period, $count, $currentFrom, $currentTo)
    {
        $comparisons = [];
        $companyId = loggedCompanyId()['company_id'];

        for ($i = 1; $i <= $count; $i++) {
            [$compareFrom, $compareTo] = $this->getComparisonDates($period, $currentFrom, $currentTo, $i);

            $debit = Transaction::where('account_id', $accountId)
                ->where('company_id', $companyId)
                ->whereDate('date', '>=', $compareFrom)
                ->whereDate('date', '<=', $compareTo)
                ->sum('debit');

            $credit = Transaction::where('account_id', $accountId)
                ->where('company_id', $companyId)
                ->whereDate('date', '>=', $compareFrom)
                ->whereDate('date', '<=', $compareTo)
                ->sum('credit');

            $comparisons[] = [
                'period_label' => $this->getPeriodLabel($period, $i, $compareFrom, $compareTo),
                'from' => $compareFrom,
                'to' => $compareTo,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $debit - $credit, // Movement
            ];
        }

        return $comparisons;
    }

    private function getComparisonDates($period, $currentFrom, $currentTo, $offset = 1, $customFrom = null, $customTo = null)
    {
        if ($period === 'custom_date' && $customFrom && $customTo) {
            return [
                Carbon::parse($customFrom)->format('Y-m-d'),
                Carbon::parse($customTo)->format('Y-m-d')
            ];
        }

        $fromDate = Carbon::parse($currentFrom);
        $toDate = Carbon::parse($currentTo);
        $intervalDays = $fromDate->diffInDays($toDate) + 1; // +1 to include start date

        switch ($period) {
            case 'previous_period':
                // E.g. if range is 30 days. offset 1 is -30 days. offset 2 is -60 days from start.
                return [
                    $fromDate->copy()->subDays($intervalDays * $offset)->format('Y-m-d'),
                    $toDate->copy()->subDays($intervalDays * $offset)->format('Y-m-d')
                ];
            case 'previous_month':
                return [
                    $fromDate->copy()->subMonths($offset)->startOfMonth()->format('Y-m-d'),
                    $fromDate->copy()->subMonths($offset)->endOfMonth()->format('Y-m-d')
                ];
            case 'previous_quarter':
                return [
                    $fromDate->copy()->subQuarters($offset)->startOfQuarter()->format('Y-m-d'),
                    $fromDate->copy()->subQuarters($offset)->endOfQuarter()->format('Y-m-d')
                ];
            case 'previous_year':
                return [
                    $fromDate->copy()->subYears($offset)->startOfYear()->format('Y-m-d'),
                    $fromDate->copy()->subYears($offset)->endOfYear()->format('Y-m-d')
                ];
            case 'previous_fiscal_year':
                $companyId = loggedCompanyId()['company_id'];
                $fiscalYears = AccountingPeriod::where('company_id', $companyId)
                    ->where('end_date', '<', $currentFrom)
                    ->orderBy('start_date', 'desc')
                    ->take($offset)
                    ->get();

                if ($fiscalYears->count() >= $offset) {
                    $fy = $fiscalYears->last();
                    return [
                        $fy->start_date->format('Y-m-d'),
                        $fy->end_date->format('Y-m-d')
                    ];
                }

                return [$currentFrom, $currentTo];
            default:
                return [$currentFrom, $currentTo];
        }
    }

    private function getPeriodLabel($period, $offset, $from, $to)
    {
        $dateStr = date('d M Y', strtotime($from)) . ' - ' . date('d M Y', strtotime($to));

        switch ($period) {
            case 'custom_date':
                return "Custom ($dateStr)";
            case 'previous_period':
                return "Period " . $offset . " ($dateStr)";
            case 'previous_month':
                return date('F Y', strtotime($from));
            case 'previous_quarter':
                return "Q" . ceil(date('n', strtotime($from)) / 3) . " " . date('Y', strtotime($from));
            case 'previous_year':
                return date('Y', strtotime($from));
            case 'previous_fiscal_year':
                return "FY ($dateStr)";
            default:
                return $dateStr;
        }
    }

    public function trialBalance(Request $request)
    {
        $request->validate([
            'account_id' => ['nullable', 'integer', \Illuminate\Validation\Rule::exists('chart_of_accounts', 'id')->where('company_id', loggedCompanyId()['company_id'])],
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
        ]);
        $companyId = loggedCompanyId()['company_id'];

        [$from, $to] = resolveDateRange($request, $request->date_range ?? 'year');
        $accountFilter = $request->account_id;
        $perPage = $request->per_page ?? 200;
        $page = $request->page ?? 1;
        $comparePeriod = $request->compare_period;
        $compareCount = (int) $request->input('compare_count', 0);
        $compareFromDate = $request->compare_from_date;
        $compareToDate = $request->compare_to_date;

        if ($comparePeriod === 'custom_date') {
            $compareCount = 1;
        }

        // Limit compare count to valid range (0-4)
        if ($compareCount < 0) $compareCount = 0;
        if ($compareCount > 11) $compareCount = 11;

        // If comparison is requested but no count, default to 1 (backward compatibility)
        if ($comparePeriod && $compareCount === 0) {
            $compareCount = 1;
        }

        $exportType = $request->export_type;

        if ($exportType) {
            $perPage = 1000;
        }

        $coaQuery = ChartOfAccount::forCompany($companyId)->orderBy('account_number');

        if ($accountFilter) {
            $coaQuery->where('id', $accountFilter);
        }

        if ($exportType) {
            $chartOfAccounts = $coaQuery->get();
        } else {
            $chartOfAccounts = $coaQuery->paginate($perPage);
        }

        $allAccounts = ChartOfAccount::forCompany($companyId)->orderBy('account_number')->get();
        $accountIds = $allAccounts->pluck('id');

        // Consolidated Query Optimization
        $selects = [];
        $bindings = [];

        // Main Period Selects
        $selects[] = "COALESCE(SUM(CASE WHEN date < ? THEN debit - credit ELSE 0 END), 0) AS main_opening";
        $bindings[] = $from;

        $selects[] = "COALESCE(SUM(CASE WHEN date >= ? AND date <= ? THEN debit ELSE 0 END), 0) AS main_debit";
        $bindings[] = $from;
        $bindings[] = $to;

        $selects[] = "COALESCE(SUM(CASE WHEN date >= ? AND date <= ? THEN credit ELSE 0 END), 0) AS main_credit";
        $bindings[] = $from;
        $bindings[] = $to;

        // Prepare comparison metadata and selects
        $bulkComparisonData = [];
        $comparisonHeaders = [];

        if ($comparePeriod && $compareCount > 0) {
            for ($i = 1; $i <= $compareCount; $i++) {
                [$cFrom, $cTo] = $this->getComparisonDates($comparePeriod, $from, $to, $i, $compareFromDate, $compareToDate);

                $bulkComparisonData[$i] = [
                    'label' => $this->getPeriodLabel($comparePeriod, $i, $cFrom, $cTo),
                    'from' => $cFrom,
                    'to' => $cTo
                ];

                $comparisonHeaders[] = [
                    'label' => $bulkComparisonData[$i]['label'],
                    'from' => $cFrom,
                    'to' => $cTo
                ];

                // Append selects for this comparison period
                $selects[] = "COALESCE(SUM(CASE WHEN date < ? THEN debit - credit ELSE 0 END), 0) AS opening_{$i}";
                $bindings[] = $cFrom;

                $selects[] = "COALESCE(SUM(CASE WHEN date >= ? AND date <= ? THEN debit ELSE 0 END), 0) AS debit_{$i}";
                $bindings[] = $cFrom;
                $bindings[] = $cTo;

                $selects[] = "COALESCE(SUM(CASE WHEN date >= ? AND date <= ? THEN credit ELSE 0 END), 0) AS credit_{$i}";
                $bindings[] = $cFrom;
                $bindings[] = $cTo;
            }
        }

        // Execute Single Aggregated Query
        $aggregatedResults = Transaction::whereIn('account_id', $accountIds)
            ->selectRaw('account_id, ' . implode(', ', $selects), $bindings)
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');

        // Map results to expected variables
        $openingMovements = $aggregatedResults->pluck('main_opening', 'account_id');
        $debits = $aggregatedResults->pluck('main_debit', 'account_id');
        $credits = $aggregatedResults->pluck('main_credit', 'account_id');

        // Scope income statement opening balances to fiscal year
        // (Revenue/COGS/Expenses should not carry forward from prior fiscal years)
        $incomeStatementIds = $allAccounts
            ->whereIn('category', ['Revenue', 'COGS', 'Expenses'])
            ->pluck('id')->toArray();

        if (!empty($incomeStatementIds)) {
            $companyId = loggedCompanyId()['company_id'];
            $fiscalYear = AccountingPeriod::where('company_id', $companyId)
                ->where('start_date', '<=', $from)
                ->where('end_date', '>=', $from)
                ->first();

            $fyStart = $fiscalYear
                ? $fiscalYear->start_date->format('Y-m-d')
                : Carbon::parse($from)->startOfYear()->format('Y-m-d');

            // If FY start is before report start, adjust opening to only include FY activity
            if ($fyStart < $from) {
                // Get balance accumulated before FY start (to subtract from all-time opening)
                $preFyBalances = Transaction::whereIn('account_id', $incomeStatementIds)
                    ->whereDate('date', '<', $fyStart)
                    ->groupBy('account_id')
                    ->selectRaw('account_id, SUM(debit - credit) AS balance')
                    ->pluck('balance', 'account_id');

                foreach ($incomeStatementIds as $accId) {
                    $preFyBalance = $preFyBalances[$accId] ?? 0;
                    if ($preFyBalance != 0 && isset($openingMovements[$accId])) {
                        $openingMovements[$accId] -= $preFyBalance;
                    }
                }
            } else {
                // FY start == report start: opening for income statement accounts is 0
                foreach ($incomeStatementIds as $accId) {
                    $openingMovements[$accId] = 0;
                }
            }
        }
        

        // Map comparison results
        if ($comparePeriod && $compareCount > 0) {
            foreach ($bulkComparisonData as $i => &$data) {
                // We need to build arrays keyed by account_id for the view loop
                $ops = [];
                $debs = [];
                $creds = [];

                foreach ($aggregatedResults as $accId => $row) {
                    $ops[$accId] = $row->{'opening_' . $i};
                    $debs[$accId] = $row->{'debit_' . $i};
                    $creds[$accId] = $row->{'credit_' . $i};
                }

                $data['opening'] = $ops;
                $data['debits'] = $debs;
                $data['credits'] = $creds;
            }
            unset($data); // Break reference
        }

        $allAccountsTotalDebit = 0;
        $allAccountsTotalCredit = 0;

        foreach ($allAccounts as $acc) {
            $allAccountsTotalDebit += $debits[$acc->id] ?? 0;
            $allAccountsTotalCredit += $credits[$acc->id] ?? 0;
        }

        $trialData = [];
        $totalDebit = 0;
        $totalCredit = 0;
        $exportData = [];

        foreach ($chartOfAccounts as $acc) {

            $openingMovement = $openingMovements[$acc->id] ?? 0;
            $openingBalance = $openingMovement;

            $debit = $debits[$acc->id] ?? 0;
            $credit = $credits[$acc->id] ?? 0;

            $yearBalance = $openingBalance + ($debit - $credit);

            $totalDebit += $debit;
            $totalCredit += $credit;

            $comparisons = [];
            if (!empty($bulkComparisonData)) {
                foreach ($bulkComparisonData as $i => $data) {
                    $cOpening = $data['opening'][$acc->id] ?? 0;

                    $cDebit = $data['debits'][$acc->id] ?? 0;
                    $cCredit = $data['credits'][$acc->id] ?? 0;
                    $cYearBalance = $cOpening + ($cDebit - $cCredit);

                    $comparisons[] = [
                        'period_label' => $data['label'],
                        'opening' => $cOpening,
                        'debit' => $cDebit,
                        'credit' => $cCredit,
                        'year_balance' => $cYearBalance,
                    ];
                }
            }

            $accountData = [
                'id' => $acc->id,
                'code' => $acc->account_number,
                'name' => $acc->name,
                'opening' => $openingBalance,
                'debit' => $debit,
                'credit' => $credit,
                'year_balance' => $yearBalance,
                'comparisons' => $comparisons,
            ];

            $hide = $debit == 0 && $credit == 0;
            // Show if any comparison activity OR if opening/closing balance is non-zero in comparisons?
            // Usually we care about activity or non-zero usage.
            // Let's stick to activity check for comparisons.
            $hasComparisonActivity = collect($comparisons)->contains(fn($c) => $c['debit'] != 0 || $c['credit'] != 0);

            if (!$hide || $openingBalance > 0 || $hasComparisonActivity) {
                $trialData[$acc->category][] = $accountData;
                if ($exportType) {
                    $row = [
                        'Account Code' => $acc->account_number,
                        'Account Name' => $acc->name,
                        'Category' => $acc->category,
                        'Opening Balance' => numberFormat($openingBalance),
                        'Debit' => numberFormat($debit),
                        'Credit' => numberFormat($credit),
                        'Year End Balance' => numberFormat($yearBalance),
                    ];

                    if (!empty($comparisons)) {
                        foreach ($comparisons as $idx => $comp) {
                            $idxStr = $idx + 1;
                            $row["Period {$idxStr}"] = $comp['period_label'];
                            $row["Period {$idxStr} Opening"] = numberFormat($comp['opening']);
                            $row["Period {$idxStr} Debit"] = numberFormat($comp['debit']);
                            $row["Period {$idxStr} Credit"] = numberFormat($comp['credit']);
                            $row["Period {$idxStr} Closing"] = numberFormat($comp['year_balance']);
                        }
                    }

                    $exportData[] = $row;
                }
            }
        }

        // $epsilon = 0.00001;
        // $status = abs($allAccountsTotalDebit - $allAccountsTotalCredit) < $epsilon ? 'Balanced' : 'Unbalanced';
        
        $debit  = round((float) $allAccountsTotalDebit, 2);
        $credit = round((float) $allAccountsTotalCredit, 2);

        $status = $debit === $credit ? 'Balanced' : 'Unbalanced';

        if ($exportType === 'pdf') {
            return $this->_exportToPDF($trialData, $exportData, $from, $to, $totalDebit, $totalCredit, $status, $comparePeriod, $compareCount, $comparisonHeaders);
        } elseif ($exportType === 'csv') {
            return $this->_exportToCSV($exportData, $from, $to, $totalDebit, $totalCredit, $status, $comparePeriod, $compareCount);
        }

        $data = [
            'trialData' => $trialData,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'allAccountsTotalDebit' => $allAccountsTotalDebit,
            'allAccountsTotalCredit' => $allAccountsTotalCredit,
            'status' => $status,
            'from' => $from,
            'to' => $to,
            'chartOfAccounts' => $chartOfAccounts,
            'comparePeriod' => $comparePeriod,
            'compareCount' => $compareCount,
            'comparisonHeaders' => $comparisonHeaders
        ];

        if ($request->ajax()) {
            $this->logReportAccess($request, 'Trial Balance', 'filter');

            return response()->json([
                'html' => view('pages.account.reports.partials.trial-balance-table', $data)->render(),
                'pagination' => $chartOfAccounts->appends(request()->query())->links()->toHtml(),
                'summary' => [
                    'total_debit' => numberFormat($allAccountsTotalDebit),
                    'total_credit' => numberFormat($allAccountsTotalCredit),
                    'status' => $status,
                ],
            ]);
        }

        $this->logReportAccess($request, 'Trial Balance', 'view');

        return view('pages.account.reports.trail-balance', $data);
    }

    public function exportToPDF(Request $request)
    {
        $this->logReportAccess($request, 'Trial Balance', 'export', 'pdf');

        $request->merge(['export_type' => 'pdf']);
        return $this->trialBalance($request);
    }

    private function _exportToPDF($trialData, $exportData, $from, $to, $totalDebit, $totalCredit, $status, $comparePeriod, $compareCount = 0, $comparisonHeaders = [])
    {
        if (empty($trialData)) {
            $trialData = [];
        }

        $invoiceTemplate = \App\Models\InvoiceTemplate::where('company_id', loggedCompanyId()['company_id'])->where('is_default', true)->first();

        $pdf = Pdf::loadView('pages.account.reports.pdf.trial-balance-pdf', [
            'trialData' => $trialData,
            'from' => $from,
            'to' => $to,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'status' => $status,
            'comparePeriod' => $comparePeriod,
            'compareCount' => $compareCount,
            'comparisonHeaders' => $comparisonHeaders,
            'generatedAt' => now(),
            'difference' => $totalDebit - $totalCredit,
            'invoiceTemplate' => $invoiceTemplate,
        ]);

        $pdf->setPaper($invoiceTemplate->page_size ?? 'a4', 'landscape');

        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'Helvetica',
            'dpi' => 96,
        ]);

        $filename = 'trial-balance-' . date('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    public function exportToCSV(Request $request)
    {
        $this->logReportAccess($request, 'Trial Balance', 'export', 'csv');

        $request->merge(['export_type' => 'csv']);
        return $this->trialBalance($request);
    }

    private function _exportToCsv(
        $exportData,
        $from,
        $to,
        $totalDebit,
        $totalCredit,
        $status,
        $comparePeriod,
        $compareCount
    ) {
        $colCount = !empty($exportData) ? count(array_keys($exportData[0])) : 7;

        $spreadsheet = $this->createReportSpreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Trial Balance');
        $row = 1;

        $this->writeTitle($sheet, 'Trial Balance Report', $row, $colCount);
        $this->writeCompanyRow($sheet, $row, $colCount);
        $this->writeMeta($sheet, [
            ['Period:', "{$from} to {$to}"],
            ['Comparison:', $comparePeriod ?: 'None'],
            ['Status:', $status],
            ['Generated:', now()->format('d M Y, h:i A')],
        ], $row);

        if (!empty($exportData)) {
            $headers = array_keys($exportData[0]);
            $this->writeColumnHeaders($sheet, $headers, $row);

            foreach ($exportData as $dataRow) {
                $this->writeDataRow($sheet, array_values($dataRow), $row, $colCount);
            }

            $row++;

            $this->writeGrandTotalRow($sheet, array_pad(['TOTAL DEBIT:', numberFormat($totalDebit)], $colCount, ''), $row, $colCount);
            $this->writeGrandTotalRow($sheet, array_pad(['TOTAL CREDIT:', numberFormat($totalCredit)], $colCount, ''), $row, $colCount);
            $this->writeGrandTotalRow($sheet, array_pad(['DIFFERENCE:', numberFormat($totalDebit - $totalCredit)], $colCount, ''), $row, $colCount);
        }

        $this->autoSizeColumns($sheet, $colCount);

        $filename = 'trial-balance-' . now()->format('Y-m-d-H-i-s') . '.xlsx';
        return $this->downloadExcel($spreadsheet, $filename);
    }
}
