<?php

namespace App\Http\Controllers\Product\Warehouse\MasterData;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
class CategoryController extends Controller
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
            $query = ProductCategory::with(['company'])
                ->select('product_categories.*')
                ->latest('product_categories.created_at');

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
                $query->whereDate('product_categories.created_at', '>=', $fromDate);
            }

            if (!empty($toDate)) {
                $query->whereDate('product_categories.created_at', '<=', $toDate);
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
            $categories = $query->paginate($perPage)->withQueryString()->onEachSide(1);

            // Get companies for filter dropdown
            $companies = companies(); // Assuming you have this helper function

            if ($request->ajax()) {
                return response()->json([
                    'html' => view('pages.products.warehouse.masterdata.category.partials.table', compact('categories'))->render(),
                    'pagination' => $categories->links()->toHtml(),
                ]);
            }

            return view('pages.products.warehouse.masterdata.category.index', compact('categories', 'companies'));

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Error fetching product categories: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return redirect()->back()->with('error', 'Error fetching product categories: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $companies = companies(); // Or use: Company::all();

        return view('pages.products.warehouse.masterdata.category.create', compact('companies'));
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
                            $exists = ProductCategory::whereRaw('LOWER(name) = ?', [strtolower($value)])
                                ->where('company_id', $companyId)
                                ->exists();
                            if ($exists) {
                                $fail('Category already exists for this company.');
                            }
                        },
                    ],
                ],
                [
                    'name.required' => 'Category name is required.',
                    'name.string' => 'Category name must be a valid string.',
                    'name.max' => 'Category name must not exceed 255 characters.',
                ]
            );

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $category = ProductCategory::create([
                'name' => $request->name,
                'company_id' => $companyId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Product category created successfully!',
                'data' => $category,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating product category: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $category = ProductCategory::with(['company'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $category,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product category not found: ' . $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        try {
            $category = ProductCategory::findOrFail($id);

            return view('pages.products.warehouse.masterdata.category.edit', compact('category'));

        } catch (\Exception $e) {
            return redirect()->route('warehouse.master-data.category.index')
                ->with('error', 'Product category not found.');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            // Find the category
            $category = ProductCategory::findOrFail($id);

            // Get current user's company ID
            $companyId = Auth::user()->company_id;

            // Validate input
            $validator = Validator::make($request->all(), [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    function ($attribute, $value, $fail) use ($id, $companyId) {
                        $exists = ProductCategory::whereRaw('LOWER(name) = ?', [strtolower($value)])
                            ->where('company_id', $companyId)
                            ->where('id', '!=', $id)
                            ->exists();

                        if ($exists) {
                            $fail('Category name already exists for this company.');
                        }
                    },
                ],
            ], [
                'name.required' => 'Category name is required.',
                'name.string' => 'Category name must be a valid string.',
                'name.max' => 'Category name must not exceed 255 characters.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Update category
            $category->update([
                'name' => $request->name,
                'company_id' => $companyId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Product category updated successfully!',
                'data' => $category,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating product category: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $category = ProductCategory::findOrFail($id);

            // Optional: Check if category has products before deleting
            // if ($category->products()->exists()) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Cannot delete category. It has products assigned.',
            //     ], 400);
            // }

            $category->delete();

            return response()->json([
                'success' => true,
                'message' => 'Product category deleted successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting product category: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Restore soft deleted category.
     */
    public function restore($id)
    {
        try {
            $category = ProductCategory::withTrashed()->findOrFail($id);
            $category->restore();

            return response()->json([
                'success' => true,
                'message' => 'Product category restored successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error restoring product category: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Force delete category.
     */
    public function forceDelete($id)
    {
        try {
            $category = ProductCategory::withTrashed()->findOrFail($id);
            $category->forceDelete();

            return response()->json([
                'success' => true,
                'message' => 'Product category permanently deleted!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error permanently deleting product category: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get categories by company (for AJAX requests)
     */
    public function getByCompany($companyId)
    {
        try {
            $categories = ProductCategory::where('company_id', $companyId)
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $categories,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching categories: ' . $e->getMessage(),
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
                    'message' => 'No categories selected for deletion.',
                ], 400);
            }

            ProductCategory::whereIn('id', $ids)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Selected product categories deleted successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting product categories: ' . $e->getMessage(),
            ], 500);
        }
    }
}
