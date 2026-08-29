<?php

namespace App\Http\Controllers\Product\Warehouse;

use App\Exports\ProductBulkUpdateTemplateExport;
use App\Exports\WarehouseProductExport;
use App\Http\Controllers\Controller;
use App\Imports\ProductBulkUpdateImport;
use App\Models\Barcode;
use App\Models\Disposition;
use App\Models\Employee;
use App\Models\GatePassItem;
use App\Models\GeneralTaskResult;
use App\Models\Part;
use App\Models\Product;
use App\Models\ProductAssignment;
use App\Models\ProductCategory;
use App\Models\ScrapCategory;
use App\Models\ScrapMaterial;
use App\Models\ScrapType;
use App\Models\WarehouseLocation;
use App\Models\WorkflowStage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{
    /**
     * Update barcode status to 'used' based on product unique_id
     *
     * @param  string  $uniqueId  The unique_id of the product/barcode
     * @param  string  $operation  The operation being performed
     * @return bool
     */
    private function updateBarcodeStatusToUsed($uniqueId, $operation = 'product_updated')
    {
        try {
            // Find the barcode with this unique_id
            $barcode = Barcode::where('unique_id', $uniqueId)->first();

            if ($barcode && $barcode->status !== 'used') {
                $oldStatus = $barcode->status;

                // Update barcode status to used
                $barcode->status = 'used';
                $barcode->save();

                Log::info("Barcode {$uniqueId} marked as used due to product operation: {$operation}");

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error("Failed to update barcode status for {$uniqueId}: ".$e->getMessage());

            return false;
        }
    }

    /**
     * Generate a QR code image (base64) for a manual product and return as JSON.
     * Only works for products with manual_status = 1.
     */
    public function generateQrCode(Product $product)
    {
        if (! $product->manual_status) {
            return response()->json(['success' => false, 'message' => 'This product is not a manual product.'], 403);
        }

        try {
            $dns2d = new \Milon\Barcode\DNS2D;
            $data = rtrim(config('app.url'), '/').'/'.$product->unique_id;
            $imgBase64 = $dns2d->getBarcodePNG($data, 'QRCODE', 8, 8);

            return response()->json([
                'success' => true,
                'image' => 'data:image/png;base64,'.$imgBase64,
                'unique_id' => $product->unique_id,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to generate QR code: '.$e->getMessage()], 500);
        }
    }

    /**
     * Render the full print page (with settings panel) for a manual product's QR code.
     * Mirrors how BarcodeController@printViewGet works for barcodes.
     */
    public function printQrView(Product $product)
    {
        if (! $product->manual_status) {
            abort(403, 'This product is not a manual product.');
        }

        return view('pages.products.warehouse.products.print-qr', compact('product'));
    }

    /**
     * Render a compact printable label containing the product's core details.
     */
    public function printSpecification(Product $product)
    {
        return view('pages.products.warehouse.products.print-specification', compact('product'));
    }

    /**
     * Store selected products so print settings can reload without losing the selection.
     */
    public function prepareBulkSpecificationPrint(Request $request)
    {
        $validated = $request->validate([
            'product_ids' => ['required', 'array', 'min:1', 'max:500'],
            'product_ids.*' => ['required', 'integer', 'distinct'],
        ]);

        $productIds = array_map('intval', $validated['product_ids']);
        $foundProductIds = Product::query()
            ->whereIn('id', $productIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (count($foundProductIds) !== count($productIds)) {
            return back()->with('error', 'One or more selected products were not found for your company.');
        }

        session(['product_specification_print_ids' => $productIds]);

        return redirect()->route('warehouse.products.bulk-print-specifications.show');
    }

    /**
     * Render one specification label page for every selected product.
     */
    public function printBulkSpecifications()
    {
        $productIds = collect(session('product_specification_print_ids', []))
            ->filter(fn ($id) => filter_var($id, FILTER_VALIDATE_INT) !== false)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($productIds->isEmpty()) {
            return redirect()->route('warehouse.products.index')
                ->with('error', 'Please select products before printing specification labels.');
        }

        $productsById = Product::query()
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $products = $productIds
            ->map(fn (int $id) => $productsById->get($id))
            ->filter()
            ->values();

        if ($products->isEmpty()) {
            return redirect()->route('warehouse.products.index')
                ->with('error', 'The selected products are no longer available.');
        }

        return view('pages.products.warehouse.products.print-specification', compact('products'));
    }

    public function catalogSearch(Request $request)
    {
        $q = $request->get('q');
        if (! $q) {
            return response()->json(['products' => []]);
        }

        try {
            // Proxy the request to the external API to bypass CORS
            $response = Http::get('https://qaam.pk/api/products', [
                'q' => $q,
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Catalog Proxy Error: '.$e->getMessage());

            return response()->json(['error' => 'Failed to fetch catalog data'], 500);
        }
    }

    /**
     * Resolve an exact product barcode into the metadata used by bulk selection.
     */
    public function scanSelection(Request $request)
    {
        $validated = $request->validate([
            'unique_id' => ['required', 'string', 'max:255'],
        ]);

        $uniqueId = trim($validated['unique_id']);
        $product = Product::query()
            ->select(['id', 'unique_id', 'title', 'status', 'assigned_to', 'manual_status'])
            ->where('unique_id', $uniqueId)
            ->first();

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => "No product was found for {$uniqueId}.",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'product' => [
                'id' => (string) $product->id,
                'status' => $product->status ?? '',
                'assigned' => $product->assigned_to !== null,
                'manual' => (bool) $product->manual_status,
                'uniqueId' => $product->unique_id,
                'title' => $product->title ?? '',
            ],
        ]);
    }

    public function index(Request $request)
    {
        try {
            // Build query with company trait - no need to add company_id condition manually
            $query = Product::query()
                ->select([
                    'id',
                    'unique_id',
                    'dpn_sn_number',
                    'title',
                    'category_id',
                    'warehouse_location_id',
                    'assigned_to',
                    'disposition_id',
                    'work_flow_stage_id',
                    'status',
                    'sold_status',
                    'scrap_status',
                    'manual_status',
                    'count',
                    'last_action',
                    'created_at',
                    'updated_at',
                ])
                ->with([
                    'warehouseLocation:id,name',
                    'category:id,name',
                    'assignedTo:id,full_name',
                    'disposition:id,name',
                    'workflowStage:id,name',
                ]);

            if ($request->input('status') === 'sold') {
                $query->withSum([
                    'gatePassItems as sold_quantity' => fn ($q) => $q->whereNull('returned_at')
                        ->whereHas('gatePass', fn ($gatePass) => $gatePass->where('status', '!=', 'REJECTED')),
                ], 'quantity');
            }

            // Search filter

            if ($request->filled('q')) {
                $search = $request->q;
                $query->where(function ($q) use ($search) {
                    $q->where('unique_id', 'LIKE', "%{$search}%")
                        ->orWhere('dpn_sn_number', 'LIKE', "%{$search}%")
                        ->orWhereRaw('LOWER(title) LIKE ?', ["%".strtolower($search)."%"])
                        ->orWhere('product_details', 'LIKE', "%{$search}%");
                });
            }

            // Category filter
            if ($request->filled('category')) {
                $query->where('category_id', $request->category);
            }

            // dd($request->all());
            // Location filter
            if ($request->filled('location')) {
                $query->where('warehouse_location_id', $request->location);
            }

            // Assigned To filter
            if ($request->filled('assigned_to')) {
                if ($request->assigned_to === 'Assigned') {
                    $query->whereNotNull('assigned_to');
                } elseif ($request->assigned_to === 'Unassigned') {
                    $query->whereNull('assigned_to');
                }
            }

            // Work Flow Stage filter
            if ($request->filled('work_flow_stage')) {
                $query->where('work_flow_stage_id', $request->work_flow_stage);
            }

            // Disposition filter
            if ($request->filled('disposition')) {
                $query->where('disposition_id', $request->disposition);
            }

            // Status filter
            if ($request->filled('status')) {
                if ($request->status == 'sold') {
                    $query->where(function ($q) {
                        $q->where('sold_status', 1)
                            ->orWhere(function ($manualSold) {
                                $manualSold->where('manual_status', 1)
                                    ->whereHas('gatePassItems', function ($item) {
                                        $item->whereNull('returned_at')
                                            ->whereHas('gatePass', fn ($gatePass) => $gatePass->where('status', '!=', 'REJECTED'));
                                    });
                            });
                    });
                } elseif ($request->status == 'scrap') {
                    $query->where('scrap_status', 1);
                } elseif ($request->status == 'active') {
                    $query->where(function ($q) {
                        $q->whereRaw("LOWER(status) = 'active'")
                            ->orWhereRaw("LOWER(status) = 'ready for sale'");
                    })->warehouseNotIssued();
                } elseif ($request->status == 'inactive') {
                    $query->where(function ($q) {
                        $q->whereRaw("LOWER(status) != 'active'")
                            ->whereRaw("LOWER(status) != 'ready for sale'")
                            ->whereRaw("LOWER(status) != 'issued'")
                            ->whereRaw("LOWER(status) != 'hold'");
                    })->warehouseNotIssued()
                        ->where('sold_status', 0)
                        ->where('scrap_status', 0);
                } elseif ($request->status == 'hold') {
                    $query->whereRaw("LOWER(status) = 'hold'")
                        ->where('sold_status', 0)
                        ->where('scrap_status', 0);
                } elseif ($request->status == 'issued') {
                    $query->warehouseIssued();
                }
            } else {
                $query->where('sold_status', 0)
                    ->warehouseNotIssued();
            }

            // Manual Status filter
            if ($request->filled('manual_status')) {
                $query->where('manual_status', $request->manual_status);
            }

            // Date Range filter based on createdAt
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Updated today filter (for "ready for sale today" dashboard link)
            if ($request->filled('updated_today') && $request->updated_today == '1') {
                $query->whereDate('updated_at', today());
            }

            // Disposition group filter (for dashboard testing result cells)
            if ($request->filled('disposition_group')) {
                $groups = [
                    'pass' => ['Pass', 'Approved', 'Completed'],
                    'hold' => ['Hold', 'Pending', 'Waiting'],
                    'fail' => ['Fail', 'Rejected', 'Failed'],
                ];
                if (isset($groups[$request->disposition_group])) {
                    $names = $groups[$request->disposition_group];
                    $query->whereHas('disposition', fn ($q) => $q->whereIn('name', $names));
                }
            }
            // else {

            //     // Default: show only available products
            //     $query->where('sold_status', 0)
            //         ->where('scrap_status', 0);
            // }

            // Per page
            $perPage = $request->input('per_page', 20);

            $sortCol = $request->input('sort') === 'unique_id' ? 'unique_id' : 'created_at';
            $sortDir = $request->input('direction') === 'asc' ? 'asc' : 'desc';
            $products = $query->orderBy($sortCol, $sortDir)->paginate($perPage);

            if ($request->ajax()) {
                return response()->json([
                    'html' => view('pages.products.warehouse.products.partials.table', compact('products'))->render(),
                    'pagination' => $products->links()->toHtml(),
                ]);
            }

            // Get data for dropdowns - trait handles company scoping automatically
            $categories = ProductCategory::select('id', 'name')->orderBy('name')->get();
            // $locations = Product::whereNotNull('location')->distinct()->orderBy('location')->pluck('location');
            $workflowStages = WorkflowStage::select('id', 'name')->orderBy('name')->get();
            $dispositions = Disposition::select('id', 'name')->orderBy('name')->get();
            $locations = WarehouseLocation::orderBy('name')->get();
            $parts = Part::select('id', 'name', 'quantity')->where('quantity', '>', 0)->get();

            // Load employee technicians using the employee display-name fields.
            $technicians = Employee::whereHas('department', function ($q) {
                $q->whereRaw('LOWER(department) LIKE ?', ['%technician%'])
                    ->orWhereRaw('LOWER(department) LIKE ?', ['%operations department%'])
                    ->orWhereRaw('LOWER(department) LIKE ?', ['%lab%'])
                    ->orWhereRaw('LOWER(department) LIKE ?', ['%repair%']);
            })
                ->orderBy('full_name')
                ->get(['id', 'full_name', 'email']);

            // dd($technicians);

            return view('pages.products.warehouse.products.index', compact(
                'parts',
                'products',
                'categories',
                'locations',
                'workflowStages',
                'dispositions',
                'technicians',
            ));

        } catch (\Exception $e) {
            Log::error('ProductController@index Error: '.$e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error loading products: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Error loading products.');
        }
    }

    public function restore($id)
    {
        try {
            $product = Product::onlyTrashed()->findOrFail($id);
            $product->restore();

            // Log activity
            $product->histories()->create([
                'action' => 'restored',
                'field' => 'deleted_at',
                'old_value' => now()->toDateTimeString(),
                'new_value' => null,
                'notes' => 'Product restored from soft delete during manual creation attempt.',
                'changed_by' => auth('web')->id(),
                'company_id' => auth('web')->user()->company_id,
            ]);

            return redirect()->route('warehouse.products.edit', $product->id)
                ->with('success', "Product \"{$product->unique_id}\" restored. You can now edit and re-save it.");
        } catch (\Exception $e) {
            Log::error('ProductController@restore Error: '.$e->getMessage());

            return redirect()->route('warehouse.products.create')->with('error', 'Failed to restore product.');
        }
    }

    public function create()
    {
        $categories = ProductCategory::select('id', 'name')->orderBy('name')->get();
        $locations = WarehouseLocation::select('id', 'name')->orderBy('name')->get();

        return view('pages.products.warehouse.products.create', compact('categories', 'locations'));
    }

    public function checkUniqueId(Request $request)
    {
        $uniqueId = trim($request->input('unique_id', ''));
        $companyId = auth('web')->user()->company_id;

        if ($uniqueId === '') {
            return response()->json(['exists' => false]);
        }

        // Check active ones
        $activeProduct = Product::where('unique_id', $uniqueId)
            ->where('company_id', $companyId)
            ->first();

        if ($activeProduct) {
            return response()->json([
                'exists' => true,
                'is_deleted' => false,
                'product_id' => $activeProduct->id,
            ]);
        }

        // Check deleted ones
        $deletedProduct = Product::onlyTrashed()
            ->where('unique_id', $uniqueId)
            ->where('company_id', $companyId)
            ->first();

        if ($deletedProduct) {
            return response()->json([
                'exists' => true,
                'is_deleted' => true,
                'product_id' => $deletedProduct->id,
            ]);
        }

        return response()->json(['exists' => false]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'container_id' => 'required|string|max:50',
            'category_id' => 'required|exists:product_categories,id',
            'sequence_number' => 'required|string|max:20',
            'title' => 'nullable|string|max:255',
            'dpn_sn_number' => 'nullable|string|max:255',
            'count' => 'nullable|integer|min:0',
            'warehouse_location_id' => 'nullable|exists:warehouse_locations,id',
            'problem' => 'nullable|string|max:255',
        ]);

        $category = ProductCategory::select('id', 'name')->find($request->category_id);
        $categoryPrefix = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $category->name), 0, 3));
        $uniqueId = strtoupper($request->container_id.'-'.$categoryPrefix.'-'.$request->sequence_number);

        $existingProduct = Product::withTrashed()->where('unique_id', $uniqueId)->first();
        if ($existingProduct) {
            if ($existingProduct->trashed()) {
                session()->flash('deleted_product_id', $existingProduct->id);

                return redirect()->back()->withInput()
                    ->with('error', "Product with ID \"{$uniqueId}\" exists in deleted records. You can restore it using the button below.");
            }

            return redirect()->back()->withInput()
                ->with('error', "Product with ID \"{$uniqueId}\" already exists.");
        }

        $manualCount = max((int) ($request->count ?? 1), 1);

        Product::create([
            'unique_id' => $uniqueId,
            'dpn_sn_number' => $request->dpn_sn_number,
            'title' => $request->title ?? '',
            'count' => $manualCount,
            'manual_status' => 1,
            'category_id' => $request->category_id,
            'warehouse_location_id' => $request->warehouse_location_id,
            'problem' => $request->problem,
            'status' => 'Active',
            'last_action' => 'Manually Created',
            'product_details' => 'Manually created product',
            'company_id' => auth('web')->user()->company_id,
            'created_by' => auth('web')->id(),
        ]);

        return redirect()->route('warehouse.products.index')
            ->with('success', "Product \"{$uniqueId}\" created successfully.");
    }

    public function show(Product $product)
    {
        try {
            $product->load([
                'category:id,name',
                'warehouseLocation:id,name',
                'assignedTo:id,full_name',
                'disposition:id,name',
                'workflowStage:id,name',
                'company:id,name',
                'createdBy:id,username',
                'parts:id,name,part_number',
                'histories' => function ($q) {
                    $q->with('changedBy:id,username')->latest()->limit(50);
                },
                'visualInspections' => function ($q) {
                    $q->latest()->limit(1);
                },
                'functionalTestings' => function ($q) {
                    $q->latest()->limit(1);
                },
            ]);

            $generalTask = GeneralTaskResult::select(
                'id',
                'product_id',
                'additional_data',
                'completed_at',
                'technician_notes',
                'remarks',
                'created_at'
            )
                ->where('product_id', $product->id)
                ->latest()
                ->first();

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'product' => $product,
                    'generalTask' => $generalTask,
                ]);
            }

            // For web view
            return view('pages.products.warehouse.products.show', compact('product', 'generalTask'));

        } catch (\Exception $e) {
            Log::error('ProductController@show Error: '.$e->getMessage());

            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error loading product: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->route('warehouse.products.index')
                ->with('error', 'Error loading product details.');
        }
    }

    /**
     * Show product history
     */
    public function history($id, Request $request)
    {
        try {
            $product = Product::with([
                'category:id,name',
                'warehouseLocation:id,name',
                'assignedTo:id,full_name',
                'disposition:id,name',
                'workflowStage:id,name',
                'company:id,name',
                'createdBy:id,username',
                'parts:id,name,part_number',
                'visualInspections' => function ($q) {
                    $q->latest()->limit(1);
                },
                'functionalTestings' => function ($q) {
                    $q->latest()->limit(1);
                },
            ])->findOrFail($id);

            $sort = $request->get('sort', 'desc') === 'asc' ? 'asc' : 'desc';

            // Get product histories with related data
            $histories = $product->histories()
                ->with('changedBy:id,username')
                ->orderBy('created_at', $sort)
                ->paginate(20)
                ->appends(['sort' => $sort]);

            // Lookup maps for resolving IDs to human-readable names
            $dispositionMap = Disposition::pluck('name', 'id');
            $workflowStageMap = WorkflowStage::pluck('name', 'id');
            $locationMap = WarehouseLocation::pluck('name', 'id');

            return view('pages.products.warehouse.products.history', compact(
                'product', 'histories', 'dispositionMap', 'workflowStageMap', 'locationMap', 'sort'
            ));
        } catch (\Exception $e) {
            Log::error('ProductController@history Error: '.$e->getMessage());

            return redirect()->route('warehouse.products.index')
                ->with('error', 'Product not found.');
        }
    }

    public function edit($id)
    {
        try {
            $product = Product::with([
                'category:id,name',
                'warehouseLocation:id,name',
                'disposition:id,name',
                'workflowStage:id,name',
                'assignedTo:id,full_name,email',
                'visualInspections' => function ($q) {
                    $q->latest()->limit(1);
                },
                'functionalTestings' => function ($q) {
                    $q->latest()->limit(1);
                },
            ])->findOrFail($id);

            $categories = ProductCategory::select('id', 'name')->orderBy('name')->get();
            $dispositions = Disposition::select('id', 'name')->orderBy('name')->get();
            $workflowStages = WorkflowStage::select('id', 'name')->orderBy('name')->get();
            $locations = WarehouseLocation::select('id', 'name')->orderBy('name')->get();
            $technicians = Employee::whereHas('department', function ($q) {
                $q->whereRaw('LOWER(department) LIKE ?', ['%technician%']);
            })
                ->orderBy('full_name')
                ->get(['id', 'full_name', 'email']);

            if (request()->ajax()) {
                return view('pages.products.warehouse.products.partials.edit_modal_content', compact(
                    'product',
                    'categories',
                    'locations',
                    'dispositions',
                    'workflowStages',
                    'technicians'
                ));
            }

            return view('pages.products.warehouse.products.edit', compact(
                'product',
                'categories',
                'locations',
                'dispositions',
                'workflowStages',
                'technicians'
            ));
        } catch (\Exception $e) {
            Log::error('ProductController@edit Error: '.$e->getMessage());

            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'Product not found.'], 404);
            }

            return redirect()->route('warehouse.products.index')
                ->with('error', 'Product not found.'.$e->getMessage());
        }
    }

    /**
     * Show the extract part page for a product
     */
    public function extractPartPage($id)
    {
        try {
            $product = Product::with([
                'category:id,name',
                'warehouseLocation:id,name',
                'disposition:id,name',
                'workflowStage:id,name',
                'assignedTo:id,full_name',
            ])->findOrFail($id);
            $scrapCategories = ScrapCategory::select('id', 'name')->orderBy('name')->get();
            $scrapTypes = ScrapType::select('id', 'name')->orderBy('name')->get();

            return view('pages.products.warehouse.products.extract', compact('product', 'scrapCategories', 'scrapTypes'));

        } catch (\Exception $e) {
            Log::error('ProductController@extractPartPage Error: '.$e->getMessage());

            return redirect()->route('warehouse.products.index')
                ->with('error', 'Product not found.');
        }
    }

    /**
     * Process the part extraction for a product
     */
    public function processExtractPart(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'parts' => 'required|array|min:1',
            'parts.*.name' => 'required|string|max:255',
            'parts.*.value' => 'nullable|string|max:255',
            'remaining_action' => 'required|in:scrap,keep',
            'scrap_data' => 'nullable|array',
            'scrap_data.item_description' => 'nullable|string|max:500',
            'scrap_data.material_category_id' => 'required_if:remaining_action,scrap|nullable|exists:scrap_categories,id',
            'scrap_data.material_category' => 'nullable|string|max:255',
            'scrap_data.material_type_id' => 'required_if:remaining_action,scrap|nullable|exists:scrap_types,id',
            'scrap_data.material_type' => 'nullable|string|max:255',
            'scrap_data.weight' => 'nullable|numeric|min:0',
            'scrap_data.disposal_reason' => 'nullable|string|max:500',
            'keep_notes' => 'nullable|string|max:1000',
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

            $product = Product::with(['category:id,name', 'disposition:id,name'])->findOrFail($id);

            $currentUserId = auth('web')->id();
            $companyId = $product->company_id;

            // Mark barcode as used when parts are extracted
            $this->updateBarcodeStatusToUsed($product->unique_id, 'parts_extracted');

            // ── 1. Build human-readable extracted parts list ─────────────────
            $extractedPartsList = [];
            foreach ($request->parts as $part) {
                $extractedPartsList[] = $part['name'].($part['value'] ? ' ('.$part['value'].')' : '');
            }
            $extractedPartsString = implode(', ', $extractedPartsList);

            // ── 2. Remove extracted parts from product specifications ────────
            $existingSpecs = $product->specifications ?? [];
            $extractedPartNames = array_map(fn ($p) => strtolower(trim($p['name'])), $request->parts);

            $updatedSpecs = array_values(array_filter($existingSpecs, function ($spec) use ($extractedPartNames) {
                $key = is_array($spec)
                    ? strtolower(trim($spec['key'] ?? $spec['name'] ?? ''))
                    : strtolower(trim($spec));

                return ! in_array($key, $extractedPartNames);
            }));

            // ── 3. Save updated specs to product ─────────────────────────────
            $oldSpecs = $product->specifications ?? [];
            $product->update(['specifications' => $updatedSpecs]);

            // ── 4. Log extraction history on the product ──────────────────────
            $product->histories()->create([
                'action' => 'parts_extracted',
                'field' => 'specifications',
                'old_value' => json_encode($oldSpecs),
                'new_value' => json_encode($updatedSpecs),
                'notes' => "Extracted parts: {$extractedPartsString}",
                'changed_by' => $currentUserId,
                'company_id' => $companyId,
            ]);

            // ── 5. Save each extracted part to the Parts inventory table ─────
            $createdPartIds = [];

            foreach ($request->parts as $extractedPart) {
                $partName = $extractedPart['name'];
                $partValue = $extractedPart['value'] ?? null;

                // Build a description from the spec value if present
                $description = $partValue
                    ? "Extracted from product: {$product->title} ({$product->unique_id}). Spec value: {$partValue}"
                    : "Extracted from product: {$product->title} ({$product->unique_id})";

                // Auto-generate part number matching the format in PartController@store
                $partNumber = 'PART-'.strtoupper(uniqid());

                $part = Part::create([
                    'part_number' => $partNumber,
                    'name' => $partName,
                    'description' => $description,
                    'category' => $product->category->name ?? null, // inherit product category
                    'warehouse_location_id' => $product->warehouse_location_id,
                    'rack_position' => null,
                    'shelf' => null,
                    'quantity' => 1,             // one unit extracted
                    'min_stock_level' => 1,
                    'reorder_level' => 1,
                    'status' => 'IN_STOCK',
                    'created_by' => $currentUserId,
                    'company_id' => $companyId,
                ]);

                // Log initial stock movement (mirrors PartController@store logic)
                $part->movements()->create([
                    'movement_type' => 'IN',
                    'quantity' => 1,
                    'before_quantity' => 0,
                    'after_quantity' => 1,
                    'reason' => "Extracted from product: {$product->title} ({$product->unique_id})",
                    'created_by' => $currentUserId,
                    'company_id' => $companyId,
                ]);

                $createdPartIds[] = $part->id;
            }

            // ── 6. Handle remaining product ───────────────────────────────────
            $redirectUrl = route('warehouse.products.index');
            $message = '';

            if ($request->remaining_action === 'scrap') {

                // Mark barcode as used when product is scrapped
                $this->updateBarcodeStatusToUsed($product->unique_id, 'product_scrapped');

                // Set product to Inactive + SCRAP disposition
                $scrapDisposition = Disposition::whereIn('name', ['SCRAP', 'Scrap'])->first();
                $updateData = ['status' => 'Inactive'];
                $updateData = ['scrap_status' => 1];
                if ($scrapDisposition) {
                    $updateData['disposition_id'] = $scrapDisposition->id;
                    $updateData['status'] = 'used';
                }
                $product->update($updateData);

                // Gather scrap form data
                $scrapData = $request->scrap_data ?? [];
                $category = ! empty($scrapData['material_category_id'])
                    ? ScrapCategory::select('id', 'name')->find($scrapData['material_category_id'])
                    : null;
                $type = ! empty($scrapData['material_type_id'])
                    ? ScrapType::select('id', 'name')->find($scrapData['material_type_id'])
                    : null;

                // Create scrap material record
                $scrap = ScrapMaterial::create([
                    'scrap_id' => $this->generateScrapId(),
                    'item_description' => ! empty($scrapData['item_description'])
                        ? $scrapData['item_description']
                        : $product->title.' (Remaining after part extraction)',
                    'scrap_category_id' => $scrapData['material_category_id'] ?? null,
                    'scrap_type_id' => $scrapData['material_type_id'] ?? null,
                    'weight' => ! empty($scrapData['weight']) ? $scrapData['weight'] : 0,
                    'quantity_type' => 'kg',
                    'scrap_reason' => ! empty($scrapData['disposal_reason'])
                        ? $scrapData['disposal_reason']
                        : 'Part extraction remaining product',
                    'source' => 'Product Extraction: '.$product->unique_id.' - '.$product->title,
                    'warehouse_location_id' => $product->warehouse_location_id,
                    'notes' => 'Created from product extraction. Product ID: '.$product->id
                        .'. Extracted parts: '.$extractedPartsString,
                    'status' => 'in_storage',
                    'created_by' => $currentUserId,
                    'company_id' => $companyId,
                ]);

                // Build scrap history notes
                $scrapNotes = "Product moved to scrap after part extraction. Scrap ID: {$scrap->scrap_id}.";
                $scrapNotes .= " Extracted parts saved to inventory: {$extractedPartsString}.";
                if ($category) {
                    $scrapNotes .= " Category: {$category->name}.";
                }
                if ($type) {
                    $scrapNotes .= " Type: {$type->name}.";
                }
                if (! empty($scrapData['weight'])) {
                    $scrapNotes .= " Weight: {$scrapData['weight']}kg.";
                }

                $product->histories()->create([
                    'action' => 'moved_to_scrap',
                    'field' => 'disposition',
                    'old_value' => null,
                    'new_value' => json_encode([
                        'scrap_id' => $scrap->scrap_id,
                        'category' => $category->name ?? null,
                        'type' => $type->name ?? null,
                        'weight' => $scrapData['weight'] ?? null,
                        'disposal_reason' => $scrapData['disposal_reason'] ?? null,
                        'extracted_parts' => $extractedPartsList,
                        'part_ids_created' => $createdPartIds,
                    ]),
                    'notes' => $scrapNotes,
                    'changed_by' => $currentUserId,
                    'company_id' => $companyId,
                ]);

                // Redirect to scrap detail page if route exists, else index
                $redirectUrl = route('warehouse.products.index');

                $message = "Parts extracted and added to inventory. Product moved to scrap (Scrap ID: {$scrap->scrap_id}).";

            } else {
                // Keep product active — specs already updated above
                $keepNotes = $request->keep_notes ?? '';
                $notesText = "Parts extracted and saved to parts inventory. Product kept active. Extracted: {$extractedPartsString}.";
                if ($keepNotes) {
                    $notesText .= " Notes: {$keepNotes}";
                }

                $product->histories()->create([
                    'action' => 'kept_after_extraction',
                    'field' => 'status',
                    'old_value' => null,
                    'new_value' => 'kept',
                    'notes' => $notesText,
                    'changed_by' => $currentUserId,
                    'company_id' => $companyId,
                ]);

                $redirectUrl = route('warehouse.products.show', $product->id);
                $message = 'Parts extracted and added to parts inventory. Specifications updated successfully!';
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $message,
                'redirect_url' => $redirectUrl,
                'extracted_parts_count' => count($request->parts),
                'parts_created' => $createdPartIds,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ProductController@processExtractPart Error: '.$e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Failed to process extraction: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate a unique scrap ID
     */
    private function generateScrapId()
    {
        $prefix = 'SCR';
        $year = date('Y');
        $month = date('m');

        // Get the latest scrap material to determine the next number
        $latest = ScrapMaterial::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        if ($latest) {
            // Extract the sequence number from the latest scrap_id
            $parts = explode('-', $latest->scrap_id);
            $lastNumber = intval(end($parts));
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        // Format: SCR-YYYY-MM-XXXX (e.g., SCR-2024-01-0001)
        return sprintf('%s-%s-%s-%04d', $prefix, $year, $month, $nextNumber);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'dpn_sn_number' => 'nullable|string|max:255',
            'product_details' => 'nullable|string',
            'remarks' => 'nullable|string',
            'category_id' => 'nullable|exists:product_categories,id',
            'location' => 'nullable|exists:warehouse_locations,id',
            'count' => 'nullable|integer|min:0',
            'assigned_to' => 'nullable|exists:employees,id',
            'disposition_id' => 'nullable|exists:dispositions,id',
            'work_flow_stage_id' => 'nullable|exists:workflow_stages,id',
            'problem' => 'nullable|string|max:255',
            'status' => 'required|in:Active,Inactive',
            'specifications' => 'nullable|json',
            'visual_checklist' => 'nullable|array',
            'visual_inspection_notes' => 'nullable|string',
            'visual_grade' => 'nullable|string|in:C0,C1,C2,C3,C4,C5,C6,C7,C8,C9',
            'functional_custom_tests' => 'nullable|array',
            'functional_test_results' => 'nullable|string',
            'functional_grade' => 'nullable|string|in:F1,F2,F3,F4,F5,F6',
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

            $product = Product::findOrFail($id);
            $oldValues = $product->getOriginal();

            $oldVisualInspection = $product->visualInspections()->latest()->first();
            $oldFunctionalTesting = $product->functionalTestings()->latest()->first();

            $visualInspectionData = [
                'grade' => $request->visual_grade,
                'inspection_notes' => $request->visual_inspection_notes,
                'checklist_items' => $request->visual_checklist ?? [],
                'custom_checklist' => $request->visual_checklist ?? [],
            ];
            $visualInspectionChanged = $request->hasAny([
                'visual_grade',
                'visual_checklist',
                'visual_inspection_notes',
            ]) && (! $oldVisualInspection || collect($visualInspectionData)->contains(
                fn ($value, $field) => $oldVisualInspection->{$field} !== $value
            ));

            $functionalTestingData = [
                'grade' => $request->functional_grade,
                'overall_results_summary' => $request->functional_test_results,
                'custom_tests' => $request->functional_custom_tests ?? [],
            ];
            $functionalTestingChanged = $request->hasAny([
                'functional_grade',
                'functional_test_results',
                'functional_custom_tests',
            ]) && (! $oldFunctionalTesting || collect($functionalTestingData)->contains(
                fn ($value, $field) => $oldFunctionalTesting->{$field} !== $value
            ));

            $employeeId = auth('web')->user()?->employee?->id;
            if (($visualInspectionChanged || $functionalTestingChanged) && ! $employeeId) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Your user account must be linked to an employee before recording inspection changes.',
                    'errors' => [
                        'inspector' => ['No employee record is linked to the authenticated user.'],
                    ],
                ], 422);
            }

            $product->update([
                'title' => $request->title,
                'dpn_sn_number' => $request->dpn_sn_number,
                'product_details' => $request->product_details,
                'remarks' => $request->remarks,
                'count' => $request->count ?? $product->count,
                'category_id' => $request->category_id,
                'warehouse_location_id' => $request->location,
                'assigned_to' => $request->assigned_to,
                'disposition_id' => $request->disposition_id,
                'work_flow_stage_id' => $request->work_flow_stage_id,
                'problem' => $request->problem,
                'status' => $request->status ?? 'Inactive',
                'specifications' => $request->specifications ? json_decode($request->specifications, true) : null,
            ]);

            // Check if this update should mark barcode as used
            $shouldMarkAsUsed = (
                $request->status === 'Inactive' ||
                $request->filled('assigned_to') ||
                $request->filled('disposition_id') ||
                $product->wasChanged('status') ||
                $product->wasChanged('assigned_to') ||
                $product->wasChanged('disposition_id')
            );

            if ($shouldMarkAsUsed) {
                $this->updateBarcodeStatusToUsed($product->unique_id, 'product_updated');
            }

            if ($visualInspectionChanged) {
                $visualInspection = $product->visualInspections()->updateOrCreate(
                    ['product_id' => $product->id],
                    $visualInspectionData + [
                        'product_id' => $product->id,
                        'inspected_by' => $employeeId,
                        'inspected_at' => now(),
                        'company_id' => $product->company_id,
                    ]
                );
                $this->trackVisualInspectionChanges($product, $oldVisualInspection, $visualInspection);

                // Mark barcode as used when visual inspection is performed
                $this->updateBarcodeStatusToUsed($product->unique_id, 'visual_inspection_performed');
            }

            if ($functionalTestingChanged) {
                $functionalTesting = $product->functionalTestings()->updateOrCreate(
                    ['product_id' => $product->id],
                    $functionalTestingData + [
                        'product_id' => $product->id,
                        'tested_by' => $employeeId,
                        'tested_at' => now(),
                        'company_id' => $product->company_id,
                    ]
                );
                $this->trackFunctionalTestingChanges($product, $oldFunctionalTesting, $functionalTesting);

                // Mark barcode as used when functional testing is performed
                $this->updateBarcodeStatusToUsed($product->unique_id, 'functional_testing_performed');
            }

            if ($product->wasChanged()) {
                $changes = $product->getChanges();
                unset($changes['updated_at']);
                foreach ($changes as $field => $newValue) {
                    $product->histories()->create([
                        'action' => 'updated',
                        'field' => $field,
                        'old_value' => is_array($oldValues[$field] ?? null) ? json_encode($oldValues[$field]) : ($oldValues[$field] ?? null),
                        'new_value' => is_array($newValue) ? json_encode($newValue) : $newValue,
                        'notes' => 'Updated '.$this->formatFieldName($field),
                        'changed_by' => auth('web')->id(),
                        'company_id' => $product->company_id,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully!',
                'data' => $product,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ProductController@update Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update product: '.$e->getMessage(),
            ], 500);
        }
    }

    public function action(Request $request, $id)
    {
        try {
            $product = Product::findOrFail($id);
            $action = $request->input('action');

            // Mark barcode as used when product action is performed
            $this->updateBarcodeStatusToUsed($product->unique_id, 'action_'.strtolower($action));

            // Update disposition based on action (case-insensitive match)
            $disposition = Disposition::whereRaw('LOWER(name) = ?', [strtolower($action)])->first();
            if ($disposition) {
                $product->disposition_id = $disposition->id;
                $product->save();

                // Log history
                $product->histories()->create([
                    'action' => strtolower($action).'_action',
                    'old_value' => null,
                    'new_value' => $action,
                    'notes' => "Product marked as {$action}",
                    'changed_by' => auth('web')->id(),
                    'company_id' => $product->company_id,
                ]);
            }

            return response()->json(['success' => true, 'message' => "Product marked as {$action}"]);
        } catch (\Exception $e) {
            Log::error('ProductController@action Error: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // public function destroy(Product $product)
    // {
    //     try {
    //         DB::beginTransaction();

    //         // Mark barcode as used when product is deleted
    //         $this->updateBarcodeStatusToUsed($product->unique_id, 'product_deleted');

    //         if ($product->barcode) {
    //             $product->barcode()->delete();
    //         }

    //         $product->histories()->delete();
    //         $product->delete();

    //         DB::commit();

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Product deleted successfully!',
    //         ]);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('ProductController@destroy Error: '.$e->getMessage());

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to delete product: '.$e->getMessage(),
    //         ], 500);
    //     }
    // }

    // public function bulkDelete(Request $request)
    // {
    //     $request->validate([
    //         'ids' => 'required|array',
    //         'ids.*' => 'exists:products,id',
    //     ]);

    //     try {
    //         DB::beginTransaction();

    //         $products = Product::whereIn('id', $request->ids)->get();
    //         $count = $products->count();

    //         foreach ($products as $product) {
    //             // Mark barcode as used when product is bulk deleted
    //             $this->updateBarcodeStatusToUsed($product->unique_id, 'bulk_product_deleted');

    //             if ($product->barcode) {
    //                 $product->barcode()->delete();
    //             }
    //             $product->histories()->delete();
    //             $product->delete();
    //         }

    //         DB::commit();

    //         return response()->json([
    //             'success' => true,
    //             'message' => "Deleted {$count} products successfully!",
    //             'count' => $count,
    //         ]);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('ProductController@bulkDelete Error: '.$e->getMessage());

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to delete products: '.$e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function destroy(Product $product)
    {
        try {
            DB::beginTransaction();

            // Check if product has any active gate pass items
            $activeGatePassItems = $product->activeGatePassItems()->exists();
            $anyGatePassItems = $product->gatePassItems()->exists();

            if ($activeGatePassItems) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete product: This product is currently in an active gate pass. Please complete or cancel the gate pass first.',
                    'code' => 'IN_ACTIVE_GATEPASS',
                ], 409);
            }

            // Check if product has any gate pass items at all (completed or pending)
            if ($anyGatePassItems) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete product: This product has been used in a gate pass. Deletion is not allowed for audit purposes.',
                    'code' => 'HAS_GATEPASS_HISTORY',
                ], 409);
            }

            // Check if product has active technician assignment
            $activeAssignment = $product->currentAssignment()->first();

            if ($activeAssignment) {
                $technicianName = $activeAssignment->technician->full_name ?? 'Unknown Technician';
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete product: This product is currently assigned to technician '{$technicianName}'. Please complete or remove the assignment first.",
                    'code' => 'ACTIVE_ASSIGNMENT',
                    'assignment_id' => $activeAssignment->id,
                ], 409);
            }

            // Optional: Check for completed assignments (just for warning/logging)
            $completedAssignments = $product->assignmentHistory()
                ->whereNotNull('completed_at')
                ->exists();

            if ($completedAssignments) {
                Log::warning("Product {$product->unique_id} (ID: {$product->id}) is being deleted but has completed assignment history", [
                    'product_id' => $product->id,
                    'assignment_count' => $product->assignmentHistory()->count(),
                    'deleted_by' => auth('web')->id(),
                ]);
            }

            // DELETE the barcode record from barcode table (not just update status)
            $barcode = Barcode::where('unique_id', $product->unique_id)->first();
            if ($barcode) {
                $barcode->delete(); // This completely removes the barcode record
                Log::info("Barcode {$product->unique_id} deleted from barcode table");
            }

            // Delete product histories
            $product->histories()->delete();

            // Delete the product itself
            $product->delete();

            DB::commit();

            $message = 'Product and its barcode deleted successfully!';
            if ($completedAssignments) {
                $message = 'Product and its barcode deleted successfully! (Note: This product had past assignments that were removed)';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'had_assignments' => $completedAssignments,
                'barcode_deleted' => $barcode ? true : false,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ProductController@destroy Error: '.$e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete product: '.$e->getMessage(),
            ], 500);
        }
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:products,id',
        ]);

        try {
            DB::beginTransaction();

            $ids = array_values(array_unique($request->ids));
            $products = Product::whereIn('id', $ids)
                ->select('id', 'unique_id', 'company_id')
                ->get();
            $count = $products->count();
            $deletedCount = 0;
            $failedProducts = [];
            $errors = [];
            $barcodesDeleted = 0;
            $productIds = $products->pluck('id');
            $activeGatePassProductIds = GatePassItem::whereIn('product_id', $productIds)
                ->whereHas('gatePass', function ($q) {
                    $q->whereNotIn('status', ['QC_AND_SECURITY_APPROVED', 'REJECTED']);
                })
                ->pluck('product_id')
                ->unique()
                ->flip();
            $anyGatePassProductIds = GatePassItem::whereIn('product_id', $productIds)
                ->pluck('product_id')
                ->unique()
                ->flip();
            $activeAssignments = ProductAssignment::with('technician:id,full_name')
                ->whereIn('product_id', $productIds)
                ->whereNull('completed_at')
                ->get()
                ->keyBy('product_id');
            $barcodes = Barcode::whereIn('unique_id', $products->pluck('unique_id'))
                ->get()
                ->keyBy('unique_id');

            foreach ($products as $product) {
                try {
                    // Check for active gate pass items
                    $activeGatePassItems = $activeGatePassProductIds->has($product->id);
                    $anyGatePassItems = $anyGatePassProductIds->has($product->id);

                    if ($activeGatePassItems) {
                        $failedProducts[] = $product->unique_id;
                        $errors[] = "Product {$product->unique_id} is in an active gate pass";

                        continue;
                    }

                    if ($anyGatePassItems) {
                        $failedProducts[] = $product->unique_id;
                        $errors[] = "Product {$product->unique_id} has gate pass history";

                        continue;
                    }

                    // Check for active technician assignment
                    $activeAssignment = $activeAssignments->get($product->id);

                    if ($activeAssignment) {
                        $technicianName = $activeAssignment->technician->full_name ?? 'Unknown';
                        $failedProducts[] = $product->unique_id;
                        $errors[] = "Product {$product->unique_id} is assigned to technician '{$technicianName}'";

                        continue;
                    }

                    // DELETE the barcode record from barcode table
                    $barcode = $barcodes->get($product->unique_id);
                    if ($barcode) {
                        $barcode->delete();
                        $barcodesDeleted++;
                    }

                    // Delete product histories
                    $product->histories()->delete();

                    // Delete the product
                    $product->delete();
                    $deletedCount++;

                } catch (\Exception $e) {
                    $failedProducts[] = $product->unique_id;
                    $errors[] = "Product {$product->unique_id}: ".$e->getMessage();
                    Log::error("Failed to delete product {$product->id}: ".$e->getMessage());
                }
            }

            DB::commit();

            $message = "Deleted {$deletedCount} out of {$count} products successfully!";
            $message .= " {$barcodesDeleted} barcode(s) deleted.";

            if (! empty($failedProducts)) {
                $message .= ' Failed to delete: '.implode(', ', $failedProducts);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'count' => $deletedCount,
                'barcodes_deleted' => $barcodesDeleted,
                'failed_count' => count($failedProducts),
                'failed_products' => $failedProducts,
                'errors' => $errors,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ProductController@bulkDelete Error: '.$e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete products: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk status change for multiple products
     */
    public function bulkStatusChange(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:products,id',
            'status' => 'nullable|in:Active,Inactive,Hold',
            'disposition_id' => 'nullable|exists:dispositions,id',
            'work_flow_stage_id' => 'nullable|exists:workflow_stages,id',
            'location' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            $updateData = [];

            // Prepare update data
            if ($request->filled('status')) {
                $updateData['status'] = $request->status;
            }
            if ($request->filled('disposition_id')) {
                $updateData['disposition_id'] = $request->disposition_id;
            }
            if ($request->filled('work_flow_stage_id')) {
                $updateData['work_flow_stage_id'] = $request->work_flow_stage_id;
            }
            if ($request->filled('location')) {
                $updateData['warehouse_location_id'] = $request->location;
            }

            if (empty($updateData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No fields to update',
                ], 400);
            }

            $products = Product::whereIn('id', $request->ids)
                ->select(array_values(array_unique(array_merge(
                    ['id', 'unique_id', 'company_id'],
                    array_keys($updateData)
                ))))
                ->get();
            $count = $products->count();

            foreach ($products as $product) {
                $oldValues = $product->only(array_keys($updateData));

                // Mark barcode as used when product status is bulk changed
                $this->updateBarcodeStatusToUsed($product->unique_id, 'bulk_status_change');

                $product->update($updateData);

                // Log history for each changed field
                foreach ($updateData as $field => $newValue) {
                    $oldValue = $oldValues[$field] ?? null;

                    if ($oldValue != $newValue) {
                        $product->histories()->create([
                            'action' => 'bulk_'.$field.'_update',
                            'old_value' => $oldValue,
                            'new_value' => $newValue,
                            'notes' => "Bulk updated {$field}",
                            'changed_by' => auth('web')->id(),
                            'company_id' => $product->company_id,
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully updated {$count} products",
                'count' => $count,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ProductController@bulkStatusChange Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update products: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk edit common fields for selected products.
     */
    public function bulkUpdateTitle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:products,id',
            'title' => 'nullable|string|max:255',
            'work_flow_stage_id' => 'nullable|integer|exists:workflow_stages,id',
            'disposition_id' => 'nullable|integer|exists:dispositions,id',
            'warehouse_location_id' => 'nullable|integer|exists:warehouse_locations,id',
            'status' => 'nullable|in:Active,Inactive,Hold',
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

            $ids = array_values(array_unique($request->input('ids', [])));
            $updateData = [];

            if ($request->filled('title')) {
                $title = trim($request->input('title'));
                if ($title !== '') {
                    $updateData['title'] = $title;
                }
            }
            if ($request->filled('work_flow_stage_id')) {
                $updateData['work_flow_stage_id'] = (int) $request->input('work_flow_stage_id');
            }
            if ($request->filled('disposition_id')) {
                $updateData['disposition_id'] = (int) $request->input('disposition_id');
            }
            if ($request->filled('warehouse_location_id')) {
                $updateData['warehouse_location_id'] = (int) $request->input('warehouse_location_id');
            }
            if ($request->filled('status')) {
                $updateData['status'] = $request->input('status');
            }

            if (empty($updateData)) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Please enter or select at least one field to update',
                ], 422);
            }

            $products = Product::whereIn('id', $ids)
                ->select(array_values(array_unique(array_merge(
                    ['id', 'unique_id', 'company_id'],
                    array_keys($updateData)
                ))))
                ->get();
            $updatedCount = 0;
            $unchangedCount = 0;

            foreach ($products as $product) {
                $changedFields = [];

                foreach ($updateData as $field => $newValue) {
                    if ($product->getAttribute($field) != $newValue) {
                        $changedFields[$field] = [
                            'old' => $product->getAttribute($field),
                            'new' => $newValue,
                        ];
                    }
                }

                if (empty($changedFields)) {
                    $unchangedCount++;
                    continue;
                }

                $product->update(array_map(
                    fn ($change) => $change['new'],
                    $changedFields
                ));

                foreach ($changedFields as $field => $change) {
                    $product->histories()->create([
                        'action' => 'bulk_'.$field.'_update',
                        'field' => $field,
                        'old_value' => $change['old'],
                        'new_value' => $change['new'],
                        'notes' => "{$field} updated via bulk edit",
                        'changed_by' => auth('web')->id(),
                        'company_id' => $product->company_id,
                    ]);
                }

                $updatedCount++;
            }

            DB::commit();

            $message = $updatedCount > 0
                ? "Successfully updated {$updatedCount} product(s)"
                : 'Selected product(s) already have these values';

            if ($unchangedCount > 0) {
                $message .= ". {$unchangedCount} product(s) were unchanged.";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'count' => $updatedCount,
                'unchanged_count' => $unchangedCount,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ProductController@bulkUpdateTitle Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update products: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add or replace the same specifications on multiple products.
     */
    public function bulkUpdateSpecifications(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:products,id',
            'specifications' => 'required|array|min:1|max:50',
            'specifications.*.key' => 'required|string|max:255',
            'specifications.*.value' => 'required|string|max:1000',
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

            $ids = array_values(array_unique($request->input('ids', [])));
            $specifications = collect($request->input('specifications', []))
                ->map(fn ($specification) => [
                    'key' => trim($specification['key']),
                    'value' => trim($specification['value']),
                ])
                ->values()
                ->all();

            if (collect($specifications)->contains(fn ($specification) => $specification['key'] === '' || $specification['value'] === '')) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Every specification must have both a key and value',
                ], 422);
            }

            $products = Product::whereIn('id', $ids)
                ->select('id', 'specifications', 'company_id')
                ->get();
            $updatedCount = 0;
            $unchangedCount = 0;

            foreach ($products as $product) {
                $oldSpecifications = is_array($product->specifications) ? $product->specifications : [];
                $mergedSpecifications = array_values($oldSpecifications);

                foreach ($specifications as $specification) {
                    $matchingIndex = null;

                    foreach ($mergedSpecifications as $index => $existingSpecification) {
                        if (! is_array($existingSpecification) || ! isset($existingSpecification['key'])) {
                            continue;
                        }

                        if (strcasecmp(trim((string) $existingSpecification['key']), $specification['key']) === 0) {
                            $matchingIndex = $index;
                            break;
                        }
                    }

                    if ($matchingIndex === null) {
                        $mergedSpecifications[] = $specification;
                    } else {
                        $mergedSpecifications[$matchingIndex] = $specification;
                    }
                }

                if ($oldSpecifications == $mergedSpecifications) {
                    $unchangedCount++;
                    continue;
                }

                $product->update(['specifications' => $mergedSpecifications]);
                $product->histories()->create([
                    'action' => 'bulk_specifications_update',
                    'field' => 'specifications',
                    'old_value' => json_encode($oldSpecifications, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'new_value' => json_encode($mergedSpecifications, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'notes' => 'Specifications added via bulk action',
                    'changed_by' => auth('web')->id(),
                    'company_id' => $product->company_id,
                ]);

                $updatedCount++;
            }

            DB::commit();

            $message = $updatedCount > 0
                ? "Successfully updated specifications for {$updatedCount} product(s)"
                : 'Selected product(s) already have these specifications';

            if ($unchangedCount > 0) {
                $message .= ". {$unchangedCount} product(s) were unchanged.";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'count' => $updatedCount,
                'unchanged_count' => $unchangedCount,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ProductController@bulkUpdateSpecifications Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update specifications: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk add part to multiple products
     */
    public function bulkAddPart(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:products,id',
            'part_id' => 'required|exists:parts,id',
            'notes' => 'nullable|string', // Changed from description to notes to match your schema
        ]);

        try {
            DB::beginTransaction();

            $ids = array_values(array_unique($request->ids));
            $products = Product::whereIn('id', $ids)
                ->select('id', 'unique_id', 'company_id')
                ->get();
            $count = $products->count();
            $part = Part::select('id', 'name', 'status', 'quantity')->findOrFail($request->part_id);
            $userId = auth('web')->id();
            $companyId = auth('web')->user()->company_id ?? null;

            if (! in_array($part->status, ['IN_STOCK', 'LOW_STOCK'])) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => "Part status is {$part->status}. It must be in stock or low stock to be added.",
                ], 400);
            }

            if ($part->quantity < $count) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => "Not enough quantity available. Required: {$count}, Available: {$part->quantity}",
                ], 400);
            }
            // Decrement part quantity
            $part->decrement('quantity', $count);
            $existingPivots = DB::table('product_part')
                ->where('part_id', $request->part_id)
                ->whereIn('product_id', $products->pluck('id'))
                ->get()
                ->keyBy('product_id');

            foreach ($products as $product) {
                // Mark barcode as used when part is added
                $this->updateBarcodeStatusToUsed($product->unique_id, 'bulk_part_added');

                // Check if the part is already attached to this product
                $existingPivot = $existingPivots->get($product->id);

                if ($existingPivot) {
                    // Update existing pivot record
                    $product->parts()->updateExistingPivot($request->part_id, [
                        'quantity' => $existingPivot->quantity + 1, // Increment quantity by 1
                        'notes' => $request->notes ?? $existingPivot->notes,
                        'added_by' => $userId,
                        'updated_at' => now(),
                    ]);
                } else {
                    // Attach new part to product with pivot data
                    $product->parts()->attach($request->part_id, [
                        'quantity' => 1, // Default quantity
                        'notes' => $request->notes,
                        'added_by' => $userId,
                        'company_id' => $companyId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                // Create history record
                $product->histories()->create([
                    'action' => 'part_added',
                    'old_value' => null,
                    'new_value' => "Part: {$part->name} (ID: {$request->part_id})",
                    'notes' => $request->notes ?? 'Added part via bulk action',
                    'changed_by' => $userId,
                    'company_id' => $product->company_id,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully added {$part->name} to {$count} products",
                'count' => $count,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ProductController@bulkAddPart Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to add part: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk extract part from multiple products
     */
    public function bulkExtractPart(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:products,id',
            'part_id' => 'required|string',
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $products = Product::whereIn('id', $request->ids)
                ->select('id', 'unique_id', 'company_id')
                ->get();
            $count = $products->count();

            foreach ($products as $product) {
                // Mark barcode as used when part is extracted
                $this->updateBarcodeStatusToUsed($product->unique_id, 'bulk_part_extracted');

                // Add your part extraction logic here
                // Example: $product->parts()->detach($request->part_id);

                $product->histories()->create([
                    'action' => 'part_extracted',
                    'old_value' => "Part ID: {$request->part_id}, Qty: {$request->quantity}",
                    'new_value' => null,
                    'notes' => $request->reason ?? 'Extracted part via bulk action',
                    'changed_by' => auth('web')->id(),
                    'company_id' => $product->company_id,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully extracted part from {$count} products",
                'count' => $count,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ProductController@bulkExtractPart Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to extract part: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk move location for multiple products
     */
    public function bulkMoveLocation(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:products,id',
            'location' => 'required|exists:warehouse_locations,id', // Changed to validate against warehouse_locations table
        ]);

        try {
            DB::beginTransaction();

            // Get the location name for display purposes
            $location = WarehouseLocation::select('id', 'name')->findOrFail($request->location);

            $products = Product::whereIn('id', $request->ids)
                ->select('id', 'unique_id', 'warehouse_location_id', 'company_id')
                ->with('warehouseLocation:id,name')
                ->get();
            $count = $products->count();

            foreach ($products as $product) {
                // Mark barcode as used when product location is moved
                $this->updateBarcodeStatusToUsed($product->unique_id, 'bulk_location_moved');

                $oldLocationId = $product->warehouse_location_id;
                $oldLocationName = $product->warehouseLocation?->name ?? 'No Location';

                $product->update([
                    'warehouse_location_id' => $request->location,
                ]);

                $product->histories()->create([
                    'action' => 'location_changed',
                    'old_value' => $oldLocationName,
                    'new_value' => $location->name,
                    'notes' => "Location moved from '{$oldLocationName}' to '{$location->name}' via bulk action",
                    'changed_by' => auth('web')->id(),
                    'company_id' => $product->company_id,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully moved {$count} products to {$location->name}",
                'count' => $count,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ProductController@bulkMoveLocation Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to move products: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk assign to technician
     */
    public function bulkAssignTech(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:products,id',
            'technician_id' => 'required|exists:employees,id',
            'disposition_id' => 'nullable|exists:dispositions,id',
            'work_flow_stage_id' => 'nullable|exists:workflow_stages,id',
            'task_type' => 'nullable|string|max:255',
            'remarks' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $products = Product::whereIn('id', $request->ids)
                ->select('id', 'unique_id', 'assigned_to', 'company_id')
                ->with([
                    'assignedTo:id,full_name',
                    'assignments' => function ($q) {
                        $q->select(
                            'id',
                            'product_id',
                            'technician_id',
                            'task_type',
                            'remarks',
                            'workflow_stage_id',
                            'disposition_id'
                        )
                            ->whereNull('completed_at');
                    },
                ])
                ->get();
            $technician = Employee::select('id', 'full_name')->findOrFail($request->technician_id);
            $currentUser = auth('web')->user();
            $count = $products->count();
            $successCount = 0;
            $errors = [];
            $wasUpdated = null;
            $activeAssignments = $products
                ->flatMap(fn ($product) => $product->assignments)
                ->filter();
            $oldTechnicianNames = Employee::whereIn(
                'id',
                $activeAssignments->pluck('technician_id')->filter()->unique()
            )->pluck('full_name', 'id');
            $workflowStageIds = $activeAssignments->pluck('workflow_stage_id')
                ->push($request->work_flow_stage_id)
                ->filter()
                ->unique();
            $dispositionIds = $activeAssignments->pluck('disposition_id')
                ->push($request->disposition_id)
                ->filter()
                ->unique();
            $workflowStageNames = WorkflowStage::whereIn('id', $workflowStageIds)->pluck('name', 'id');
            $dispositionNames = Disposition::whereIn('id', $dispositionIds)->pluck('name', 'id');
            $updateData = [
                'assigned_to' => $request->technician_id,
            ];

            if ($request->filled('disposition_id')) {
                $updateData['disposition_id'] = $request->disposition_id;
            }
            if ($request->filled('work_flow_stage_id')) {
                $updateData['work_flow_stage_id'] = $request->work_flow_stage_id;
            }

            foreach ($products as $product) {
                try {
                    // Mark barcode as used when product is assigned to technician
                    $this->updateBarcodeStatusToUsed($product->unique_id, 'bulk_assigned_to_technician');

                    // Get old assignment before updating
                    $oldAssignedTo = $product->assignedTo?->full_name ?? 'Unassigned';

                    // Check if there's an existing active assignment for this product
                    $existingAssignment = $product->assignments->first();

                    if ($existingAssignment) {
                        // Update existing assignment
                        $oldValues = [
                            'technician_id' => $existingAssignment->technician_id,
                            'task_type' => $existingAssignment->task_type,
                            'remarks' => $existingAssignment->remarks,
                            'workflow_stage_id' => $existingAssignment->workflow_stage_id,
                            'disposition_id' => $existingAssignment->disposition_id,
                        ];

                        $existingAssignment->update([
                            'technician_id' => $request->technician_id,
                            'assigned_by' => $currentUser->id,
                            'task_type' => $request->task_type,
                            'remarks' => $request->remarks,
                            'workflow_stage_id' => $request->work_flow_stage_id,
                            'disposition_id' => $request->disposition_id,
                            'assigned_at' => now(),
                        ]);

                        $assignment = $existingAssignment;
                        $wasUpdated = true;

                        // Log assignment update in product history
                        $product->histories()->create([
                            'action' => 'assignment_updated',
                            'field' => 'product_assignment',
                            'old_value' => json_encode([
                                'technician' => $oldTechnicianNames[$oldValues['technician_id']] ?? 'Unknown',
                                'task_type' => $oldValues['task_type'],
                                'remarks' => $oldValues['remarks'],
                                'workflow_stage' => $oldValues['workflow_stage_id'] ? ($workflowStageNames[$oldValues['workflow_stage_id']] ?? null) : null,
                                'disposition' => $oldValues['disposition_id'] ? ($dispositionNames[$oldValues['disposition_id']] ?? null) : null,
                            ]),
                            'new_value' => json_encode([
                                'technician' => $technician->full_name,
                                'task_type' => $request->task_type,
                                'remarks' => $request->remarks,
                                'workflow_stage' => $request->work_flow_stage_id ? ($workflowStageNames[$request->work_flow_stage_id] ?? null) : null,
                                'disposition' => $request->disposition_id ? ($dispositionNames[$request->disposition_id] ?? null) : null,
                            ]),
                            'notes' => "Assignment updated for {$technician->full_name}",
                            'changed_by' => $currentUser->id,
                            'company_id' => $product->company_id,
                        ]);

                    } else {
                        // No existing assignment - create new one
                        $assignmentData = [
                            'product_id' => $product->id,
                            'technician_id' => $request->technician_id,
                            'assigned_by' => $currentUser->id,
                            'task_type' => $request->task_type,
                            'remarks' => $request->remarks,
                            'workflow_stage_id' => $request->work_flow_stage_id,
                            'disposition_id' => $request->disposition_id,
                            'assigned_at' => now(),
                            'completed_at' => null,
                            'company_id' => $product->company_id,
                        ];

                        $assignment = ProductAssignment::create($assignmentData);
                        $wasUpdated = false;

                        // Log assignment creation in product history
                        $product->histories()->create([
                            'action' => 'assignment_created',
                            'field' => 'product_assignment',
                            'old_value' => null,
                            'new_value' => json_encode([
                                'assignment_id' => $assignment->id,
                                'technician' => $technician->full_name,
                                'task_type' => $request->task_type,
                                'remarks' => $request->remarks,
                                'workflow_stage' => $request->work_flow_stage_id ? ($workflowStageNames[$request->work_flow_stage_id] ?? null) : null,
                                'disposition' => $request->disposition_id ? ($dispositionNames[$request->disposition_id] ?? null) : null,
                            ]),
                            'notes' => "New assignment created for {$technician->full_name}",
                            'changed_by' => $currentUser->id,
                            'company_id' => $product->company_id,
                        ]);
                    }

                    // Update product basic fields
                    $product->update($updateData);

                    // Prepare notes for history
                    $notes = $wasUpdated
                        ? "Assignment updated for {$technician->full_name}"
                        : "Assigned to {$technician->full_name}";

                    if ($request->filled('task_type')) {
                        $notes .= " | Task: {$request->task_type}";
                    }
                    if ($request->filled('remarks')) {
                        $notes .= " | Remarks: {$request->remarks}";
                    }
                    if ($request->filled('work_flow_stage_id')) {
                        $notes .= ' | Workflow: '.($workflowStageNames[$request->work_flow_stage_id] ?? '');
                    }
                    if ($request->filled('disposition_id')) {
                        $notes .= ' | Disposition: '.($dispositionNames[$request->disposition_id] ?? '');
                    }

                    // Log product history for assignment change
                    $product->histories()->create([
                        'action' => $wasUpdated ? 'assigned_to_technician_updated' : 'assigned_to_technician',
                        'field' => 'assigned_to',
                        'old_value' => $oldAssignedTo,
                        'new_value' => $technician->full_name,
                        'notes' => $notes,
                        'changed_by' => $currentUser->id,
                        'company_id' => $product->company_id,
                    ]);

                    $successCount++;

                } catch (\Exception $e) {
                    $errors[] = "Product {$product->unique_id}: ".$e->getMessage();
                    Log::error("Failed to assign product {$product->id}: ".$e->getMessage());
                }
            }

            DB::commit();

            $message = 'Successfully '.($wasUpdated ? 'updated' : 'assigned')." {$successCount} out of {$count} products to {$technician->full_name}";

            if (! empty($errors)) {
                $message .= '. Failed: '.implode(', ', $errors);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'count' => $successCount,
                'errors' => $errors,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ProductController@bulkAssignTech Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to assign products: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Track visual inspection changes
     */
    private function trackVisualInspectionChanges($product, $oldInspection, $newInspection)
    {
        // If no old inspection exists, this is a new inspection
        if (! $oldInspection) {
            $product->histories()->create([
                'action' => 'visual_inspection_created',
                'field' => 'visual_inspection',
                'old_value' => null,
                'new_value' => json_encode([
                    'grade' => $newInspection->grade,
                    'notes' => $newInspection->inspection_notes,
                    'checklist_items_count' => count($newInspection->checklist_items ?? []),
                ]),
                'notes' => "Visual inspection created with grade {$newInspection->grade}",
                'changed_by' => auth('web')->id(),
                'company_id' => $product->company_id,
            ]);

            return;
        }

        // Track grade changes
        if ($oldInspection->grade != $newInspection->grade) {
            $product->histories()->create([
                'action' => 'visual_grade_updated',
                'field' => 'visual_grade',
                'old_value' => $oldInspection->grade ?? 'Not Set',
                'new_value' => $newInspection->grade ?? 'Not Set',
                'notes' => "Visual grade changed from {$oldInspection->grade} to {$newInspection->grade}",
                'changed_by' => auth('web')->id(),
                'company_id' => $product->company_id,
            ]);
        }

        // Track inspection notes changes
        if ($oldInspection->inspection_notes != $newInspection->inspection_notes) {
            $product->histories()->create([
                'action' => 'visual_notes_updated',
                'field' => 'visual_inspection_notes',
                'old_value' => $oldInspection->inspection_notes ?? '',
                'new_value' => $newInspection->inspection_notes ?? '',
                'notes' => 'Visual inspection notes updated',
                'changed_by' => auth('web')->id(),
                'company_id' => $product->company_id,
            ]);
        }

        // Track checklist items changes
        $oldChecklist = $oldInspection->checklist_items ?? [];
        $newChecklist = $newInspection->checklist_items ?? [];

        $addedItems = array_diff($newChecklist, $oldChecklist);
        $removedItems = array_diff($oldChecklist, $newChecklist);
        $checkedStatus = $this->trackChecklistCheckedStatus($oldInspection, $newInspection);

        if (! empty($addedItems)) {
            $product->histories()->create([
                'action' => 'visual_checklist_items_added',
                'field' => 'visual_checklist',
                'old_value' => null,
                'new_value' => json_encode(array_values($addedItems)),
                'notes' => 'Added '.count($addedItems).' new inspection point(s): '.implode(', ', $addedItems),
                'changed_by' => auth('web')->id(),
                'company_id' => $product->company_id,
            ]);
        }

        if (! empty($removedItems)) {
            $product->histories()->create([
                'action' => 'visual_checklist_items_removed',
                'field' => 'visual_checklist',
                'old_value' => json_encode(array_values($removedItems)),
                'new_value' => null,
                'notes' => 'Removed '.count($removedItems).' inspection point(s): '.implode(', ', $removedItems),
                'changed_by' => auth('web')->id(),
                'company_id' => $product->company_id,
            ]);
        }

        if ($checkedStatus['checked'] > 0 || $checkedStatus['unchecked'] > 0) {
            $product->histories()->create([
                'action' => 'visual_checklist_status_updated',
                'field' => 'visual_checklist_status',
                'old_value' => null,
                'new_value' => null,
                'notes' => "Checklist status updated: {$checkedStatus['checked']} item(s) checked, {$checkedStatus['unchecked']} item(s) unchecked",
                'changed_by' => auth('web')->id(),
                'company_id' => $product->company_id,
            ]);
        }
    }

    /**
     * Track functional testing changes
     */
    private function trackFunctionalTestingChanges($product, $oldTesting, $newTesting)
    {
        // If no old testing exists, this is new testing
        if (! $oldTesting) {
            $product->histories()->create([
                'action' => 'functional_testing_created',
                'field' => 'functional_testing',
                'old_value' => null,
                'new_value' => json_encode([
                    'grade' => $newTesting->grade,
                    'summary' => $newTesting->overall_results_summary,
                    'tests_count' => count($newTesting->custom_tests ?? []),
                ]),
                'notes' => "Functional testing created with grade {$newTesting->grade}",
                'changed_by' => auth('web')->id(),
                'company_id' => $product->company_id,
            ]);

            return;
        }

        // Track grade changes
        if ($oldTesting->grade != $newTesting->grade) {
            $product->histories()->create([
                'action' => 'functional_grade_updated',
                'field' => 'functional_grade',
                'old_value' => $oldTesting->grade ?? 'Not Set',
                'new_value' => $newTesting->grade ?? 'Not Set',
                'notes' => "Functional grade changed from {$oldTesting->grade} to {$newTesting->grade}",
                'changed_by' => auth('web')->id(),
                'company_id' => $product->company_id,
            ]);
        }

        // Track overall results summary changes
        if ($oldTesting->overall_results_summary != $newTesting->overall_results_summary) {
            $product->histories()->create([
                'action' => 'functional_summary_updated',
                'field' => 'functional_test_results',
                'old_value' => $oldTesting->overall_results_summary ?? '',
                'new_value' => $newTesting->overall_results_summary ?? '',
                'notes' => 'Functional test summary updated',
                'changed_by' => auth('web')->id(),
                'company_id' => $product->company_id,
            ]);
        }

        // Track custom tests changes
        // Normalize: flat string arrays (from technician submission) → associative arrays
        $normalizeTests = function ($tests) {
            $result = [];
            foreach ((array) $tests as $key => $test) {
                if (is_string($test)) {
                    $normalized = strtolower(preg_replace('/[^a-z0-9]/i', '_', $test));
                    $result[$normalized] = ['name' => $test, 'notes' => ''];
                } else {
                    $result[$key] = $test;
                }
            }

            return $result;
        };

        $oldTests = $normalizeTests($oldTesting->custom_tests ?? []);
        $newTests = $normalizeTests($newTesting->custom_tests ?? []);

        $addedTests = $this->getAddedTests($oldTests, $newTests);
        $removedTests = $this->getRemovedTests($oldTests, $newTests);
        $updatedTests = $this->getUpdatedTests($oldTests, $newTests);

        foreach ($addedTests as $testKey => $test) {
            $product->histories()->create([
                'action' => 'functional_test_added',
                'field' => 'functional_custom_tests',
                'old_value' => null,
                'new_value' => json_encode($test),
                'notes' => "Added new test: {$test['name']}".(isset($test['notes']) ? " - {$test['notes']}" : ''),
                'changed_by' => auth('web')->id(),
                'company_id' => $product->company_id,
            ]);
        }

        foreach ($removedTests as $testKey => $test) {
            $product->histories()->create([
                'action' => 'functional_test_removed',
                'field' => 'functional_custom_tests',
                'old_value' => json_encode($test),
                'new_value' => null,
                'notes' => "Removed test: {$test['name']}" ?? null,
                'changed_by' => auth('web')->id(),
                'company_id' => $product->company_id,
            ]);
        }

        foreach ($updatedTests as $testKey => $update) {
            $product->histories()->create([
                'action' => 'functional_test_updated',
                'field' => 'functional_custom_tests',
                'old_value' => json_encode($update['old']),
                'new_value' => json_encode($update['new']),
                'notes' => "Updated test '{$update['new']['name']}'",
                'changed_by' => auth('web')->id(),
                'company_id' => $product->company_id,
            ]);
        }
    }

    /**
     * Track checklist checked/unchecked status
     */
    private function trackChecklistCheckedStatus($oldInspection, $newInspection)
    {
        // This assumes you have a way to track which checkboxes were checked
        // You might need to modify this based on how you store checked status
        $oldChecked = $oldInspection->checked_items ?? [];
        $newChecked = $newInspection->checked_items ?? [];

        $checkedCount = count(array_diff($newChecked, $oldChecked));
        $uncheckedCount = count(array_diff($oldChecked, $newChecked));

        return [
            'checked' => $checkedCount,
            'unchecked' => $uncheckedCount,
        ];
    }

    /**
     * Get added tests
     */
    private function getAddedTests($oldTests, $newTests)
    {
        $added = [];
        foreach ($newTests as $key => $test) {
            if (! isset($oldTests[$key])) {
                $added[$key] = $test;
            }
        }

        return $added;
    }

    /**
     * Get removed tests
     */
    private function getRemovedTests($oldTests, $newTests)
    {
        $removed = [];
        foreach ($oldTests as $key => $test) {
            if (! isset($newTests[$key])) {
                $removed[$key] = $test;
            }
        }

        return $removed;
    }

    /**
     * Get updated tests
     */
    private function getUpdatedTests($oldTests, $newTests)
    {
        $updated = [];
        foreach ($newTests as $key => $newTest) {
            if (isset($oldTests[$key])) {
                $oldTest = $oldTests[$key];
                if (($oldTest['name'] ?? '') != ($newTest['name'] ?? '') ||
                    ($oldTest['notes'] ?? '') != ($newTest['notes'] ?? '')) {
                    $updated[$key] = [
                        'old' => $oldTest,
                        'new' => $newTest,
                    ];
                }
            }
        }

        return $updated;
    }

    /**
     * Format field name for better readability
     */
    private function formatFieldName($field)
    {
        $formatted = str_replace('_', ' ', $field);
        $formatted = ucwords($formatted);

        $replacements = [
            'Id' => 'ID',
            'Assigned To' => 'Assigned Technician',
            'Work Flow Stage Id' => 'Workflow Stage',
            'Category Id' => 'Category',
            'Disposition Id' => 'Disposition',
            'Product Details' => 'Product Details',
            'Dpn Sn Number' => 'DPN/SN Number',
        ];

        foreach ($replacements as $search => $replace) {
            $formatted = str_replace($search, $replace, $formatted);
        }

        return $formatted;
    }

    /**
     * Export products as PDF.
     */
    public function exportPdf(Request $request)
    {
        try {
            $filters = $request->all();
            $companyId = auth('web')->user()?->company_id;
            $exporter = new WarehouseProductExport($filters, $companyId);
            $products = $exporter->collection();
            $columns = $exporter->columns();
            $headings = $exporter->headings();

            $pdf = Pdf::loadView('pdf.products-export', compact('products', 'filters', 'exporter', 'columns', 'headings'));
            $pdf->setPaper('A4', 'landscape');

            return $pdf->download('products-'.now()->format('Y-m-d').'.pdf');

        } catch (\Exception $e) {
            Log::error('ProductController@exportPdf: '.$e->getMessage());

            return redirect()->back()->with('error', 'PDF export failed: '.$e->getMessage());
        }
    }

    /**
     * Export products as CSV.
     */
    public function exportExcel(Request $request)
    {
        try {
            $companyId = auth('web')->user()?->company_id;
            $exporter = new WarehouseProductExport($request->all(), $companyId);
            $products = $exporter->collection();
            $filename = 'products-'.now()->format('Y-m-d').'.csv';

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $callback = function () use ($products, $exporter) {
                $file = fopen('php://output', 'w');
                fputcsv($file, $exporter->headings());

                foreach ($products as $product) {
                    fputcsv($file, $exporter->map($product));
                }
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('ProductController@exportExcel: '.$e->getMessage());

            return redirect()->back()->with('error', 'CSV export failed: '.$e->getMessage());
        }
    }

    /**
     * Download a blank partial-update template for the selected products.
     */
    public function downloadBulkUpdateTemplate(Request $request)
    {
        $validated = $request->validate([
            'product_ids' => ['required', 'array', 'min:1', 'max:500'],
            'product_ids.*' => ['required', 'integer', 'distinct'],
        ]);

        $products = Product::query()
            ->whereIn('id', $validated['product_ids'])
            ->orderBy('id')
            ->get(['id', 'unique_id', 'specifications']);

        if ($products->count() !== count($validated['product_ids'])) {
            return back()->with('error', 'One or more selected products were not found for your company.');
        }

        return Excel::download(
            new ProductBulkUpdateTemplateExport($products),
            'product-bulk-update-'.now()->format('Y-m-d-His').'.xlsx'
        );
    }

    /**
     * Apply only populated cells from a product bulk-update workbook.
     */
    public function importBulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        try {
            $user = auth('web')->user();
            if (! $user?->company_id) {
                return response()->json(['success' => false, 'message' => 'A company is required to import product updates.'], 422);
            }

            $import = new ProductBulkUpdateImport((int) $user->company_id, (int) $user->id);
            Excel::import($import, $validated['file']);

            if ($import->errors() !== []) {
                return response()->json([
                    'success' => false,
                    'message' => 'No products were updated. Please correct the workbook errors and upload it again.',
                    'errors' => $import->errors(),
                ], 422);
            }

            if ($import->updatedCount() === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No populated changes were found in the Products sheet.',
                    'errors' => [],
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => "Updated {$import->updatedCount()} product(s) successfully. {$import->unchangedCount()} row(s) had no changes.",
            ]);
        } catch (\Throwable $e) {
            Log::error('ProductController@importBulkUpdate: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'The product update import failed. Please verify the file and try again.',
            ], 500);
        }
    }
}
