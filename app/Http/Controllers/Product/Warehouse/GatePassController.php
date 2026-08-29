<?php

// app/Http/Controllers/Product/Warehouse/GatePassController.php

namespace App\Http\Controllers\Product\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\GatePass;
use App\Models\GatePassItem;
use App\Models\Product;
use App\Models\WarehouseLocation;
use App\Services\Warehouse\GatePassService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class GatePassController extends Controller
{
    public function __construct(private readonly GatePassService $gatePasses) {}

    /**
     * Display a listing of gate passes.
     */
    public function index(Request $request)
    {
        try {
            $status = $request->get('status', 'All');
            $search = $request->get('search');
            $perPage = $request->get('per_page', 20);

            $query = GatePass::with(['preparedBy', 'items', 'warehouseLocation'])->latest();

            if ($status !== 'All') {
                $query->where('status', $status);
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('gate_pass_id', 'LIKE', "%{$search}%")
                        ->orWhere('customer_info', 'LIKE', "%{$search}%")
                        ->orWhere('invoice_no', 'LIKE', "%{$search}%")
                        ->orWhereHas('warehouseLocation', fn ($sq) => $sq->where('name', 'LIKE', "%{$search}%"))
                        ->orWhereHas('preparedBy', function ($sq) use ($search) {
                            $sq->where('username', 'LIKE', "%{$search}%")
                                ->orWhere('email', 'LIKE', "%{$search}%");
                        });
                });
            }

            $gatePasses = $query->paginate($perPage);

            $stats = [
                'total' => GatePass::count(),
                'pending_qc' => GatePass::where('status', 'QC_APPROVAL_PENDING')->count(),
                'pending_security' => GatePass::where('status', 'SECURITY_APPROVAL_PENDING')->count(),
                'approved' => GatePass::where('status', 'QC_AND_SECURITY_APPROVED')->count(),
            ];

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'html' => view('pages.products.warehouse.gatepass.partials.table', compact('gatePasses'))->render(),
                    'pagination' => $gatePasses->links()->toHtml(),
                    'stats' => $stats,
                ]);
            }

            return view('pages.products.warehouse.gatepass.index', compact('gatePasses', 'stats'));

        } catch (\Exception $e) {
            Log::error('GatePassController@index: '.$e->getMessage());

            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Error loading gate passes.'], 500);
            }

            return redirect()->back()->with('error', 'Error loading gate passes: '.$e->getMessage());
        }
    }

    /**
     * Show the form for creating a new gate pass.
     */
    public function create(Request $request)
    {
        try {
            $locations = WarehouseLocation::orderBy('name')->get();
            $rawProductIds = $request->query('product_ids', []);

            if (! is_array($rawProductIds)) {
                $rawProductIds = explode(',', $rawProductIds);
            }

            $requestedProductIds = collect($rawProductIds)
                ->flatMap(fn ($value) => explode(',', (string) $value))
                ->map(fn ($value) => trim($value))
                ->filter(fn ($value) => $value !== '' && ctype_digit($value))
                ->map(fn ($value) => (int) $value)
                ->filter(fn ($value) => $value > 0)
                ->unique()
                ->take(500)
                ->values();

            $preselectedProducts = collect();

            if ($requestedProductIds->isNotEmpty()) {
                $preselectedProducts = Product::with('category')
                    ->whereKey($requestedProductIds)
                    ->where(function ($outerQuery) {
                        $outerQuery->where('manual_status', 1)
                            ->orWhereDoesntHave('gatePassItems', function ($gatePassItemQuery) {
                                $gatePassItemQuery->whereHas('gatePass', function ($gatePassQuery) {
                                    $gatePassQuery->whereNotIn('status', [
                                        'QC_AND_SECURITY_APPROVED',
                                        'REJECTED',
                                    ]);
                                });
                            });
                    })
                    ->where('status', '!=', 'used')
                    ->get()
                    ->sortBy(fn ($product) => $requestedProductIds->search($product->id))
                    ->values()
                    ->map(fn ($product) => [
                        'id' => $product->id,
                        'unique_id' => $product->unique_id ?? '',
                        'product_name' => $product->title ?? $product->name ?? '',
                        'category_name' => $product->category?->name ?? 'Uncategorized',
                        'manual_status' => (int) $product->manual_status,
                        'count' => (int) ($product->count ?? 0),
                        'quantity' => 1,
                    ]);
            }

            $unavailableSelectedProductCount = max(
                0,
                $requestedProductIds->count() - $preselectedProducts->count()
            );

            return view('pages.products.warehouse.gatepass.create', compact(
                'locations',
                'preselectedProducts',
                'unavailableSelectedProductCount'
            ));

        } catch (\Exception $e) {
            Log::error('GatePassController@create: '.$e->getMessage());

            return redirect()->route('warehouse.gate-pass.index')
                ->with('error', 'Error loading create form: '.$e->getMessage());
        }
    }

    /**
     * Store a newly created gate pass.
     */
    public function store(Request $request)
    {
        // Decode the JSON string from the hidden input before validation
        $selectedProducts = [];
        if ($request->filled('selected_products')) {
            $decoded = json_decode($request->input('selected_products'), true);
            if (is_array($decoded)) {
                $selectedProducts = $decoded;
                // Replace the string with the decoded array so validation works
                $request->merge(['selected_products' => $decoded]);
            }
        }

        $validator = Validator::make($request->all(), [
            'gate_pass_id' => 'nullable|string|max:100|unique:gate_passes,gate_pass_id',
            'invoice_no' => 'nullable|string|max:255',
            'customer_info' => 'nullable|string|max:2000',
            'warehouse_location_id' => 'required|integer',
            'departure_date' => 'required|date',
            'warranty_days' => 'nullable|integer|min:0',
            'selected_products' => 'required|array|min:1',
            'selected_products.*.id' => 'required|integer|exists:products,id',
        ], [
            'selected_products.required' => 'Please select at least one product.',
            'selected_products.min' => 'Please select at least one product.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $gatePassId = $request->gate_pass_id ?: $this->generateGatePassId();

            // Derive status and timestamps from the toggle inputs
            $qcEnabled = $request->input('qc_approval_enabled') === '1';
            $securityEnabled = $request->input('security_approval_enabled') === '1';

            $status = 'QC_APPROVAL_PENDING';
            $qcApprovedAt = null;
            $securityApprovedAt = null;

            if ($qcEnabled) {
                $qcApprovedAt = now();
                $status = 'SECURITY_APPROVAL_PENDING';

                if ($securityEnabled) {
                    $securityApprovedAt = now();
                    $status = 'QC_AND_SECURITY_APPROVED';
                }
            }

            $gatePass = GatePass::create([
                'gate_pass_id' => $gatePassId,
                'warehouse_location_id' => $request->warehouse_location_id,
                'departure_date' => $request->departure_date,
                'invoice_no' => $request->invoice_no,
                'customer_info' => $request->customer_info,
                'prepared_by' => auth('web')->id(),
                'status' => $status,
                'qc_remarks' => $request->qc_remarks,
                'qc_approved_at' => $qcApprovedAt,
                'security_incharge_name' => $request->security_incharge_name,
                'security_approved_at' => $securityApprovedAt,
                'warranty_days' => $request->warranty_days,
                'company_id' => auth('web')->user()->company_id ?? null,
            ]);

            foreach ($selectedProducts as $productData) {
                $product = Product::findOrFail($productData['id']);
                $quantity = isset($productData['quantity']) ? (int) $productData['quantity'] : 1;
                $this->gatePasses->reserveProduct(
                    $gatePass,
                    $product,
                    $quantity,
                    $productData,
                    auth('web')->id()
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Gate pass created successfully.',
                'redirect' => route('warehouse.gate-pass.show', $gatePass->id),
                'data' => $gatePass,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('GatePassController@store: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create gate pass: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified gate pass.
     */
    public function show($id)
    {
        try {
            $gatePass = GatePass::with(['items.product.category', 'preparedBy', 'company', 'warehouseLocation'])
                ->findOrFail($id);

            return view('pages.products.warehouse.gatepass.show', compact('gatePass'));

        } catch (\Exception $e) {
            Log::error('GatePassController@show: '.$e->getMessage());

            return redirect()->route('warehouse.gate-pass.index')->with('error', 'Gate pass not found.');
        }
    }

    /**
     * Show the form for editing the specified gate pass.
     */
    public function edit($id)
    {
        try {
            $gatePass = GatePass::with('items.product', 'warehouseLocation')->findOrFail($id);
            $locations = WarehouseLocation::orderBy('name')->get();

            // if ($gatePass->isFullyApproved()) {
            //     return redirect()->route('warehouse.gate-pass.show', $id)
            //         ->with('error', 'Cannot edit an approved gate pass.');
            // }

            return view('pages.products.warehouse.gatepass.edit', compact('gatePass', 'locations'));

        } catch (\Exception $e) {
            Log::error('GatePassController@edit: '.$e->getMessage());

            return redirect()->route('warehouse.gate-pass.index')
                ->with('error', 'Error loading edit form: '.$e->getMessage());
        }
    }

    /**
     * Update the specified gate pass.
     */
    public function update(Request $request, $id)
    {
        $gatePass = GatePass::findOrFail($id);

        // if ($gatePass->isFullyApproved()) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Cannot update an approved gate pass.',
        //     ], 403);
        // }

        // Decode JSON selected_products
        if ($request->filled('selected_products') && is_string($request->input('selected_products'))) {
            $decoded = json_decode($request->input('selected_products'), true);
            if (is_array($decoded)) {
                $request->merge(['selected_products' => $decoded]);
            }
        }

        $validator = Validator::make($request->all(), [
            'invoice_no' => 'nullable|string|max:255',
            'customer_info' => 'nullable|string|max:2000',
            'warehouse_location_id' => 'required|string|max:255',
            'departure_date' => 'required|date',
            'warranty_days' => 'nullable|integer|min:0',
            'selected_products' => 'required|array|min:1',
            'selected_products.*.id' => 'required|integer|exists:products,id',
        ], [
            'selected_products.required' => 'Please select at least one product.',
            'selected_products.min' => 'Please select at least one product.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Derive new status and timestamps from the toggle inputs
            $qcEnabled = $request->input('qc_approval_enabled') === '1';
            $securityEnabled = $request->input('security_approval_enabled') === '1';

            $status = 'QC_APPROVAL_PENDING';
            $qcApprovedAt = null;
            $securityApprovedAt = null;

            if ($qcEnabled) {
                // Preserve the original timestamp if already approved, otherwise stamp now
                $qcApprovedAt = $gatePass->qc_approved_at ?? now();
                $status = 'SECURITY_APPROVAL_PENDING';

                if ($securityEnabled) {
                    $securityApprovedAt = $gatePass->security_approved_at ?? now();
                    $status = 'QC_AND_SECURITY_APPROVED';
                }
            }

            $gatePass->update([
                'warehouse_location_id' => $request->warehouse_location_id,
                'departure_date' => $request->departure_date,
                'invoice_no' => $request->invoice_no,
                'customer_info' => $request->customer_info,
                'qc_remarks' => $request->qc_remarks,
                'qc_approved_at' => $qcApprovedAt,
                'security_incharge_name' => $request->security_incharge_name,
                'security_approved_at' => $securityApprovedAt,
                'warranty_days' => $request->warranty_days,
                'status' => $status,
            ]);

            // Decode returned_product_ids (products marked as returned, not deleted)
            $returnedProductIds = [];
            if ($request->filled('returned_product_ids')) {
                $decoded = json_decode($request->input('returned_product_ids'), true);
                if (is_array($decoded)) {
                    $returnedProductIds = array_map('intval', $decoded);
                }
            }

            // Mark items as returned instead of deleting them
            if (! empty($returnedProductIds)) {
                foreach ($returnedProductIds as $productId) {
                    $item = GatePassItem::where('gate_pass_id', $gatePass->id)
                        ->where('product_id', $productId)
                        ->whereNull('returned_at')
                        ->first();

                    if ($item) {
                        $item->update(['returned_at' => now()]);

                        $product = Product::find($productId);
                        if ($product) {
                            $oldStatus = $product->status;
                            if ($product->manual_status == 1) {
                                $newCount = $product->count + $item->quantity;
                                $product->update(['count' => $newCount, 'sold_status' => 0, 'status' => 'available']);
                            } else {
                                $product->update(['sold_status' => 0, 'status' => 'available']);
                            }

                            $product->histories()->create([
                                'action' => 'returned_from_gate_pass',
                                'field' => 'gate_pass',
                                'old_value' => json_encode(['gate_pass_id' => $gatePass->gate_pass_id, 'status' => $oldStatus]),
                                'new_value' => json_encode(['gate_pass_id' => $gatePass->gate_pass_id, 'status' => 'available', 'returned' => true]),
                                'notes' => "Product returned from gate pass {$gatePass->gate_pass_id} and marked as available",
                                'changed_by' => auth('web')->id(),
                                'company_id' => $gatePass->company_id,
                            ]);
                        }
                    }
                }
            }

            // For non-approved gate passes: remove de-selected products entirely
            $existingActiveProductIds = $gatePass->items()->whereNull('returned_at')->pluck('product_id')->toArray();
            $newProductIds = collect($request->selected_products)->pluck('id')->toArray();

            // Only delete items that are neither in selected_products nor in returned_product_ids
            $toRemove = array_diff($existingActiveProductIds, $newProductIds, $returnedProductIds);
            if (! empty($toRemove)) {
                GatePassItem::where('gate_pass_id', $gatePass->id)
                    ->whereIn('product_id', $toRemove)
                    ->delete();

                // Reset status for removed products
                foreach ($toRemove as $productId) {
                    $product = Product::find($productId);
                    if ($product) {
                        $removedItem = GatePassItem::where('gate_pass_id', $gatePass->id)->where('product_id', $productId)->first();
                        $oldStatus = $product->status;
                        if ($product->manual_status == 1 && $removedItem) {
                            $newCount = $product->count + $removedItem->quantity;
                            $product->update(['count' => $newCount, 'sold_status' => 0, 'status' => 'available']);
                        } else {
                            $product->update(['sold_status' => 0, 'status' => 'available']);
                        }

                        $product->histories()->create([
                            'action' => 'removed_from_gate_pass',
                            'field' => 'gate_pass',
                            'old_value' => json_encode([
                                'gate_pass_id' => $gatePass->gate_pass_id,
                                'status' => $oldStatus,
                            ]),
                            'new_value' => json_encode([
                                'gate_pass_id' => null,
                                'status' => 'available',
                            ]),
                            'notes' => "Product removed from gate pass {$gatePass->gate_pass_id} and marked as available",
                            'changed_by' => auth('web')->id(),
                            'company_id' => $gatePass->company_id,
                        ]);
                    }
                }
            }

            // Add newly selected products - mark them as used
            foreach ($request->selected_products as $productData) {
                $exists = GatePassItem::where('gate_pass_id', $gatePass->id)
                    ->where('product_id', $productData['id'])
                    ->exists();

                if (! $exists) {
                    $product = Product::findOrFail($productData['id']);
                    $quantity = isset($productData['quantity']) ? (int) $productData['quantity'] : 1;
                    $oldStatus = $product->status;
                    $this->gatePasses->reserveProduct(
                        $gatePass,
                        $product,
                        $quantity,
                        $productData,
                        auth('web')->id(),
                        json_encode(['gate_pass_id' => null, 'status' => $oldStatus]),
                        true
                    );
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Gate pass updated successfully.',
                'redirect' => route('warehouse.gate-pass.show', $gatePass->id),
                'data' => $gatePass,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('GatePassController@update: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update gate pass: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * QC Approval
     */
    public function qcApproval(Request $request, $id)
    {
        $request->validate(['qc_remarks' => 'nullable|string|max:1000']);

        try {
            $gatePass = GatePass::findOrFail($id);
            $this->gatePasses->approveQc($gatePass, $request->qc_remarks, auth('web')->id());

            return response()->json([
                'success' => true,
                'message' => 'QC approved successfully.',
                'redirect' => route('warehouse.gate-pass.show', $gatePass->id),
            ]);

        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            Log::error('GatePassController@qcApproval: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to approve QC.'], 500);
        }
    }

    /**
     * Security Approval
     */
    public function securityApproval(Request $request, $id)
    {
        $request->validate(['security_incharge_name' => 'required|string|max:255']);

        try {
            $gatePass = GatePass::findOrFail($id);
            $this->gatePasses->approveSecurity(
                $gatePass,
                $request->security_incharge_name,
                auth('web')->id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Security approved successfully.',
                'redirect' => route('warehouse.gate-pass.show', $gatePass->id),
            ]);

        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            Log::error('GatePassController@securityApproval: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to approve security.'], 500);
        }
    }

    /**
     * Reject Gate Pass
     */
    public function reject(Request $request, $id)
    {
        $request->validate(['rejection_reason' => 'required|string|max:1000']);

        try {
            $gatePass = GatePass::findOrFail($id);
            $this->gatePasses->reject(
                $gatePass,
                $request->rejection_reason,
                auth('web')->id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Gate pass rejected successfully.',
                'redirect' => route('warehouse.gate-pass.show', $gatePass->id),
            ]);

        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            Log::error('GatePassController@reject: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to reject gate pass.'], 500);
        }
    }

    /**
     * Bulk delete gate passes
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:gate_passes,id',
        ]);

        try {
            DB::beginTransaction();

            $count = 0;
            GatePass::whereIn('id', $request->ids)->each(function ($gatePass) use (&$count) {
                if (! $gatePass->isFullyApproved()) {
                    // Restore product status for deleted gate pass
                    foreach ($gatePass->items as $item) {
                        if ($item->product) {
                            if ($item->product->manual_status == 1) {
                                $newCount = $item->product->count + ($item->quantity ?? 1);
                                $item->product->update([
                                    'count' => $newCount,
                                    'sold_status' => 0,
                                    'status' => 'available',
                                ]);
                            } else {
                                $item->product->update([
                                    'sold_status' => 0,
                                    'status' => 'available',
                                ]);
                            }
                        }
                    }
                    $gatePass->delete();
                    $count++;
                }
            });

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$count} gate pass(es).",
                'count' => $count,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('GatePassController@bulkDelete: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to delete gate passes.'], 500);
        }
    }

    /**
     * Delete a single gate pass
     */
    public function destroy($id)
    {
        try {
            $gatePass = GatePass::with('items')->findOrFail($id);

            if ($gatePass->isFullyApproved()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete an approved gate pass.',
                ], 400);
            }

            DB::beginTransaction();

            // Restore product status for deleted gate pass
            foreach ($gatePass->items as $item) {
                if ($item->product) {
                    $oldStatus = $item->product->status;
                    if ($item->product->manual_status == 1) {
                        $newCount = $item->product->count + ($item->quantity ?? 1);
                        $item->product->update([
                            'count' => $newCount,
                            'sold_status' => 0,
                            'status' => 'available',
                        ]);
                        $note = "Gate pass deleted, restored quantity: {$item->quantity}. New count: {$newCount}";
                        $newValue = json_encode(['gate_pass_id' => null, 'status' => 'available', 'count' => $newCount]);
                    } else {
                        $item->product->update([
                            'sold_status' => 0,
                            'status' => 'available',
                        ]);
                        $note = 'Gate pass deleted, product marked as available';
                        $newValue = json_encode(['gate_pass_id' => null, 'status' => 'available']);
                    }

                    $item->product->histories()->create([
                        'action' => 'gate_pass_deleted',
                        'field' => 'gate_pass',
                        'old_value' => json_encode([
                            'gate_pass_id' => $gatePass->gate_pass_id,
                            'status' => $oldStatus,
                        ]),
                        'new_value' => $newValue,
                        'notes' => $note,
                        'changed_by' => auth('web')->id(),
                        'company_id' => $gatePass->company_id,
                    ]);
                }
            }

            $gatePass->delete();

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Gate pass deleted successfully.']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('GatePassController@destroy: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to delete gate pass.'], 500);
        }
    }

    /**
     * Search products for gate pass
     * Only show products that are not already in another pending gate pass
     */
    public function searchProducts(Request $request)
    {
        $request->validate(['search' => 'required|string|min:2|max:100']);

        try {
            $search = $request->search;
            $excludeGatePassId = $request->exclude_gate_pass_id;

            $products = Product::with('category')
                ->where(function ($q) use ($search) {
                    $q->where('unique_id', 'LIKE', "%{$search}%")
                        ->orWhereRaw('LOWER(title) LIKE ?', ['%'.strtolower($search).'%'])
                        ->orWhereHas('category', function ($sq) use ($search) {
                            $sq->where('name', 'LIKE', "%{$search}%");
                        });
                })
                // Manual products: always include (stock-based); non-manual: exclude if already in a pending gate pass
                ->where(function ($outerQ) use ($excludeGatePassId) {
                    $outerQ->where('manual_status', 1)
                        ->orWhereDoesntHave('gatePassItems', function ($q) use ($excludeGatePassId) {
                            $q->whereHas('gatePass', function ($sq) use ($excludeGatePassId) {
                                $sq->whereNotIn('status', ['QC_AND_SECURITY_APPROVED', 'REJECTED']);
                                if ($excludeGatePassId) {
                                    $sq->where('id', '!=', $excludeGatePassId);
                                }
                            });
                        });
                })
                // Only show available products (manual products with count=0 are marked 'used')
                ->where('status', '!=', 'used')
                // ->limit(20)
                ->get();
            // dd($products);
            // dd(
            //     Product::whereRaw('LOWER(title) LIKE ?', ['%3500%'])->count()
            // );

            return response()->json([
                'success' => true,
                'products' => $products->map(fn ($product) => [
                    'id' => $product->id,
                    'unique_id' => $product->unique_id ?? '',
                    'product_name' => $product->title ?? $product->name ?? '',
                    'category_name' => $product->category?->name ?? 'Uncategorized',
                    'manual_status' => (int) $product->manual_status,
                    'count' => (int) ($product->count ?? 0),
                ]),
            ]);

        } catch (\Exception $e) {
            Log::error('GatePassController@searchProducts: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to search products.'], 500);
        }
    }

    /**
     * Export gate passes as PDF.
     */
    public function exportPDF(Request $request)
    {
        try {
            $query = GatePass::with(['preparedBy', 'items', 'warehouseLocation'])->latest();

            $status = $request->get('status', 'All');
            $search = $request->get('search');

            if ($status !== 'All') {
                $query->where('status', $status);
            }
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('gate_pass_id', 'LIKE', "%{$search}%")
                        ->orWhere('customer_info', 'LIKE', "%{$search}%")
                        ->orWhere('invoice_no', 'LIKE', "%{$search}%")
                        ->orWhereHas('warehouseLocation', fn ($sq) => $sq->where('name', 'LIKE', "%{$search}%"))
                        ->orWhereHas('preparedBy', fn ($sq) => $sq->where('username', 'LIKE', "%{$search}%"));
                });
            }

            $gatePasses = $query->get();
            $filters = ['status' => $status, 'search' => $search];

            $pdf = Pdf::loadView('pdf.gate-pass-export', compact('gatePasses', 'filters'));
            $pdf->setPaper('A4', 'landscape');

            return $pdf->download('gate-passes-'.now()->format('Y-m-d').'.pdf');

        } catch (\Exception $e) {
            Log::error('GatePassController@exportPDF: '.$e->getMessage());

            return redirect()->back()->with('error', 'PDF export failed: '.$e->getMessage());
        }
    }

    /**
     * Export gate passes as CSV.
     */
    public function exportExcel(Request $request)
    {
        try {
            $query = GatePass::with(['preparedBy', 'items', 'warehouseLocation'])->latest();

            $status = $request->get('status', 'All');
            $search = $request->get('search');

            if ($status !== 'All') {
                $query->where('status', $status);
            }
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('gate_pass_id', 'LIKE', "%{$search}%")
                        ->orWhere('customer_info', 'LIKE', "%{$search}%")
                        ->orWhere('invoice_no', 'LIKE', "%{$search}%")
                        ->orWhereHas('warehouseLocation', fn ($sq) => $sq->where('name', 'LIKE', "%{$search}%"))
                        ->orWhereHas('preparedBy', fn ($sq) => $sq->where('username', 'LIKE', "%{$search}%"));
                });
            }

            $gatePasses = $query->get();
            $filename = 'gate-passes-'.now()->format('Y-m-d').'.csv';

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $callback = function () use ($gatePasses) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['S.No', 'Gate Pass ID', 'Source Location', 'Items', 'Prepared By', 'Date', 'Status', 'QC Approved At', 'Security Approved At']);

                foreach ($gatePasses as $i => $gp) {
                    $statusText = match ($gp->status) {
                        'QC_AND_SECURITY_APPROVED' => 'Approved',
                        'SECURITY_APPROVAL_PENDING' => 'Security Pending',
                        'QC_APPROVAL_PENDING' => 'QC Pending',
                        'REJECTED' => 'Rejected',
                        default => $gp->status,
                    };
                    fputcsv($file, [
                        $i + 1,
                        $gp->gate_pass_id,
                        $gp->warehouseLocation->name ?? 'N/A',
                        $gp->items->count(),
                        $gp->preparedBy?->username ?? 'N/A',
                        $gp->created_at->format('d-m-Y'),
                        $statusText,
                        $gp->qc_approved_at ? \Carbon\Carbon::parse($gp->qc_approved_at)->format('d-m-Y') : '',
                        $gp->security_approved_at ? \Carbon\Carbon::parse($gp->security_approved_at)->format('d-m-Y') : '',
                    ]);
                }
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('GatePassController@exportExcel: '.$e->getMessage());

            return redirect()->back()->with('error', 'CSV export failed: '.$e->getMessage());
        }
    }

    /**
     * Print Gate Pass.
     */
    public function print(Request $request, $id)
    {
        try {
            $gatePass = GatePass::with(['items.product.category', 'preparedBy', 'company', 'warehouseLocation'])
                ->findOrFail($id);

            $copyLabels = [
                'customer' => 'Customer Copy',
                'warehouse_management' => 'Warehouse Management Copy',
                'warehouse_manager' => 'Warehouse Manager Copy',
                'hr_manager' => 'HR Manager Copy',
            ];
            $printCopy = $copyLabels[$request->query('copy', 'customer')] ?? $copyLabels['customer'];

            return view('pages.products.warehouse.gatepass.print', compact('gatePass', 'printCopy'));

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('GatePassController@print: '.$e->getMessage());

            return redirect()->route('warehouse.gate-pass.index')->with('error', 'Gate pass not found.');
        }
    }

    /**
     * Generate a unique gate pass ID  e.g. GP-202502-001
     */
    private function generateGatePassId(): string
    {
        $prefix = 'GP';
        $year = date('Y');
        $month = date('m');
        $base = "{$prefix}-{$year}{$month}-";

        $last = GatePass::where('gate_pass_id', 'LIKE', "{$base}%")
            ->orderBy('id', 'desc')
            ->value('gate_pass_id');

        $nextNum = $last ? (intval(substr($last, -3)) + 1) : 1;

        return $base.str_pad($nextNum, 3, '0', STR_PAD_LEFT);
    }
}
