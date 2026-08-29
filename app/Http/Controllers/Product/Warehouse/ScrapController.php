<?php

namespace App\Http\Controllers\Product\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\ScrapCategory;
use App\Models\ScrapMaterial;
use App\Models\ScrapType;
use App\Models\WarehouseLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ScrapController extends Controller
{
    /**
     * Display a listing of scrap materials.
     */
    public function index(Request $request)
    {
        try {
            $typeOfMaterial = $request->get('type_of_material');
            $source = $request->get('source');
            $search = $request->get('search');
            $perPage = $request->get('per_page', 20);

            $query = ScrapMaterial::with(['createdBy', 'scrapCategory', 'scrapType', 'warehouseLocation'])->latest();

            if ($typeOfMaterial) {
                $query->whereHas('scrapCategory', function ($q) use ($typeOfMaterial) {
                    $q->where('name', 'LIKE', "%{$typeOfMaterial}%");
                });
            }

            if ($source) {
                $query->where('source', $source);
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('scrap_id', 'LIKE', "%{$search}%")
                        ->orWhere('item_description', 'LIKE', "%{$search}%")
                        ->orWhere('source', 'LIKE', "%{$search}%")
                        ->orWhere('current_location', 'LIKE', "%{$search}%")
                        ->orWhereHas('scrapCategory', function ($sq) use ($search) {
                            $sq->where('name', 'LIKE', "%{$search}%");
                        })
                        ->orWhereHas('scrapType', function ($sq) use ($search) {
                            $sq->where('name', 'LIKE', "%{$search}%");
                        });
                });
            }

            $scraps = $query->paginate($perPage);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'html' => view('pages.products.warehouse.scrap.partials.table', compact('scraps'))->render(),
                ]);
            }

            return view('pages.products.warehouse.scrap.index', compact('scraps'));

        } catch (\Exception $e) {
            Log::error('ScrapController@index: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Error loading scrap materials.'], 500);
            }

            return redirect()->back()->with('error', 'Error loading scrap materials: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new scrap material.
     */
    public function create()
    {
        $scrapCategories = ScrapCategory::all();
        $scrapTypes = ScrapType::all();
        $locations = WarehouseLocation::orderBy('name')->get();

        return view('pages.products.warehouse.scrap.create', compact('scrapCategories', 'scrapTypes', 'locations'));
    }

    /**
     * Store a newly created scrap material.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_description' => 'required|string|max:255',
            'scrap_category_id' => 'required|exists:scrap_categories,id',
            'scrap_type_id' => 'required|exists:scrap_types,id',
            'weight' => 'required|numeric|min:0',
            'quantity' => 'required|numeric|min:0',
            'quantity_type' => 'required|string|max:100',
            'scrap_reason' => 'required|string',
            'source' => 'required|string|max:255',
            'warehouse_location_id' => 'required|integer',
            'notes' => 'nullable|string',
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

            $scrap = ScrapMaterial::create([
                'scrap_id' => $this->generateScrapId(),
                'item_description' => $request->item_description,
                'scrap_category_id' => $request->scrap_category_id,
                'scrap_type_id' => $request->scrap_type_id,
                'weight' => $request->weight,
                'quantity_type' => $request->quantity_type,
                'quantity' => $request->quantity,
                'scrap_reason' => $request->scrap_reason,
                'source' => $request->source,
                'warehouse_location_id' => $request->warehouse_location_id,
                'notes' => $request->notes,
                'status' => 'in_storage',
                'created_by' => auth('web')->id(),
                'company_id' => auth('web')->user()->company_id ?? null,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Scrap material created successfully.',
                'redirect' => route('warehouse.scrap.details', $scrap->id),
                'data' => $scrap,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ScrapController@store: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create scrap material: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified scrap material.
     */
    public function details($id)
    {
        try {
            $scrap = ScrapMaterial::with(['createdBy', 'company', 'scrapCategory', 'scrapType', 'warehouseLocation'])->findOrFail($id);

            return view('pages.products.warehouse.scrap.show', compact('scrap'));

        } catch (\Exception $e) {
            Log::error('ScrapController@details: ' . $e->getMessage());

            return redirect()->route('warehouse.scrap.index')->with('error', 'Scrap material not found.');
        }
    }

    /**
     * Show the form for editing the specified scrap material.
     */
    public function edit($id)
    {
        try {
            $scrap = ScrapMaterial::findOrFail($id);
            $scrapCategories = ScrapCategory::all();
            $scrapTypes = ScrapType::all();
            $locations = WarehouseLocation::orderBy('name')->get();


            if ($scrap->isDisposed()) {
                return redirect()->route('warehouse.scrap.details', $id)
                    ->with('error', 'Cannot edit a disposed scrap material.');
            }

            return view('pages.products.warehouse.scrap.edit', compact('scrap', 'scrapCategories', 'scrapTypes', 'locations'));

        } catch (\Exception $e) {
            Log::error('ScrapController@edit: ' . $e->getMessage());

            return redirect()->route('warehouse.scrap.index')
                ->with('error', 'Error loading edit form: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified scrap material.
     */
    public function update(Request $request, $id)
    {
        $scrap = ScrapMaterial::findOrFail($id);

        if ($scrap->isDisposed()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update a disposed scrap material.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'item_description' => 'required|string|max:255',
            'scrap_category_id' => 'required|exists:scrap_categories,id',
            'scrap_type_id' => 'required|exists:scrap_types,id',
            'weight' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'quantity_type' => 'required|string|max:100',
            'scrap_reason' => 'required|string',
            'source' => 'required|string|max:255',
            'warehouse_location_id' => 'required|integer',
            'notes' => 'nullable|string',
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

            $scrap->update([
                'item_description' => $request->item_description,
                'scrap_category_id' => $request->scrap_category_id,
                'scrap_type_id' => $request->scrap_type_id,
                'weight' => $request->weight,
                'quantity_type' => $request->quantity_type,
                'quantity' => $request->quantity,
                'scrap_reason' => $request->scrap_reason,
                'source' => $request->source,
                'warehouse_location_id' => $request->warehouse_location_id,
                'notes' => $request->notes,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Scrap material updated successfully.',
                'redirect' => route('warehouse.scrap.details', $scrap->id),
                'data' => $scrap,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ScrapController@update: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update scrap material: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a single scrap material (AJAX).
     */
    public function destroy($id)
    {
        try {
            $scrap = ScrapMaterial::findOrFail($id);

            if (!$scrap->canDelete()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete a disposed scrap material.',
                ], 400);
            }

            $scrap->delete();

            return response()->json(['success' => true, 'message' => 'Scrap material deleted successfully.']);

        } catch (\Exception $e) {
            Log::error('ScrapController@destroy: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to delete scrap material.'], 500);
        }
    }

    /**
     * Bulk delete scrap materials (AJAX).
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:scrap_materials,id',
        ]);

        try {
            DB::beginTransaction();

            $count = 0;
            ScrapMaterial::whereIn('id', $request->ids)->each(function ($scrap) use (&$count) {
                if ($scrap->canDelete()) {
                    $scrap->delete();
                    $count++;
                }
            });

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$count} scrap material(s).",
                'count' => $count,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ScrapController@bulkDelete: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to delete scrap materials.'], 500);
        }
    }

    /**
     * Export to PDF (stub – wire up with DomPDF or Snappy).
     */
    public function exportPDF(Request $request)
    {
        return redirect()->back()->with('info', 'PDF export coming soon.');
    }

    /**
     * Export to Excel (stub – wire up with Maatwebsite Excel).
     */
    public function exportExcel(Request $request)
    {
        return redirect()->back()->with('info', 'Excel export coming soon.');
    }

    /**
     * Export to CSV (stub).
     */
    public function exportCSV(Request $request)
    {
        return redirect()->back()->with('info', 'CSV export coming soon.');
    }

    /**
     * Generate a unique scrap ID  e.g.  SC-202502-001
     */
    private function generateScrapId(): string
    {
        $prefix = 'SC';
        $year = date('Y');
        $month = date('m');
        $base = "{$prefix}-{$year}{$month}-";

        $last = ScrapMaterial::where('scrap_id', 'LIKE', "{$base}%")
            ->orderBy('id', 'desc')
            ->value('scrap_id');

        $nextNum = $last ? (intval(substr($last, -3)) + 1) : 1;

        return $base . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
    }
}
