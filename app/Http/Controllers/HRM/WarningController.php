<?php

namespace App\Http\Controllers\HRM;

use App\Http\Controllers\Controller;
use App\Models\Warning;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WarningController extends Controller
{
    public function index(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;
            $search    = $request->input('q');
            $perPage   = $request->input('per_page', 20);
            $fromDate  = $request->input('from_date');
            $toDate    = $request->input('to_date');

            $query = Warning::where('company_id', $companyId)->with('employee')->latest();

            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('title', 'LIKE', "%{$search}%")
                      ->orWhereHas('employee', function($empQuery) use ($search) {
                          $empQuery->where('full_name', 'LIKE', "%{$search}%");
                      });
                });
            }

            if (!empty($fromDate)) {
                $query->whereDate('warning_date', '>=', $fromDate);
            }

            if (!empty($toDate)) {
                $query->whereDate('warning_date', '<=', $toDate);
            }

            $warnings = $query->paginate($perPage)->withQueryString();

            if ($request->ajax()) {
                return response()->json([
                    'html'       => view('pages.hrm.warnings.partials.table', compact('warnings'))->render(),
                    'pagination' => $warnings->links()->toHtml(),
                ]);
            }

            return view('pages.hrm.warnings.index', compact('warnings'));
        } catch (\Exception $e) {
            Log::error('Error fetching warnings: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error fetching warnings: ' . $e->getMessage());
        }
    }

    public function create()
    {
        $companyId = Auth::user()->company_id;
        $employees = Employee::where('company_id', $companyId)->get();
        return view('pages.hrm.warnings.create', compact('employees'));
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'employee_id' => 'required|exists:employees,id',
                'warning_date' => 'required|date',
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation error',
                        'errors' => $validator->errors(),
                    ], 422);
                }
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $warning = Warning::create([
                'company_id' => Auth::user()->company_id,
                'employee_id' => $request->employee_id,
                'warning_date' => $request->warning_date,
                'title' => $request->title,
                'description' => $request->description,
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Warning recorded successfully!',
                    'redirect_to' => route('hrm.warnings.index'),
                ]);
            }

            return redirect()->route('hrm.warnings.index')->with('success', 'Warning recorded successfully!');
        } catch (\Exception $e) {
            Log::error('Error creating warning: ' . $e->getMessage());
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error creating warning: ' . $e->getMessage(),
                ], 500);
            }
            return redirect()->back()->with('error', 'Error creating warning: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            $companyId = Auth::user()->company_id;
            $warning = Warning::where('company_id', $companyId)->findOrFail($id);
            $employees = Employee::where('company_id', $companyId)->get();
            return view('pages.hrm.warnings.edit', compact('warning', 'employees'));
        } catch (\Exception $e) {
            return redirect()->route('hrm.warnings.index')->with('error', 'Warning record not found.');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $companyId = Auth::user()->company_id;
            $warning = Warning::where('company_id', $companyId)->findOrFail($id);

            $validator = Validator::make($request->all(), [
                'employee_id' => 'required|exists:employees,id',
                'warning_date' => 'required|date',
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation error',
                        'errors' => $validator->errors(),
                    ], 422);
                }
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $warning->update([
                'employee_id' => $request->employee_id,
                'warning_date' => $request->warning_date,
                'title' => $request->title,
                'description' => $request->description,
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Warning updated successfully!',
                    'redirect_to' => route('hrm.warnings.index'),
                ]);
            }

            return redirect()->route('hrm.warnings.index')->with('success', 'Warning updated successfully!');
        } catch (\Exception $e) {
            Log::error('Error updating warning: ' . $e->getMessage());
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error updating warning: ' . $e->getMessage(),
                ], 500);
            }
            return redirect()->back()->with('error', 'Error updating warning: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $companyId = Auth::user()->company_id;
            $warning = Warning::where('company_id', $companyId)->findOrFail($id);
            $warning->delete();

            return response()->json([
                'success' => true,
                'message' => 'Warning record deleted successfully!',
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting warning: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting warning: ' . $e->getMessage(),
            ], 500);
        }
    }
}
