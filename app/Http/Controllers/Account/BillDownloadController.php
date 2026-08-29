<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Company;
use App\Models\InvoiceTemplate;
use App\Services\BillService;
use App\Support\PdfPaperSize;
use App\Support\SelectedRecordOrdering;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use ZipArchive;

class BillDownloadController extends Controller
{
    public function __construct(
        private BillService $billService
    ) {}

    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 50);
        $perPage = $perPage > 0 ? $perPage : 50;

        $selectedBillIds = SelectedRecordOrdering::ids($request, 'selected_bill_ids');

        $bills = SelectedRecordOrdering::apply($this->filteredBills($request), $selectedBillIds)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'bill_page')
            ->appends($request->except('bill_page'));

        $exportColumns = $this->exportColumns();
        $invoiceExportColumns = $this->invoiceExportColumns();
        $paperSizeOptions = PdfPaperSize::options();

        return view('pages.account.bill-download.index', compact('bills', 'exportColumns', 'invoiceExportColumns', 'paperSizeOptions'));
    }

    public function pdf(Request $request)
    {
        $validated = $request->validate([
            'bill_ids' => 'required|array|min:1',
            'bill_ids.*' => 'integer|exists:bills,id',
            'columns' => 'nullable|array',
            'columns.*' => 'string',
            'paper_size' => PdfPaperSize::validationRule(),
        ]);

        $bills = Bill::with(['vendor', 'currency', 'accountingPeriod', 'creator', 'updater'])
            ->whereIn('id', $validated['bill_ids'])
            ->orderByDesc('date')
            ->get();

        $invoiceTemplate = InvoiceTemplate::where('company_id', loggedCompanyId()['company_id'])
            ->where('is_default', true)
            ->first() ?? new InvoiceTemplate();
        $exportColumns = $this->selectedExportColumns($request);

        $paperSize = PdfPaperSize::normalize($request->input('paper_size'), PdfPaperSize::DEFAULT_REPORT);
        $pdf = Pdf::loadView('pdf.bill-pdf', compact('bills', 'invoiceTemplate', 'exportColumns'));
        PdfPaperSize::apply($pdf, $paperSize, 'landscape');

        return $pdf->download('selected_bills_' . now()->format('Ymd_His') . '.pdf');
    }

    public function separatePdfs(Request $request)
    {
        $validated = $request->validate([
            'bill_ids' => 'required|array|min:1',
            'bill_ids.*' => 'integer|exists:bills,id',
            'columns' => 'nullable|array',
            'columns.*' => 'string',
            'paper_size' => PdfPaperSize::validationRule(),
        ]);

        if (!class_exists(ZipArchive::class)) {
            return back()->with('error', 'ZIP extension is not enabled on this server.');
        }

        $bills = Bill::with([
            'vendor',
            'currency',
            'billItems.account',
            'billItems.tax',
            'creator',
            'attachments',
        ])
            ->whereIn('id', $validated['bill_ids'])
            ->orderByDesc('date')
            ->get();

        $company = auth('web')->user()?->company ?? Company::find(loggedCompanyId()['company_id']) ?? Company::first();
        $invoiceTemplate = InvoiceTemplate::where('company_id', loggedCompanyId()['company_id'])
            ->where('is_default', true)
            ->first() ?? new InvoiceTemplate();
        $selectedColumns = $this->selectedInvoiceColumnKeys($request);
        $paperSize = PdfPaperSize::normalize($request->input('paper_size'));
        $paperCssSize = PdfPaperSize::cssSize($paperSize);

        $tempDirectory = storage_path('app/temp/bill-downloads');
        if (!is_dir($tempDirectory)) {
            mkdir($tempDirectory, 0755, true);
        }

        $downloadName = 'selected_bill_pdfs_' . now()->format('Ymd_His') . '.zip';
        $zipPath = $tempDirectory . DIRECTORY_SEPARATOR . Str::random(10) . '_' . $downloadName;

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Unable to create bill PDF zip file.');
        }

        foreach ($bills as $bill) {
            $vendor = $bill->vendor;
            $items = $bill->billItems;
            $date = $bill->date instanceof \Carbon\Carbon ? $bill->date->format('d-m-Y') : \Carbon\Carbon::parse($bill->date)->format('d-m-Y');
            $subtotal = $bill->sub_total;
            $discount = $bill->discount_total ?? 0;
            $tax = $bill->tax;
            $total = $bill->total_after_discount;
            $paid_amount = $bill->paid_amount;
            $remaining_amount = $bill->remaining_amount;
            $amount_in_words = $this->billService->convertToWords($bill->total_after_discount);

            $pdf = Pdf::loadView('pdf.export-columns-bill', [
                'bill' => $bill,
                'vendor' => $vendor,
                'company' => $company,
                'items' => $items,
                'date' => $date,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total' => $total,
                'paid_amount' => $paid_amount,
                'remaining_amount' => $remaining_amount,
                'amount_in_words' => $amount_in_words,
                'invoiceTemplate' => $invoiceTemplate,
                'title' => 'Bill Invoice',
                'selectedColumns' => $selectedColumns,
                'paperCssSize' => $paperCssSize,
            ]);
            PdfPaperSize::apply($pdf, $paperSize);

            $fileName = 'bill_' . $this->safeFilenamePart($bill->invoice_no ?? $bill->ref_no ?? $bill->id) . '_' . $bill->id . '.pdf';
            $zip->addFromString($fileName, $pdf->output());
        }

        $zip->close();

        return response()->download($zipPath, $downloadName)->deleteFileAfterSend(true);
    }

    public function csv(Request $request)
    {
        $validated = $request->validate([
            'bill_ids' => 'required|array|min:1',
            'bill_ids.*' => 'integer|exists:bills,id',
        ]);

        $bills = Bill::with(['vendor', 'creator', 'updater'])
            ->whereIn('id', $validated['bill_ids'])
            ->orderByDesc('date')
            ->get();
        $exportColumns = $this->selectedExportColumns($request);

        $filename = 'selected_bills_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
        ];

        return response()->stream(function () use ($bills, $exportColumns) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, array_column($exportColumns, 'label'));

            foreach ($bills as $index => $bill) {
                fputcsv($handle, $this->exportRow($bill, $index, $exportColumns));
            }

            fclose($handle);
        }, 200, $headers);
    }

    private function filteredBills(Request $request)
    {
        return Bill::with(['vendor:id,name', 'creator' => function ($q) {
            $q->select('id', 'first_name', 'last_name')->without('company');
        }, 'updater' => function ($q) {
            $q->select('id', 'first_name', 'last_name')->without('company');
        }])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->search);

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('invoice_no', 'like', "%{$search}%")
                        ->orWhere('ref_no', 'like', "%{$search}%")
                        ->orWhere('note', 'like', "%{$search}%")
                        ->orWhereHas('vendor', fn($vendorQuery) => $vendorQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('from_date'), fn($query) => $query->whereDate('date', '>=', $request->from_date))
            ->when($request->filled('to_date'), fn($query) => $query->whereDate('date', '<=', $request->to_date))
            ->when($request->filled('vendor_id'), fn($query) => $query->where('vendor_id', $request->vendor_id))
            ->when($request->filled('approval_status'), fn($query) => $query->where('approval_status', $request->approval_status))
            ->when($request->filled('payment_status'), fn($query) => $query->where('payment_status', $request->payment_status));
    }

    private function statusLabel(Bill $bill): string
    {
        return ucfirst((string) $bill->approval_status) . ' / ' . ucfirst(str_replace('_', ' ', $bill->payment_status ?? 'unpaid'));
    }

    private function safeFilenamePart($value): string
    {
        $filename = preg_replace('/[^A-Za-z0-9._-]+/', '_', (string) $value);
        $filename = trim($filename, '._-');

        return $filename !== '' ? $filename : 'bill';
    }

    private function selectedExportColumns(Request $request): array
    {
        $allowedColumns = $this->exportColumns();
        $selectedKeys = $request->input('columns', array_keys($allowedColumns));
        $selectedKeys = is_array($selectedKeys) ? $selectedKeys : explode(',', (string) $selectedKeys);
        $selectedKeys = array_values(array_intersect($selectedKeys, array_keys($allowedColumns)));

        if (empty($selectedKeys)) {
            $selectedKeys = array_keys($allowedColumns);
        }

        return array_intersect_key($allowedColumns, array_flip($selectedKeys));
    }

    private function exportRow(Bill $bill, int $index, array $exportColumns): array
    {
        return array_map(
            fn($column) => $column['value']($bill, $index),
            $exportColumns
        );
    }

    private function exportColumns(): array
    {
        return [
            'serial' => [
                'label' => 'S.No',
                'align' => 'left',
                'value' => fn(Bill $bill, int $index) => $index + 1,
            ],
            'date' => [
                'label' => 'Date',
                'align' => 'left',
                'value' => fn(Bill $bill, int $index) => optional($bill->date)->format('Y-m-d'),
            ],
            'ref_no' => [
                'label' => 'Reference No',
                'align' => 'left',
                'value' => fn(Bill $bill, int $index) => $bill->ref_no,
            ],
            'invoice_no' => [
                'label' => 'Invoice No',
                'align' => 'left',
                'value' => fn(Bill $bill, int $index) => $bill->invoice_no,
            ],
            'vendor' => [
                'label' => 'Vendor',
                'align' => 'left',
                'value' => fn(Bill $bill, int $index) => $bill->vendor?->name,
            ],
            'note' => [
                'label' => 'Note',
                'align' => 'left',
                'value' => fn(Bill $bill, int $index) => $bill->note,
            ],
            'total' => [
                'label' => 'Total Amount',
                'align' => 'right',
                'is_total' => true,
                'value' => fn(Bill $bill, int $index) => $bill->total,
            ],
            'total_after_discount' => [
                'label' => 'Total After Discount / Tax',
                'align' => 'right',
                'is_total' => true,
                'value' => fn(Bill $bill, int $index) => $bill->total_after_discount,
            ],
            'paid_amount' => [
                'label' => 'Paid Amount',
                'align' => 'right',
                'is_total' => true,
                'value' => fn(Bill $bill, int $index) => $bill->paid_amount,
            ],
            'remaining_amount' => [
                'label' => 'Remaining Amount',
                'align' => 'right',
                'is_total' => true,
                'value' => fn(Bill $bill, int $index) => $bill->remaining_amount,
            ],
            'status' => [
                'label' => 'Status',
                'align' => 'left',
                'value' => fn(Bill $bill, int $index) => $this->statusLabel($bill),
            ],
            'created_by' => [
                'label' => 'Created By',
                'align' => 'left',
                'value' => fn(Bill $bill, int $index) => $bill->creator?->fullName,
            ],
            'updated_by' => [
                'label' => 'Updated By',
                'align' => 'left',
                'value' => fn(Bill $bill, int $index) => $bill->updater?->fullName,
            ],
        ];
    }

    private function selectedInvoiceColumnKeys(Request $request): array
    {
        $allowedColumns = $this->invoiceExportColumns();
        $selectedKeys = $request->input('columns', array_keys($allowedColumns));
        $selectedKeys = is_array($selectedKeys) ? $selectedKeys : explode(',', (string) $selectedKeys);
        $selectedKeys = array_values(array_intersect($selectedKeys, array_keys($allowedColumns)));

        return !empty($selectedKeys) ? $selectedKeys : array_keys($allowedColumns);
    }

    private function invoiceExportColumns(): array
    {
        return [
            'vendor' => 'Vendor',
            'invoice_no' => 'Invoice No',
            'date' => 'Date',
            'ref_no' => 'Reference No',
            'status' => 'Status',
            'note' => 'Note',
            'account' => 'Item / Account',
            'tax' => 'Tax',
            'sub_total' => 'Sub Total',
            'discount' => 'Discount',
            'total' => 'Total',
            'paid_amount' => 'Paid Amount',
            'remaining_amount' => 'Remaining Amount',
        ];
    }
}
