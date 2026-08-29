<?php
// app/Http/Controllers/Product/Warehouse/MasterData/ScrapTypeController.php

namespace App\Http\Controllers\Product\Warehouse\MasterData;

use App\Http\Controllers\Controller;
use App\Models\ScrapType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
class ScrapTypeController extends Controller
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
            $query = ScrapType::with(['company'])
                ->select('scrap_types.*')
                ->latest('scrap_types.created_at');

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
                $query->whereDate('scrap_types.created_at', '>=', $fromDate);
            }

            if (!empty($toDate)) {
                $query->whereDate('scrap_types.created_at', '<=', $toDate);
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
            $scrapTypes = $query->paginate($perPage)->withQueryString()->onEachSide(1);

            // Get companies for filter dropdown
            $companies = companies(); // Assuming you have this helper function

            if ($request->ajax()) {
                return response()->json([
                    'html' => view('pages.products.warehouse.masterdata.scrap-type.partials.table', compact('scrapTypes'))->render(),
                    'pagination' => $scrapTypes->links()->toHtml(),
                ]);
            }

            return view('pages.products.warehouse.masterdata.scrap-type.index', compact('scrapTypes', 'companies'));

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Error fetching scrap types: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return redirect()->back()->with('error', 'Error fetching scrap types: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $companies = companies(); // Or use: Company::all();

        return view('pages.products.warehouse.masterdata.scrap-type.create', compact('companies'));
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
                            $exists = ScrapType::whereRaw('LOWER(name) = ?', [strtolower($value)])
                                ->where('company_id', $companyId)
                                ->exists();
                            if ($exists) {
                                $fail('Scrap type already exists for this company.');
                            }
                        },
                    ],
                ],
                [
                    'name.required' => 'Scrap type name is required.',
                    'name.string' => 'Scrap type name must be a valid string.',
                    'name.max' => 'Scrap type name must not exceed 255 characters.',
                ]
            );

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $scrapType = ScrapType::create([
                'name' => $request->name,
                'company_id' => $companyId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Scrap type created successfully!',
                'data' => $scrapType,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating scrap type: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        try {
            $scrapType = ScrapType::findOrFail($id);
            $companies = companies(); // Or use: Company::all();

            return view('pages.products.warehouse.masterdata.scrap-type.edit', compact('scrapType', 'companies'));

        } catch (\Exception $e) {
            return redirect()->route('warehouse.master-data.scrap-types.index')
                ->with('error', 'Scrap type not found.');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            // Find the scrap type
            $scrapType = ScrapType::findOrFail($id);

            // Get current user's company ID
            $companyId = Auth::user()->company_id;

            // Validate input
            $validator = Validator::make($request->all(), [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    function ($attribute, $value, $fail) use ($id, $companyId) {
                        $exists = ScrapType::whereRaw('LOWER(name) = ?', [strtolower($value)])
                            ->where('company_id', $companyId)
                            ->where('id', '!=', $id)
                            ->exists();

                        if ($exists) {
                            $fail('Scrap type name already exists for this company.');
                        }
                    },
                ],
            ], [
                'name.required' => 'Scrap type name is required.',
                'name.string' => 'Scrap type name must be a valid string.',
                'name.max' => 'Scrap type name must not exceed 255 characters.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Update scrap type
            $scrapType->update([
                'name' => $request->name,
                'company_id' => $companyId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Scrap type updated successfully!',
                'data' => $scrapType,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating scrap type: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $scrapType = ScrapType::findOrFail($id);

            // Optional: Check if scrap type has records before deleting
            // if ($scrapType->scrapRecords()->exists()) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Cannot delete scrap type. It has scrap records assigned.',
            //     ], 400);
            // }

            $scrapType->delete();

            return response()->json([
                'success' => true,
                'message' => 'Scrap type deleted successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting scrap type: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Restore soft deleted scrap type.
     */
    public function restore($id)
    {
        try {
            $scrapType = ScrapType::withTrashed()->findOrFail($id);
            $scrapType->restore();

            return response()->json([
                'success' => true,
                'message' => 'Scrap type restored successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error restoring scrap type: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Force delete scrap type.
     */
    public function forceDelete($id)
    {
        try {
            $scrapType = ScrapType::withTrashed()->findOrFail($id);
            $scrapType->forceDelete();

            return response()->json([
                'success' => true,
                'message' => 'Scrap type permanently deleted!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error permanently deleting scrap type: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get scrap types by company (for AJAX requests)
     */
    public function getByCompany($companyId)
    {
        try {
            $scrapTypes = ScrapType::where('company_id', $companyId)
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $scrapTypes,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching scrap types: ' . $e->getMessage(),
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
                    'message' => 'No scrap types selected for deletion.',
                ], 400);
            }

            ScrapType::whereIn('id', $ids)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Selected scrap types deleted successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting scrap types: ' . $e->getMessage(),
            ], 500);
        }
    }
}
