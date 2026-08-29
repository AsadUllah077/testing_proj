<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\Currency;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderAuditLog;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseOrderPayment;
use App\Models\TaxRate;
use App\Models\Vendor;
use App\Services\PurchaseOrderAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = PurchaseOrder::with(['vendor', 'currency', 'accountingPeriod']);

        // Search functionality
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_no', 'like', "%{$search}%")
                    ->orWhere('ref_no', 'like', "%{$search}%")
                    ->orWhere('invoice_no', 'like', "%{$search}%")
                    ->orWhere('note', 'like', "%{$search}%")
                    ->orWhereHas('vendor', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Date filter
        if ($request->has('from_date') && $request->from_date != '') {
            $query->where('order_date', '>=', $request->from_date);
        }

        if ($request->has('to_date') && $request->to_date != '') {
            $query->where('order_date', '<=', $request->to_date);
        }

        // Vendor filter
        if ($request->has('vendor_id') && $request->vendor_id != '') {
            $query->where('vendor_id', $request->vendor_id);
        }

        // Order No filter
        if ($request->has('order_no') && $request->order_no != '') {
            $query->where('order_no', 'like', "%{$request->order_no}%");
        }

        // Ref No filter
        if ($request->has('ref_no') && $request->ref_no != '') {
            $query->where('ref_no', 'like', "%{$request->ref_no}%");
        }

        // Amount filter
        if ($request->has('amount') && $request->amount != '') {
            $query->where('total', '>=', $request->amount);
        }

        $perPage = $request->per_page ?? 20;
        $purchaseOrders = $query->orderBy('order_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        $refNos = PurchaseOrder::whereNotNull('ref_no')->distinct()->pluck('ref_no');

        // Calculate totals
        $totalAmount = $purchaseOrders->sum('total');
        $totalRemaining = $purchaseOrders->sum('remaining_amount');

        // Column visibility
        // $visibleColumns = \App\Services\ColumnService::getVisibleColumns('account_purchase_order_columns', [
        //     'order_date' => true,
        //     'order_no' => true,
        //     'departure_date' => true,
        //     'reference_no' => true,
        //     'vendor' => true,
        //     'description' => true,
        //     'total_amount' => true,
        //     'remaining_amount' => true,
        // ]);

        return view('pages.account.purchase-order.index', compact(
            'purchaseOrders',
            'request',
            'refNos',
            'totalAmount',
            'totalRemaining',
            'perPage'
        ));
    }

    public function list(Request $request)
    {
        $status = $request->status ?? 0; // Default status 0 (Pending)

        $query = PurchaseOrder::with(['vendor', 'currency', 'accountingPeriod'])
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

        $perPage = $request->per_page ?? 20;
        $purchaseOrders = $query->orderBy('order_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        // Calculate totals
        $totalAmount = $purchaseOrders->sum('total');
        $totalOrders = $purchaseOrders->total();

        return view('pages.account.purchase-order.list', compact(
            'purchaseOrders',
            'status',
            'totalAmount',
            'totalOrders',
            'perPage'
        ));
    }

    public function create()
    {
        $vendors = Vendor::take(20)->get();
        $currencies = Currency::all();
        $accounts = ChartOfAccount::forCompany(loggedCompanyId()['company_id'])->get();
        $taxes = TaxRate::all();

        return view('pages.account.purchase-order.create', compact('vendors', 'currencies', 'accounts', 'taxes'));
    }

    public function store(Request $request)
    {
        try {

            Log::info('Purchase Order Validation Started');

            $validator = Validator::make($request->all(), [
                'vendor_id' => 'required|exists:vendors,id',
                'order_date' => 'required|date',
                'departure_date' => 'required|date',
                'order_no' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('purchase_orders', 'order_no')->whereNull('deleted_at'),
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
            Log::info('Purchase Order Validation complete');

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

            // Generate unique number
            $latestPO = PurchaseOrder::latest('id')->first();
            $nextNo = $latestPO ? $latestPO->id + 1 : 1;
            $uniqueNo = 'PO-' . str_pad($nextNo, 6, '0', STR_PAD_LEFT);

            Log::info('Purchase Order Creation started');

            // Create purchase order
            $purchaseOrder = PurchaseOrder::create([
                'company_id' => Auth::user()->company_id,
                'accounting_period_id' => $period->id,
                'vendor_id' => $request->vendor_id,
                'account_id' => $request->account_id ?? null,
                'currency_id' => $request->currency_id,
                'unique_no' => $uniqueNo,
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
                'status' => 0,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            // Log the creation
            PurchaseOrderAuditService::logCreation($purchaseOrder, $request);
            Log::info('Purchase Order Creation complete');

            // Create purchase order items
            foreach ($request->rows as $row) {
                $itemTotal = ($row['quantity'] ?? 1) * ($row['price'] ?? 0);

                PurchaseOrderItem::create([
                    'company_id' => Auth::user()->company_id,
                    'accounting_period_id' => $period->id,
                    'purchase_order_id' => $purchaseOrder->id,
                    'unique_no' => $uniqueNo,
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
            Log::info('Purchase Order Item complete');

            DB::commit();
            Log::info('Purchase Order full process complete');

            return response()->json([
                'success' => true,
                'message' => 'Purchase Order created successfully!',
                'unique_no' => $uniqueNo,
                'purchase_order_id' => $purchaseOrder->id,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Purchase Order creation failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => 'Failed to create purchase order',
            ]);
        }
    }

    public function show($id)
    {
        $purchaseOrder = PurchaseOrder::with([
            'vendor',
            'currency',
            'purchaseOrderItems.account',
            'purchaseOrderItems.tax',
            'createdBy',
        ])->findOrFail($id);
        // Load payments separately if needed
        $payments = PurchaseOrderPayment::with(['user', 'account'])
            ->where('purchase_order_id', $purchaseOrder->id)
            ->get();

        return view('pages.account.purchase-order.show', compact('purchaseOrder', 'payments'));
    }

    public function edit($id)
    {
        $purchaseOrder = PurchaseOrder::with('purchaseOrderItems')->findOrFail($id);

        $vendors = Vendor::where('id', '!=', $purchaseOrder->vendor_id)
            ->orderBy('id')
            ->limit(1000)
            ->get();

        $purchaseOrderVendor = $purchaseOrder->vendor;

        $vendors = $vendors->prepend($purchaseOrderVendor)->unique('id')->values();
        $currencies = Currency::all();
        $accounts = ChartOfAccount::forCompany(loggedCompanyId()['company_id'])->get();
        $taxes = TaxRate::all();

        return view('pages.account.purchase-order.edit', compact('purchaseOrder', 'vendors', 'currencies', 'accounts', 'taxes'));
    }

    public function update(Request $request, $id)
    {
        try {

            $purchaseOrder = PurchaseOrder::findOrFail($id);
            $oldData = $purchaseOrder->toArray();
            Log::info('Purchase Order update started');

            $validator = Validator::make($request->all(), [
                'vendor_id' => 'required|exists:vendors,id',
                'order_date' => 'required|date',
                'departure_date' => 'required|date',
                'order_no' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('purchase_orders', 'order_no')->ignore($id)->whereNull('deleted_at'),
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
            Log::info('Purchase Order validation complete');

            // Validate accounting period
            $period = AccountingPeriod::findPeriodForDate($request->order_date, $purchaseOrder->company_id);
            if (! $period) {
                return response()->json([
                    'success' => false,
                    'message' => 'The selected date does not fall within any unlocked accounting period.',
                ]);
            }
            Log::info('Purchase Order Account Period find');

            DB::beginTransaction();

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

            // Update purchase order
            $purchaseOrder->update([
                'accounting_period_id' => $period->id,
                'vendor_id' => $request->vendor_id,
                'account_id' => $request->account_id ?? null,
                'currency_id' => $request->currency_id,
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
                'updated_by' => Auth::id(),
            ]);

            // Get new data and log the update
            $newData = $purchaseOrder->fresh()->toArray();
            PurchaseOrderAuditService::logUpdate($purchaseOrder, $oldData, $newData, $request);
            Log::info('Purchase Order updated');

            // Delete old purchase order items
            PurchaseOrderItem::where('purchase_order_id', $purchaseOrder->id)->delete();

            // Create new purchase order items
            foreach ($request->rows as $row) {
                $itemTotal = ($row['quantity'] ?? 1) * ($row['price'] ?? 0);

                PurchaseOrderItem::create([
                    'company_id' => Auth::user()->company_id,
                    'accounting_period_id' => $period->id,
                    'purchase_order_id' => $purchaseOrder->id,
                    'unique_no' => $purchaseOrder->unique_no,
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
            Log::info('Purchase Order Item update complete');

            DB::commit();
            Log::info('Purchase Order update complete');

            return response()->json([
                'success' => true,
                'message' => 'Purchase Order updated successfully!',
                'unique_no' => $purchaseOrder->unique_no,
                'purchase_order_id' => $purchaseOrder->id,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Purchase Order update failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => 'Failed to update purchase order',
            ]);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $purchaseOrder = PurchaseOrder::findOrFail($id);
            PurchaseOrderAuditService::logDeletion($purchaseOrder, request());
            $uniqueNo = $purchaseOrder->unique_no;

            // Delete related records
            PurchaseOrderItem::where('purchase_order_id', $id)->delete();

            // Delete purchase order
            $purchaseOrder->delete();

            DB::commit();

            return redirect()->route('account.purchase-order.index')
                ->with('success', 'Purchase Order deleted successfully!');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Purchase Order deletion failed: ' . $e->getMessage());

            return redirect()->route('account.purchase-order.index')
                ->with('error', 'Failed to delete purchase order: ' . $e->getMessage());
        }
    }

    public function auditLogs($id)
    {
        $purchaseOrder = PurchaseOrder::with(['vendor'])->findOrFail($id);
        $logs = $purchaseOrder->auditLogs()->with('user')->orderBy('created_at', 'desc')->paginate(20);

        return view('pages.account.purchase-order.audit-logs', compact('purchaseOrder', 'logs'));
    }

    public function getLogDetails($logId)
    {
        try {
            // Find the specific audit log by its ID
            $log = PurchaseOrderAuditLog::with(['user', 'purchaseOrder'])->find($logId);

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

            $html = view('pages.account.purchase-order.partials.log-details', compact('log'))->render();

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
            ]);
        }
    }
}
