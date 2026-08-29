<?php

namespace App\Http\Controllers\Account\Report;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class JournalController extends Controller
{
    use \App\Traits\LogsReportAccess, \App\Traits\ExportsToExcel;
    private function journalQuery(Request $request)
    {
        [$from, $to] = resolveDateRange($request, $request->date_range ?? 'year');
        $accountId = $request->account_id;
        $invoiceNo = $request->invoice_no;
        $type = $request->type;

        $query = Transaction::with(['account', 'payable', 'customer'])
            ->orderBy('date', 'desc')
            ->orderBy('unique_no', 'desc')
            ->orderBy('id', 'desc');

        if ($from) {
            $query->whereDate('date', '>=', $from);
        }

        if ($to) {
            $query->whereDate('date', '<=', $to);
        }

        if ($accountId) {
            $query->where('account_id', $accountId);
        }

        if ($invoiceNo) {
            $query->where('invoice_no', $invoiceNo);
        }

        if ($type) {
            $query->where('type', $type);
        }

        return [$query, $from, $to];
    }

    public function journal(Request $request)
    {
        [$query, $from, $to] = $this->journalQuery($request);
        $perPage = $request->per_page ?? 40;

        $totalDebit = (clone $query)->sum('debit');
        $totalCredit = (clone $query)->sum('credit');

        $transactions = $query->paginate($perPage)->withQueryString()->appends($request->query());

        // $journals = $transactions->getCollection()->groupBy('unique_no');

        $journals = $transactions->getCollection()->groupBy([
            'unique_no',
            function ($t) {
                // Payroll / Vendor grouping
                if ($t->payable_type && $t->payable_id) {
                    return $t->payable_type . ':' . $t->payable_id;
                }

                // Customer receivable grouping
                if ($t->customer_id) {
                    return 'customer:' . $t->customer_id;
                }

                return 'general';
            }
        ]);

        $data = [
            'journals'     => $journals,
            'transactions' => $transactions,
            'from'         => $from,
            'to'           => $to,
            'totalDebit'   => $totalDebit,
            'totalCredit'  => $totalCredit,
            'grandDebit'   => $totalDebit,
            'grandCredit'  => $totalCredit,
        ];

        if ($request->ajax()) {
            $this->logReportAccess($request, 'Journal', 'filter');

            return response()->json([
                'html' => view('pages.account.reports.partials.journal-table', $data)->render(),
                'pagination' => $transactions->links()->toHtml(),
                'summary' => [
                    'total_debit'  => numberFormat($totalDebit),
                    'total_credit' => numberFormat($totalCredit),
                ],
            ]);
        }

        $this->logReportAccess($request, 'Journal', 'view');

        return view('pages.account.reports.journal', $data);
    }

    public function getExportColumns()
    {
        $columns = [
            ['key' => 'date', 'label' => 'Date', 'checked' => true],
            ['key' => 'unique_no', 'label' => 'Journal No', 'checked' => true],
            ['key' => 'account', 'label' => 'Account', 'checked' => true],
            ['key' => 'debit', 'label' => 'Debit', 'checked' => true],
            ['key' => 'credit', 'label' => 'Credit', 'checked' => true],
            ['key' => 'description', 'label' => 'Description', 'checked' => true],
            ['key' => 'counterparty', 'label' => 'Counterparty', 'checked' => true],
            ['key' => 'type', 'label' => 'Type', 'checked' => false],
            ['key' => 'invoice_no', 'label' => 'Invoice No', 'checked' => false],
        ];

        return response()->json($columns);
    }

