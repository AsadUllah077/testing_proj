<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\InvoiceTemplate;
use App\Models\Sale;
use App\Services\SaleService;
use App\Support\PdfPaperSize;
use App\Support\SelectedRecordOrdering;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use ZipArchive;

class SaleDownloadController extends Controller
{
    public function __construct(
        private SaleService $saleService
    ) {}

    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 50);
        $perPage = $perPage > 0 ? $perPage : 50;

        $selectedSaleIds = SelectedRecordOrdering::ids($request, 'selected_sale_ids');

        $sales = SelectedRecordOrdering::apply($this->filteredSales($request), $selectedSaleIds)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'sale_page')
            ->appends($request->except('sale_page'));

        $exportColumns = $this->exportColumns();
        $invoiceExportColumns = $this->invoiceExportColumns();
        $paperSizeOptions = PdfPaperSize::options();

        return view('pages.account.sale-download.index', compact('sales', 'exportColumns', 'invoiceExportColumns', 'paperSizeOptions'));
    }

    public function pdf(Request $request)
    {
        $validated = $request->validate([
            'sale_ids' => 'required|array|min:1',
            'sale_ids.*' => 'integer|exists:sales,id',
            'columns' => 'nullable|array',
            'columns.*' => 'string',
            'paper_size' => PdfPaperSize::validationRule(),
        ]);

        $sales = Sale::with(['customer', 'currency', 'accountingPeriod', 'createdBy', 'updatedBy', 'saleItems.taxRate', 'saleItems.account'])
            ->whereIn('id', $validated['sale_ids'])
            ->orderByDesc('date')
            ->get();

        $invoiceTemplate = InvoiceTemplate::where('company_id', loggedCompanyId()['company_id'])
            ->where('is_default', true)
            ->first() ?? new InvoiceTemplate();
        $exportColumns = $this->selectedExportColumns($request);

        $paperSize = PdfPaperSize::normalize($request->input('paper_size'), PdfPaperSize::DEFAULT_REPORT);
        $pdf = Pdf::loadView('pdf.sale-pdf', compact('sales', 'invoiceTemplate', 'exportColumns'));
        PdfPaperSize::apply($pdf, $paperSize, 'landscape');

        return $pdf->download('selected_sales_' . now()->format('Ymd_His') . '.pdf');
    }

    public function separatePdfs(Request $request)
    {
        $validated = $request->validate([
            'sale_ids' => 'required|array|min:1',
            'sale_ids.*' => 'integer|exists:sales,id',
            'columns' => 'nullable|array',
            'columns.*' => 'string',
            'paper_size' => PdfPaperSize::validationRule(),
        ]);

        if (!class_exists(ZipArchive::class)) {
            return back()->with('error', 'ZIP extension is not enabled on this server.');
        }

        $sales = Sale::with([
            'customer',
            'currency',
            'saleItems.account',
            'saleItems.taxRate',
            'createdBy',
            'attachments',
            'creditNotes',
        ])
            ->whereIn('id', $validated['sale_ids'])
            ->orderByDesc('date')
            ->get();

        $company = auth('web')->user()?->company ?? Company::find(loggedCompanyId()['company_id']) ?? Company::first();
        $invoiceTemplate = InvoiceTemplate::where('company_id', loggedCompanyId()['company_id'])
            ->where('is_default', true)
            ->first() ?? new InvoiceTemplate();
        $title = 'Sale Invoice';
        $selectedColumns = $this->selectedInvoiceColumnKeys($request);
        $paperSize = PdfPaperSize::normalize($request->input('paper_size'));
        $paperCssSize = PdfPaperSize::cssSize($paperSize);

        $tempDirectory = storage_path('app/temp/sale-downloads');
        if (!is_dir($tempDirectory)) {
            mkdir($tempDirectory, 0755, true);
        }

        $downloadName = 'selected_sale_pdfs_' . now()->format('Ymd_His') . '.zip';
        $zipPath = $tempDirectory . DIRECTORY_SEPARATOR . Str::random(10) . '_' . $downloadName;

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Unable to create sale PDF zip file.');
        }

        foreach ($sales as $sale) {
            $customer = $sale->customer;
            $items = $sale->saleItems;
            $date = $sale->date instanceof \Carbon\Carbon ? $sale->date->format('d-m-Y') : \Carbon\Carbon::parse($sale->date)->format('d-m-Y');
            $subtotal = $sale->sub_total;
            $discount = $sale->discount ?? 0;
            $tax = $sale->tax;
            $total = $sale->total_after_discount;
            $paid_amount = $sale->paid_amount;
            $remaining_amount = $sale->remaining_amount;
            $amount_in_words = $this->saleService->convertToWords($sale->total_after_discount);

            $pdf = Pdf::loadView('pdf.export-columns', [
                'sale' => $sale,
                'customer' => $customer,
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
                'title' => $title,
                'selectedColumns' => $selectedColumns,
                'paperCssSize' => $paperCssSize,
            ]);
            PdfPaperSize::apply($pdf, $paperSize);

            $fileName = 'invoice_' . $this->safeFilenamePart($sale->invoice_no ?? $sale->ref_no ?? $sale->id) . '_' . $sale->id . '.pdf';
            $zip->addFromString($fileName, $pdf->output());
        }

        $zip->close();

        return response()->download($zipPath, $downloadName)->deleteFileAfterSend(true);
    }

    public function csv(Request $request)
    {
        $validated = $request->validate([
            'sale_ids' => 'required|array|min:1',
            'sale_ids.*' => 'integer|exists:sales,id',
        ]);

        $sales = Sale::with(['customer', 'createdBy', 'updatedBy'])
            ->whereIn('id', $validated['sale_ids'])
            ->orderByDesc('date')
            ->get();
        $exportColumns = $this->selectedExportColumns($request);

        $filename = 'selected_sales_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
        ];

        return response()->stream(function () use ($sales, $exportColumns) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, array_column($exportColumns, 'label'));

            foreach ($sales as $index => $sale) {
                fputcsv($handle, $this->exportRow($sale, $index, $exportColumns));
            }

            fclose($handle);
        }, 200, $headers);
    }

    private function filteredSales(Request $request)
    {
        return Sale::with([
            'customer:id,name',
            'createdBy' => function ($q) {
                $q->select('id', 'first_name', 'last_name')->without('company');
            },
            'updatedBy' => function ($q) {
                $q->select('id', 'first_name', 'last_name')->without('company');
            }
        ])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->search);

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('invoice_no', 'like', "%{$search}%")
                        ->orWhere('ref_no', 'like', "%{$search}%")
                        ->orWhere('note', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn($customerQuery) => $customerQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('from_date'), fn($query) => $query->whereDate('date', '>=', $request->from_date))
            ->when($request->filled('to_date'), fn($query) => $query->whereDate('date', '<=', $request->to_date))
            ->when($request->filled('customer_id'), fn($query) => $query->where('customer_id', $request->customer_id))
            ->when($request->filled('approval_status'), fn($query) => $query->where('approval_status', $request->approval_status))
            ->when($request->filled('payment_status'), fn($query) => $query->where('payment_status', $request->payment_status));
    }

    private function statusLabel(Sale $sale): string
    {
        if ($sale->approval_status === 'approved') {
            if ($sale->payment_status === 'paid') {
                return 'Paid';
            }

            return $sale->payment_status === 'partial' ? 'Partially Paid' : 'Unpaid';
        }

        return ucfirst((string) $sale->approval_status);
    }

    private function safeFilenamePart($value): string
    {
        $filename = preg_replace('/[^A-Za-z0-9._-]+/', '_', (string) $value);
        $filename = trim($filename, '._-');

        return $filename !== '' ? $filename : 'invoice';
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

    private function exportRow(Sale $sale, int $index, array $exportColumns): array
    {
        return array_map(
            fn($column) => $column['value']($sale, $index),
            $exportColumns
        );
    }

    private function exportColumns(): array
    {
        return [
            'serial' => [
                'label' => 'S.No',
                'align' => 'left',
                'value' => fn(Sale $sale, int $index) => $index + 1,
            ],
            'date' => [
                'label' => 'Date',
                'align' => 'left',
                'value' => fn(Sale $sale, int $index) => optional($sale->date)->format('Y-m-d'),
            ],
            'ref_no' => [
                'label' => 'Reference No',
                'align' => 'left',
                'value' => fn(Sale $sale, int $index) => $sale->ref_no,
            ],
            'invoice_no' => [
                'label' => 'Invoice No',
                'align' => 'left',
                'value' => fn(Sale $sale, int $index) => $sale->invoice_no,
            ],
            'customer' => [
                'label' => 'Customer',
                'align' => 'left',
                'value' => fn(Sale $sale, int $index) => $sale->customer?->name,
            ],
            'note' => [
                'label' => 'Description',
                'align' => 'left',
                'value' => fn(Sale $sale, int $index) => $sale->note,
            ],
            'total' => [
                'label' => 'Total Amount',
                'align' => 'right',
                'is_total' => true,
                'value' => fn(Sale $sale, int $index) => $sale->total,
            ],
            'total_after_discount' => [
                'label' => 'Total After Discount / Tax',
                'align' => 'right',
                'is_total' => true,
                'value' => fn(Sale $sale, int $index) => $sale->total_after_discount,
            ],
            'paid_amount' => [
                'label' => 'Paid Amount',
                'align' => 'right',
                'is_total' => true,
                'value' => fn(Sale $sale, int $index) => $sale->paid_amount,
            ],
            'remaining_amount' => [
                'label' => 'Remaining Amount',
                'align' => 'right',
                'is_total' => true,
                'value' => fn(Sale $sale, int $index) => $sale->credit_note_status == 1 ? 'CRN Applied' : $sale->remaining_amount,
            ],
            'status' => [
                'label' => 'Status',
                'align' => 'left',
                'value' => fn(Sale $sale, int $index) => $this->statusLabel($sale),
            ],
            'created_by' => [
                'label' => 'Created By',
                'align' => 'left',
                'value' => fn(Sale $sale, int $index) => $sale->createdBy?->fullName,
            ],
            'updated_by' => [
                'label' => 'Updated By',
                'align' => 'left',
                'value' => fn(Sale $sale, int $index) => $sale->updatedBy?->fullName,
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
            'customer' => 'Customer',
            'invoice_no' => 'Invoice No',
            'date' => 'Date',
            'ref_no' => 'Reference No',
            'status' => 'Status',
            'note' => 'Description',
            'account' => 'Item / Account',
            'tax' => 'Tax',
            'sub_total' => 'Sub Total',
            'discount' => 'Discount',
            'total' => 'Total',
            'paid_amount' => 'Paid Amount',
            'remaining_amount' => 'Remaining Amount',
            'credit_notes' => 'Applied Credit Notes',
        ];
    }
}
