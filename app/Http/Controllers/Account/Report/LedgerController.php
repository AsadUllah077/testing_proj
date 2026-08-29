<?php

namespace App\Http\Controllers\Account\Report;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class LedgerController extends Controller
{
    use \App\Traits\LogsReportAccess, \App\Traits\ExportsToExcel;
    /**
     * Get account IDs based on filter
     */
    private function getAccountIds(Request $request): array
    {
        $accountId     = $request->account_id;
        $headAccountId = $request->head_account_id;

        if ($accountId) {
            return [$accountId];
        } elseif ($headAccountId) {
            return ChartOfAccount::forCompany(loggedCompanyId()['company_id'])
                ->where(function ($query) use ($headAccountId) {
                    $query->where('id', $headAccountId)
                        ->orWhere('parent_id', $headAccountId);
                })
                ->accounts()
                ->pluck('id')
                ->all();
        }

        return ChartOfAccount::forCompany(loggedCompanyId()['company_id'])->accounts()->pluck('id')->all();;
    }

    /**
     * Get dropdown data (cached)
     */
    private function getDropdownData(): array
    {
        $childAccount = [];
        $parentAccount = [];

        $allChartAccounts = cache()->remember(
            'chart_accounts_list_' . (loggedCompanyId()['company_id'] ?? 0),
            300,
            fn() => ChartOfAccount::forCompany(loggedCompanyId()['company_id'])
                ->select('id', 'name', 'account_number', 'parent_id')
                ->get()
        );

        foreach ($allChartAccounts as $account) {
            if ($account->parent_id) {
                $childAccount[] = $account;
            } else {
                $parentAccount[] = $account;
            }
        }

        return compact('childAccount', 'parentAccount', 'allChartAccounts');
    }

