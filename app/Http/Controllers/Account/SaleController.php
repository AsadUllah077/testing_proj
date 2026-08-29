<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sale\SaleCreateRequest;
use App\Http\Requests\Sale\SaleImportRequest;
use App\Http\Requests\Sale\SalePaymentRequest;
use App\Http\Requests\Sale\SaleUpdateRequest;
use App\Models\ChartOfAccount;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\SaleAuditLog;
use App\Models\SaleItem;
use App\Models\SaleAttachments;
use App\Models\SaleInvoiceVersion;
use App\Models\TaxRate;
use App\Models\Transaction;
use App\Models\User;
use App\Services\SaleAuditService;
use App\Services\SaleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Company;
use App\Models\CustomerPayment;
use App\Models\CustomerPaymentItem;
use App\Services\Account\InvoiceExtractionService;
use App\Support\PdfPaperSize;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Validation\Rule;


class SaleController extends Controller
{
    public $saleService;

    public function __construct()
    {
        $this->saleService = new SaleService;
    }

    public function index(Request $request)
    {
        $query = Sale::with([
            'customer:id,name',
            'createdBy' => function ($q) {
                $q->select('id', 'first_name', 'last_name')->without('company');
            },
            'updatedBy' => function ($q) {
                $q->select('id', 'first_name', 'last_name')->without('company');
            }
        ]);

        // Search functionality
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                    ->orWhere('ref_no', 'like', "%{$search}%")
                    ->orWhere('note', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Date filter
        if ($request->has('from_date') && $request->from_date != '') {
            $query->where('date', '>=', $request->from_date);
        }

        if ($request->has('to_date') && $request->to_date != '') {
            $query->where('date', '<=', $request->to_date);
        }

        // Customer filter
        if ($request->has('customer_id') && $request->customer_id != '') {
            $query->where('customer_id', $request->customer_id);
        }

        // Invoice No filter
        if ($request->has('invoice_no') && $request->invoice_no != '') {
            $query->where('invoice_no', 'like', "%{$request->invoice_no}%");
        }

        // Approval Status filter
        if ($request->has('approval_status') && $request->approval_status != '') {
            $query->where('approval_status', $request->approval_status);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        $sales = $query->sortable()
            ->paginate($request->per_page ?? 20);

        $data = [
            'sales'     => $sales,
        ];

        if ($request->ajax()) {
            return response()->json([
                'html' => view('pages.account.sale.partials.table', $data)->render(),
                'pagination' => $sales->appends(request()->query())->links()->toHtml(),
            ]);
        }

        return view('pages.account.sale.index', $data);
    }

    public function create()
    {
        $customers = Customer::take(20)->get();
        $currencies = Currency::all();
        $accounts = byOrderCoa();
        $taxes = TaxRate::where('company_id', auth()->user()->company_id)->whereIn('tax_type', ['default_tax', 'default_sale_tax', 'sales_tax'])->get();
        $salespeople = User::where('company_id', auth()->user()->company_id)->orderBy('first_name')->get(['id', 'first_name', 'last_name']);

        return view('pages.account.sale.create', compact('accounts', 'customers', 'currencies', 'taxes', 'salespeople'));
    }

    /**
     * Extract invoice data from an uploaded file using AI.
     * Returns structured JSON for auto-filling the sale invoice creation form.
     */
    public function extractInvoice(Request $request, InvoiceExtractionService $extractor)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $result = $extractor->extract($request->file('file'), 'customer');

        return response()->json($result);
    }

    public function store(SaleCreateRequest $request)
    {
        try {
            // Validate basic fields
            $validatedData = $request->validated();
            Log::info('Sale Validation complete');

            // Validate accounting period
            $period = $this->saleService->validateAccountingPeriod($request->date, loggedCompanyId()['company_id']);

            // Calculate totals
            $totals = $this->saleService->calculateTotals($validatedData['rows']);
            $paidAmount = (float) ($validatedData['paid_amount'] ?? 0);

            // Validate paid amount
            if ($paidAmount > $totals['total_after_discount']) {
                return response()->json([
                    'success' => false,
                    'message' => ['Paid amount cannot be greater than the total amount.'],
                ], 422);
            }

            $remainingAmount = $totals['total_after_discount'] - $paidAmount;

            DB::beginTransaction();

            // Create sale record
            $sale = $this->saleService->createSaleRecord($validatedData, $period, $totals, $remainingAmount);

            // Log audit
            SaleAuditService::logCreation($sale, $request);

            // Handle attachments
            $this->saleService->handleAttachments($request, $sale, $period);

            // Create sale items and transactions
            $this->saleService->createSaleItemsAndTransactions(
                $validatedData,
                $sale,
                $period,
                $totals,
                $remainingAmount
            );

            $this->saleService->captureVersion($sale->fresh(), 'created');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => [$request->boolean('send_email')
                    ? 'Draft invoice created. It will be emailed after approval.'
                    : 'Draft invoice created successfully.'],
                'unique_no' => $sale->unique_no,
                'sale_id' => $sale->id,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->all(),
            ], 422);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Sale creation error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => ['Sale invoice could not be created. Please review the accounting configuration and try again.'],
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $sale = Sale::with([
                'customer',
                'currency',
                'saleItems.account',
                'saleItems.taxRate',
                'createdBy',
                'attachments',
                'creditNotes',
                'paymentAllocations.customerPayment.confirmedBy',
                'deliveries',
                'versions',
                'salesperson',
            ])->findOrFail($id);

            return view('pages.account.sale.show', compact('sale'));
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            $sale = Sale::with('saleItems', 'attachments', 'customer')->findOrFail($id);

            if (! $sale->isEditable()) {
                return redirect()->route('account.sale-invoice.show', $sale->id)
                    ->with('error', 'Approved, paid, or voided invoices cannot be edited.');
            }

            $customers = Customer::where('id', '!=', $sale->customer_id)
                ->orderBy('id')
                ->limit(1000)
                ->get();

            $saleCustomer = $sale->customer;

            $customers = $customers->prepend($saleCustomer)->unique('id')->values();
            $currencies = Currency::get();
            $accounts = byOrderCoa();
            $taxes = TaxRate::where('company_id', auth()->user()->company_id)
                ->whereIn('tax_type', ['default_tax', 'default_sale_tax', 'sales_tax'])
                ->get();
            $salespeople = User::where('company_id', auth()->user()->company_id)->orderBy('first_name')->get(['id', 'first_name', 'last_name']);

            return view('pages.account.sale.edit', compact('sale', 'customers', 'currencies', 'accounts', 'taxes', 'salespeople'));
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    public function update(SaleUpdateRequest $request, $id)
    {
        try {

            // dd($request->all());

            $sale = Sale::findOrFail($id);
            $oldData = $sale->toArray();

            $validatedData = $request->validated();

            if (! $sale->isEditable()) {
                return response()->json([
                    'success' => false,
                    'message' => ['Approved, paid, or voided invoices cannot be edited. Use a credit note or void/reversal.'],
                ], 422);
            }

            // Validate accounting period (New Date)
            $period = $this->saleService->validateAccountingPeriod($validatedData['date'], $sale->company_id);

            // Validate accounting period (Existing Date) - Prevent modifying locked records
            $this->saleService->validateAccountingPeriod($sale->date instanceof \Carbon\Carbon ? $sale->date->format('Y-m-d') : $sale->date, $sale->company_id);

            $totals = $this->saleService->calculateTotals($validatedData['rows']);
            $subTotal = $totals['sub_total'];
            $taxTotal = $totals['tax_total'];
            $total = $totals['total_after_discount'];
            $paidAmount = (float) ($validatedData['paid_amount'] ?? 0);

            if ($paidAmount > $total) {
                return response()->json([
                    'success' => false,
                    'message' => ['Paid amount cannot be greater than the invoice total.'],
                ], 422);
            }

            DB::beginTransaction();

            $remainingAmount = $total - $paidAmount;

            $userId = Auth::id();

            $val = $this->saleService->updateSale(
                $sale,
                $validatedData,
                $period,
                $subTotal,
                $taxTotal,
                $total,
                $paidAmount,
                $remainingAmount
            );
            // dd($val);

            // Audit log
            $newData = $sale->fresh()->toArray();
            SaleAuditService::logUpdate($sale, $oldData, $newData, $request);

            $this->saleService->updateSaleAttachments($request, $sale, $period, $userId);

            $this->saleService->deleteSaleItemAndTransaction($sale);

            $this->saleService->updateSaleItemAndTransaction(
                $validatedData,
                $sale,
                $period,
                $userId,
                $paidAmount,
                $remainingAmount
            );

            $this->saleService->captureVersion($sale->fresh(), 'updated');

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => ['Draft invoice updated successfully.'],
                'unique_no' => $sale->unique_no,
                'sale_id' => $sale->id,
            ]);
        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => ['Sale invoice could not be updated. Please review the data and try again.'],
            ], 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $sale = Sale::findOrFail($id);

