<?php

namespace App\Http\Controllers\HRM;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ComplaintController extends Controller
{
    public function index(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;
            $search = $request->input('q');
            $perPage = $request->input('per_page', 20);
            $fromDate  = $request->input('from_date');
            $toDate    = $request->input('to_date');

            $query = Complaint::where('company_id', $companyId)
                ->with(['fromEmployee', 'againstEmployee'])
                ->latest();

            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('title', 'LIKE', "%{$search}%")
                      ->orWhereHas('fromEmployee', function($empQuery) use ($search) {
                          $empQuery->where('full_name', 'LIKE', "%{$search}%");
                      })
                      ->orWhereHas('againstEmployee', function($empQuery) use ($search) {
                          $empQuery->where('full_name', 'LIKE', "%{$search}%");
                      });
                });
            }

            if (!empty($fromDate)) {
                $query->whereDate('date', '>=', $fromDate);
            }

            if (!empty($toDate)) {
                $query->whereDate('date', '<=', $toDate);
            }

            $complaints = $query->paginate($perPage)->withQueryString();

            if ($request->ajax()) {
                return response()->json([
                    'html' => view('pages.hrm.complaints.partials.table', compact('complaints'))->render(),
                    'pagination' => $complaints->links()->toHtml(),
                ]);
            }

            return view('pages.hrm.complaints.index', compact('complaints'));
        } catch (\Exception $e) {
            Log::error('Error fetching complaints: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error fetching complaints: ' . $e->getMessage());
        }
    }

    public function create()
    {
        $companyId = Auth::user()->company_id;
        $employees = Employee::where('company_id', $companyId)->get();
        return view('pages.hrm.complaints.create', compact('employees'));
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'date' => 'required|date',
                'from_employee_id' => 'required|exists:employees,id',
                'against_employee_id' => 'required|exists:employees,id|different:from_employee_id',
                'file' => 'nullable|file|mimes:pdf,doc,docx,png,jpg,jpeg|max:10240', // Max 10MB
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

            $filePath = null;
            if ($request->hasFile('file')) {
                $filePath = $request->file('file')->store('complaints', 'public');
            }

            Complaint::create([
                'company_id' => Auth::user()->company_id,
                'title' => $request->title,
                'date' => $request->date,
                'from_employee_id' => $request->from_employee_id,
                'against_employee_id' => $request->against_employee_id,
                'file_path' => $filePath,
                'description' => $request->description,
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Complaint logged successfully!',
                    'redirect_to' => route('hrm.complaints.index'),
                ]);
            }

            return redirect()->route('hrm.complaints.index')->with('success', 'Complaint logged successfully!');
        } catch (\Exception $e) {
            Log::error('Error creating complaint: ' . $e->getMessage());
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error creating complaint: ' . $e->getMessage(),
                ], 500);
            }
            return redirect()->back()->with('error', 'Error creating complaint: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            $companyId = Auth::user()->company_id;
            $complaint = Complaint::where('company_id', $companyId)->findOrFail($id);
            $employees = Employee::where('company_id', $companyId)->get();
            return view('pages.hrm.complaints.edit', compact('complaint', 'employees'));
        } catch (\Exception $e) {
            return redirect()->route('hrm.complaints.index')->with('error', 'Complaint not found.');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $companyId = Auth::user()->company_id;
            $complaint = Complaint::where('company_id', $companyId)->findOrFail($id);

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'date' => 'required|date',
                'from_employee_id' => 'required|exists:employees,id',
                'against_employee_id' => 'required|exists:employees,id|different:from_employee_id',
                'file' => 'nullable|file|mimes:pdf,doc,docx,png,jpg,jpeg|max:10240',
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

            $filePath = $complaint->file_path;
            if ($request->hasFile('file')) {
                if ($complaint->file_path && Storage::disk('public')->exists($complaint->file_path)) {
                    Storage::disk('public')->delete($complaint->file_path);
                }
                $filePath = $request->file('file')->store('complaints', 'public');
            }

            $complaint->update([
                'title' => $request->title,
                'date' => $request->date,
                'from_employee_id' => $request->from_employee_id,
                'against_employee_id' => $request->against_employee_id,
                'file_path' => $filePath,
                'description' => $request->description,
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Complaint updated successfully!',
                    'redirect_to' => route('hrm.complaints.index'),
                ]);
            }

            return redirect()->route('hrm.complaints.index')->with('success', 'Complaint updated successfully!');
        } catch (\Exception $e) {
            Log::error('Error updating complaint: ' . $e->getMessage());
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error updating complaint: ' . $e->getMessage(),
                ], 500);
            }
            return redirect()->back()->with('error', 'Error updating complaint: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $companyId = Auth::user()->company_id;
            $complaint = Complaint::where('company_id', $companyId)->findOrFail($id);

            if ($complaint->file_path && Storage::disk('public')->exists($complaint->file_path)) {
                Storage::disk('public')->delete($complaint->file_path);
            }

            $complaint->delete();

            return response()->json([
                'success' => true,
                'message' => 'Complaint deleted successfully!',
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting complaint: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting complaint: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function download($id)
    {
        try {
            $companyId = Auth::user()->company_id;
            $complaint = Complaint::where('company_id', $companyId)->findOrFail($id);

            if (!$complaint->file_path || !Storage::disk('public')->exists($complaint->file_path)) {
                return redirect()->back()->with('error', 'File not found on server.');
            }

            return Storage::disk('public')->download($complaint->file_path);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error downloading file: ' . $e->getMessage());
        }
    }
}