    /**
     * OPTIMIZED: Build ledger with PAGINATED transactions
     */
    private function buildPaginatedLedger(array $accountIds, $from, $to, int $perPage, int $page): array
    {
        if (empty($accountIds)) {
            return ['ledgers' => [], 'total' => 0];
        }

        // Step 1: Get total transaction count for pagination
        $totalTransactions = Transaction::whereIn('account_id', $accountIds)
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->count();

        // Step 2: Get ONLY the transactions for current page
        $offset = ($page - 1) * $perPage;

        $paginatedTransactions = Transaction::with([
            'sale:id,total_after_discount,tax',
            'bill:id,total_after_discount,tax',
        ])
            ->whereIn('account_id', $accountIds)
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->orderBy('account_id')
            ->orderBy('date')
            ->orderBy('id')
            ->skip($offset)
            ->take($perPage)
            ->get();

        // Step 3: Get unique account IDs from paginated results
        $activeAccountIds = $paginatedTransactions->pluck('account_id')->unique()->all();

        if (empty($activeAccountIds)) {
            return ['ledgers' => [], 'total' => $totalTransactions];
        }

        // Step 4: Load accounts
        $accounts = ChartOfAccount::with(['parent:id,name'])
            ->whereIn('id', $activeAccountIds)
            ->get()
            ->keyBy('id');

        // Step 5: Calculate opening balance + running balance UP TO this page
        // This is the cumulative balance BEFORE the first transaction on this page
        $openingBalances = [];

        foreach ($activeAccountIds as $accId) {
            // Opening balance = all transactions before date range (OBE entry included)
            $beforeRange = Transaction::where('account_id', $accId)
                ->whereDate('date', '<', $from)
                ->selectRaw('SUM(debit - credit) AS balance')
                ->value('balance') ?? 0;

            $openingBalances[$accId] = $beforeRange;
        }

        // Step 6: For running balance, we need to know cumulative sum before each account's first transaction on this page
        $runningBalancesBefore = [];
        foreach ($activeAccountIds as $accId) {
            $firstTxnOnPage = $paginatedTransactions->where('account_id', $accId)->first();

            if ($firstTxnOnPage) {
                // Get sum of all transactions for this account BEFORE this transaction
                $cumulativeBefore = Transaction::where('account_id', $accId)
                    ->where(function ($q) use ($firstTxnOnPage, $from) {
                        $q->whereDate('date', '<', $from)
                            ->orWhere(function ($q2) use ($firstTxnOnPage, $from) {
                                $q2->whereDate('date', '>=', $from)
                                    ->whereDate('date', '<', $firstTxnOnPage->date);
                            })
                            ->orWhere(function ($q2) use ($firstTxnOnPage, $from) {
                                $q2->whereDate('date', '=', $firstTxnOnPage->date)
                                    ->where('id', '<', $firstTxnOnPage->id);
                            });
                    })
                    ->selectRaw('SUM(debit - credit) AS balance')
                    ->value('balance') ?? 0;

                $runningBalancesBefore[$accId] = $cumulativeBefore;
            } else {
                $runningBalancesBefore[$accId] = $openingBalances[$accId];
            }
        }

        // Step 7: Group transactions by account
        $groupedTransactions = $paginatedTransactions->groupBy('account_id');

        // Step 8: Build ledgers
        $ledgers = [];

        foreach ($activeAccountIds as $accId) {
            $account = $accounts[$accId] ?? null;
            if (!$account) continue;

            $entries = $groupedTransactions[$accId] ?? collect();
            $runningBalance = $runningBalancesBefore[$accId];

            // Credit-normal accounts (Liabilities, Equity, Revenue) show balance in natural sign
            $isCreditNormal = in_array($account->category, ['Liabilities', 'Equity', 'Revenue']);

            $rows = $entries->map(function ($row) use (&$runningBalance, $isCreditNormal) {
                $runningBalance += ($row->debit - $row->credit);

                $gross = 0;
                $tax = 0;

                if ($row->type === 'Sale' && $row->sale) {
                    $gross = $row->sale->total_after_discount;
                    $tax = $row->sale->tax;
                } elseif ($row->type === 'Bill' && $row->bill) {
                    $gross = $row->bill->total_after_discount;
                    $tax = $row->bill->tax;
                } else {
                    $gross = abs($row->debit - $row->credit);
                }

                // Display balance in natural sign: positive for debit-normal, inverted for credit-normal
                $displayBalance = $isCreditNormal ? -$runningBalance : $runningBalance;

                return [
                    'date'            => $row->date,
                    'posting_date'    => $row->posted_at ?: $row->created_at,
                    'journal_no'      => $row->unique_no,
                    'source_type'     => $row->source_system ?: $row->type,
                    'correction_type' => $row->correction_type ?: ($row->reversal_of_transaction_id ? 'reversal' : 'original'),
                    'linked_original' => $row->reversal_of_unique_no,
                    'opening_batch_id' => $row->opening_balance_batch_id,
                    'posted_by'       => $row->posted_by ?: $row->created_by,
                    'description'     => $row->description,
                    'debit'           => $row->debit,
                    'credit'          => $row->credit,
                    'running_balance' => $displayBalance,
                    'gross'           => $gross,
                    'tax'             => $tax,
                    'unique_no'       => $row->unique_no,
                ];
            })->all();

            // Get account totals (for entire date range, not just this page)
            $totals = Transaction::where('account_id', $accId)
                ->whereDate('date', '>=', $from)
                ->whereDate('date', '<=', $to)
                ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
                ->first();

            $rawClosing = $openingBalances[$accId] + ($totals->total_debit ?? 0) - ($totals->total_credit ?? 0);
            $closingBalance = $isCreditNormal ? -$rawClosing : $rawClosing;

            $displayOpening = $isCreditNormal ? -$openingBalances[$accId] : $openingBalances[$accId];

            $ledgers[] = [
                'account'         => $account,
                'opening_balance' => $displayOpening,
                'rows'            => $rows,
                'closing_balance' => $closingBalance,
                'total_debit'     => $totals->total_debit ?? 0,
                'total_credit'    => $totals->total_credit ?? 0,
                'total_gross'     => collect($rows)->sum('gross'),
                'total_tax'       => collect($rows)->sum('tax'),
            ];
        }

        return [
            'ledgers' => $ledgers,
            'total'   => $totalTransactions,
        ];
    }

