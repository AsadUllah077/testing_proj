<?php
// app/Http/Controllers/Product/Warehouse/MasterData/WorkflowStageController.php

namespace App\Http\Controllers\Product\Warehouse\MasterData;

use App\Http\Controllers\Controller;
use App\Models\WorkflowStage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
class WorkflowStageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            // Get search parameters
            $search = strtolower($request->input('q'));
            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');

            // Get per_page from request or use default
            $perPage = $request->input('per_page', 20);

            // Start query with relationships
            $query = WorkflowStage::with(['company'])
                ->select('workflow_stages.*')
                ->latest('workflow_stages.created_at');

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
                    $q->whereRaw('LOWER(name) LIKE ?', "%{$search}%")
                        ->orWhereHas('company', function ($subQuery) use ($search) {
                            $subQuery->whereRaw('LOWER(name) LIKE ?', "%{$search}%");
                        });
                });
            }

            // Apply date filters
            if (!empty($fromDate)) {
                $query->whereDate('workflow_stages.created_at', '>=', $fromDate);
            }

            if (!empty($toDate)) {
                $query->whereDate('workflow_stages.created_at', '<=', $toDate);
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
            $stages = $query->paginate($perPage)->withQueryString()->onEachSide(1);

            // Get companies for filter dropdown
            $companies = companies(); // Assuming you have this helper function

            if ($request->ajax()) {
                return response()->json([
                    'html' => view('pages.products.warehouse.masterdata.workflow-stage.partials.table', compact('stages'))->render(),
                    'pagination' => $stages->links()->toHtml(),
                ]);
            }

            return view('pages.products.warehouse.masterdata.workflow-stage.index', compact('stages', 'companies'));

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Error fetching workflow stages: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return redirect()->back()->with('error', 'Error fetching workflow stages: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $companies = companies(); // Or use: Company::all();

        return view('pages.products.warehouse.masterdata.workflow-stage.create', compact('companies'));
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
                            $exists = WorkflowStage::whereRaw('LOWER(name) = ?', [strtolower($value)])
                                ->where('company_id', $companyId)
                                ->exists();
                            if ($exists) {
                                $fail('Workflow stage already exists for this company.');
                            }
                        },
                    ],
                ],
                [
                    'name.required' => 'Workflow stage name is required.',
                    'name.string' => 'Workflow stage name must be a valid string.',
                    'name.max' => 'Workflow stage name must not exceed 255 characters.',
                ]
            );

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $stage = WorkflowStage::create([
                'name' => $request->name,
                'company_id' => $companyId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Workflow stage created successfully!',
                'data' => $stage,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating workflow stage: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        try {
            $stage = WorkflowStage::findOrFail($id);
            $companies = companies(); // Or use: Company::all();

            return view('pages.products.warehouse.masterdata.workflow-stage.edit', compact('stage', 'companies'));

        } catch (\Exception $e) {
            return redirect()->route('warehouse.master-data.workflow-stages.index')
                ->with('error', 'Workflow stage not found.');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            // Find the stage
            $stage = WorkflowStage::findOrFail($id);

            // Get current user's company ID
            $companyId = Auth::user()->company_id;

            // Validate input
            $validator = Validator::make($request->all(), [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    function ($attribute, $value, $fail) use ($id, $companyId) {
                        $exists = WorkflowStage::whereRaw('LOWER(name) = ?', [strtolower($value)])
                            ->where('company_id', $companyId)
                            ->where('id', '!=', $id)
                            ->exists();

                        if ($exists) {
                            $fail('Workflow stage name already exists for this company.');
                        }
                    },
                ],
            ], [
                'name.required' => 'Workflow stage name is required.',
                'name.string' => 'Workflow stage name must be a valid string.',
                'name.max' => 'Workflow stage name must not exceed 255 characters.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Update stage
            $stage->update([
                'name' => $request->name,
                'company_id' => $companyId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Workflow stage updated successfully!',
                'data' => $stage,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating workflow stage: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $stage = WorkflowStage::findOrFail($id);

            // Optional: Check if stage has records before deleting
            // if ($stage->workflows()->exists()) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Cannot delete workflow stage. It has workflows assigned.',
            //     ], 400);
            // }

            $stage->delete();

            return response()->json([
                'success' => true,
                'message' => 'Workflow stage deleted successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting workflow stage: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Restore soft deleted workflow stage.
     */
    public function restore($id)
    {
        try {
            $stage = WorkflowStage::withTrashed()->findOrFail($id);
            $stage->restore();

            return response()->json([
                'success' => true,
                'message' => 'Workflow stage restored successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error restoring workflow stage: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Force delete workflow stage.
     */
    public function forceDelete($id)
    {
        try {
            $stage = WorkflowStage::withTrashed()->findOrFail($id);
            $stage->forceDelete();

            return response()->json([
                'success' => true,
                'message' => 'Workflow stage permanently deleted!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error permanently deleting workflow stage: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get workflow stages by company (for AJAX requests)
     */
    public function getByCompany($companyId)
    {
        try {
            $stages = WorkflowStage::where('company_id', $companyId)
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $stages,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching workflow stages: ' . $e->getMessage(),
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
                    'message' => 'No workflow stages selected for deletion.',
                ], 400);
            }

            WorkflowStage::whereIn('id', $ids)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Selected workflow stages deleted successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting workflow stages: ' . $e->getMessage(),
            ], 500);
        }
    }
}
