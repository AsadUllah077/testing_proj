<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bill\BillCreateRequest;
use App\Http\Requests\Bill\BillImportRequest;
use App\Http\Requests\Bill\BillPaymentRequest;
use App\Http\Requests\Bill\BillUpdateRequest;
use App\Models\Bill;
use App\Models\BillAuditLog;
use App\Models\BillItem;
use App\Models\BillAttachment;
use App\Models\BillInvoiceVersion;
use App\Jobs\SendBillInvoiceEmail;
use App\Models\ChartOfAccount;
use App\Models\Currency;
use App\Models\TaxRate;
use App\Models\Transaction;
use App\Models\VendorPaymentRun;
use App\Models\VendorPaymentRunItem;
use App\Models\Vendor;
use App\Services\BillAuditService;
use App\Services\Account\InvoiceExtractionService;
use App\Services\BillService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Company;
use App\Support\PdfPaperSize;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Schema;

class BillController extends Controller
{
    public function __construct(
        private BillService $billService
    ) {}

    public function index(Request $request)
    {
        $query = Bill::with([
            'debitNotes',
            'vendor:id,name', 
            'creator'=> function($q) {
                $q->select('id', 'first_name', 'last_name')->without('company');
            },
            'updater' => function($q) {
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
                    ->orWhereHas('vendor', function ($q) use ($search) {
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

        // Vendor filter
        if ($request->has('vendor_id') && $request->vendor_id != '') {
            $query->where('vendor_id', $request->vendor_id);
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

        $bills = $query->sortable()
            ->paginate($request->per_page ?? 20);

        // Calculate totals (Page totals as per current implementation)
        $totalAmount = $bills->sum('total');
        $totalRemaining = $bills->sum('remaining_amount');
        // In your controller's index method
        $totalAfterDiscount = $bills->sum('total_after_discount');

        $data = [
            'bills' => $bills,
            'totalAmount' => $totalAmount,
            'totalRemaining' => $totalRemaining,
            'totalAfterDiscount' => $totalAfterDiscount,
        ];

        if ($request->ajax()) {
            return response()->json([
                'html' => view('pages.account.bill.partials.table', $data)->render(),
                'pagination' => $bills->appends(request()->query())->links()->toHtml(),
            ]);
        }

        return view('pages.account.bill.index', $data);
    }

    public function create()
    {
        $vendors = Vendor::take(20)->get();
        $currencies = Currency::all();
        $accounts = byOrderCoa();
        $taxes = TaxRate::where('company_id', auth()->user()->company_id)->whereIn('tax_type', ['default_tax', 'default_purchase_tax', 'purchase_tax'])->get();

        return view('pages.account.bill.create', compact('vendors', 'currencies', 'accounts', 'taxes'));
    }

    /**
     * Extract invoice data from an uploaded file using AI (Claude Vision).
     * Returns structured JSON for auto-filling the bill creation form.
     */
    public function extractInvoice(Request $request, InvoiceExtractionService $extractor)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $result = $extractor->extract($request->file('file'));

        return response()->json($result);
    }

    public function store(BillCreateRequest $request)
    {
        try {

            Log::info('Bill Validation Started');
            $validatedData = $request->validated();
            Log::info('Bill Validation complete');

            // Validate accounting period
            $period = $this->billService->validateAccountingPeriod($validatedData['posting_date'] ?? $validatedData['date'], loggedCompanyId()['company_id']);

            Log::info('Account Period Find');
            DB::beginTransaction();

            // Calculate totals including discount
            $totals = $this->billService->calculateTotals($validatedData['rows']);
            $withholding = (float) ($validatedData['withholding_tax'] ?? 0);
            if ($withholding > $totals['total_after_discount']) {
                throw ValidationException::withMessages(['withholding_tax' => 'Withholding tax cannot exceed the gross bill total.']);
            }
            $total_after_discount = round($totals['total_after_discount'] - $withholding, 2);
            $paidAmount = (float) ($validatedData['paid_amount'] ?? 0);
            // Validate paid amount
            if ($paidAmount > $total_after_discount) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Paid amount cannot be greater than the total amount.',
                ], 422);
            }

            $remainingAmount = $total_after_discount - $paidAmount;

            Log::info('Bill Creation started');

            // Create bill
            $bill = $this->billService->createBillRecord($validatedData, $period, $totals, $remainingAmount);
            $uniqueNo = $bill->unique_no;

            // Log the creation
            BillAuditService::logCreation($bill, $request);
            Log::info('Bill Creation complete');

            // Handle multiple file uploads
            $this->billService->handleAttachment($request, $period, $bill);

            Log::info('Bill Attachment complete');

            $this->billService->createBillItemsAndTransactions(
                $request,
                $bill,
                $period,
                $uniqueNo,
                $paidAmount,
                $remainingAmount
            );
            $this->billService->captureVersion($bill->fresh('billItems'), 'created');

            DB::commit();
            Log::info('Bill full process complete');
            return response()->json([
                'success' => true,
                'message' => ['Bill saved as draft successfully.'],
                'unique_no' => $uniqueNo,
                'bill_id' => $bill->id,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function show($id)
    {
        try {
            $bill = Bill::with([
                'vendor',
                'currency',
                'billItems.account',
                'billItems.tax',
                'creator',
                'attachments',
                'debitNotes',
                'paymentAllocations.vendorPaymentRun.confirmedBy',
                'deliveries',
                'versions',
            ])->findOrFail($id);

            return view('pages.account.bill.show', compact('bill'));
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            $bill = Bill::with('billItems', 'attachments', 'vendor')->findOrFail($id);
            if (! $bill->isEditable()) {
                return redirect()->route('account.bill-invoice.show', $bill->id)->with('error', 'Only unpaid draft or rejected bills can be edited.');
            }
            $vendors = Vendor::where('id', '!=', $bill->vendor_id)
                ->orderBy('id')
                ->limit(1000)
                ->get();

            $billVendor = $bill->vendor;

            $vendors = $vendors->prepend($billVendor)->unique('id')->values();
            $currencies = Currency::all();
            $accounts = byOrderCoa();
            $taxes = TaxRate::where('company_id', auth()->user()->company_id)
                ->whereIn('tax_type', ['default_tax', 'default_purchase_tax', 'purchase_tax'])
                ->get();

            return view('pages.account.bill.edit', compact('bill', 'vendors', 'currencies', 'accounts', 'taxes'));
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    public function update(BillUpdateRequest $request, $id)
    {
        try {
            $bill = Bill::findOrFail($id);
            if (! $bill->isEditable()) {
                throw ValidationException::withMessages(['bill' => 'Only unpaid draft or rejected bills can be edited.']);
            }
            $oldData = $bill->toArray();
            Log::info('Bill update started');
            $validatedData = $request->validated();
            Log::info('Bill validation complete');

            // Validate accounting period (New Date)
            $period = $this->billService->validateAccountingPeriod($validatedData['posting_date'] ?? $validatedData['date'], $bill->company_id);

            // Validate accounting period (Existing Date) - Prevent modifying locked records
            $this->billService->validateAccountingPeriod($bill->date instanceof \Carbon\Carbon ? $bill->date->format('Y-m-d') : $bill->date, $bill->company_id);
            Log::info('Bill Account Period find');
            DB::beginTransaction();
            // Calculate totals
            $totals = $this->billService->calculateTotals($validatedData['rows']);
            $withholding = (float) ($validatedData['withholding_tax'] ?? 0);
            if ($withholding > $totals['total_after_discount']) throw ValidationException::withMessages(['withholding_tax' => 'Withholding tax cannot exceed the gross bill total.']);
            $total_after_discount = round($totals['total_after_discount'] - $withholding, 2);
            $total = $total_after_discount;
            $subTotal = $totals['sub_total'];
            $taxTotal = $totals['tax_total'];
            $paidAmount = (float) ($validatedData['paid_amount'] ?? 0);
            if ($paidAmount > $total_after_discount) throw ValidationException::withMessages(['paid_amount' => 'Paid amount cannot exceed the payable total.']);
            $remainingAmount = $total_after_discount - $paidAmount;

            // Update bill
            $this->billService->updateBill(
                $period,
                $bill,
                $request,
                $subTotal,
                $taxTotal,
                $total,
                $total_after_discount,
                $paidAmount,
                $remainingAmount
            );

            // Get new data and log the update
            $newData = $bill->fresh()->toArray();
            BillAuditService::logUpdate($bill, $oldData, $newData, $request);
            Log::info('Bill updated');

            // Handle multiple file uploads
            $this->billService->updateHandleAttachment($request, $bill, $period);

            Log::info('Bill attachment update complete');

            // Delete old bill items and transactions

            $this->billService->deleteBillItemAndTransaction($bill);

            $this->billService->createBillItemsAndTransactions($request, $bill, $period, $bill->unique_no, $paidAmount, $remainingAmount);
            $this->billService->captureVersion($bill->fresh('billItems'), 'updated');

            DB::commit();
            Log::info('Bill update complete');
            return response()->json([
                'success' => true,

                'message' => ['Bill updated successfully!'],
                'unique_no' => $bill->unique_no,
                'bill_id' => $bill->id,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $bill = Bill::findOrFail($id);
            if (! $bill->isEditable()) {
                throw new \RuntimeException('Only unpaid draft or rejected bills can be deleted.');
            }

            // Validate accounting period before deletion
            $this->billService->validateAccountingPeriod($bill->date, $bill->company_id);

            BillAuditService::logDeletion($bill, request());
            $billId = $bill->id;

            // 1. Delete attachment files + DB rows
            foreach ($bill->attachments as $attachment) {
                // Delete from storage
                $disk = $attachment->storage_disk ?: 'public';
                if (Storage::disk($disk)->exists($attachment->attachment)) {
                    Storage::disk($disk)->delete($attachment->attachment);
                }
                // Delete DB record
                $attachment->delete();
            }

            // 2. Delete related records
            BillItem::where('bill_id', $id)->delete();
            Transaction::withoutGlobalScopes()->where('company_id', $bill->company_id)->where('reference_id', (string) $billId)->where('status', 0)->delete();

            // 3. Delete bill
            $bill->delete();

            DB::commit();

            return redirect()->route('account.bill-invoice.index')
                ->with('success', 'Bill deleted successfully!');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->route('account.bill-invoice.index')
                ->with('error', 'Failed to delete bill: ' . $e->getMessage());
        }
    }

    public function void(Request $request, $id)
    {
        $validated = $request->validate(['reason' => 'required|string|max:1000']);
        try {
            DB::transaction(function () use ($id, $validated) {
                $bill = Bill::whereKey($id)->lockForUpdate()->firstOrFail();
                $this->billService->validateAccountingPeriod(($bill->posting_date ?? $bill->date)->format('Y-m-d'), $bill->company_id);
                $this->billService->voidBill($bill, $validated['reason']);
            });
            return back()->with('success', 'Bill voided and its journal reversed successfully.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function resendEmail($id)
    {
        $bill = Bill::findOrFail($id);
        if ($bill->approval_status !== 'approved') return back()->with('error', 'Only approved bills can be emailed.');
        if (! $bill->vendor?->email) return back()->with('error', 'The vendor has no email address.');
        SendBillInvoiceEmail::dispatch($bill->id);
        return back()->with('success', 'Bill email queued for delivery.');
    }

    public function duplicate($id)
    {
        try {
            $copy = DB::transaction(function () use ($id) {
                $source = Bill::with('billItems')->findOrFail($id);
                $date = now()->toDateString();
                $period = $this->billService->validateAccountingPeriod($date, $source->company_id);
                $rows = $source->billItems->map(fn ($item) => [
                    'item' => $item->item,
                    'manual_item' => $item->manual_item,
                    'description' => $item->description,
                    'hs_code' => $item->hs_code,
                    'quantity' => (float) $item->quantity,
                    'kg' => (float) $item->kg,
                    'unit_of_measure' => $item->unit_of_measure,
                    'price' => (float) $item->price,
                    'discount_percentage' => (float) $item->discount,
                    'account_id' => $item->account_id,
                    'tax_id' => $item->tax_id,
                    'total' => (float) $item->total,
                ])->all();
                $data = [
                    'vendor_id' => $source->vendor_id,
                    'invoice_no' => 'COPY-' . $source->invoice_no . '-' . now()->format('YmdHis'),
                    'date' => $date,
                    'vendor_invoice_date' => $date,
                    'posting_date' => $date,
                    'due_date' => null,
                    'ref_no' => $source->ref_no,
                    'purchase_order_no' => $source->purchase_order_no,
                    'goods_receipt_no' => $source->goods_receipt_no,
                    'project' => $source->project,
                    'cost_centre' => $source->cost_centre,
                    'department' => $source->department,
                    'warehouse' => $source->warehouse,
                    'vendor_tax_invoice_reference' => $source->vendor_tax_invoice_reference,
                    'note' => $source->note,
                    'currency_id' => $source->currency_id,
                    'exchange_rate' => (float) ($source->exchange_rate ?: 1),
                    'withholding_tax' => (float) $source->withholding_tax,
                    'withholding_account_id' => $source->withholding_account_id,
                    'paid_amount' => 0,
                    'payment_type' => null,
                    'send_email' => false,
                    'rows' => $rows,
                ];
                $totals = $this->billService->calculateTotals($rows);
                $payable = round($totals['total_after_discount'] - (float) $source->withholding_tax, 2);
                $bill = $this->billService->createBillRecord($data, $period, $totals, $payable);
                $synthetic = new Request($data);
                $this->billService->createBillItemsAndTransactions($synthetic, $bill, $period, $bill->unique_no, 0, $payable);
                $this->billService->captureVersion($bill->fresh('billItems'), 'duplicated');
                return $bill;
            });
            return redirect()->route('account.bill-invoice.edit', $copy->id)->with('success', 'Draft duplicate created. Enter the vendor invoice number before submission.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function downloadAttachment($id)
    {
        $attachment = BillAttachment::whereKey($id)->where('company_id', loggedCompanyId()['company_id'])->firstOrFail();
        $disk = $attachment->storage_disk ?: 'public';
        abort_unless(Storage::disk($disk)->exists($attachment->attachment), 404);
        return Storage::disk($disk)->download($attachment->attachment, $attachment->original_name ?: basename($attachment->attachment));
    }

    public function downloadVersionPdf($id, $version)
    {
        $bill = Bill::findOrFail($id);
        $record = BillInvoiceVersion::where('bill_id', $bill->id)->where('version', $version)->firstOrFail();
        $company = Company::find($bill->company_id);
        return Pdf::loadView('pdf.bill-version', ['version' => $record, 'snapshot' => $record->snapshot, 'company' => $company])
            ->setPaper('a4')->download("bill-{$bill->invoice_no}-v{$version}.pdf");
    }

    public function auditLogs($id)
    {
        $bill = Bill::with(['vendor'])->findOrFail($id);
        $logs = $bill->auditLogs()->with('user')->orderBy('created_at', 'desc')->paginate(20);

        return view('pages.account.bill.audit-logs', compact('bill', 'logs'));
    }

    public function getLogDetails($logId)
    {
        try {
            // Find the specific audit log by its ID
            $companyId = loggedCompanyId()['company_id'];
            $log = BillAuditLog::with(['user', 'bill'])
                ->whereHas('bill', fn ($query) => $query->where('company_id', $companyId))
                ->find($logId);
            Log::info($log);
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

            $html = view('pages.account.bill.partials.log-details', compact('log'))->render();

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

    public function recordPayment(BillPaymentRequest $request)
    {
        DB::beginTransaction();

        try {
            $validatedData = $request->validated();

            // Get the bill
            $bill = Bill::whereKey($validatedData['bill_id'])->lockForUpdate()->firstOrFail();

            if ($bill->approval_status !== 'approved') {
                throw ValidationException::withMessages(['bill_id' => 'Only approved bills can receive payments.']);
            }

            // Check if amount exceeds remaining amount
            if ($validatedData['amount'] > $bill->remaining_amount) {
                throw ValidationException::withMessages(['amount' => 'Payment amount cannot exceed remaining amount of ' . number_format((float) $bill->remaining_amount, 2)]);
            }

            // Validate accounting period
            $period = $this->billService->validateAccountingPeriod($validatedData['payment_date'], $bill->company_id);

            $userId = Auth::id();
            $paymentAmount = $validatedData['amount'];

            // Update bill remaining amount
            $oldRemainingAmount = $bill->remaining_amount;
            $bill->remaining_amount -= $paymentAmount;
            $bill->paid_amount += $paymentAmount;
            if ($bill->remaining_amount <= 0) $bill->remaining_amount = 0;
            $bill->payment_status = $this->billService->paymentStatus((float) $bill->paid_amount, (float) $bill->remaining_amount);
            $bill->updated_by = $userId;
            $bill->save();

            // Create unique reference for this payment
            // $paymentReference = $this->billService->generatePaymentUniqueNo();

            /* if it does not work then the above line 484 uncomment and from  487 to 508 comment */
            $companyId = $bill->company_id;
            $paymentReference = VendorPaymentRun::nextReference($companyId);

            // Create Vendor Payment Run Record (History)
            $paymentRun = VendorPaymentRun::create([
                'company_id'    => $companyId,
                'account_id'    => $validatedData['account_id'],
                'run_reference' => $paymentReference,
                'payment_date'  => $validatedData['payment_date'],
                'status'        => 'processed', // Single payments are processed immediately
                'total_amount'  => $paymentAmount,
                'created_by'    => $userId,
                'confirmed_by'  => $userId,
                'confirmed_at'  => now(),
            ]);

            // Create Payment Run Item
            VendorPaymentRunItem::create([
                'vendor_payment_run_id' => $paymentRun->id,
                'bill_id'               => $bill->id,
                'vendor_id'             => $bill->vendor_id,
                'amount_to_pay'         => $paymentAmount,
                'bill_total'            => $bill->total,
                'bill_remaining'        => $oldRemainingAmount,
            ]);

            // Create credit transaction (Payment Account - Money Out)
            $ledgerAmount = round((float) $paymentAmount * (float) ($bill->exchange_rate ?: 1), 4);
            $postedAt = now();
            Transaction::create([
                'unique_no' => $paymentReference,
                'date' => $validatedData['payment_date'],
                'account_id' => $validatedData['account_id'], // Payment account (e.g., Cash/Bank)
                'credit' => $ledgerAmount,
                'debit' => 0,
                'description' => $validatedData['description'] ?? "Payment for Bill: {$bill->invoice_no}",
                'accounting_period_id' => $period->id,
                'vendor_id' => $bill->vendor_id,
                'reference_id' => $bill->id,
                'invoice_no' => $bill->invoice_no,
                'type' => 'BillPayment',
                'company_id' => $companyId,
                'payable_type' => 'vendor',
                'payable_id' => $bill->vendor_id,
                'created_by' => $userId,
                'updated_by' => $userId,
                'status' => 1,
                'posted_by' => $userId,
                'posted_at' => $postedAt,
            ]);

            // Create debit transaction for Accounts Payable (reducing the payable)
            Transaction::create([
                'unique_no' => $paymentReference,
                'date' => $validatedData['payment_date'],
                'account_id' => ChartOfAccount::getAccountsPayableId($companyId),
                'credit' => 0,
                'debit' => $ledgerAmount,
                'description' => $validatedData['description'] ?? "Payment for Bill: {$bill->invoice_no}",
                'accounting_period_id' => $period->id,
                'vendor_id' => $bill->vendor_id,
                'reference_id' => $bill->id,
                'invoice_no' => $bill->invoice_no,
                'type' => 'BillPayment',
                'company_id' => $companyId,
                'payable_type' => 'vendor',
                'payable_id' => $bill->vendor_id,
                'created_by' => $userId,
                'updated_by' => $userId,
                'status' => 1,
                'posted_by' => $userId,
                'posted_at' => $postedAt,
            ]);
            $this->billService->captureVersion($bill->fresh(), 'payment-recorded');

            // Log the payment in audit
            BillAuditLog::create([
                'bill_id' => $bill->id,
                'user_id' => $userId,
                'action' => 'payment_recorded',
                'old_data' => json_encode([
                    'remaining_amount' => $oldRemainingAmount,
                ]),
                'new_data' => json_encode([
                    'payment_amount' => $paymentAmount,
                    'remaining_amount' => $bill->remaining_amount,
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
                'message' => 'Payment recorded successfully! Remaining balance: ' . number_format((float)$bill->remaining_amount, 2),
                'data' => [
                    'remaining_amount' => $bill->remaining_amount,
                    'payment_amount' => $paymentAmount,
                ],
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Payment recording error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to record payment: ' . $e->getMessage(),
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
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $validatedData = $validator->validated();

            // Get the transaction
            $transaction = Transaction::where('company_id', $companyId)->findOrFail($transactionId);
            if ((int) $transaction->status === 1 || $transaction->posted_at) {
                throw ValidationException::withMessages(['transaction' => 'Posted journal entries are immutable. Reverse and repost the bill instead.']);
            }

            // Store old data for audit
            $oldAccountId = $transaction->account_id;
            $oldAccount = ChartOfAccount::forCompany($companyId)->find($oldAccountId);
            $newAccount = ChartOfAccount::forCompany($companyId)->find($validatedData['account_id']);

            if (! $newAccount) {
                DB::rollBack();
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
                    $sale = Bill::where('invoice_no', $invoiceNo)->first();
                    if ($sale) {
                        BillAuditLog::create([
                            'bill_id' => $sale->id,
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
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Transaction update error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update transaction: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function import(BillImportRequest $request)
    {
        try {
            $import = new \App\Imports\BillImport();
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
                'message' => 'Bills imported successfully!',
            ]);
        } catch (\Throwable $e) {
            Log::error('Bill import error: ' . $e->getMessage());

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
                return response()->json(['success' => true, 'message' => 'Empty chunk ignored']);
            }

            $import = new \App\Imports\BillImport();
            $result = $import->importChunk($rows);

            return response()->json($result);
        } catch (\Throwable $e) {
            Log::error('Bill import chunk error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function downloadTemplate()
    {
        return Excel::download(new \App\Exports\BillTemplateExport(), 'bill_import_template.csv');
    }

    public function export($format)
    {
        $bills = Bill::with(['vendor', 'currency', 'accountingPeriod', 'creator', 'updater'])->get();

        $companyId = loggedCompanyId()['company_id'];
        $invoiceTemplate = \App\Models\InvoiceTemplate::where('company_id', $companyId)
            ->where('is_default', true)
            ->first() ?? new \App\Models\InvoiceTemplate();

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('pdf.bill-pdf', compact('bills', 'invoiceTemplate'));
            return $pdf->download('bills_report_' . now()->format('Ymd_His') . '.pdf');
        }

        return Excel::download(new \App\Exports\BillExport(), 'bills_' . now()->format('Ymd_His') . '.' . $format);
    }

    public function exportPdf($id, Request $request)
    {
        try {
            $columns = $request->query('columns') ? explode(',', $request->query('columns')) : [];

            $bill = Bill::with([
                'vendor',
                'currency',
                'billItems.account',
                'billItems.tax',
                'creator',
                'attachments',
            ])->findOrFail($id);

            $company = auth('web')->user()?->company ?? Company::find(loggedCompanyId()['company_id']) ?? Company::first();
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

            $companyId = loggedCompanyId()['company_id'];
            $invoiceTemplate = \App\Models\InvoiceTemplate::where('company_id', $companyId)
                ->where('is_default', true)
                ->first() ?? new \App\Models\InvoiceTemplate();
            $paperSize = PdfPaperSize::normalize($request->query('paper_size'));
            $paperCssSize = PdfPaperSize::cssSize($paperSize);

            // If columns are selected, use a simple PDF view
            if (!empty($columns)) {
                $pdfData = [
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
                    'selectedColumns' => $columns,
                    'paperCssSize' => $paperCssSize,
                ];

                $pdf = Pdf::loadView('pdf.export-columns-bill', $pdfData);
            } else {
                $pdf = Pdf::loadView('pdf.bill', compact(
                    'bill',
                    'vendor',
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
                    'paperCssSize'
                ));
            }

            PdfPaperSize::apply($pdf, $paperSize);

            return $pdf->download('bill_' . $this->safeExportFilenamePart($bill->invoice_no) . '.pdf');
        } catch (\Throwable $th) {
            Log::error('Bill PDF export error: ' . $th->getMessage());
            return back()->with('error', $th->getMessage());
        }
    }

    /**
     * Export single bill to Excel
     */
    public function exportExcel($id, Request $request)
    {
        try {
            $columns = $request->query('columns') ? explode(',', $request->query('columns')) : [];

            $bill = Bill::with([
                'vendor',
                'currency',
                'billItems.account',
                'billItems.tax',
                'creator',
                'attachments',
            ])->findOrFail($id);

            return Excel::download(new \App\Exports\BillSingleExport($bill, $columns), 'bill_invoice_' . $this->safeExportFilenamePart($bill->invoice_no) . '.xlsx');
        } catch (\Throwable $th) {
            Log::error('Bill Excel export error: ' . $th->getMessage());
            return back()->with('error', $th->getMessage());
        }
    }

    /**
     * Get available columns for export
     */
    public function getExportColumns(Request $request)
    {
        // Get actual columns from database
        $dbColumns = Schema::getColumnListing('bills');
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
        $formattedColumns[] = ['key' => 'vendor', 'label' => 'Vendor', 'checked' => true];
        $formattedColumns[] = ['key' => 'account', 'label' => 'Account', 'checked' => false];
        $formattedColumns[] = ['key' => 'debit_notes', 'label' => 'Applied Debit Notes', 'checked' => true];
        $formattedColumns[] = ['key' => 'created_by_user', 'label' => 'Created By', 'checked' => false];

        return response()->json($formattedColumns);
    }

    private function safeExportFilenamePart(?string $value): string
    {
        $filename = trim((string) $value);
        $filename = preg_replace('/[\/\\\\]+/', '-', $filename);
        $filename = preg_replace('/[\x00-\x1F\x7F]+/', '', $filename);

        return $filename !== '' ? $filename : 'export';
    }
}
