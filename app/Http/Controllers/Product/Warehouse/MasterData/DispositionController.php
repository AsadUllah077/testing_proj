<?php
// app/Http/Controllers/Product/Warehouse/MasterData/DispositionController.php

namespace App\Http\Controllers\Product\Warehouse\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Disposition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
class DispositionController extends Controller
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
            $query = Disposition::with(['company'])
                ->select('dispositions.*')
                ->latest('dispositions.created_at');

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
                $query->whereDate('dispositions.created_at', '>=', $fromDate);
            }

            if (!empty($toDate)) {
                $query->whereDate('dispositions.created_at', '<=', $toDate);
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
            $dispositions = $query->paginate($perPage)->withQueryString()->onEachSide(1);

            // Get companies for filter dropdown
            $companies = companies(); // Assuming you have this helper function

            if ($request->ajax()) {
                return response()->json([
                    'html' => view('pages.products.warehouse.masterdata.disposition.partials.table', compact('dispositions'))->render(),
                    'pagination' => $dispositions->links()->toHtml(),
                ]);
            }

            return view('pages.products.warehouse.masterdata.disposition.index', compact('dispositions', 'companies'));

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Error fetching dispositions: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return redirect()->back()->with('error', 'Error fetching dispositions: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $companies = companies(); // Or use: Company::all();

        return view('pages.products.warehouse.masterdata.disposition.create', compact('companies'));
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
                            $exists = Disposition::whereRaw('LOWER(name) = ?', [strtolower($value)])
                                ->where('company_id', $companyId)
                                ->exists();
                            if ($exists) {
                                $fail('Disposition already exists for this company.');
                            }
                        },
                    ],
                ],
                [
                    'name.required' => 'Disposition name is required.',
                    'name.string' => 'Disposition name must be a valid string.',
                    'name.max' => 'Disposition name must not exceed 255 characters.',
                ]
            );

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $disposition = Disposition::create([
                'name' => $request->name,
                'company_id' => $companyId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Disposition created successfully!',
                'data' => $disposition,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating disposition: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        try {
            $disposition = Disposition::findOrFail($id);
            $companies = companies(); // Or use: Company::all();

            return view('pages.products.warehouse.masterdata.disposition.edit', compact('disposition', 'companies'));

        } catch (\Exception $e) {
            return redirect()->route('warehouse.master-data.dispositions.index')
                ->with('error', 'Disposition not found.');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            // Find the disposition
            $disposition = Disposition::findOrFail($id);

            // Get current user's company ID
            $companyId = Auth::user()->company_id;

            // Validate input
            $validator = Validator::make($request->all(), [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    function ($attribute, $value, $fail) use ($id, $companyId) {
                        $exists = Disposition::whereRaw('LOWER(name) = ?', [strtolower($value)])
                            ->where('company_id', $companyId)
                            ->where('id', '!=', $id)
                            ->exists();

                        if ($exists) {
                            $fail('Disposition name already exists for this company.');
                        }
                    },
                ],
            ], [
                'name.required' => 'Disposition name is required.',
                'name.string' => 'Disposition name must be a valid string.',
                'name.max' => 'Disposition name must not exceed 255 characters.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Update disposition
            $disposition->update([
                'name' => $request->name,
                'company_id' => $companyId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Disposition updated successfully!',
                'data' => $disposition,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating disposition: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $disposition = Disposition::findOrFail($id);

            // Optional: Check if disposition has records before deleting
            // if ($disposition->records()->exists()) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Cannot delete disposition. It has records assigned.',
            //     ], 400);
            // }

            $disposition->delete();

            return response()->json([
                'success' => true,
                'message' => 'Disposition deleted successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting disposition: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Restore soft deleted disposition.
     */
    public function restore($id)
    {
        try {
            $disposition = Disposition::withTrashed()->findOrFail($id);
            $disposition->restore();

            return response()->json([
                'success' => true,
                'message' => 'Disposition restored successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error restoring disposition: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Force delete disposition.
     */
    public function forceDelete($id)
    {
        try {
            $disposition = Disposition::withTrashed()->findOrFail($id);
            $disposition->forceDelete();

            return response()->json([
                'success' => true,
                'message' => 'Disposition permanently deleted!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error permanently deleting disposition: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get dispositions by company (for AJAX requests)
     */
    public function getByCompany($companyId)
    {
        try {
            $dispositions = Disposition::where('company_id', $companyId)
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $dispositions,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching dispositions: ' . $e->getMessage(),
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
                    'message' => 'No dispositions selected for deletion.',
                ], 400);
            }

            Disposition::whereIn('id', $ids)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Selected dispositions deleted successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting dispositions: ' . $e->getMessage(),
            ], 500);
        }
    }
}