            if (! $sale->isEditable()) {
                throw new \LogicException('Only unpaid draft or rejected invoices can be deleted. Use void/reversal for an approved invoice.');
            }

            // Validate accounting period before deletion
            $this->saleService->validateAccountingPeriod($sale->date, $sale->company_id);

            SaleAuditService::logDeletion($sale, request());
            $saleId = $sale->id;

            // 1. Delete attachment files + DB rows
            foreach ($sale->attachments as $attachment) {

                // Delete from storage
                $disk = $attachment->storage_disk ?: 'public';
                if (Storage::disk($disk)->exists($attachment->attachment)) {
                    Storage::disk($disk)->delete($attachment->attachment);
                }

                // Delete DB record
                $attachment->delete();
            }

            // 2. Delete related records
            SaleItem::where('sale_id', $id)->delete();
            Transaction::withoutGlobalScopes()
                ->where('company_id', $sale->company_id)
                ->where('reference_id', $saleId)
                ->where('type', 'Sale')
                ->where('status', 0)
                ->whereNull('deleted_at')
                ->delete();

            // 3. Delete sale
            $sale->delete();

            DB::commit();

            return redirect()->route('account.sale-invoice.index')
                ->with('success', 'Sale invoice deleted successfully!');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->route('account.sale-invoice.index')
                ->with('error', 'Failed to delete sale invoice: ' . $e->getMessage());
        }
    }

    public function void(Request $request, $id)
    {
        $validated = $request->validate(['reason' => 'required|string|max:1000']);

        try {
            DB::transaction(function () use ($id, $validated, $request) {
                $sale = Sale::whereKey($id)->lockForUpdate()->firstOrFail();
                $this->saleService->validateAccountingPeriod(now()->toDateString(), $sale->company_id);
                $oldData = $sale->toArray();
                $this->saleService->voidSale($sale, $validated['reason']);
                SaleAuditService::logUpdate($sale, $oldData, $sale->fresh()->toArray(), $request);
            });

            return back()->with('success', 'Invoice voided and its journal was reversed successfully.');
        } catch (\Throwable $e) {
            Log::error('Sale void error: ' . $e->getMessage());
            return back()->with('error', $e instanceof \LogicException ? $e->getMessage() : 'The invoice could not be voided.');
        }
    }

    public function resendEmail($id)
    {
        $sale = Sale::findOrFail($id);
        if ($sale->approval_status !== 'approved') {
            return back()->with('error', 'Only approved invoices can be emailed.');
        }

        \App\Jobs\SendSaleInvoiceEmail::dispatch($sale->id)->afterCommit();

        return back()->with('success', 'Invoice email has been queued for delivery.');
    }

    public function duplicate($id)
    {
        try {
            $source = Sale::with('saleItems')->findOrFail($id);
            $period = $this->saleService->validateAccountingPeriod(now()->toDateString(), $source->company_id);

            $sale = DB::transaction(function () use ($source, $period) {
                $rows = $source->saleItems->map(fn ($item) => [
                    'item' => $item->item,
                    'manual_item' => $item->manual_item,
                    'description' => $item->description,
                    'hs_code' => $item->hs_code,
                    'quantity' => $item->quantity,
                    'kg' => $item->kg,
                    'unit_of_measure' => $item->unit_of_measure,
                    'price' => $item->price,
                    'discount_percentage' => $item->discount,
                    'account_id' => $item->account_id,
                    'tax_id' => $item->tax_id,
                    'total' => $item->total,
                ])->all();

                $data = [
                    'customer_id' => $source->customer_id,
                    'invoice_no' => substr('COPY-' . $source->invoice_no . '-' . now()->format('His'), 0, 255),
                    'date' => now()->toDateString(),
                    'due_date' => null,
                    'ref_no' => $source->ref_no,
                    'customer_po_no' => null,
                    'delivery_address' => $source->delivery_address,
                    'salesperson_id' => $source->salesperson_id,
                    'cost_centre' => $source->cost_centre,
                    'project' => $source->project,
                    'warehouse' => $source->warehouse,
                    'tax_invoice_reference' => null,
                    'electronic_invoice_qr_payload' => null,
                    'note' => $source->note,
                    'payment_type' => null,
                    'currency_id' => $source->currency_id,
                    'exchange_rate' => $source->exchange_rate ?: 1,
                    'paid_amount' => 0,
                    'send_email' => false,
                    'rows' => $rows,
                ];
                $totals = $this->saleService->calculateTotals($rows);
                $copy = $this->saleService->createSaleRecord($data, $period, $totals, $totals['total_after_discount']);
                $this->saleService->createSaleItemsAndTransactions($data, $copy, $period, $totals, $totals['total_after_discount']);
                $this->saleService->captureVersion($copy->fresh(), 'duplicated');

                return $copy;
            });

            return redirect()->route('account.sale-invoice.edit', $sale->id)
                ->with('success', 'Draft copy created. Review its invoice number and details before submission.');
        } catch (\Throwable $e) {
            Log::error('Sale duplication error: ' . $e->getMessage());
            return back()->with('error', 'The invoice could not be duplicated.');
        }
    }

    public function downloadAttachment($id)
    {
        $attachment = SaleAttachments::with('sale')->findOrFail($id);
        $disk = $attachment->storage_disk ?: 'public';

        abort_unless(Storage::disk($disk)->exists($attachment->attachment), 404);

        return Storage::disk($disk)->download(
            $attachment->attachment,
            $attachment->original_name ?: basename($attachment->attachment)
        );
    }

    public function downloadVersion($id, $version)
    {
        $sale = Sale::findOrFail($id);
        $snapshot = SaleInvoiceVersion::where('sale_id', $sale->id)
            ->where('version', $version)
            ->firstOrFail();
        $company = Company::findOrFail($sale->company_id);

        return Pdf::loadView('pdf.sale-version', [
            'snapshot' => $snapshot->snapshot,
            'company' => $company,
            'version' => $snapshot,
        ])->download('invoice_' . $sale->invoice_no . '_v' . $version . '.pdf');
    }

    public function auditLogs($id)
    {
        try {
            $sale = Sale::with(['customer'])->findOrFail($id);
            $logs = $sale->auditLogs()->with('user')->orderBy('created_at', 'desc')->paginate(20);

            return view('pages.account.sale.audit-logs', compact('sale', 'logs'));
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    public function getLogDetails($logId)
    {
        try {
            // Find the specific audit log by its ID
            $log = SaleAuditLog::with(['user', 'sale'])
                ->whereHas('sale')
                ->find($logId);

            if (! $log) {
                return response()->json([
                    'html' => '
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Log Not Found</h3>
                        <p class="mt-1 text-sm text-gray-500">The requested audit log could not be found.</p>
                    </div>
                ',
                ], 404);
            }

            $html = view('pages.account.sale.partials.log-details', compact('log'))->render();

            return response()->json(['html' => $html]);
        } catch (\Exception $e) {
            Log::error('Error fetching audit log details: ' . $e->getMessage());

            return response()->json([
                'html' => '
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Error Loading Details</h3>
                    <p class="mt-1 text-sm text-gray-500">An error occurred while loading the log details. Please try again.</p>
                    <button onclick="closeLogDetails()" class="mt-4 px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors text-sm">
                        Close
                    </button>
                </div>
            ',
            ], 500);
        }
    }

    public function recordPayment(SalePaymentRequest $request)
    {
        DB::beginTransaction();

        try {
            $validatedData = $request->validated();

            // Lock the invoice so concurrent requests cannot over-allocate its balance.
            $sale = Sale::whereKey($validatedData['sale_id'])->lockForUpdate()->firstOrFail();

            if ($sale->approval_status !== 'approved') {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Payments can only be recorded against an approved invoice.',
                ], 422);
            }

            // Check if amount exceeds remaining amount
            if ($validatedData['amount'] > (float)$sale->remaining_amount) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Payment amount cannot exceed remaining amount of ' . number_format((float)$sale->remaining_amount, 2),
                    'errors' => [
                        'amount' => ['Payment amount exceeds remaining balance'],
                    ],
                ], 422);
            }

            // Validate accounting period
            $period = $this->saleService->validateAccountingPeriod($validatedData['payment_date'], $sale->company_id);

            $userId = Auth::id();
            $paymentAmount = $validatedData['amount'];
            $ledgerPaymentAmount = round($paymentAmount * (float) ($sale->exchange_rate ?: 1), 4);

            // Update sale remaining amount
            $oldRemainingAmount = $sale->remaining_amount;
            $sale->remaining_amount -= $paymentAmount;
            $sale->paid_amount += $paymentAmount;
            if ($sale->remaining_amount <= 0) {
                $sale->remaining_amount = 0;
            }
            $sale->payment_status = $this->saleService->paymentStatus(
                (float) $sale->paid_amount,
                (float) $sale->remaining_amount
            );
            $sale->updated_by = $userId;
            $sale->save();

            $companyId = loggedCompanyId()['company_id'];
            $paymentSequence = $sale->paymentAllocations()->count() + 1;
            $paymentReference = 'CP-' . $sale->unique_no . '-' . str_pad((string) $paymentSequence, 3, '0', STR_PAD_LEFT);

            // Create Master Payment History Record
            $customerPayment = CustomerPayment::create([
                'company_id'   => $companyId,
                'customer_id'  => $sale->customer_id,
                'account_id'   => $validatedData['account_id'],
                'reference_no' => $paymentReference,
                'payment_date' => $validatedData['payment_date'],
                'note'         => $validatedData['description'] ?? "Payment for Invoice #{$sale->invoice_no}",
                'total_amount' => $paymentAmount,
                'status'       => 'confirmed',
                'created_by'   => $userId,
                'confirmed_by' => $userId,
                'confirmed_at' => now(),
            ]);

            // Create Detail Item
            CustomerPaymentItem::create([
                'customer_payment_id' => $customerPayment->id,
                'sale_id'            => $sale->id,
                'amount_to_apply'    => $paymentAmount,
                'invoice_total'      => $sale->total_after_discount,
                'invoice_remaining'  => $oldRemainingAmount,
            ]);

            // Create debit transaction (Payment Account - Money In)
            Transaction::create([
                'unique_no' => $paymentReference,
                'date' => $validatedData['payment_date'],
                'account_id' => $validatedData['account_id'], // Payment account (e.g., Cash/Bank)
                'debit' => $ledgerPaymentAmount,
                'credit' => 0,
                'description' => $validatedData['description'] ?? "Payment received for Invoice: {$sale->invoice_no}",
                'accounting_period_id' => $period->id,
                'customer_id' => $sale->customer_id,
                'reference_id' => $sale->id,
                'invoice_no' => $sale->invoice_no,
                'type' => 'Sale',
                'status' => 1,
                'posted_by' => $userId,
                'posted_at' => now(),
                'company_id' => $companyId,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            Transaction::create([
                'unique_no' => $paymentReference,
                'date' => $validatedData['payment_date'],
                'account_id' => ChartOfAccount::getAccountsReceivableId($companyId),
                'debit' => 0,
                'credit' => $ledgerPaymentAmount,
                'description' => $validatedData['description'] ?? "Payment received for Invoice: {$sale->invoice_no}",
                'accounting_period_id' => $period->id,
                'customer_id' => $sale->customer_id,
                'reference_id' => $sale->id,
                'invoice_no' => $sale->invoice_no,
                'type' => 'Sale',
                'status' => 1,
                'posted_by' => $userId,
                'posted_at' => now(),
                'company_id' => $companyId,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            // Log the payment in audit
            SaleAuditLog::create([
                'sale_id' => $sale->id,
                'user_id' => $userId,
                'action' => 'payment_recorded',
                'old_data' => json_encode([
                    'remaining_amount' => $oldRemainingAmount,
                ]),
                'new_data' => json_encode([
                    'payment_amount' => $paymentAmount,
                    'remaining_amount' => $sale->remaining_amount,
                    'payment_date' => $validatedData['payment_date'],
                    'payment_account' => $validatedData['account_id'],
                    'description' => $validatedData['description'] ?? null,
                ]),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment recorded successfully! Remaining balance: ' . number_format((float)$sale->remaining_amount, 2),
                'data' => [
                    'remaining_amount' => $sale->remaining_amount,
                    'payment_amount' => $paymentAmount,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Payment recording error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to record payment. Please try again or review the accounting configuration.',
            ], 500);
        }
    }

    public function updateTransaction(Request $request, $transactionId)
    {
        DB::beginTransaction();

        try {
            // Validate request
            $companyId = loggedCompanyId()['company_id'];
            $validator = Validator::make($request->all(), [
                'account_id' => [
                    'required',
                    Rule::exists('chart_of_accounts', 'id')->where('company_id', $companyId),
                ],
                'date' => 'required',
                'description' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $validatedData = $validator->validated();

            // Get the transaction
            $transaction = Transaction::where('company_id', $companyId)->findOrFail($transactionId);

            if ($transaction->type === 'Sale' && (int) $transaction->status === 1) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Posted sale journal lines are immutable. Void or reverse the invoice instead.',
                ], 422);
            }

            // Store old data for audit
            $oldAccountId = $transaction->account_id;
            $oldAccount = ChartOfAccount::forCompany($companyId)->find($oldAccountId);
            $newAccount = ChartOfAccount::forCompany($companyId)->find($validatedData['account_id']);

            if (! $newAccount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid account selected',
                ], 422);
            }

            $userId = Auth::id();
            $transaction->account_id = $validatedData['account_id'];
            $transaction->date = $validatedData['date'];
            $transaction->description = $validatedData['description'] ?? $transaction->description;
            $transaction->updated_by = $userId;
            $transaction->save();

            // Log the update in sale audit if it's related to a sale
            if (strpos($transaction->unique_no, 'INV-') !== false || strpos($transaction->unique_no, 'PMT-') !== false) {
                // Try to find related sale
                $invoiceNo = null;
                if (strpos($transaction->unique_no, 'PMT-') !== false) {
                    // Extract invoice number from payment reference
                    preg_match('/PMT-(INV-[^-]+)/', $transaction->unique_no, $matches);
                    $invoiceNo = $matches[1] ?? null;
                } else {
                    $invoiceNo = explode('-', $transaction->unique_no)[0] . '-' . explode('-', $transaction->unique_no)[1];
                }

                if ($invoiceNo) {
                    $sale = Sale::where('invoice_no', $invoiceNo)->first();
                    if ($sale) {
                        SaleAuditLog::create([
                            'sale_id' => $sale->id,
                            'user_id' => $userId,
                            'action' => 'transaction_updated',
                            'old_data' => json_encode([
                                'transaction_id' => $transaction->id,
                                'old_account_id' => $oldAccountId,
                                'old_account_name' => $oldAccount->account_name ?? null,
                            ]),
                            'new_data' => json_encode([
                                'transaction_id' => $transaction->id,
                                'new_account_id' => $validatedData['account_id'],
                                'new_account_name' => $newAccount->account_name,
                                'description' => $validatedData['description'] ?? null,
                            ]),
                            'ip_address' => $request->ip(),
                            'user_agent' => $request->userAgent(),
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaction updated successfully!',
                'data' => [
                    'transaction_id' => $transaction->id,
                    'account_name' => $newAccount->account_name,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Transaction update error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update transaction: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function import(SaleImportRequest $request)
    {
        try {
            $import = new \App\Imports\SaleImport();
            // Excel::import($import, $request->file('csv_file')); 
            // Use Fast Import Stream
            $import->fastImport($request->file('csv_file')->getPathname());

            $errors = $import->getErrors();

            if (!empty($errors)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Import completed with errors',
                    'errors' => $errors,
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Sales imported successfully!',
            ]);
        } catch (\Throwable $e) {
            Log::error('Sale import error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function importChunk(Request $request)
    {
        try {
            $rows = $request->input('rows');
            if (empty($rows)) {
                return response()->json([
                    'success' => true,
                    'processed' => 0,
                    'skipped' => 0,
                    'errors' => [],
                    'message' => 'Empty chunk ignored'
                ]);
            }

            $import = new \App\Imports\SaleImport();
            $result = $import->importChunk($rows);

            return response()->json($result);
        } catch (\Throwable $e) {
            Log::error('Sale import chunk error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function downloadTemplate()
    {
        return Excel::download(new \App\Exports\SaleTemplateExport(), 'sale_import_template.csv');
    }

    public function export($format)
    {
        $sales = Sale::with(['customer', 'currency', 'accountingPeriod', 'createdBy', 'updatedBy', 'saleItems.taxRate', 'saleItems.account'])->get();

        $companyId = loggedCompanyId()['company_id'];
        $invoiceTemplate = \App\Models\InvoiceTemplate::where('company_id', $companyId)
            ->where('is_default', true)
            ->first() ?? new \App\Models\InvoiceTemplate();

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('pdf.sale-pdf', compact('sales', 'invoiceTemplate'));
            return $pdf->download('sales_report_' . now()->format('Ymd_His') . '.pdf');
        }

        return Excel::download(new \App\Exports\SaleExport(), 'sales_' . now()->format('Ymd_His') . '.' . $format);
    }

    public function exportPdf($id, Request $request)
    {
        try {
            $columns = $request->query('columns') ? explode(',', $request->query('columns')) : [];

            $sale = Sale::with([
                'customer',
                'currency',
                'saleItems.account',
                'saleItems.taxRate',
                'createdBy',
                'attachments',
                'creditNotes',
            ])->findOrFail($id);

            $company = auth('web')->user()?->company ?? Company::find(loggedCompanyId()['company_id']) ?? Company::first();
            $customer = $sale->customer;
            $items = $sale->saleItems;
            $date = $sale->date instanceof \Carbon\Carbon ? $sale->date->format('d-m-Y') : \Carbon\Carbon::parse($sale->date)->format('d-m-Y');
            $subtotal = $sale->sub_total;
            $discount = $sale->discount_total ?? 0;
            $tax = $sale->tax;
            $total = $sale->grand_total ?: $sale->total_after_discount;
            $paid_amount = $sale->paid_amount;
            $remaining_amount = $sale->remaining_amount;
            $amount_in_words = $this->saleService->convertToWords($sale->total_after_discount);

            $companyId = loggedCompanyId()['company_id'];
            $invoiceTemplate = \App\Models\InvoiceTemplate::where('company_id', $companyId)
                ->where('is_default', true)
                ->first() ?? new \App\Models\InvoiceTemplate();

            $title = 'Sale Invoice';
            $paperSize = PdfPaperSize::normalize($request->query('paper_size'));
            $paperCssSize = PdfPaperSize::cssSize($paperSize);

            // If columns are selected, use a simple PDF view
            if (!empty($columns)) {
                $pdfData = [
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
                    'selectedColumns' => $columns,
                    'paperCssSize' => $paperCssSize,
                ];

                $pdf = Pdf::loadView('pdf.export-columns', $pdfData);
            } else {
                $pdf = Pdf::loadView('pdf.invoice', compact(
                    'sale',
                    'customer',
                    'company',
                    'items',
                    'date',
                    'subtotal',
                    'discount',
                    'tax',
                    'total',
                    'paid_amount',
                    'remaining_amount',
                    'amount_in_words',
                    'invoiceTemplate',
                    'title',
                    'paperCssSize'
                ));
            }

            PdfPaperSize::apply($pdf, $paperSize);

            return $pdf->download('invoice_' . $sale->invoice_no . '.pdf');
        } catch (\Throwable $th) {
            Log::error('Sale PDF export error: ' . $th->getMessage());
            return back()->with('error', $th->getMessage());
        }
    }

    /**
     * Export single sale to Excel
     */
    public function exportExcel($id, Request $request)
    {
        try {
            $columns = $request->query('columns') ? explode(',', $request->query('columns')) : [];

            $sale = Sale::with([
                'customer',
                'currency',
                'saleItems.account',
                'saleItems.taxRate',
                'createdBy',
                'attachments',
                'creditNotes',
            ])->findOrFail($id);

            return Excel::download(new \App\Exports\SaleSingleExport($sale, $columns), 'sale_invoice_' . $sale->invoice_no . '.xlsx');
        } catch (\Throwable $th) {
            Log::error('Sale Excel export error: ' . $th->getMessage());
            return back()->with('error', $th->getMessage());
        }
    }

    /**
     * Get available columns for export
     */
    public function getExportColumns(Request $request)
    {
        $type = $request->query('type', 'sale');

        // Get actual columns from database
        $dbColumns = Schema::getColumnListing('sales');
        $formattedColumns = [];

        foreach ($dbColumns as $col) {
            if (!in_array($col, ['id', 'company_id', 'accounting_period_id', 'currency_id', 'deleted_at', 'updated_at'])) {
                $label = ucwords(str_replace(['_', 'id'], [' ', ' ID'], $col));
                $formattedColumns[] = [
                    'key' => $col,
                    'label' => $label,
                    'checked' => in_array($col, ['invoice_no', 'date', 'ref_no', 'sub_total', 'tax', 'total', 'total_after_discount', 'paid_amount', 'remaining_amount', 'status'])
                ];
            }
        }

        // Add related columns
        $formattedColumns[] = ['key' => 'customer', 'label' => 'Customer', 'checked' => true];
        $formattedColumns[] = ['key' => 'account', 'label' => 'Account', 'checked' => false];
        $formattedColumns[] = ['key' => 'credit_notes', 'label' => 'Applied Credit Notes', 'checked' => true];
        $formattedColumns[] = ['key' => 'created_by_user', 'label' => 'Created By', 'checked' => false];

        return response()->json($formattedColumns);
    }
}
