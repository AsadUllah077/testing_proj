<?php

namespace App\Http\Controllers\HRM;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
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

            // Start query with relationships and count
            $query = Department::with(['company'])
                // ->withCount('employees') // Count employees for each department
                ->select('departments.*')
                ->latest('departments.created_at');

            // Apply search filter
            if (! empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('department', 'LIKE', "%{$search}%")
                        ->orWhereHas('company', function ($subQuery) use ($search) {
                            $subQuery->where('name', 'LIKE', "%{$search}%");
                        });
                });
            }

            // Apply date filters
            if (! empty($fromDate)) {
                $query->whereDate('departments.created_at', '>=', $fromDate);
            }

            if (! empty($toDate)) {
                $query->whereDate('departments.created_at', '<=', $toDate);
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
                $sortableColumns = ['department', 'created_at', 'employees_count'];
                if (in_array($sortBy, $sortableColumns)) {
                    $query->orderBy($sortBy, $sortOrder);
                }
            }

            // Paginate results
            $departments = $query->paginate($perPage)->withQueryString()->onEachSide(1);

            // Get companies for filter dropdown (optional)
            $companies = companies(); // Assuming you have this helper function

            if ($request->ajax()) {
                return response()->json([
                    'html' => view('pages.hrm.department.partials.table', compact('departments'))->render(),
                    'pagination' => $departments->links()->toHtml(),
                ]);
            }

            return view('pages.hrm.department.index', compact('departments', 'companies'));

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Error fetching departments: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return redirect()->back()->with('error', 'Error fetching departments: '.$e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $companies = companies(); // Or use: Company::all();

        return view('pages.hrm.department.create', compact('companies'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'department' => [
                        'required',
                        'string',
                        'max:255',
                        Rule::unique('departments', 'department')
                            ->where(function ($query) {
                                return $query->where('company_id', Auth::user()->company_id);
                            }),
                    ],
                    'head' => 'required|string|max:255',
                ],
                [
                    'department.required' => 'Department name is required.',
                    'department.unique' => 'Department already exists for this company.',
                    'department.string' => 'Department name must be a valid string.',
                    'department.max' => 'Department name must not exceed 255 characters.',
                    'head.required' => 'Head name is required.',
                    'head.string' => 'Head name must be a valid string.',
                ]
            );

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $department = Department::create([
                'department' => $request->department,
                'head' => $request->head,
                'company_id' => Auth::user()->company_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Department created successfully!',
                'data' => $department,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating department: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $department = Department::with(['company', 'employees'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $department,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Department not found: '.$e->getMessage(),
            ], 404);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        try {
            $department = Department::findOrFail($id);
            $companies = companies(); // Or use: Company::all();

            return view('pages.hrm.department.edit', compact('department', 'companies'));

        } catch (\Exception $e) {
            return redirect()->route('hrm.department.index')
                ->with('error', 'Department not found.');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            // Find the department
            $department = Department::findOrFail($id);

            // Validate input
            $validator = Validator::make($request->all(), [
                'department' => [
                    'required',
                    'string',
                    'max:255',
                    function ($attribute, $value, $fail) use ($id) {
                        $companyId = Auth::user()->company_id;

                        $exists = Department::where('department', $value)
                            ->where('company_id', $companyId)
                            ->where('id', '!=', $id) // exclude current department
                            ->exists();

                        if ($exists) {
                            $fail('Department name already exists for this company.');
                        }
                    },
                ],
                'company_id' => 'nullable|exists:companies,id',
                'head' => 'required|string|max:255',
            ], [
                'department.required' => 'Department name is required.',
                'department.string' => 'Department name must be a valid string.',
                'head.required' => 'Head name is required.',
                'head.string' => 'Head name must be a valid string.',
                'department.max' => 'Department name must not exceed 255 characters.',
                'company_id.exists' => 'Selected company does not exist.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Update department
            $department->update([
                'department' => $request->department,
                'head' => $request->head,
                'company_id' => Auth::user()->company_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Department updated successfully!',
                'data' => $department,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating department: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $department = Department::findOrFail($id);

            // Check if department has employees
            // if ($department->employees()->exists()) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Cannot delete department. It has employees assigned. Please reassign employees first.',
            //     ], 400);
            // }

            $department->delete();

            return response()->json([
                'success' => true,
                'message' => 'Department deleted successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting department: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Restore soft deleted department.
     */
    public function restore($id)
    {
        try {
            $department = Department::withTrashed()->findOrFail($id);
            $department->restore();

            return response()->json([
                'success' => true,
                'message' => 'Department restored successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error restoring department: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Force delete department.
     */
    public function forceDelete($id)
    {
        try {
            $department = Department::withTrashed()->findOrFail($id);
            $department->forceDelete();

            return response()->json([
                'success' => true,
                'message' => 'Department permanently deleted!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error permanently deleting department: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get departments by company (for AJAX requests)
     */
    public function getByCompany($companyId)
    {
        try {
            $departments = Department::where('company_id', $companyId)
                ->orderBy('department')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $departments,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching departments: '.$e->getMessage(),
            ], 500);
        }
    }
}
