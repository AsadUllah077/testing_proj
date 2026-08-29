<?php

namespace App\Http\Controllers\HRM;

use App\Http\Controllers\Controller;
use App\Models\EmployeeType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EmployeeTypeController extends Controller
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

            // Start query
            $query = EmployeeType::with('company')
                ->select('employee_types.*')
                ->latest();

            // Apply search filter
            if (! empty($search)) {
                $query->where(function ($q) use ($search) {
                    $searchLower = strtolower($search);
                    $q->whereRaw('LOWER(employee_type) LIKE ?', ["%{$searchLower}%"])
                        ->orWhereHas('company', function ($subQuery) use ($search) {
                            $searchLower = strtolower($search);
                            $subQuery->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"]);
                        });
                });
            }

            // Apply date filters
            if (! empty($fromDate)) {
                $query->whereDate('employee_types.created_at', '>=', $fromDate);
            }

            if (! empty($toDate)) {
                $query->whereDate('employee_types.created_at', '<=', $toDate);
            }

            // Paginate results
            $employeeTypes = $query->paginate($request->input('per_page', 20))->onEachSide(1);
            if ($request->ajax()) {
                return response()->json([
                    'html' => view('pages.hrm.employee-type.partials.table', compact('employeeTypes'))->render(),
                    'pagination' => $employeeTypes->links()->toHtml(),
                ]);
            }

            return view('pages.hrm.employee-type.index', compact('employeeTypes'));

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error fetching employee types: '.$e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Assuming you have a companies() helper function
        // If not, you'll need to import the Company model
        $companies = companies(); // Or use: Company::all();

        return view('pages.hrm.employee-type.create', compact('companies'));
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
                    'employee_type' => [
                        'required',
                        'string',
                        'max:255',
                        Rule::unique('employee_types', 'employee_type')
                            ->where(function ($query) {
                                return $query->where('company_id', Auth::user()->company_id);
                            }),
                    ],
                ],
                [
                    'employee_type.required' => 'Employee type is required.',
                    'employee_type.unique' => 'Employee type already exists for this company.',
                    'employee_type.string' => 'Employee type must be a valid string.',
                    'employee_type.max' => 'Employee type must not exceed 255 characters.',
                ]
            );

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $employeeType = EmployeeType::create([
                'employee_type' => $request->employee_type,
                'company_id' => Auth::user()->company_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Employee type created successfully!',
                'data' => $employeeType,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating employee type: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $employeeType = EmployeeType::with('company')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $employeeType,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Employee type not found: '.$e->getMessage(),
            ], 404);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        try {
            $employeeType = EmployeeType::findOrFail($id);
            $companies = companies(); // Or use: Company::all();

            return view('pages.hrm.employee-type.edit', compact('employeeType', 'companies'));

        } catch (\Exception $e) {
            return redirect()->route('hrm.employee-type.index')
                ->with('error', 'Employee type not found.');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $employeeType = EmployeeType::findOrFail($id);

            $validator = Validator::make(
                $request->all(),
                [
                    'employee_type' => [
                        'required',
                        'string',
                        'max:255',
                        Rule::unique('employee_types', 'employee_type')
                            ->where(function ($query) {
                                return $query->where('company_id', Auth::user()->company_id);
                            })
                            ->ignore($employeeType->id),
                    ],
                ],
                [
                    'employee_type.required' => 'Employee type is required.',
                    'employee_type.unique' => 'Employee type already exists for this company.',
                    'employee_type.string' => 'Employee type must be a valid string.',
                    'employee_type.max' => 'Employee type must not exceed 255 characters.',
                ]
            );

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $employeeType->update([
                'employee_type' => $request->employee_type,
                'company_id' => Auth::user()->company_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Employee type updated successfully!',
                'data' => $employeeType,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating employee type: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $employeeType = EmployeeType::findOrFail($id);

            // Check if employee type is being used elsewhere (optional)
            // You can add checks here if employee type has related records

            $employeeType->delete();

            return response()->json([
                'success' => true,
                'message' => 'Employee type deleted successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting employee type: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Restore soft deleted employee type.
     */
    public function restore($id)
    {
        try {
            $employeeType = EmployeeType::withTrashed()->findOrFail($id);
            $employeeType->restore();

            return response()->json([
                'success' => true,
                'message' => 'Employee type restored successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error restoring employee type: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Force delete employee type.
     */
    public function forceDelete($id)
    {
        try {
            $employeeType = EmployeeType::withTrashed()->findOrFail($id);
            $employeeType->forceDelete();

            return response()->json([
                'success' => true,
                'message' => 'Employee type permanently deleted!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error permanently deleting employee type: '.$e->getMessage(),
            ], 500);
        }
    }
}
