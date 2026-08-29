<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Models\ProcessedDocument;
use App\Models\ChartOfAccount;
use App\Services\AI\DocumentProcessorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class DocumentIngestionController extends Controller
{
    protected $processor;

    public function __construct(DocumentProcessorService $processor)
    {
        $this->processor = $processor;
    }

    public function index(Request $request)
    {
        $query = ProcessedDocument::where('company_id', loggedCompanyId()['company_id']);

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        if ($request->filled('document_type')) {
            $query->where('document_type', $request->document_type);
        }

        if ($request->filled('min_amount')) {
            $query->where('extracted_data->total', '>=', $request->min_amount);
        }

        if ($request->filled('max_amount')) {
            $query->where('extracted_data->total', '<=', $request->max_amount);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', 'draft');
        }

        $companyId = loggedCompanyId()['company_id'];
        $drafts = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 20));

        $accounts = ChartOfAccount::forCompany($companyId)->where('type', 'Account')->get();

        if ($request->ajax()) {
            return response()->json([
                'html' => view('pages.ai.partials.table', compact('drafts'))->render(),
                'pagination' => $drafts->links()->toHtml(),
                'drafts' => $drafts->keyBy('id')
            ]);
        }

        return view('pages.ai.document-ingestion', compact('drafts', 'accounts'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'files.*' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB max
            'document_type' => 'required|in:invoice,receipt,bank_statement',
        ]);

        $companyId = loggedCompanyId()['company_id'];
        $userId = Auth::id();
        $type = $request->document_type;
        $processedIds = [];

        foreach ($request->file('files') as $file) {
            try {
                // 1. Process via Gemini AI
                $result = $this->processor->process($file, $type);
                
                if (!$result['success']) {
                    Log::error("Document processing failed for file: {$file->getClientOriginalName()} - " . $result['message']);
                    continue; // Skip on AI failure, though in a real app we might create a 'failed' record
                }

                // 2. Store file securely
                $cleanName = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
                $timestamp = now()->format('Ymd_His');
                $newFileName = "{$cleanName}_{$timestamp}." . $file->getClientOriginalExtension();
                $path = $file->storeAs("document-ingestion/{$companyId}", $newFileName, 'public');

                // 3. Create Draft Record
                $doc = ProcessedDocument::create([
                    'company_id' => $companyId,
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'document_type' => $type,
                    'extracted_data' => $result['data'],
                    'status' => 'draft',
                    'created_by' => $userId,
                ]);

                $processedIds[] = $doc->id;

            } catch (\Exception $e) {
                Log::error("Error processing document batch: " . $e->getMessage());
            }
        }

        if (empty($processedIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process any documents.'
            ], 422);
        }

        // Return the newly created documents to the frontend
        $documents = ProcessedDocument::whereIn('id', $processedIds)->get();

        return response()->json([
            'success' => true,
            'message' => count($processedIds) . ' documents processed successfully.',
            'documents' => $documents
        ]);
    }

    public function delete($id)
    {
        $doc = ProcessedDocument::where('company_id', loggedCompanyId()['company_id'])
            ->findOrFail($id);

        if (Storage::disk('public')->exists($doc->file_path)) {
            Storage::disk('public')->delete($doc->file_path);
        }

        $doc->delete();

        return response()->json([
            'success' => true,
            'message' => 'Document draft removed.'
        ]);
    }
    
    public function confirm(Request $request, $id)
    {
        $doc = ProcessedDocument::where('company_id', loggedCompanyId()['company_id'])
            ->findOrFail($id);

        if ($doc->status !== 'draft') {
            return response()->json(['success' => false, 'message' => 'Document already processed.']);
        }

        try {
            // For now, only handle Bills (Supplier Invoices) and Sales (Customer Receipts)
            if ($doc->document_type === 'invoice') {
                return $this->createBillFromDraft($request, $doc);
            } elseif ($doc->document_type === 'receipt') {
                return $this->createSaleFromDraft($request, $doc);
            } elseif ($doc->document_type === 'bank_statement') {
                return response()->json(['success' => false, 'message' => 'Bank statement import from draft not yet fully implemented.']);
            }

            return response()->json(['success' => false, 'message' => 'Processing for this document type is not yet implemented in Phase 4.'], 400);

        } catch (\Exception $e) {
            Log::error("Draft confirmation error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    private function createBillFromDraft(Request $request, ProcessedDocument $doc)
    {
        $companyId = $doc->company_id;

        $request->validate([
            'vendor_id' => [
                'required',
                Rule::exists('vendors', 'id')->where('company_id', $companyId),
            ],
            'invoice_no' => 'required|string',
            'date' => 'required|date',
            'rows' => 'required|array|min:1',
            'rows.*.description' => 'nullable|string',
            'rows.*.amount' => 'required|numeric|min:0',
            'rows.*.account_id' => [
                'required',
                Rule::exists('chart_of_accounts', 'id')->where('company_id', $companyId),
            ],
        ]);

        $billService = app(\App\Services\BillService::class);
        
        $period = $billService->validateAccountingPeriod($request->date);
        
        // Map UI rows for BillService calculations
        $formattedRows = collect($request->rows)->map(function ($row) {
            return [
                'item' => $row['description'],
                'manual_item' => null,
                'description' => $row['description'],
                'hs_code' => null,
                'quantity' => 1,
                'kg' => 0,
                'price' => $row['amount'],
                'discount_percentage' => 0,
                'account_id' => $row['account_id'],
                'tax_id' => null,
            ];
        })->toArray();

        $totals = $billService->calculateTotals($formattedRows);
        $subTotal = $totals['sub_total'];
        $taxTotal = $totals['tax_total'];
        $grandTotal = $totals['total_after_discount'];
        
        $currencyId = \App\Models\Currency::first()->id ?? 1;

        $billData = [
            'vendor_id' => $request->vendor_id,
            'invoice_no' => $request->invoice_no,
            'date' => $request->date,
            'ref_no' => null,
            'currency_id' => $currencyId,
            'payment_type' => null,
            'paid_amount' => 0,
            'note' => 'Auto-generated from AI Document Ingestion',
        ];

        \Illuminate\Support\Facades\DB::beginTransaction();

        $bill = $billService->createBillRecord($billData, $period, $totals, $grandTotal);
        $uniqueNo = $bill->unique_no;

        // Inject formatted rows into request for the service
        $request->merge(['rows' => $formattedRows]);

        $billService->createBillItemsAndTransactions(
            $request,
            $bill,
            $period,
            $uniqueNo,
            0,
            $grandTotal
        );
        $billService->captureVersion($bill->fresh('billItems'), 'ai-ingested');

        $doc->update([
            'status' => 'processed',
            'reference_id' => $bill->id,
            'reference_type' => get_class($bill),
        ]);

        \Illuminate\Support\Facades\DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Bill created successfully.',
            'redirect' => route('account.bill-invoice.show', $bill->id)
        ]);
    }

    private function createSaleFromDraft(Request $request, ProcessedDocument $doc)
    {
        // UI passes 'vendor_id', we'll map it to 'customer_id' for sales.
        $companyId = $doc->company_id;

        $request->validate([
            'vendor_id' => [
                'required',
                Rule::exists('customers', 'id')->where('company_id', $companyId),
            ],
            'invoice_no' => 'required|string',
            'date' => 'required|date',
            'rows' => 'required|array|min:1',
            'rows.*.description' => 'nullable|string',
            'rows.*.amount' => 'required|numeric|min:0',
            'rows.*.account_id' => [
                'required',
                Rule::exists('chart_of_accounts', 'id')->where('company_id', $companyId),
            ],
        ], [
            'vendor_id.exists' => 'The selected customer is invalid.'
        ]);

        $saleService = app(\App\Services\SaleService::class);
        
        $period = $saleService->validateAccountingPeriod($request->date);
        
        // Prepare rows for calculation
        $rows = collect($request->rows)->map(function ($row) {
            return [
                'item' => $row['description'],
                'description' => $row['description'],
                'quantity' => 1,
                'kg' => 0,
                'price' => $row['amount'],
                'account_id' => $row['account_id'],
                'discount_percentage' => 0,
            ];
        })->toArray();

        $totals = $saleService->calculateTotals($rows);
        $grandTotal = $totals['total_after_discount'];
        
        $currencyId = \App\Models\Currency::first()->id ?? 1;

        $saleData = [
            'customer_id' => $request->vendor_id,
            'invoice_no' => $request->invoice_no,
            'date' => $request->date,
            'ref_no' => null,
            'currency_id' => $currencyId,
            'payment_type' => null,
            'paid_amount' => 0,
            'note' => 'Auto-generated from AI Document Ingestion',
            'rows' => $rows,
        ];

        \Illuminate\Support\Facades\DB::beginTransaction();

        // Create Sale Record
        $sale = $saleService->createSaleRecord($saleData, $period, $totals, $grandTotal);

        // Create Items & Transactions
        $saleService->createSaleItemsAndTransactions($saleData, $sale, $period, $totals, $grandTotal);

        $doc->update([
            'status' => 'processed',
            'reference_id' => $sale->id,
            'reference_type' => get_class($sale),
        ]);

        \Illuminate\Support\Facades\DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Sale created successfully.',
            'redirect' => route('account.sale-invoice.show', $sale->id)
        ]);
    }
}