    /**
     * For exports - load all data (with chunking for memory efficiency)
     */
    private function ledgerDataForExport(Request $request): array
    {
        [$from, $to] = resolveDateRange($request, $request->date_range ?? 'year');
        $accountIds = $this->getAccountIds($request);

        if (empty($accountIds)) {
            return [[], $from, $to];
        }

        $accounts = ChartOfAccount::with(['parent:id,name'])
            ->whereIn('id', $accountIds)
            ->get()
            ->keyBy('id');

        $openingBalances = Transaction::whereIn('account_id', $accountIds)
            ->whereDate('date', '<', $from)
            ->groupBy('account_id')
            ->selectRaw('account_id, SUM(debit - credit) AS balance')
            ->pluck('balance', 'account_id');

        $ledgers = [];

        // Process each account separately to avoid memory issues
        foreach ($accountIds as $accId) {
            $account = $accounts[$accId] ?? null;
            if (!$account) continue;

            $rawOpening = $openingBalances[$accId] ?? 0;
            $runningBalance = $rawOpening;
            $isCreditNormal = in_array($account->category, ['Liabilities', 'Equity', 'Revenue']);
            $totalDebit = 0;
            $totalCredit = 0;
            $totalGross = 0;
            $totalTax = 0;
            $rows = [];

            // Use chunk to avoid memory issues with large datasets
            Transaction::with([
                'sale:id,total_after_discount,tax',
                'bill:id,total_after_discount,tax'
            ])
                ->select([
                    'id',
                    'date',
                    'unique_no',
                    'description',
                    'debit',
                    'credit',
                    'account_id',
                    'type',
                    'reference_id'
                ])
                ->where('account_id', $accId)
                ->whereDate('date', '>=', $from)
                ->whereDate('date', '<=', $to)
                ->orderBy('date')
                ->orderBy('id')
                ->chunk(1000, function ($entries) use (&$runningBalance, &$totalDebit, &$totalCredit, &$totalGross, &$totalTax, &$rows, $isCreditNormal) {
                    foreach ($entries as $row) {
                        $runningBalance += ($row->debit - $row->credit);
                        $totalDebit += $row->debit;
                        $totalCredit += $row->credit;

                        $gross = 0;
                        $tax = 0;

                        if ($row->type === 'Sale' && $row->sale) {
                            $gross = $row->sale->total_after_discount;
                            $tax = $row->sale->tax;
                        } elseif ($row->type === 'Bill' && $row->bill) {
                            $gross = $row->bill->total_after_discount;
                            $tax = $row->bill->tax;
                        } else {
                            $gross = abs($row->debit - $row->credit);
                        }

                        $totalGross += $gross;
                        $totalTax += $tax;

                        $rows[] = [
                            'date'            => $row->date,
                            'journal_no'      => $row->unique_no,
                            'description'     => $row->description,
                            'debit'           => $row->debit,
                            'credit'          => $row->credit,
                            'running_balance' => $isCreditNormal ? -$runningBalance : $runningBalance,
                            'gross'           => $gross,
                            'tax'             => $tax,
                            'unique_no'       => $row->unique_no,
                        ];
                    }
                });

            if (empty($rows) && $rawOpening == 0) {
                continue;
            }

            $ledgers[] = [
                'account'         => $account,
                'opening_balance' => $isCreditNormal ? -$rawOpening : $rawOpening,
                'rows'            => $rows,
                'closing_balance' => $isCreditNormal ? -$runningBalance : $runningBalance,
                'total_debit'     => $totalDebit,
                'total_credit'    => $totalCredit,
                'total_gross'     => $totalGross,
                'total_tax'       => $totalTax,
            ];
        }

        return [$ledgers, $from, $to];
    }

    public function ledger(Request $request)
    {
        $request->validate([
            'account_id'      => ['nullable', 'integer', \Illuminate\Validation\Rule::exists('chart_of_accounts', 'id')->where('company_id', loggedCompanyId()['company_id'])],
            'head_account_id' => ['nullable', 'integer', \Illuminate\Validation\Rule::exists('chart_of_accounts', 'id')->where('company_id', loggedCompanyId()['company_id'])],
            'from_date'       => 'nullable|date',
            'to_date'         => 'nullable|date',
        ]);

        [$from, $to] = resolveDateRange($request, $request->date_range ?? 'year');
        $perPage = $request->per_page ?? 1000; // Transactions per page
        $currentPage = LengthAwarePaginator::resolveCurrentPage();

        // Get dropdown data
        $dropdownData = $this->getDropdownData();

        // Get account IDs based on filter
        $accountIds = $this->getAccountIds($request);

        // Empty state
        if (empty($accountIds)) {
            $data = [
                'ledgers' => collect(),
                'from'    => $from,
                'to'      => $to,
                'entries' => null,
                ...$dropdownData,
            ];

            if ($request->ajax()) {
                return response()->json([
                    'html' => view('pages.account.reports.partials.ledger-table', $data)->render(),
                ]);
            }
            return view('pages.account.reports.ledger', $data);
        }

        // Build paginated ledger (paginating TRANSACTIONS)
        $result = $this->buildPaginatedLedger($accountIds, $from, $to, $perPage, $currentPage);

        // Create paginator
        $paginatedLedgers = new LengthAwarePaginator(
            collect($result['ledgers']),
            $result['total'],
            $perPage,
            $currentPage,
            [
                'path'  => request()->url(),
                'query' => request()->query(),
            ]
        );

        $data = [
            'ledgers' => $paginatedLedgers,
            'from'    => $from,
            'to'      => $to,
            'entries' => null,
            ...$dropdownData,
        ];

        if ($request->ajax()) {
            $this->logReportAccess($request, 'Ledger', 'filter');

            return response()->json([
                'html'       => view('pages.account.reports.partials.ledger-table', $data)->render(),
                'pagination' => $paginatedLedgers->links()->toHtml(),
                'summary'    => [],
            ]);
        }

        $this->logReportAccess($request, 'Ledger', 'view');

        return view('pages.account.reports.ledger', $data);
    }

