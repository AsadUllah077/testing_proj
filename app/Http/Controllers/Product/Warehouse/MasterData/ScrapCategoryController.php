<?php
// app/Http/Controllers/Product/Warehouse/MasterData/ScrapCategoryController.php

namespace App\Http\Controllers\Product\Warehouse\MasterData;

use App\Http\Controllers\Controller;
use App\Models\ScrapCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
class ScrapCategoryController extends Controller
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
            $query = ScrapCategory::with(['company'])
                ->select('scrap_categories.*')
                ->latest('scrap_categories.created_at');

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
                $query->whereDate('scrap_categories.created_at', '>=', $fromDate);
            }

            if (!empty($toDate)) {
                $query->whereDate('scrap_categories.created_at', '<=', $toDate);
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
            $scrapCategories = $query->paginate($perPage)->withQueryString()->onEachSide(1);

            // Get companies for filter dropdown
            $companies = companies(); // Assuming you have this helper function

            if ($request->ajax()) {
                return response()->json([
                    'html' => view('pages.products.warehouse.masterdata.scrap-category.partials.table', compact('scrapCategories'))->render(),
                    'pagination' => $scrapCategories->links()->toHtml(),
                ]);
            }

            return view('pages.products.warehouse.masterdata.scrap-category.index', compact('scrapCategories', 'companies'));

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Error fetching scrap categories: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return redirect()->back()->with('error', 'Error fetching scrap categories: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $companies = companies(); // Or use: Company::all();

        return view('pages.products.warehouse.masterdata.scrap-category.create', compact('companies'));
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
                            $exists = ScrapCategory::whereRaw('LOWER(name) = ?', [strtolower($value)])
                                ->where('company_id', $companyId)
                                ->exists();
                            if ($exists) {
                                $fail('Scrap category already exists for this company.');
                            }
                        },
                    ],
                ],
                [
                    'name.required' => 'Scrap category name is required.',
                    'name.string' => 'Scrap category name must be a valid string.',
                    'name.max' => 'Scrap category name must not exceed 255 characters.',
                ]
            );

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $scrapCategory = ScrapCategory::create([
                'name' => $request->name,
                'company_id' => $companyId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Scrap category created successfully!',
                'data' => $scrapCategory,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating scrap category: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        try {
            $scrapCategory = ScrapCategory::findOrFail($id);
            $companies = companies(); // Or use: Company::all();

            return view('pages.products.warehouse.masterdata.scrap-category.edit', compact('scrapCategory', 'companies'));

        } catch (\Exception $e) {
            return redirect()->route('warehouse.master-data.scrap-category.index')
                ->with('error', 'Scrap category not found.');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            // Find the scrap category
            $scrapCategory = ScrapCategory::findOrFail($id);

            // Get current user's company ID
            $companyId = Auth::user()->company_id;

            // Validate input
            $validator = Validator::make($request->all(), [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    function ($attribute, $value, $fail) use ($id, $companyId) {
                        $exists = ScrapCategory::whereRaw('LOWER(name) = ?', [strtolower($value)])
                            ->where('company_id', $companyId)
                            ->where('id', '!=', $id)
                            ->exists();

                        if ($exists) {
                            $fail('Scrap category name already exists for this company.');
                        }
                    },
                ],
            ], [
                'name.required' => 'Scrap category name is required.',
                'name.string' => 'Scrap category name must be a valid string.',
                'name.max' => 'Scrap category name must not exceed 255 characters.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Update scrap category
            $scrapCategory->update([
                'name' => $request->name,
                'company_id' => $companyId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Scrap category updated successfully!',
                'data' => $scrapCategory,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating scrap category: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $scrapCategory = ScrapCategory::findOrFail($id);

            $scrapCategory->delete();

            return response()->json([
                'success' => true,
                'message' => 'Scrap category deleted successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting scrap category: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Restore soft deleted scrap category.
     */
    public function restore($id)
    {
        try {
            $scrapCategory = ScrapCategory::withTrashed()->findOrFail($id);
            $scrapCategory->restore();

            return response()->json([
                'success' => true,
                'message' => 'Scrap category restored successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error restoring scrap category: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Force delete scrap category.
     */
    public function forceDelete($id)
    {
        try {
            $scrapCategory = ScrapCategory::withTrashed()->findOrFail($id);
            $scrapCategory->forceDelete();

            return response()->json([
                'success' => true,
                'message' => 'Scrap category permanently deleted!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error permanently deleting scrap category: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get scrap categories by company (for AJAX requests)
     */
    public function getByCompany($companyId)
    {
        try {
            $scrapCategories = ScrapCategory::where('company_id', $companyId)
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $scrapCategories,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching scrap categories: ' . $e->getMessage(),
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
                    'message' => 'No scrap categories selected for deletion.',
                ], 400);
            }

            ScrapCategory::whereIn('id', $ids)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Selected scrap categories deleted successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting scrap categories: ' . $e->getMessage(),
            ], 500);
        }
    }
}
