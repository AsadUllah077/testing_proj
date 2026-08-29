<?php

namespace App\Http\Controllers\HRM;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\EmployeeType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        try {
            $search = strtolower($request->q);
            $fromDate = $request->from_date;
            $toDate = $request->to_date;
            $departmentId = $request->department_id;
            $status = $request->has('status') ? $request->status : 'active';
            $perPage = $request->per_page ?? 20;

            $query = Employee::with(['company', 'department', 'employmentType']);

            // Search filter
            if (!empty($search)) {

                $query->where(function ($q) use ($search) {
                    $q->whereRaw('LOWER(full_name) LIKE ?', "%{$search}%")
                        ->orWhereRaw('LOWER(email) LIKE ?', "%{$search}%")
                        ->orWhereRaw('LOWER(phone_number) LIKE ?', "%{$search}%")
                        ->orWhereRaw('LOWER(national_id) LIKE ?', "%{$search}%");
                });
            }

            // Department filter
            if (!empty($departmentId) && $departmentId !== 'all') {
                $query->where('department_id', $departmentId);
            }

            // Status filter
            if (!empty($status) && $status !== 'all') {
                $query->where('status', $status);
            }

            // Date filters
            if (!empty($fromDate)) {
                $query->whereDate('created_at', '>=', $fromDate);
            }

            if (!empty($toDate)) {
                $query->whereDate('created_at', '<=', $toDate);
            }

            // Company filter
            if (!empty($request->company_id) && $request->company_id !== 'all') {
                $query->where('company_id', $request->company_id);
            }

            $employees = $query->sortable()->paginate($perPage);
            $departments = Department::all();
            $companies = companies();

            if ($request->ajax()) {
                return response()->json([
                    'html' => view('pages.hrm.employee.partials.table', compact('employees'))->render(),
                    'pagination' => $employees->links()->toHtml(),
                ]);
            }