    public function getExportColumns()
    {
        $columns = [
            ['key' => 'date', 'label' => 'Date', 'checked' => true],
            ['key' => 'journal_no', 'label' => 'Journal No', 'checked' => true],
            ['key' => 'description', 'label' => 'Description', 'checked' => true],
            ['key' => 'debit', 'label' => 'Debit', 'checked' => true],
            ['key' => 'credit', 'label' => 'Credit', 'checked' => true],
            ['key' => 'running_balance', 'label' => 'Running Balance', 'checked' => true],
            ['key' => 'gross', 'label' => 'Gross', 'checked' => true],
            ['key' => 'tax', 'label' => 'Tax', 'checked' => true],
        ];

        return response()->json($columns);
    }

    public function exportLedgerCsv(Request $request)
    {
        $this->logReportAccess($request, 'Ledger', 'export', 'csv');

        set_time_limit(0);
        ini_set('max_execution_time', '0');

        [$from, $to] = resolveDateRange($request, $request->date_range ?? 'year');
        $accountIds = $this->getAccountIds($request);

        if (empty($accountIds)) {
            abort(400, "No data found for Ledger export.");
        }

        $selectedColumns = $request->columns ? explode(',', $request->columns) : null;

        $allColumns = [
            'date' => 'Date',
            'journal_no' => 'Journal No',
            'description' => 'Description',
            'debit' => 'Debit',
            'credit' => 'Credit',
            'running_balance' => 'Running Balance',
            'gross' => 'Gross',
            'tax' => 'Tax',
        ];

        $activeColumns = $selectedColumns
            ? array_filter($allColumns, fn($key) => in_array($key, $selectedColumns), ARRAY_FILTER_USE_KEY)
            : $allColumns;

        $headers = array_values($activeColumns);
        $colCount = count($headers);

        $spreadsheet = $this->createReportSpreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('General Ledger');
        $row = 1;

        // Report header
        $this->writeTitle($sheet, 'General Ledger Report', $row, $colCount);
        $this->writeCompanyRow($sheet, $row, $colCount);
        $this->writeMeta($sheet, [
            ['Period:', $from . ' to ' . $to],
            ['Generated:', now()->format('d M Y, h:i A')],
        ], $row);

        // Pre-fetch data
        $openingBalances = Transaction::whereIn('account_id', $accountIds)
            ->whereDate('date', '<', $from)
            ->groupBy('account_id')
            ->selectRaw('account_id, SUM(debit - credit) AS balance')
            ->pluck('balance', 'account_id');

        $accounts = ChartOfAccount::whereIn('id', $accountIds)
            ->select('id', 'name', 'account_number', 'category')
            ->get();

        $activeAccountIds = Transaction::whereIn('account_id', $accountIds)
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->distinct()
            ->pluck('account_id');

        $accountsWithActivity = $activeAccountIds
            ->merge($openingBalances->filter(fn($bal) => $bal != 0)->keys())
            ->unique();

        foreach ($accounts as $account) {
            if (!$accountsWithActivity->contains($account->id)) {
                continue;
            }

            $rawOpening = $openingBalances[$account->id] ?? 0;
            $isCreditNormal = in_array($account->category, ['Liabilities', 'Equity', 'Revenue']);
            $displayOpening = $isCreditNormal ? -$rawOpening : $rawOpening;

            // Account section header
            $this->writeSectionHeader($sheet, $account->name . ' (' . $account->account_number . ')', $row, $colCount);
            $this->writeDataRow($sheet, array_pad(['Opening Balance:', numberFormat($displayOpening)], $colCount, ''), $row, $colCount);
            $this->writeColumnHeaders($sheet, $headers, $row);

            $runningBalance = $rawOpening;
            $totalDebit = 0;
            $totalCredit = 0;
            $totalGross = 0;
            $totalTax = 0;

            $transactions = Transaction::with([
                'sale:id,total_after_discount,tax',
                'bill:id,total_after_discount,tax'
            ])
                ->where('account_id', $account->id)
                ->whereDate('date', '>=', $from)
                ->whereDate('date', '<=', $to)
                ->orderBy('date')
                ->orderBy('id')
                ->get();

            foreach ($transactions as $txn) {
                $runningBalance += ($txn->debit - $txn->credit);
                $totalDebit += $txn->debit;
                $totalCredit += $txn->credit;

                $gross = 0;
                $tax = 0;
                if ($txn->type === 'Sale' && $txn->sale) {
                    $gross = $txn->sale->total_after_discount;
                    $tax = $txn->sale->tax;
                } elseif ($txn->type === 'Bill' && $txn->bill) {
                    $gross = $txn->bill->total_after_discount;
                    $tax = $txn->bill->tax;
                } else {
                    $gross = abs($txn->debit - $txn->credit);
                }
                $totalGross += $gross;
                $totalTax += $tax;

                $rowData = [
                    'date' => dateFormat($txn->date),
                    'journal_no' => $txn->unique_no,
                    'description' => $txn->description,
                    'debit' => numberFormat($txn->debit),
                    'credit' => numberFormat($txn->credit),
                    'running_balance' => numberFormat($isCreditNormal ? -$runningBalance : $runningBalance),
                    'gross' => numberFormat($gross),
                    'tax' => numberFormat($tax),
                ];
                $filtered = array_values(array_intersect_key($rowData, $activeColumns));
                $this->writeDataRow($sheet, $filtered, $row, $colCount);
            }

            // Account totals
            $totalRow = [
                'date' => 'Total:',
                'journal_no' => '',
                'description' => '',
                'debit' => numberFormat($totalDebit),
                'credit' => numberFormat($totalCredit),
                'running_balance' => '',
                'gross' => numberFormat($totalGross),
                'tax' => numberFormat($totalTax),
            ];
            $this->writeSubtotalRow($sheet, array_values(array_intersect_key($totalRow, $activeColumns)), $row, $colCount);

            // Closing balance
            $displayClosing = $isCreditNormal ? -$runningBalance : $runningBalance;
            $closingRow = array_pad(['Closing Balance:', numberFormat($displayClosing)], $colCount, '');
            $this->writeSubtotalRow($sheet, $closingRow, $row, $colCount);
            $row++; // Spacer between accounts
        }

        $this->autoSizeColumns($sheet, $colCount);

        $filename = 'ledger_report_' . now()->format('Ymd_His') . '.xlsx';
        return $this->downloadExcel($spreadsheet, $filename);
    }

