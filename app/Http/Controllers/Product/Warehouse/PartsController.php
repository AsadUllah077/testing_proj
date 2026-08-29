<?php

namespace App\Http\Controllers\Product\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Part;
use App\Models\Product;
use App\Models\WarehouseLocation;
use App\Services\Warehouse\InventoryMovementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PartsController extends Controller
{
    public function __construct(private readonly InventoryMovementService $inventoryMovements) {}

    /**
     * Display a listing of parts.
     */
    public function index(Request $request)
    {
        try {
            $query = Part::with(['createdBy', 'company', 'warehouseLocation']);

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('category')) {
                $query->where('category', $request->category);
            }

            if ($request->filled('location')) {
                $query->where('location', $request->location);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('part_number', 'LIKE', "%{$search}%")
                        ->orWhere('description', 'LIKE', "%{$search}%")
                        ->orWhere('category', 'LIKE', "%{$search}%");
                });
            }

            $query->orderBy($request->get('sort_by', 'created_at'), $request->get('sort_dir', 'desc'));

            $perPage = $request->get('per_page', 20);
            $parts = $query->paginate($perPage);

            $categories = Part::distinct('category')->whereNotNull('category')->pluck('category');
            $locations = WarehouseLocation::get();
            $statuses = ['IN_STOCK', 'LOW_STOCK', 'OUT_OF_STOCK', 'REQUESTED', 'RESERVED'];

            $stats = [
                'total_parts' => Part::count(),
                'total_quantity' => Part::sum('quantity'),
                'low_stock_count' => Part::where('quantity', '>', 0)
                    ->where('reorder_level', '>', 0)
                    ->whereColumn('quantity', '<=', 'reorder_level')
                    ->count(),
                'out_of_stock_count' => Part::where('quantity', '<=', 0)->count(),
            ];

            if ($request->ajax()) {
                $html = view('pages.products.warehouse.parts.partials.table', compact('parts'))->render();
                $pagination = $parts->appends($request->query())->links()->toHtml();

                return response()->json([
                    'success' => true,
                    'html' => $html,
                    'pagination' => $pagination,
                    'stats' => $stats,
                ]);
            }

            return view('pages.products.warehouse.parts.list', compact('parts', 'categories', 'locations', 'statuses', 'stats'));

        } catch (\Exception $e) {
            Log::error('PartController@index Error: '.$e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error loading parts: '.$e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Error loading parts: '.$e->getMessage());
        }
    }

    /**
     * Show form for creating a new part.
     */
    public function create()
    {
        $locations = WarehouseLocation::orderBy('name')->get();

        return view('pages.products.warehouse.parts.create', compact('locations'));
    }

    /**
     * Store a newly created part.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'category' => 'nullable|string|max:100',
                'warehouse_location_id' => 'required|string|max:255',
                'rack_position' => 'nullable|string|max:50',
                'shelf' => 'nullable|string|max:50',
                'quantity' => 'required|integer|min:0',
                'min_stock_level' => 'nullable|integer|min:0',
                'reorder_level' => 'nullable|integer|min:0',
            ]);

            DB::beginTransaction();

            $data = $request->all();
            $data['created_by'] = auth('web')->id();
            $data['company_id'] = auth('web')->user()?->company_id;
            $data['part_number'] = 'PART-'.strtoupper(uniqid());
            $data['warehouse_location_id'] = $request->warehouse_location_id;

            // Set initial status based on quantity
            if ($request->quantity <= 0) {
                $data['status'] = 'OUT_OF_STOCK';
            } elseif ($request->reorder_level && $request->quantity <= $request->reorder_level) {
                $data['status'] = 'LOW_STOCK';
            } else {
                $data['status'] = 'IN_STOCK';
            }

            $part = Part::create($data);

            // Create initial movement record
            if ($part->quantity > 0) {
                $part->movements()->create([
                    'movement_type' => 'IN',
                    'quantity' => $part->quantity,
                    'before_quantity' => 0,
                    'after_quantity' => $part->quantity,
                    'reason' => 'Initial stock',
                    'created_by' => auth('web')->id(),
                    'company_id' => $part->company_id,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Part created successfully!',
                'data' => $part->load('movements'),
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PartController@store Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create part',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified part.
     */
    public function show($id)
    {
        try {
            $part = Part::with([
                'createdBy',
                'warehouseLocation',
                'movements' => function ($q) {
                    $q->with('createdBy', 'product')->latest()->limit(50);
                },
                'products' => function ($q) {
                    $q->withPivot('quantity', 'notes', 'added_by', 'created_at');
                },
            ])->findOrFail($id);

            $totalUsed = $part->movements()
                ->where('movement_type', 'OUT')
                ->sum('quantity');

            $totalReceived = $part->movements()
                ->where('movement_type', 'IN')
                ->sum('quantity');

            $recentUsage = $part->products()
                ->withPivot('quantity', 'created_at')
                ->orderBy('product_part.created_at', 'desc')
                ->limit(10)
                ->get();

            return view('pages.products.warehouse.parts.detail', compact('part', 'totalUsed', 'totalReceived', 'recentUsage'));

        } catch (\Exception $e) {
            Log::error('PartController@show Error: '.$e->getMessage());

            return redirect()->route('warehouse.parts.index')
                ->with('error', 'Part not found: '.$e->getMessage());
        }
    }

    /**
     * Show form for editing a part.
     */
    public function edit($id)
    {
        try {
            $part = Part::with('warehouseLocation')->findOrFail($id);
            $locations = WarehouseLocation::orderBy('name')->get();

            return view('pages.products.warehouse.parts.edit', compact('part', 'locations'));

        } catch (\Exception $e) {
            Log::error('PartController@edit Error: '.$e->getMessage());

            return redirect()->route('warehouse.parts.index')
                ->with('error', 'Part not found: '.$e->getMessage());
        }
    }

    /**
     * Update the specified part.
     */
    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'category' => 'nullable|string|max:100',
                'warehouse_location_id' => 'required|string|max:255',
                'rack_position' => 'nullable|string|max:50',
                'shelf' => 'nullable|string|max:50',
                'quantity' => 'required|integer|min:0',
                'min_stock_level' => 'nullable|integer|min:0',
                'reorder_level' => 'nullable|integer|min:0',
            ]);

            DB::beginTransaction();

            $part = Part::findOrFail($id);
            $oldQuantity = $part->quantity;
            $oldLocation = $part->location;

            $updateData = $request->except(['part_number', 'status']);

            // Update status based on new quantity
            if ($request->quantity <= 0) {
                $updateData['status'] = 'OUT_OF_STOCK';
            } elseif ($request->reorder_level && $request->quantity <= $request->reorder_level) {
                $updateData['status'] = 'LOW_STOCK';
            } else {
                $updateData['status'] = 'IN_STOCK';
            }

            $part->update($updateData);

            // Create movement record if quantity changed
            if ($oldQuantity != $part->quantity) {
                $movementType = $part->quantity > $oldQuantity ? 'IN' : 'OUT';
                $quantityChange = abs($part->quantity - $oldQuantity);

                $part->movements()->create([
                    'movement_type' => $movementType,
                    'quantity' => $quantityChange,
                    'before_quantity' => $oldQuantity,
                    'after_quantity' => $part->quantity,
                    'reason' => 'Manual adjustment',
                    'created_by' => auth('web')->id(),
                    'company_id' => $part->company_id,
                ]);
            }

            // Create transfer record if location changed
            if ($oldLocation != $part->location) {
                $part->movements()->create([
                    'movement_type' => 'TRANSFER',
                    'quantity' => $part->quantity,
                    'from_location' => $oldLocation,
                    'to_location' => $part->location,
                    'before_quantity' => $part->quantity,
                    'after_quantity' => $part->quantity,
                    'reason' => 'Location update',
                    'created_by' => auth('web')->id(),
                    'company_id' => $part->company_id,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Part updated successfully!',
                'data' => $part->load('movements'),
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Part not found',
                'error' => 'The requested part does not exist',
            ], 404);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PartController@update Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update part',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified part.
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $part = Part::findOrFail($id);

            if ($part->products()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete part that is used in products',
                ], 400);
            }

            $part->movements()->delete();
            $part->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Part deleted successfully!',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PartController@destroy Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete part: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add part to product.
     */
    public function addToProduct(Request $request)
    {
        $request->validate([
            'part_id' => 'required|exists:parts,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $part = Part::findOrFail($request->part_id);
            $product = Product::findOrFail($request->product_id);

            if ($part->quantity < $request->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock. Available: '.$part->quantity,
                ], 400);
            }

            // Check if product already has this part
            $existingPivot = $product->parts()->where('part_id', $part->id)->first();

            if ($existingPivot) {
                $newQuantity = $existingPivot->pivot->quantity + $request->quantity;
                $product->parts()->updateExistingPivot($part->id, [
                    'quantity' => $newQuantity,
                    'notes' => $request->notes,
                    'added_by' => auth('web')->id(),
                    'updated_at' => now(),
                ]);
            } else {
                $product->parts()->attach($part->id, [
                    'quantity' => $request->quantity,
                    'notes' => $request->notes,
                    'added_by' => auth('web')->id(),
                    'company_id' => $part->company_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Remove stock from part
            $part->removeStock(
                $request->quantity,
                'Added to product: '.$product->unique_id,
                'product',
                $product->id
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Part added to product successfully!',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PartController@addToProduct Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to add part to product: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Transfer part to new location.
     */
    public function transfer(Request $request, $id)
    {
        $request->validate([
            'to_location' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string',
        ]);

        try {
            $part = Part::findOrFail($id);
            $this->inventoryMovements->transferPart(
                $part,
                $request->to_location,
                (int) $request->quantity,
                $request->reason
            );

            return response()->json([
                'success' => true,
                'message' => 'Part transferred successfully!',
            ]);

        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            Log::error('PartController@transfer Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to transfer part: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get part movements.
     */
    public function movements($id)
    {
        try {
            $part = Part::findOrFail($id);
            $movements = $part->movements()
                ->with(['product', 'createdBy'])
                ->latest()
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $movements,
            ]);

        } catch (\Exception $e) {
            Log::error('PartController@movements Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to load movements: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk delete parts.
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:parts,id',
        ]);

        try {
            DB::beginTransaction();

            $parts = Part::whereIn('id', $request->ids)->get();
            $deletedCount = 0;
            $skippedCount = 0;

            foreach ($parts as $part) {
                if ($part->products()->count() === 0) {
                    $part->movements()->delete();
                    $part->delete();
                    $deletedCount++;
                } else {
                    $skippedCount++;
                }
            }

            DB::commit();

            $message = "Successfully deleted {$deletedCount} parts";
            if ($skippedCount > 0) {
                $message .= ". {$skippedCount} parts skipped (used in products)";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PartController@bulkDelete Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete parts: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export parts to PDF.
     */
    public function exportPDF(Request $request)
    {
        try {
            $parts = Part::with(['createdBy'])->get();

            // You can implement PDF generation here
            // For now, return a JSON response

            return response()->json([
                'success' => false,
                'message' => 'PDF export coming soon',
            ], 501);

        } catch (\Exception $e) {
            Log::error('PartController@exportPDF Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to export PDF: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export parts to Excel.
     */
    public function exportExcel(Request $request)
    {
        try {
            $parts = Part::with(['createdBy'])->get();

            // You can implement Excel export here
            // For now, return a JSON response

            return response()->json([
                'success' => false,
                'message' => 'Excel export coming soon',
            ], 501);

        } catch (\Exception $e) {
            Log::error('PartController@exportExcel Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to export Excel: '.$e->getMessage(),
            ], 500);
        }
    }
}