    public function exportJournalCsv(Request $request)
    {
        $this->logReportAccess($request, 'Journal', 'export', 'csv');

        set_time_limit(0);
        ini_set('max_execution_time', '0');

        [$query, $from, $to] = $this->journalQuery($request);

        if (!(clone $query)->exists()) {
            abort(400, "No data found for Journal export.");
        }

        $selectedColumns = $request->columns ? explode(',', $request->columns) : null;

        $allColumns = [
            'date' => 'Date',
            'unique_no' => 'Journal No',
            'account' => 'Account',
            'debit' => 'Debit',
            'credit' => 'Credit',
            'description' => 'Description',
            'counterparty' => 'Counterparty',
            'type' => 'Type',
            'invoice_no' => 'Invoice No',
        ];

        $activeColumns = $selectedColumns
            ? array_filter($allColumns, fn($key) => in_array($key, $selectedColumns), ARRAY_FILTER_USE_KEY)
            : $allColumns;

        $headers = array_values($activeColumns);
        $colCount = count($headers);

        $spreadsheet = $this->createReportSpreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Journal');
        $row = 1;

        $this->writeTitle($sheet, 'Journal Report', $row, $colCount);
        $this->writeCompanyRow($sheet, $row, $colCount);
        $this->writeMeta($sheet, [
            ['Period:', $from . ' to ' . $to],
            ['Generated:', now()->format('d M Y, h:i A')],
        ], $row);

        $this->writeColumnHeaders($sheet, $headers, $row);

        $totalDebit = 0;
        $totalCredit = 0;

        foreach (
            $query->select([
                'id', 'date', 'unique_no', 'account_id', 'debit', 'credit',
                'description', 'payable_type', 'payable_id', 'customer_id', 'type', 'invoice_no',
            ])->with([
                'account:id,name', 'payable', 'customer:id,name',
            ])->get() as $t
        ) {
            $totalDebit += $t->debit;
            $totalCredit += $t->credit;

            $rowData = [
                'date' => dateFormat($t->date),
                'unique_no' => $t->unique_no,
                'account' => $t->account->name ?? '',
                'debit' => numberFormat($t->debit),
                'credit' => numberFormat($t->credit),
                'description' => $t->description ?? '',
                'counterparty' => $t->counterparty ?? '',
                'type' => $t->type ?? '',
                'invoice_no' => $t->invoice_no ?? '',
            ];
            $filtered = array_values(array_intersect_key($rowData, $activeColumns));
            $this->writeDataRow($sheet, $filtered, $row, $colCount);
        }

        // Grand total
        $totalRow = [
            'date' => 'TOTAL',
            'unique_no' => '',
            'account' => '',
            'debit' => numberFormat($totalDebit),
            'credit' => numberFormat($totalCredit),
            'description' => '',
            'counterparty' => '',
            'type' => '',
            'invoice_no' => '',
        ];
        $this->writeGrandTotalRow($sheet, array_values(array_intersect_key($totalRow, $activeColumns)), $row, $colCount);

        $this->autoSizeColumns($sheet, $colCount);

        $filename = 'journal_report_' . now()->format('Ymd_His') . '.xlsx';
        return $this->downloadExcel($spreadsheet, $filename);
    }

    public function exportJournalPdf(Request $request)
    {
        $this->logReportAccess($request, 'Journal', 'export', 'pdf');

        set_time_limit(300);

        $companyId = loggedCompanyId()['company_id'];
        [$query, $from, $to] = $this->journalQuery($request);

        // For large datasets, fall back to CSV (DomPDF struggles beyond ~5k rows)
        $rowCount = (clone $query)->count();
        if ($rowCount > 5000) {
            return $this->exportJournalCsv($request);
        }

        // Parse selected columns
        $selectedColumns = $request->columns ? explode(',', $request->columns) : ['date', 'account', 'debit', 'credit', 'description'];

        // Compute totals via SQL instead of loading everything into memory
        $totalDebit  = (clone $query)->sum('debit');
        $totalCredit = (clone $query)->sum('credit');

        // Fetch only needed columns with constrained eager-loads
        $transactions = $query->select([
            'id',
            'date',
            'unique_no',
            'account_id',
            'debit',
            'credit',
            'description',
            'payable_type',
            'payable_id',
            'customer_id',
            'type',
            'invoice_no',
        ])->with([
            'account:id,name',
            'payable',
            'customer:id,name',
        ])->get();

        // ✅ SAME grouping as UI
        $journals = $transactions->groupBy([
            'unique_no',
            function ($t) {
                if ($t->payable_type && $t->payable_id) {
                    return $t->payable_type . ':' . $t->payable_id;
                }
                if ($t->customer_id) {
                    return 'customer:' . $t->customer_id;
                }
                return 'general';
            }
        ]);

        // Free the raw collection to reclaim memory before PDF rendering
        unset($transactions);

        $invoiceTemplate = \App\Models\InvoiceTemplate::where('company_id', $companyId)
            ->where('is_default', true)
            ->first();

        $pdf = Pdf::loadView('pages.account.reports.pdf.journal-pdf', [
            'journals'        => $journals,
            'from'            => $from,
            'to'              => $to,
            'companyName'     => loggedCompanyId()['company_name'] ?? 'Company',
            'totalDebit'      => $totalDebit,
            'totalCredit'     => $totalCredit,
            'invoiceTemplate' => $invoiceTemplate,
            'selectedColumns' => $selectedColumns,
        ]);

        $pdf->setPaper($invoiceTemplate->page_size ?? 'a4', 'landscape');

        return $pdf->download("journal_report_" . now()->format('Ymd_His') . ".pdf");
    }
}