    public function exportLedgerPdf(Request $request)
    {
        $this->logReportAccess($request, 'Ledger', 'export', 'pdf');

        set_time_limit(300);
        ini_set('memory_limit', '512M');

        // Check total transaction count (approximate check for large datasets)
        $accountIds = $this->getAccountIds($request);
        [$from, $to] = resolveDateRange($request, $request->date_range ?? 'year');

        if (!empty($accountIds)) {
            $totalCount = Transaction::whereIn('account_id', $accountIds)
                ->whereDate('date', '>=', $from)
                ->whereDate('date', '<=', $to)
                ->count();

            // If > 5000 rows, redirect to CSV export for performance
            if ($totalCount > 5000) {
                return $this->exportLedgerCsv($request);
            }
        }

        // Parse selected columns
        $selectedColumns = $request->columns ? explode(',', $request->columns) : ['date', 'journal_no', 'description', 'debit', 'credit', 'running_balance', 'gross', 'tax'];

        [$ledgers, $from, $to] = $this->ledgerDataForExport($request);

        if (empty($ledgers)) {
            abort(400, "No data found for Ledger export.");
        }

        $invoiceTemplate = \App\Models\InvoiceTemplate::where('company_id', loggedCompanyId()['company_id'])
            ->where('is_default', true)
            ->first();

        $pdf = Pdf::loadView('pages.account.reports.pdf.ledger', [
            'companyName'     => loggedCompanyId()['company_name'] ?? 'Company',
            'ledgers'         => $ledgers,
            'from'            => $from,
            'to'              => $to,
            'invoiceTemplate' => $invoiceTemplate,
            'selectedColumns' => $selectedColumns,
        ]);

        $pdf->setPaper($invoiceTemplate->page_size ?? 'a4', 'landscape');

        return $pdf->download("ledger_report_" . now()->format('Ymd_His') . ".pdf");
    }
}
