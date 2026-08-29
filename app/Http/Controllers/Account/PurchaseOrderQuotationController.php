<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseOrder\PurchasOrderPaymentRequest;
use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\Currency;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderPayment;
use App\Models\PurchaseOrderQuotation;
use App\Models\PurchaseOrderQuotationItem;
use App\Models\TaxRate;
use App\Models\Vendor;
use App\Services\PurchaseOrderAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PurchaseOrderQuotationController extends Controller
{
    public function good_receive(Request $request)
    {
        $status = $request->status ?? 2; // Default status 2 (Good Receive)

        $query = PurchaseOrderQuotation::with(['vendor', 'currency', 'accountingPeriod'])
            ->where('status', $status);

        // Search functionality
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('unique_no', 'like', "%{$search}%")
                    ->orWhere('ref_no', 'like', "%{$search}%")
                    ->orWhere('invoice_no', 'like', "%{$search}%")
                    ->orWhere('requested_by', 'like', "%{$search}%")
                    ->orWhere('note', 'like', "%{$search}%")
                    ->orWhereHas('vendor', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Date range filter
        if ($request->has('start_date') && $request->start_date != '') {
            $query->whereDate('order_date', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date != '') {
            $query->whereDate('order_date', '<=', $request->end_date);
        }

        $perPage = $request->per_page ?? 20;
        $purchaseOrders = $query->orderBy('order_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        // Calculate totals
        $totalAmount = $purchaseOrders->sum('total');
        $totalOrders = $purchaseOrders->total();

        return view('pages.account.purchase-order-quotation.goodrecieve', compact(
            'purchaseOrders',
            'status',
            'totalAmount',
            'totalOrders',
            'perPage'
        ));
    }

    public function edit($id)
    {
        $purchaseOrder = PurchaseOrder::with('purchaseOrderItems')->findOrFail($id);
        // dd($purchaseOrder);

        $vendors = Vendor::all();
        $currencies = Currency::all();
        $accounts = ChartOfAccount::forCompany(loggedCompanyId()['company_id'])->get();
        $taxes = TaxRate::all();

        return view('pages.account.purchase-order-quotation.edit', compact('purchaseOrder', 'vendors', 'currencies', 'accounts', 'taxes'));
    }

    public function store(Request $request)
    {
        Log::info('Purchase Order Quotation Validation Started');

        $validator = Validator::make($request->all(), [
            'vendor_id' => 'required|exists:vendors,id',
            'order_date' => 'required|date',
            'departure_date' => 'required|date',
            'order_no' => [
                'required',
                'string',
                'max:255',
                Rule::unique('purchase_order_quotations', 'order_no')->whereNull('deleted_at'),
            ],
            'ref_no' => 'nullable|string|max:255',
            'requested_by' => 'nullable|string|max:255',
            'note' => 'nullable|string',
            'delivery_address' => 'nullable|string',
            'delivery_instruction' => 'nullable|string',
            'rows' => 'required|array|min:1',
            'rows.*.item' => 'nullable|string|max:255',
            'rows.*.manual_item' => 'nullable|string|max:255',
            'rows.*.description' => 'nullable|string',
            'rows.*.hs_code' => 'nullable|string|max:50',
            'rows.*.quantity' => 'required|numeric|min:0',
            'rows.*.kg' => 'nullable|numeric|min:0',
            'rows.*.price' => 'required|numeric|min:0',
            'rows.*.account_id' => ['required', Rule::exists('chart_of_accounts', 'id')->where('company_id', loggedCompanyId()['company_id'])],
            'rows.*.tax_id' => 'nullable|exists:tax_rates,id',
            'rows.*.project' => 'nullable|string|max:255',
            'rows.*.total' => 'required|numeric|min:0',
            'currency_id' => 'required|exists:currencies,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->all(),
                'errors' => 'Validation failed',
            ]);
        }
        Log::info('Purchase Order Quotation Validation complete');

        // Validate accounting period
        $period = AccountingPeriod::findPeriodForDate($request->order_date, Auth::user()->company_id);
        if (! $period) {
            return response()->json([
                'success' => false,
                'message' => 'The selected date does not fall within any unlocked accounting period.',
            ]);
        }

        Log::info('Account Period Find');
        DB::beginTransaction();

        try {
            // Calculate totals
            $subTotal = collect($request->rows)->sum(function ($row) {
                return ($row['quantity'] ?? 1) * ($row['price'] ?? 0);
            });

            $totalDiscount = collect($request->rows)->sum(function ($row) {
                return $row['discount'] ?? 0;
            });

            $taxTotal = collect($request->rows)->sum(function ($row) {
                $rowTotal = ($row['quantity'] ?? 1) * ($row['price'] ?? 0);
                $taxRate = TaxRate::find($row['tax_id'])?->rate ?? 0;

                return $rowTotal * ($taxRate / 100);
            });

            $total = $subTotal - $totalDiscount + $taxTotal;

            Log::info('Purchase Order Creation started');

            // Create purchase order
            $purchaseOrder = PurchaseOrderQuotation::create([
                'company_id' => Auth::user()->company_id,
                'accounting_period_id' => $period->id,
                'vendor_id' => $request->vendor_id,
                'account_id' => $request->account_id ?? null,
                'currency_id' => $request->currency_id,
                'unique_no' => $request->unique_no,
                'invoice_no' => $request->invoice_no ?? null,
                'order_date' => $request->order_date,
                'departure_date' => $request->departure_date,
                'order_no' => $request->order_no,
                'ref_no' => $request->ref_no,
                'requested_by' => $request->requested_by,
                'note' => $request->note,
                'sub_total' => $subTotal,
                'total_discount' => $totalDiscount,
                'tax' => $taxTotal,
                'total' => $total,
                'remaining_amount' => $total,
                'delivery_address' => $request->delivery_address,
                'delivery_instruction' => $request->delivery_instruction,
                'status' => $request->status,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
            $purchaseOrder1 = PurchaseOrder::where('unique_no', $request->unique_no)->first();

            if ($purchaseOrder1) {
                $purchaseOrder1->update([
                    'status' => $request->status,
                ]);
            }
            // Log the creation
            // PurchaseOrderAuditService::logCreation($purchaseOrder, $request);
            Log::info('Purchase Order Quotation Creation complete');

            // Create purchase order items
            foreach ($request->rows as $row) {
                $itemTotal = ($row['quantity'] ?? 1) * ($row['price'] ?? 0);

                PurchaseOrderQuotationItem::create([
                    'company_id' => Auth::user()->company_id,
                    'accounting_period_id' => $period->id,
                    'purchase_order_quotation_id' => $purchaseOrder->id,
                    'unique_no' => $request->unique_no,
                    'item' => $row['item'],
                    'manual_item' => $row['manual_item'] ?? null,
                    'description' => $row['description'],
                    'hs_code' => $row['hs_code'],
                    'quantity' => $row['quantity'],
                    'kg' => $row['kg'],
                    'price' => $row['price'],
                    'account_id' => $row['account_id'],
                    'tax_id' => $row['tax_id'],
                    'project' => $row['project'],
                    'total' => $itemTotal,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
            }
            Log::info('Purchase Order Quotation Item complete');

            DB::commit();
            Log::info('Purchase Order Quotation full process complete');

            return response()->json([
                'success' => true,
                'message' => 'Purchase Order Quotation created successfully!',
                'unique_no' => $request->unique_no,
                'purchase_order_quotation_id' => $purchaseOrder->id,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Purchase Order Quotation creation failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => 'Failed to create purchase order Quotation',
            ]);
        }
    }

    public function show($id)
    {
        $purchaseOrder = PurchaseOrderQuotation::with([
            'vendor',
            'currency',
            'purchaseOrderQuotationItems.account',
            'purchaseOrderQuotationItems.tax',
            'createdBy',
        ])->where('unique_no', $id)->firstOrFail();

        return view('pages.account.purchase-order-quotation.show',
            compact('purchaseOrder'));
    }

    public function gd_edit($id)
    {
        // dd($id);
        $purchaseOrder = PurchaseOrderQuotation::with([
            'vendor',
            'currency',
            'purchaseOrderQuotationItems.account',
            'purchaseOrderQuotationItems.tax',
            'createdBy',
        ])->where('id', $id)->first();

        $vendors = Vendor::all();
        $currencies = Currency::all();
        $accounts = ChartOfAccount::forCompany(loggedCompanyId()['company_id'])->get();
        $taxes = TaxRate::all();

        return view('pages.account.purchase-order-quotation.gd_edit', compact('purchaseOrder', 'vendors', 'currencies', 'accounts', 'taxes'));
    }

    public function gd_update(Request $request, $id)
    {
        Log::info('Purchase Order Quotation Update Validation Started');

        $validator = Validator::make($request->all(), [
            'vendor_id' => 'required|exists:vendors,id',
            'order_date' => 'required|date',
            'departure_date' => 'required|date',
            'order_no' => [
                'required',
                'string',
                'max:255',
                Rule::unique('purchase_order_quotations', 'order_no')
                    ->ignore($id)
                    ->whereNull('deleted_at'),
            ],
            'ref_no' => 'nullable|string|max:255',
            'requested_by' => 'nullable|string|max:255',
            'note' => 'nullable|string',
            'delivery_address' => 'nullable|string',
            'delivery_instruction' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item' => 'nullable|string|max:255',
            'items.*.description' => 'nullable|string',
            'items.*.hs_code' => 'nullable|string|max:50',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.kg' => 'nullable|numeric|min:0',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.account_id' => ['required', Rule::exists('chart_of_accounts', 'id')->where('company_id', loggedCompanyId()['company_id'])],
            'items.*.tax_id' => 'nullable|exists:tax_rates,id',
            'items.*.project' => 'nullable|string|max:255',
            'currency_id' => 'required|exists:currencies,id',
            'payment_type' => ['nullable', Rule::exists('chart_of_accounts', 'id')->where('company_id', loggedCompanyId()['company_id'])],
            'paid_amount' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->all(),
                'errors' => 'Validation failed',
            ]);
        }

        Log::info('Purchase Order Quotation Validation complete');

        // Find existing purchase order
        $purchaseOrder = PurchaseOrderQuotation::findOrFail($id);
        $oldData = $purchaseOrder->toArray();

        // Validate accounting period
        $period = AccountingPeriod::findPeriodForDate($request->order_date, $purchaseOrder->company_id);
        if (! $period) {
            return response()->json([
                'success' => false,
                'message' => 'The selected date does not fall within any unlocked accounting period.',
            ]);
        }

        Log::info('Account Period Find');
        DB::beginTransaction();

        try {
            // Calculate totals
            $subTotal = collect($request->items)->sum(function ($item) {
                return ($item['quantity'] ?? 1) * ($item['price'] ?? 0);
            });

            $totalDiscount = 0; // Calculate discount if you have discount field
            // If you have discount in items, calculate like this:
            // $totalDiscount = collect($request->items)->sum('discount');

            $taxTotal = collect($request->items)->sum(function ($item) {
                $rowTotal = ($item['quantity'] ?? 1) * ($item['price'] ?? 0);
                $taxRate = TaxRate::find($item['tax_id'] ?? null)?->rate ?? 0;

                return $rowTotal * ($taxRate / 100);
            });

            $total = $subTotal - $totalDiscount + $taxTotal;
            $paidAmount = $request->paid_amount ?? 0;

            // Calculate remaining amount
            $remainingAmount = $total - $paidAmount;

            // Determine status based on payment
            $status = $this->calculateStatus($total, $paidAmount, $request->status ?? $purchaseOrder->status);

            // Update purchase order
            $purchaseOrder->update([
                'vendor_id' => $request->vendor_id,
                'account_id' => $request->payment_type ?? $purchaseOrder->account_id, // Update account based on payment type
                'currency_id' => $request->currency_id,
                'order_date' => $request->order_date,
                'departure_date' => $request->departure_date,
                'order_no' => $request->order_no,
                'ref_no' => $request->ref_no,
                'requested_by' => $request->requested_by,
                'note' => $request->note,
                'sub_total' => $subTotal,
                'total_discount' => $totalDiscount,
                'tax' => $taxTotal,
                'total' => $total,
                'paid_amount' => $paidAmount,
                'remaining_amount' => $remainingAmount,
                'delivery_address' => $request->delivery_address,
                'delivery_instruction' => $request->delivery_instruction,
                'status' => $status,
                'accounting_period_id' => $period->id,
                'updated_by' => Auth::id(),
            ]);

            // Update related purchase order status
            $purchaseOrder1 = PurchaseOrder::where('unique_no', $purchaseOrder->unique_no)->first();
            if ($purchaseOrder1) {
                $purchaseOrder1->update([
                    'status' => $status,
                ]);
            }

            // Delete existing items and create new ones
            $purchaseOrder->purchaseOrderQuotationItems()->delete();

            foreach ($request->items as $item) {
                $itemTotal = ($item['quantity'] ?? 1) * ($item['price'] ?? 0);

                PurchaseOrderQuotationItem::create([
                    'company_id' => Auth::user()->company_id,
                    'accounting_period_id' => $period->id,
                    'purchase_order_quotation_id' => $purchaseOrder->id,
                    'unique_no' => $purchaseOrder->unique_no,
                    'item' => $item['item'] ?? null,
                    'description' => $item['description'] ?? null,
                    'hs_code' => $item['hs_code'] ?? null,
                    'quantity' => $item['quantity'],
                    'kg' => $item['kg'] ?? 0,
                    'price' => $item['price'],
                    'account_id' => $item['account_id'],
                    'tax_id' => $item['tax_id'] ?? null,
                    'project' => $item['project'] ?? null,
                    'total' => $itemTotal,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
            }

            // Log the update
            // PurchaseOrderAuditService::logUpdate($purchaseOrder, $oldData, $request);

            Log::info('Purchase Order Quotation Update complete');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Purchase Order Quotation updated successfully!',
                'unique_no' => $purchaseOrder->unique_no,
                'purchase_order_quotation_id' => $purchaseOrder->id,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Purchase Order Quotation update failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => 'Failed to update purchase order Quotation',
            ]);
        }
    }

    /**
     * Calculate status based on payment and total amount
     */
    private function calculateStatus($total, $paidAmount, $existingStatus)
    {
        if ($total == 0) {
            return 3; // Cancelled if total is 0
        }

        if ($paidAmount == 0) {
            return 0; // Draft/Unpaid
        }

        if ($paidAmount >= $total) {
            return 3; // Fully Paid (changed from original status 3 for "Fully Paid")
        }

        if ($paidAmount > 0 && $paidAmount < $total) {
            return 4; // Partially Paid (changed from original status 4 for "Partial Paid")
        }

        return $existingStatus; // Return existing status if none of the above
    }

    public function report(Request $request)
    {
        $query = PurchaseOrderQuotationItem::with([
            'purchaseOrderQuotation.vendor',
            'account',
            'purchaseOrderQuotation', // Add this to access purchase order data
        ]);

        // Search by date range for order_date
        if ($request->filled('from_date')) {
            $query->whereHas('purchaseOrderQuotation', function ($q) use ($request) {
                $q->whereDate('order_date', '>=', $request->from_date);
            });
        }

        if ($request->filled('to_date')) {
            $query->whereHas('purchaseOrderQuotation', function ($q) use ($request) {
                $q->whereDate('order_date', '<=', $request->to_date);
            });
        }

        // Search by account
        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        // Search by vendor (corrected from customer_id to vendor_id)
        if ($request->filled('vendor_id')) {
            $query->whereHas('purchaseOrderQuotation', function ($q) use ($request) {
                $q->where('vendor_id', $request->vendor_id);
            });
        }

        // Search by requested by (created_by)
        if ($request->filled('requested_by')) {
            $query->whereHas('purchaseOrderQuotation', function ($q) use ($request) {
                $q->whereHas('createdBy', function ($subQ) use ($request) {
                    $subQ->where('name', 'like', '%'.$request->requested_by.'%');
                });
            });
        }

        // Search by product name
        if ($request->filled('product_name')) {
            $query->where('item', 'like', '%'.$request->product_name.'%');
        }

        // Get per page value (default 20)
        $perPage = $request->get('per_page', 20);

        // Calculate totals before pagination
        $totals = [
            'total_kg' => $query->sum('kg'),
            'total_quantity' => $query->sum('quantity'),
            'total_amount' => $query->sum('total'),
            'total_price' => $query->sum('price'),
        ];

        // Paginate results
        $items = $query->latest()->paginate($perPage)->appends($request->except('page'));


        return view('pages.account.purchase-order.reports', compact(
            'items',
            'totals',
        ));
    }

    public function recordPayment(PurchasOrderPaymentRequest $request)
    {
        DB::beginTransaction();

        try {
            $validatedData = $request->validated();
            // Get the bill
            $purchaseOrder = PurchaseOrder::findOrFail($validatedData['purchase_order_id']);

            // Check if amount exceeds remaining amount
            if ($validatedData['amount'] > $purchaseOrder->remaining_amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment amount cannot exceed remaining amount of '.number_format($purchaseOrder->remaining_amount, 2),
                    'errors' => [
                        'amount' => ['Payment amount exceeds remaining balance'],
                    ],
                ], 422);
            }

            $userId = auth('web')->id();
            $paymentAmount = $validatedData['amount'];

            // Update bill remaining amount
            $oldRemainingAmount = $purchaseOrder->remaining_amount;
            $purchaseOrder->remaining_amount -= $paymentAmount;
            $purchaseOrder->updated_by = $userId;
            $purchaseOrder->save();

            // Log the payment in audit
            PurchaseOrderPayment::create([
                'purchase_order_id' => $purchaseOrder->id,
                'user_id' => $userId,
                'account_id' => $request->account_id,
                'payment_date' => $request->payment_date,
                'note' => $request->description,
                'amount' => $paymentAmount,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment recorded successfully! Remaining balance: '.number_format($purchaseOrder->remaining_amount, 2),
                'data' => [
                    'remaining_amount' => $purchaseOrder->remaining_amount,
                    'payment_amount' => $paymentAmount,
                ],
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Payment recording error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to record payment: '.$e->getMessage(),
            ], 500);
        }
    }

    public function updatePayment(Request $request, PurchaseOrderPayment $payment)
    {
        $validated = $request->validate([
            'account_id' => ['required', Rule::exists('chart_of_accounts', 'id')->where('company_id', loggedCompanyId()['company_id'])],
        ]);

        $payment->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Payment updated successfully!',
        ]);
    }
}
