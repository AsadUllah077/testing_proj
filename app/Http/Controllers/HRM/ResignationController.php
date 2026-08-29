<?php

namespace App\Http\Controllers\HRM;

use App\Http\Controllers\Controller;
use App\Models\Resignation;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ResignationController extends Controller
{
    public function index(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;
            $search = $request->input('q');
            $perPage = $request->input('per_page', 20);

            $query = Resignation::where('company_id', $companyId)->with('employee')->latest();

            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('title', 'LIKE', "%{$search}%")
                      ->orWhereHas('employee', function($empQuery) use ($search) {
                          $empQuery->where('full_name', 'LIKE', "%{$search}%");
                      });
                });
            }

            if ($request->filled('from_date')) {
                $query->whereDate('resign_date', '>=', $request->from_date);
            }

            if ($request->filled('to_date')) {
                $query->whereDate('resign_date', '<=', $request->to_date);
            }

            $resignations = $query->paginate($perPage)->withQueryString();

            if ($request->ajax()) {
                return response()->json([
                    'html' => view('pages.hrm.resignations.partials.table', compact('resignations'))->render(),
                    'pagination' => $resignations->links()->toHtml(),
                ]);
            }

            return view('pages.hrm.resignations.index', compact('resignations'));
        } catch (\Exception $e) {
            Log::error('Error fetching resignations: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error fetching resignations: ' . $e->getMessage());
        }
    }

    public function create()
    {
        $companyId = Auth::user()->company_id;
        $employees = Employee::where('company_id', $companyId)->get();
        return view('pages.hrm.resignations.create', compact('employees'));
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'employee_id' => 'required|exists:employees,id',
                'resign_date' => 'required|date',
                'last_working_day' => 'required|date|after_or_equal:resign_date',
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

            $resignation = Resignation::create([
                'company_id' => Auth::user()->company_id,
                'employee_id' => $request->employee_id,
                'resign_date' => $request->resign_date,
                'last_working_day' => $request->last_working_day,
                'title' => $request->title,
                'description' => $request->description,
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Resignation recorded successfully!',
                    'redirect_to' => route('hrm.resignations.index'),
                ]);
            }

            return redirect()->route('hrm.resignations.index')->with('success', 'Resignation recorded successfully!');
        } catch (\Exception $e) {
            Log::error('Error creating resignation: ' . $e->getMessage());
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error creating resignation: ' . $e->getMessage(),
                ], 500);
            }
            return redirect()->back()->with('error', 'Error creating resignation: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            $companyId = Auth::user()->company_id;
            $resignation = Resignation::where('company_id', $companyId)->findOrFail($id);
            $employees = Employee::where('company_id', $companyId)->get();
            return view('pages.hrm.resignations.edit', compact('resignation', 'employees'));
        } catch (\Exception $e) {
            return redirect()->route('hrm.resignations.index')->with('error', 'Resignation record not found.');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $companyId = Auth::user()->company_id;
            $resignation = Resignation::where('company_id', $companyId)->findOrFail($id);

            $validator = Validator::make($request->all(), [
                'employee_id' => 'required|exists:employees,id',
                'resign_date' => 'required|date',
                'last_working_day' => 'required|date|after_or_equal:resign_date',
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

            $resignation->update([
                'employee_id' => $request->employee_id,
                'resign_date' => $request->resign_date,
                'last_working_day' => $request->last_working_day,
                'title' => $request->title,
                'description' => $request->description,
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Resignation updated successfully!',
                    'redirect_to' => route('hrm.resignations.index'),
                ]);
            }

            return redirect()->route('hrm.resignations.index')->with('success', 'Resignation updated successfully!');
        } catch (\Exception $e) {
            Log::error('Error updating resignation: ' . $e->getMessage());
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error updating resignation: ' . $e->getMessage(),
                ], 500);
            }
            return redirect()->back()->with('error', 'Error updating resignation: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $companyId = Auth::user()->company_id;
            $resignation = Resignation::where('company_id', $companyId)->findOrFail($id);
            $resignation->delete();

            return response()->json([
                'success' => true,
                'message' => 'Resignation record deleted successfully!',
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting resignation: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting resignation: ' . $e->getMessage(),
            ], 500);
        }
    }
}
