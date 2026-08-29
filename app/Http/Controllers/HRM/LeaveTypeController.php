<?php

namespace App\Http\Controllers\HRM;

use App\Http\Controllers\Controller;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class LeaveTypeController extends Controller
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
            $query = LeaveType::with(['company'])
                ->select('leave_types.*')
                ->latest('leave_types.created_at');

            // Apply search filter
            if (! empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhereHas('company', function ($subQuery) use ($search) {
                            $subQuery->where('name', 'LIKE', "%{$search}%");
                        });
                });
            }

            // Apply date filters
            if (! empty($fromDate)) {
                $query->whereDate('leave_types.created_at', '>=', $fromDate);
            }

            if (! empty($toDate)) {
                $query->whereDate('leave_types.created_at', '<=', $toDate);
            }

            // Filter by company if provided
            if ($request->has('company_id') && ! empty($request->company_id)) {
                $query->where('company_id', $request->company_id);
            }

            // Ordering
            if ($request->has('sort_by') && $request->has('sort_order')) {
                $sortBy = $request->sort_by;
                $sortOrder = $request->sort_order === 'desc' ? 'desc' : 'asc';

                // Validate sortable columns
                $sortableColumns = ['name', 'count', 'created_at'];
                if (in_array($sortBy, $sortableColumns)) {
                    $query->orderBy($sortBy, $sortOrder);
                }
            }

            // Paginate results
            $leaveTypes = $query->paginate($perPage)->withQueryString();

            if ($request->ajax()) {
                return response()->json([
                    'html' => view('pages.hrm.leave-types.partials.table', compact('leaveTypes'))->render(),
                    'pagination' => $leaveTypes->links()->toHtml(),
                ]);
            }

            return view('pages.hrm.leave-types.index', compact('leaveTypes'));

        } catch (\Exception $e) {
            Log::error('Error fetching leave types: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return redirect()->back()->with('error', 'Error fetching leave types: '.$e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pages.hrm.leave-types.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $currentYear = now()->year;

            $validator = Validator::make(
                $request->all(),
                [
                    'name' => [
                        'required',
                        'string',
                        'max:255',
                        Rule::unique('leave_types', 'name')
                            ->where(function ($query) use ($currentYear) {
                                return $query->where('company_id', Auth::user()->company_id)
                                    ->whereYear('created_at', $currentYear);
                            }),
                    ],
                    'count' => 'required|integer|min:0',
                    'per' => 'required',
                ],
                [
                    'name.required' => 'Leave type name is required.',
                    'name.unique' => 'Leave type already exists for this year.',
                    'name.string' => 'Leave type name must be a valid string.',
                    'name.max' => 'Leave type name must not exceed 255 characters.',
                    'count.required' => 'Leave count is required.',
                    'count.integer' => 'Leave count must be a number.',
                    'count.min' => 'Leave count cannot be negative.',
                ]
            );

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $leaveType = LeaveType::create([
                'name' => $request->name,
                'per' => $request->per,
                'count' => $request->count,
                'company_id' => Auth::user()->company_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Leave type created successfully!',
                'data' => $leaveType,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating leave type: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $leaveType = LeaveType::with(['company'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $leaveType,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Leave type not found: '.$e->getMessage(),
            ], 404);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        try {
            $leaveType = LeaveType::findOrFail($id);

            return view('pages.hrm.leave-types.edit', compact('leaveType'));

        } catch (\Exception $e) {
            return redirect()->route('hrm.leave-types.index')
                ->with('error', 'Leave type not found.');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            // Find the leave type
            $leaveType = LeaveType::findOrFail($id);

            $currentYear = $leaveType->created_at->year; // preserve original year

            // Validate input
            $validator = Validator::make(
                $request->all(),
                [
                    'name' => [
                        'required',
                        'string',
                        'max:255',
                        function ($attribute, $value, $fail) use ($id, $currentYear) {
                            $companyId = Auth::user()->company_id;

                            $exists = LeaveType::where('name', $value)
                                ->where('company_id', $companyId)
                                ->whereYear('created_at', $currentYear)
                                ->where('id', '!=', $id) // exclude current record
                                ->exists();

                            if ($exists) {
                                $fail('Leave type already exists for this year.');
                            }
                        },
                    ],
                    'count' => 'required|integer|min:0',
                    'per' => 'required',
                ],
                [
                    'name.required' => 'Leave type name is required.',
                    'name.string' => 'Leave type name must be a valid string.',
                    'name.max' => 'Leave type name must not exceed 255 characters.',
                    'count.required' => 'Leave count is required.',
                    'count.integer' => 'Leave count must be a number.',
                    'count.min' => 'Leave count cannot be negative.',
                ]
            );

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Update leave type (do NOT touch created_at)
            $leaveType->update([
                'name' => $request->name,
                'count' => $request->count,
                'per' => $request->per,
                'company_id' => Auth::user()->company_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Leave type updated successfully!',
                'data' => $leaveType,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating leave type: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $leaveType = LeaveType::findOrFail($id);
            $leaveType->delete();

            return response()->json([
                'success' => true,
                'message' => 'Leave type deleted successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting leave type: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Restore soft deleted leave type.
     */
    public function restore($id)
    {
        try {
            $leaveType = LeaveType::withTrashed()->findOrFail($id);
            $leaveType->restore();

            return response()->json([
                'success' => true,
                'message' => 'Leave type restored successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error restoring leave type: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Force delete leave type.
     */
    public function forceDelete($id)
    {
        try {
            $leaveType = LeaveType::withTrashed()->findOrFail($id);
            $leaveType->forceDelete();

            return response()->json([
                'success' => true,
                'message' => 'Leave type permanently deleted!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error permanently deleting leave type: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get leave types by company (for AJAX requests)
     */
    public function getByCompany($companyId)
    {
        try {
            $leaveTypes = LeaveType::where('company_id', $companyId)
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $leaveTypes,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching leave types: '.$e->getMessage(),
            ], 500);
        }
    }
}