            return view('pages.hrm.employee.index', compact('employees', 'departments', 'companies'));
        } catch (\Exception $e) {
            Log::error('EmployeeController@index Error: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Error loading employees.');
        }
    }

    public function create()
    {
        try {
            $companies = companies();
            $departments = Department::all();
            $employmentTypes = EmployeeType::all();
            $erpUsers = User::all();
            $apiRoles = Role::whereHas('permissions', function ($q) {
                $q->where('name', 'like', 'api.%');
            })->get();

            return view('pages.hrm.employee.create', compact(
                'companies',
                'departments',
                'erpUsers',
                'employmentTypes',
                'apiRoles'
            ));
        } catch (\Exception $e) {
            Log::error('EmployeeController@create Error: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Error loading create form.');
        }
    }

    public function store(Request $request)
    {
        try {
            $days = 'Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday';
            $companyId = loggedCompanyId()['company_id'];
            $validator = Validator::make($request->all(), [
                // Personal Information
                'full_name' => 'required|string|max:255',
                'f_name' => 'required|string|max:255',
                'gender' => 'nullable|in:male,female,other',
                'date_of_birth' => 'nullable|date',
                'marital_status' => 'nullable|in:single,married,divorced,widowed',
                // 'blood_group' => 'nullable|string|max:10',
                'national_id' => 'required|string|max:50',

                // Contact Information
                'phone_number' => 'nullable|string|max:20',
                'email' => [
                    'nullable',
                    'email',
                    Rule::unique('employees', 'email')->where(function ($query) use ($companyId) {
                        $query->where('company_id', $companyId);
                    })
                ],
                'residential_address' => 'nullable|string',
                'city' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'salary' => 'nullable|numeric|min:0',
                'country' => 'nullable|string|max:100',
                'emergency_contact_name' => 'nullable|string|max:255',
                'emergency_contact_phone' => 'nullable|string|max:20',
                'emergency_contact_relationship' => 'nullable|string|max:100',

                // Job Information
                'employment_type_id' => 'nullable|exists:employee_types,id',
                'reporting_to' => 'nullable|string|max:255',
                'department_id' => 'nullable|exists:departments,id',
                'designation' => 'nullable|string|max:255',
                'date_of_joining' => 'nullable|date',
                // 'date_of_confirmation' => 'nullable|date|after_or_equal:date_of_joining',
                'work_location' => 'nullable|string|max:255',
                'job_description' => 'nullable|string',
                'linked_erp_user_id' => 'nullable|exists:users,id',
                'role_id' => 'nullable|exists:roles,id',
                'status' => 'nullable|in:active,inactive,terminated,resigned',
                'start_time' => 'nullable|date_format:H:i',
                'end_time' => 'nullable|date_format:H:i|after:start_time',
                'working_day_from' => 'nullable|in:' . $days,
                'working_day_to' => 'nullable|in:' . $days . '|gte:working_day_from',

                // Profile
                'profile_picture' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',

                // Documents validation
                'documents' => 'nullable|array',
                'documents.*.document_type' => 'nullable|string|max:100',
                'documents.*.document_name' => 'nullable|string|max:255',
                'documents.*.file' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,txt|max:5120',
                // 'documents.*.description' => 'nullable|string',
                // 'documents.*.issue_date' => 'nullable|date',
                // 'documents.*.expiry_date' => 'nullable|date|after_or_equal:documents.*.issue_date',
                'documents.*.is_verified' => 'nullable|boolean',
            ], [
                'email.unique' => 'This email is already registered.',
                // 'date_of_confirmation.after_or_equal' => 'Confirmation date must be on or after joining date.',
                'end_time.after' => 'End time must be after start time.',
                'national_id.required' => 'National ID/CNIC is required.',
                'documents.*.document_type.required_with' => 'Document type is required.',
                'documents.*.document_name.required_with' => 'Document name is required.',
                'documents.*.file.required_with' => 'File is required.',
                'documents.*.file.mimes' => 'Invalid file type. Allowed: PDF, DOC, DOCX, JPG, PNG, TXT',
                'documents.*.file.max' => 'File size must be less than 5MB.',
                'working_day_to.gte' => 'The closing day must be a day after the starting day.',
                // 'documents.*.expiry_date.after_or_equal' => 'Expiry date must be after or equal to issue date.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->all(),
                ]);
            }

            $data = $request->except(['profile_picture', '_token', 'documents']);

            // Handle profile picture upload
            if ($request->hasFile('profile_picture')) {
                $file = $request->file('profile_picture');
                $filename = 'profile_' . time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('employees/profile_pictures', $filename, 'public');
                $data['profile_picture'] = $path;
            }

            // Set default status
            if (empty($data['status'])) {
                $data['status'] = 'active';
            }
            $data['company_id'] = Auth::user()->company_id;

            // Generate employee_id
            $date = !empty($data['date_of_joining']) ? \Carbon\Carbon::parse($data['date_of_joining']) : now();
            $month = $date->format('m');
            $year = $date->format('Y');

            // Next sequence number based on company total employees
            $currentCount = Employee::where('company_id', $data['company_id'])->count();
            $sequence = $currentCount + 1;

            $data['employee_id'] = sprintf("emp-%s-%s-%03d", $month, $year, $sequence);

            // Create employee
            $employee = Employee::create($data);

            // Handle document uploads
            if ($request->has('documents') && is_array($request->documents)) {
                foreach ($request->documents as $index => $documentData) {
                    if (isset($documentData['file']) && $documentData['file']->isValid()) {
                        $file = $documentData['file'];
                        $originalName = $file->getClientOriginalName();
                        $extension = $file->getClientOriginalExtension();
                        $filename = 'document_' . time() . '_' . Str::random(10) . '_' . $index . '.' . $extension;
                        $path = $file->storeAs('employees/documents/' . $employee->id, $filename, 'public');

                        EmployeeDocument::create([
                            'employee_id' => $employee->id,
                            'document_type' => $documentData['document_type'],
                            'document_name' => $documentData['document_name'],
                            'file_path' => $path,
                            'file_original_name' => $originalName,
                            'file_size' => $file->getSize(),
                            'file_extension' => $extension,
                            'description' => $documentData['description'] ?? null,
                            'issue_date' => isset($documentData['issue_date']) && !empty($documentData['issue_date']) ? $documentData['issue_date'] : null,
                            'expiry_date' => isset($documentData['expiry_date']) && !empty($documentData['expiry_date']) ? $documentData['expiry_date'] : null,
                            'is_verified' => isset($documentData['is_verified']) && $documentData['is_verified'] == '1' ? 1 : 0,
                        ]);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Employee created successfully!' . (isset($request->documents) ? ' ' . count($request->documents) . ' document(s) uploaded.' : ''),
                'data' => $employee,
            ]);
        } catch (\Exception $e) {
            Log::error('EmployeeController@store Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error creating employee: ' . $e->getMessage(),
            ]);
        }
    }

    public function show($id)
    {
        try {
            $employee = Employee::with([
                'company',
                'department',
                'linkedErpUser',
                'employmentType',
                'documents',
                'productIssues.product.category',
            ])->findOrFail($id);

            $startOfMonth = now()->startOfMonth();
            $endOfMonth = now()->endOfMonth();

            $attendances = $employee->attendances()
                ->whereBetween('attendance_date', [$startOfMonth, $endOfMonth])
                ->get()
                ->keyBy(fn($item) => $item->attendance_date->format('Y-m-d'));

            $leaves = $employee->leaves()
                ->where(function($q) use ($startOfMonth, $endOfMonth) {
                    $q->whereBetween('start_date', [$startOfMonth, $endOfMonth])
                      ->orWhereBetween('end_date', [$startOfMonth, $endOfMonth])
                      ->orWhere(function($sq) use ($startOfMonth, $endOfMonth) {
                          $sq->where('start_date', '<=', $startOfMonth)
                            ->where('end_date', '>=', $endOfMonth);
                      });
                })->get();

            $leaveDates = [];
            foreach ($leaves as $leave) {
                $current = $leave->start_date->copy();
                while ($current->lte($leave->end_date)) {
                    if ($current->between($startOfMonth, $endOfMonth)) {
                        $leaveDates[$current->format('Y-m-d')] = $leave;
                    }
                    $current->addDay();
                }
            }

            return view('pages.hrm.employee.show', compact('employee', 'attendances', 'leaveDates'));
        } catch (\Exception $e) {
            Log::error('EmployeeController@show Error: ' . $e->getMessage());

            return redirect()->route('hrm.employee.index')->with('error', 'Employee not found.');
        }
    }

    public function edit($id)
    {
        try {
            $employee = Employee::with('documents')->findOrFail($id);

            // Debug: Log the document count
            Log::info('Employee ID: ' . $employee->id . ', Documents: ' . $employee->documents->count());

            $companies = companies();
            $departments = Department::all();
            $employmentTypes = EmployeeType::all();
            $erpUsers = User::all();
            $apiRoles = Role::whereHas('permissions', function ($q) {
                $q->where('name', 'like', 'api.%');
            })->get();

            return view('pages.hrm.employee.edit', compact(
                'employee',
                'companies',
                'departments',
                'erpUsers',
                'employmentTypes',
                'apiRoles',
            ));
        } catch (\Exception $e) {
            Log::error('EmployeeController@edit Error: ' . $e->getMessage());

            return redirect()->route('hrm.employee.index')->with('error', 'Employee not found.');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $employee = Employee::findOrFail($id);
            $companyId = loggedCompanyId()['company_id'];
            $days = 'Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday';

            $validator = Validator::make($request->all(), [
                // Personal Information
                'full_name' => 'required|string|max:255',
                'f_name' => 'required|string|max:255',
                'gender' => 'nullable|in:male,female,other',
                'marital_status' => 'nullable|in:single,married,divorced,widowed',
                'national_id' => 'required|string|max:50',
                'date_of_birth' => 'nullable|date',
                'salary' => 'nullable|numeric|min:0',

                // Contact Information
                'phone_number' => 'nullable|string|max:20',
                'email' => [
                    'nullable',
                    'email',
                    Rule::unique('employees', 'email')
                        ->where(function ($query) use ($companyId) {
                            return $query->where('company_id', $companyId);
                        })
                        ->ignore($id)
                ],
                'residential_address' => 'nullable|string',
                'city' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'country' => 'nullable|string|max:100',
                'emergency_contact_name' => 'nullable|string|max:255',
                'emergency_contact_phone' => 'nullable|string|max:20',
                'emergency_contact_relationship' => 'nullable|string|max:100',

                // Job Information
                'employment_type_id' => 'nullable|exists:employee_types,id',
                'reporting_to' => 'nullable|string|max:255',
                'department_id' => 'nullable|exists:departments,id',
                'designation' => 'nullable|string|max:255',
                'work_location' => 'nullable|string|max:255',
                'job_description' => 'nullable|string',
                'linked_erp_user_id' => 'nullable|exists:users,id',
                'role_id' => 'nullable|exists:roles,id',
                'status' => 'nullable|in:active,inactive,terminated,resigned',
                'date_of_joining' => 'nullable|date',
                'working_day_from' => 'nullable|in:' . $days,
                'working_day_to' => 'nullable|in:' . $days . '|gte:working_day_from',
                // Profile
                'profile_picture' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',

                // ✅ Documents (fully optional)
                'documents' => 'nullable|array',

                'documents.*.document_type' => 'nullable|string|max:100',
                'documents.*.document_name' => 'nullable|string|max:255',
                'documents.*.file' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,txt|max:5120',
                // 'documents.*.description' => 'nullable|string',
                // 'documents.*.issue_date' => 'nullable|date',
                // 'documents.*.expiry_date' => 'nullable|date|after_or_equal:documents.*.issue_date',
                'documents.*.is_verified' => 'nullable|boolean',

            ], [
                'email.unique' => 'This email is already registered.',
                'national_id.required' => 'National ID/CNIC is required.',
                'documents.*.file.mimes' => 'Invalid file type. Allowed: PDF, DOC, DOCX, JPG, PNG, TXT',
                'documents.*.file.max' => 'File size must be less than 5MB.',
                'documents.*.expiry_date.after_or_equal' => 'Expiry date must be after or equal to issue date.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->all(),
                ], 422);
            }

            $data = $request->except(['profile_picture', '_token', '_method', 'documents', 'remove_profile_picture']);

            // Handle profile picture removal
            if ($request->has('remove_profile_picture') && $request->remove_profile_picture == '1') {
                if ($employee->profile_picture && Storage::disk('public')->exists($employee->profile_picture)) {
                    Storage::disk('public')->delete($employee->profile_picture);
                }
                $data['profile_picture'] = null;
            }

            // Handle profile picture upload
            if ($request->hasFile('profile_picture')) {
                // Delete old profile picture if exists
                if ($employee->profile_picture && Storage::disk('public')->exists($employee->profile_picture)) {
                    Storage::disk('public')->delete($employee->profile_picture);
                }

                $file = $request->file('profile_picture');
                $filename = 'profile_' . time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('employees/profile_pictures', $filename, 'public');
                $data['profile_picture'] = $path;
            }
            $data['company_id'] = Auth::user()->company_id;
            $employee->update($data);

            // Handle new document uploads
            if ($request->has('documents') && is_array($request->documents)) {
                foreach ($request->documents as $index => $documentData) {
                    if (isset($documentData['file']) && $documentData['file']->isValid()) {
                        $file = $documentData['file'];
                        $originalName = $file->getClientOriginalName();
                        $extension = $file->getClientOriginalExtension();
                        $filename = 'document_' . time() . '_' . Str::random(10) . '_' . $index . '.' . $extension;
                        $path = $file->storeAs('employees/documents/' . $employee->id, $filename, 'public');

                        EmployeeDocument::create([
                            'employee_id' => $employee->id,
                            'document_type' => $documentData['document_type'],
                            'document_name' => $documentData['document_name'],
                            'file_path' => $path,
                            'file_original_name' => $originalName,
                            'file_size' => $file->getSize(),
                            'file_extension' => $extension,
                            'description' => $documentData['description'] ?? null,
                            'issue_date' => isset($documentData['issue_date']) && !empty($documentData['issue_date']) ? $documentData['issue_date'] : null,
                            'expiry_date' => isset($documentData['expiry_date']) && !empty($documentData['expiry_date']) ? $documentData['expiry_date'] : null,
                            'is_verified' => isset($documentData['is_verified']) && $documentData['is_verified'] == '1' ? 1 : 0,
                        ]);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Employee updated successfully!',
                'data' => $employee,
            ]);
        } catch (\Exception $e) {
            Log::error('EmployeeController@update Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error updating employee: ' . $e->getMessage(),
            ]);
        }
    }

    public function destroy($id)
    {
        try {
            $employee = Employee::with('documents')->findOrFail($id);

            // Check if employee is reporting manager for others
            if ($employee->subordinates()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete employee. They are reporting manager for other employees.',
                ]);
            }

            // Delete profile picture if exists
            if ($employee->profile_picture && Storage::disk('public')->exists($employee->profile_picture)) {
                Storage::disk('public')->delete($employee->profile_picture);
            }

            // Delete all documents
            foreach ($employee->documents as $document) {
                if (Storage::disk('public')->exists($document->file_path)) {
                    Storage::disk('public')->delete($document->file_path);
                }
                $document->delete();
            }

            $employee->delete();

            return response()->json([
                'success' => true,
                'message' => 'Employee deleted successfully!',
            ]);
        } catch (\Exception $e) {
            Log::error('EmployeeController@destroy Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error deleting employee: ' . $e->getMessage(),
            ]);
        }
    }

    public function destroyDocument($id)
    {
        try {
            $document = EmployeeDocument::findOrFail($id);

            // Delete file from storage
            if (Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }

            $document->delete();

            return response()->json([
                'success' => true,
                'message' => 'Document deleted successfully!',
            ]);
        } catch (\Exception $e) {
            Log::error('EmployeeController@destroyDocument Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error deleting document: ' . $e->getMessage(),
            ]);
        }
    }

    public function changeStatus(Request $request, $id)
    {
        try {
            $employee = Employee::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:active,inactive,terminated,resigned',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid status',
                ]);
            }

            $employee->update(['status' => $request->status]);

            return response()->json([
                'success' => true,
                'message' => 'Employee status updated successfully!',
            ]);
        } catch (\Exception $e) {
            Log::error('EmployeeController@changeStatus Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error updating status',
            ]);
        }
    }

    public function getManagers(Request $request)
    {
        try {
            $managers = Employee::where('status', 'active')
                ->when($request->has('department_id'), function ($query) use ($request) {
                    $query->where('department_id', $request->department_id);
                })
                ->select('id', 'full_name', 'employee_code', 'designation')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $managers,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching managers',
            ]);
        }
    }

    public function registerFingerprint(Request $request)
    {
        // Find the employee by email or name
        $employee = Employee::where('email', $request->email)
            ->orWhere('full_name', $request->full_name)
            ->first();

        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        // Update the record with the fingerprint data
        $employee->update([
            'fingerprint_template' => $request->fingerprint_template,
        ]);

        return response()->json(['message' => 'Fingerprint registered successfully for ' . $employee->full_name]);
    }
}
