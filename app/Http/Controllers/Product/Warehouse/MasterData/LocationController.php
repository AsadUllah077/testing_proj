<?php
// app/Http/Controllers/Product/Warehouse/MasterData/LocationController.php

namespace App\Http\Controllers\Product\Warehouse\MasterData;

use App\Http\Controllers\Controller;
use App\Models\WarehouseLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
class LocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            // Get search parameters
            $search = $request->input('q');
            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');

            // Get per_page from request or use default
            $perPage = $request->input('per_page', 20);

            // Start query with relationships
            $query = WarehouseLocation::with(['company'])
                ->select('warehouse_locations.*')
                ->latest('warehouse_locations.created_at');

            // Get current user's company ID
            $currentCompanyId = Auth::user()->company_id ?? null;

            // If user has a company, filter by that company
            if ($currentCompanyId) {
                $query->where('company_id', $currentCompanyId);
            } elseif ($request->has('company_id') && !empty($request->company_id)) {
                // If user can view all companies, use the filter
                $query->where('company_id', $request->company_id);
            }

            // Apply search filter
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhereHas('company', function ($subQuery) use ($search) {
                            $subQuery->where('name', 'LIKE', "%{$search}%");
                        });
                });
            }

            // Apply date filters
            if (!empty($fromDate)) {
                $query->whereDate('warehouse_locations.created_at', '>=', $fromDate);
            }

            if (!empty($toDate)) {
                $query->whereDate('warehouse_locations.created_at', '<=', $toDate);
            }

            // Ordering
            if ($request->has('sort_by') && $request->has('sort_order')) {
                $sortBy = $request->sort_by;
                $sortOrder = $request->sort_order === 'desc' ? 'desc' : 'asc';

                // Validate sortable columns
                $sortableColumns = ['name', 'created_at'];
                if (in_array($sortBy, $sortableColumns)) {
                    $query->orderBy($sortBy, $sortOrder);
                }
            } else {
                $query->orderBy('name', 'asc');
            }

            // Paginate results
            $locations = $query->paginate($perPage)->withQueryString()->onEachSide(1);

            // Get companies for filter dropdown
            $companies = companies(); // Assuming you have this helper function

            if ($request->ajax()) {
                return response()->json([
                    'html' => view('pages.products.warehouse.masterdata.location.partials.table', compact('locations'))->render(),
                    'pagination' => $locations->links()->toHtml(),
                ]);
            }

            return view('pages.products.warehouse.masterdata.location.index', compact('locations', 'companies'));

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Error fetching warehouse locations: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return redirect()->back()->with('error', 'Error fetching warehouse locations: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pages.products.warehouse.masterdata.location.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Get current user's company ID
            $companyId = Auth::user()->company_id;

            $validator = Validator::make(
                $request->all(),
                [
                    'name' => [
                        'required',
                        'string',
                        'max:255',
                        function ($attribute, $value, $fail) use ($companyId) {
                            $exists = WarehouseLocation::whereRaw('LOWER(name) = ?', [strtolower($value)])
                                ->where('company_id', $companyId)
                                ->exists();
                            if ($exists) {
                                $fail('Location already exists for this company.');
                            }
                        },
                    ],
                ],
                [
                    'name.required' => 'Location name is required.',
                    'name.string' => 'Location name must be a valid string.',
                    'name.max' => 'Location name must not exceed 255 characters.',
                ]
            );

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $location = WarehouseLocation::create([
                'name' => $request->name,
                'company_id' => $companyId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Warehouse location created successfully!',
                'data' => $location,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating warehouse location: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $location = WarehouseLocation::with(['company'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $location,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Warehouse location not found: ' . $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        try {
            $location = WarehouseLocation::findOrFail($id);
            return view('pages.products.warehouse.masterdata.location.edit', compact('location'));

        } catch (\Exception $e) {
            return redirect()->route('warehouse.master-data.locations.index')
                ->with('error', 'Warehouse location not found.');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            // Find the location
            $location = WarehouseLocation::findOrFail($id);

            // Get current user's company ID
            $companyId = Auth::user()->company_id;

            // Validate input
            $validator = Validator::make($request->all(), [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    function ($attribute, $value, $fail) use ($id, $companyId) {
                        $exists = WarehouseLocation::whereRaw('LOWER(name) = ?', [strtolower($value)])
                            ->where('company_id', $companyId)
                            ->where('id', '!=', $id)
                            ->exists();

                        if ($exists) {
                            $fail('Location name already exists for this company.');
                        }
                    },
                ],
            ], [
                'name.required' => 'Location name is required.',
                'name.string' => 'Location name must be a valid string.',
                'name.max' => 'Location name must not exceed 255 characters.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Update location
            $location->update([
                'name' => $request->name,
                'company_id' => $companyId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Warehouse location updated successfully!',
                'data' => $location,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating warehouse location: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $location = WarehouseLocation::findOrFail($id);

            // Optional: Check if location has products before deleting
            // if ($location->products()->exists()) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Cannot delete location. It has products assigned.',
            //     ], 400);
            // }

            $location->delete();

            return response()->json([
                'success' => true,
                'message' => 'Warehouse location deleted successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting warehouse location: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Restore soft deleted location.
     */
    public function restore($id)
    {
        try {
            $location = WarehouseLocation::withTrashed()->findOrFail($id);
            $location->restore();

            return response()->json([
                'success' => true,
                'message' => 'Warehouse location restored successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error restoring warehouse location: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Force delete location.
     */
    public function forceDelete($id)
    {
        try {
            $location = WarehouseLocation::withTrashed()->findOrFail($id);
            $location->forceDelete();

            return response()->json([
                'success' => true,
                'message' => 'Warehouse location permanently deleted!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error permanently deleting warehouse location: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get locations by company (for AJAX requests)
     */
    public function getByCompany($companyId)
    {
        try {
            $locations = WarehouseLocation::where('company_id', $companyId)
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $locations,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching locations: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove multiple resources from storage.
     */
    public function bulkDelete(Request $request)
    {
        try {
            $ids = $request->input('ids');

            if (empty($ids) || !is_array($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No locations selected for deletion.',
                ], 400);
            }

            WarehouseLocation::whereIn('id', $ids)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Selected warehouse locations deleted successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting warehouse locations: ' . $e->getMessage(),
            ], 500);
        }
    }
}
