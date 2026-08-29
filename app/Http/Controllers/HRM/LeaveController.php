<?php

namespace App\Http\Controllers\HRM;

use App\Exports\LeaveRecordsDepartmentWiseExport;
use App\Exports\LeaveRecordsExport;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRecord;
use App\Models\LeaveType;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class LeaveController extends Controller
{
    /**
     * Display a listing of leave records.
     */
    public function index(Request $request)
    {
        // dd("fsff");
        try {
            // Get search parameters
            $search = $request->input('q');
            $employeeId = $request->input('employee_id');
            $leaveTypeId = $request->input('leave_type_id');
            $status = $request->input('status');
            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');
            $useOverlapDateFilter = $request->boolean('overlap');
            $companyId = Auth::user()->company_id;

            // Get per_page from request or use default
            $perPage = $request->input('per_page', 20);

            // Start query
            $query = LeaveRecord::with(['employee', 'leaveType', 'createdBy'])
                ->where('company_id', $companyId);

            // Apply search filter
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('employee', function ($subQuery) use ($search) {
                        $subQuery->where('full_name', 'ILIKE', "%{$search}%")
                            ->orWhere('employee_id', 'ILIKE', "%{$search}%");
                    })
                        ->orWhere('adjustment_reason', 'ILIKE', "%{$search}%");
                });
            }

            // Apply employee filter
            if (!empty($employeeId)) {
                $query->where('employee_id', $employeeId);
            }

            // Apply leave type filter
            if (!empty($leaveTypeId)) {
                $query->where('leave_type_id', $leaveTypeId);
            }

            // Apply date range filter
            if ($useOverlapDateFilter && (!empty($fromDate) || !empty($toDate))) {
                $rangeStart = $fromDate ?: '0001-01-01';
                $rangeEnd = $toDate ?: '9999-12-31';

                $query->where(function ($q) use ($rangeStart, $rangeEnd) {
                    $q->whereBetween('start_date', [$rangeStart, $rangeEnd])
                        ->orWhereBetween('end_date', [$rangeStart, $rangeEnd])
                        ->orWhere(function ($q2) use ($rangeStart, $rangeEnd) {
                            $q2->where('start_date', '<=', $rangeStart)
                                ->where('end_date', '>=', $rangeEnd);
                        });
                });
            } else {
                if (!empty($fromDate)) {
                    $query->whereDate('start_date', '>=', $fromDate);
                }

                if (!empty($toDate)) {
                    $query->whereDate('end_date', '<=', $toDate);
                }
            }

            // Apply manual adjustments filter
            if ($request->has('is_manual')) {
                $query->whereNotNull('adjustment_reason');
            }

            if ($request->has('status')) {
                $toLower = strtolower($request->status);
                $query->where('status', $toLower);
            }

            // Paginate results
            $leaveRecords = $query->sortable()->paginate($perPage)->withQueryString();

            // Get dropdown data
            $employees = Employee::where('company_id', $companyId)
                ->orderBy('full_name')
                ->get();

            $leaveTypes = LeaveType::where('company_id', $companyId)
                ->orderBy('name')
                ->get();

            $statusOptions = [
                'pending' => 'Pending',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
                'manually_adjusted' => 'Manually Adjusted',
            ];

            if ($request->ajax()) {
                return response()->json([
                    'html' => view('pages.hrm.leave.partials.table', data: compact('leaveRecords'))->render(),
                    'pagination' => $leaveRecords->links()->toHtml(),
                ]);
            }

            return view('pages.hrm.leave.index', compact(
                'leaveRecords',
                'employees',
                'leaveTypes',
                'statusOptions'
            ));
        } catch (\Exception $e) {
            Log::error('Error fetching leave records: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Error fetching leave records: ' . $e->getMessage());
        }
    }

    /**
     * Display pending leave requests with optimized querying.
     * This function is designed to handle large volumes of pending requests efficiently.
     */
    public function pendingLeaveRequests(Request $request)
    {
        try {
            // Get search parameters
            $search = $request->input('q');
            $employeeId = $request->input('employee_id');
            $leaveTypeId = $request->input('leave_type_id');
            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');
            $companyId = Auth::user()->company_id;
            $status = $request->input('status', 'pending');

            // Get per_page from request or use default
            $perPage = $request->input('per_page', 20);

            // Optimized query - only fetch pending records with eager loading
            $query = LeaveRecord::with([
                'employee:id,full_name,department_id',
                'employee.department:id,department',
                'leaveType:id,name',
                'createdBy:id,first_name,last_name'
            ])
                ->where('company_id', $companyId)
                ->where('status', $status);

            // Apply search filter
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('employee', function ($subQuery) use ($search) {
                        $subQuery->where('full_name', 'ILIKE', "%{$search}%")
                            ->orWhere('email', 'ILIKE', "%{$search}%")
                        ;
                    })
                        ->orWhere('adjustment_reason', 'ILIKE', "%{$search}%");
                });
            }

            // Apply employee filter
            if (!empty($employeeId)) {
                $query->where('employee_id', $employeeId);
            }

            // Apply leave type filter
            if (!empty($leaveTypeId)) {
                $query->where('leave_type_id', $leaveTypeId);
            }

            // Apply date range filter for start_date
            if (!empty($fromDate)) {
                $query->whereDate('start_date', '>=', $fromDate);
            }

            if (!empty($toDate)) {
                $query->whereDate('end_date', '<=', $toDate);
            }

            // Paginate results
            $leaveRecords = $query->sortable()->paginate($perPage)->withQueryString();

            // Get dropdown data - only active employees
            $employees = Employee::where('company_id', $companyId)
                ->orderBy('full_name')
                ->get(['id', 'full_name', 'email']);

            $leaveTypes = LeaveType::where('company_id', $companyId)
                ->orderBy('name')
                ->get(['id', 'name']);

            // Get counts for tabs
            $pendingCount = LeaveRecord::where('company_id', $companyId)
                ->where('status', 'pending')
                ->count();

            $approvedCount = LeaveRecord::where('company_id', $companyId)
                ->where('status', 'approved')
                ->count();

            $rejectedCount = LeaveRecord::where('company_id', $companyId)
                ->where('status', 'rejected')
                ->count();

            if ($request->ajax()) {
                return response()->json([
                    'html' => view('pages.hrm.leave.partials.pending_table', compact('leaveRecords'))->render(),
                    'pagination' => $leaveRecords->links()->toHtml(),
                ]);
            }

            return view('pages.hrm.leave.pending', compact(
                'leaveRecords',
                'employees',
                'leaveTypes',
                'pendingCount',
                'approvedCount',
                'rejectedCount'
            ));
        } catch (\Exception $e) {
            Log::error('Error fetching pending leave requests: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Error fetching pending leave requests: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new leave record.
     */
    public function create()
    {
        try {
            $companyId = Auth::user()->company_id;

            $employees = Employee::where('company_id', $companyId)
                ->orderBy('full_name')
                ->get();

            $leaveTypes = LeaveType::where('company_id', $companyId)
                ->orderBy('name')
                ->get();

            return view('pages.hrm.leave.create', compact('employees', 'leaveTypes'));
        } catch (\Exception $e) {
            Log::error('Error loading leave create form: ' . $e->getMessage());

            return redirect()->route('hrm.leave.index')->with('error', 'Error loading form: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created leave record in storage.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'employee_id' => 'required|exists:employees,id',
                    'leave_type_id' => 'required|exists:leave_types,id',
                    'start_date' => 'required|date',
                    'end_date' => 'required|date|after_or_equal:start_date',
                    'half' => 'nullable|in:first_half,second_half',
                    'early_leave_time' => 'nullable|date_format:H:i',
                    'adjustment_reason' => 'nullable|string|max:1000',
                    'file_upload' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
                ],
                [
                    'employee_id.required' => 'Please select an employee.',
                    'leave_type_id.required' => 'Please select a leave type.',
                    'start_date.required' => 'Start date is required.',
                    'end_date.required' => 'End date is required.',
                    'end_date.after_or_equal' => 'End date must be after or equal to start date.',
                    'file_upload.max' => 'File size must not exceed 5MB.',
                    'file_upload.mimes' => 'Only PDF, JPG, PNG, DOC, and DOCX files are allowed.',
                ]
            );

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Calculate total leave days
            $startDate = \Carbon\Carbon::parse($request->start_date);
            $endDate = \Carbon\Carbon::parse($request->end_date);
            $year = $startDate->year;
            $totalDays = $this->calculateLeaveDays($startDate, $endDate, $request->half, $request->employee_id);

            // Check if leave balance exists for this employee and leave type
            $balance = LeaveBalance::where('employee_id', $request->employee_id)
                ->where('leave_type_id', $request->leave_type_id)
                ->where('year', $year)
                ->first();

            // If no balance exists, get default total leaves from leave type
            if (!$balance) {
                $leaveType = LeaveType::find($request->leave_type_id);

                // Create a new leave balance record
                $balance = LeaveBalance::create([
                    'employee_id' => $request->employee_id,
                    'company_id' => Auth::user()->company_id,
                    'leave_type_id' => $request->leave_type_id,
                    'total_leaves' => $leaveType->count ?? 20, // Default to 20 if not set
                    'used_leaves' => 0,
                    'remaining_leaves' => $leaveType->count ?? 20,
                    'year' => $year,
                ]);
            }

            // Check if employee has sufficient leave balance
            if ($balance->remaining_leaves < $totalDays) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient leave balance. Available: ' . $balance->remaining_leaves . ' days, Requested: ' . $totalDays . ' days.',
                ], 400);
            }

            // Handle file upload
            $filePath = null;
            if ($request->hasFile('file_upload')) {
                $file = $request->file('file_upload');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('leave_documents', $fileName, 'public');
            }

            // Create leave record
            $leaveRecord = LeaveRecord::create([
                'employee_id' => $request->employee_id,
                'company_id' => Auth::user()->company_id,
                'leave_type_id' => $request->leave_type_id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'half' => $request->half,
                'early_leave_time' => $request->early_leave_time,
                'adjustment_reason' => $request->adjustment_reason,
                'file_upload' => $filePath,
                'created_by' => Auth::id(),
            ]);

            // Update leave balance
            // $balance->incrementUsedLeaves($totalDays);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Leave record created successfully!',
                'data' => $leaveRecord,
                'redirect' => route('hrm.leave.index'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating leave record: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error creating leave record: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified leave record.
     */
    public function show($id)
    {
        try {
            $companyId = Auth::user()->company_id;
            $leaveRecord = LeaveRecord::with(['employee', 'leaveType', 'createdBy'])
                ->where('company_id', $companyId)
                ->findOrFail($id);

            // Calculate total days for display
            $totalDays = $leaveRecord->calculateTotalDays();

            // Get all leave types for the company
            $leaveTypes = LeaveType::where('company_id', $companyId)->get();

            // Get employee leave balances for the year of this leave record
            $year = \Carbon\Carbon::parse($leaveRecord->start_date)->year;
            $employeeId = $leaveRecord->employee_id;

            // Get all leave balances for this employee and year
            $leaveBalances = \App\Models\LeaveBalance::where('employee_id', $employeeId)
                ->where('year', $year)
                ->with('leaveType')
                ->get()
                ->keyBy('leave_type_id');

            return view('pages.hrm.leave.show', compact(
                'leaveRecord',
                'totalDays',
                'leaveTypes',
                'leaveBalances'
            ));
        } catch (\Exception $e) {
            Log::error('Error fetching leave record: ' . $e->getMessage());

            return redirect()->route('hrm.leave.index')->with('error', 'Leave record not found.');
        }
    }

    /**
     * Show the form for editing the specified leave record.
     */
    public function edit($id)
    {
        try {
            $companyId = Auth::user()->company_id;

            $leaveRecord = LeaveRecord::with(['employee', 'leaveType'])
                ->where('company_id', $companyId)
                ->findOrFail($id);

            $employees = Employee::where('company_id', $companyId)
                ->orderBy('full_name')
                ->get();

            $leaveTypes = LeaveType::where('company_id', $companyId)
                ->orderBy('name')
                ->get();

            return view('pages.hrm.leave.edit', compact('leaveRecord', 'employees', 'leaveTypes'));
        } catch (\Exception $e) {
            Log::error('Error loading leave edit form: ' . $e->getMessage());

            return redirect()->route('hrm.leave.index')->with('error', 'Leave record not found.');
        }
    }

    public function calendar(Request $request)
    {
        $companyId = Auth::user()->company_id;

        // Get departments and leave types for filters
        $departments = Department::where('company_id', $companyId)
            ->orderBy('department')
            ->get();

        $leaveTypes = LeaveType::where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        return view('pages.hrm.leave.calendar', compact('departments', 'leaveTypes'));
    }

    public function getCalendarData(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;

            $year = $request->input('year', date('Y'));
            $month = $request->input('month', date('m'));

            // Calculate start and end dates for the month
            $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

            // Query leaves for the month - use leftJoin to ensure we get all records even if relationships are missing
            $query = LeaveRecord::with([
                'employee' => function ($q) {
                    $q->withTrashed(); // Include soft-deleted employees if any
                },
                'employee.department',
                'leaveType',
            ])
                ->where('company_id', $companyId)
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('start_date', [$startDate, $endDate])
                        ->orWhereBetween('end_date', [$startDate, $endDate])
                        ->orWhere(function ($q) use ($startDate, $endDate) {
                            $q->where('start_date', '<=', $startDate)
                                ->where('end_date', '>=', $endDate);
                        });
                });

            // Filter by department - only if a specific value is selected
            if ($request->filled('department_id') && $request->department_id != '') {
                $query->whereHas('employee', function ($q) use ($request) {
                    $q->where('department_id', $request->department_id);
                });
            }

            // Filter by leave type - only if a specific value is selected
            if ($request->filled('leave_type_id') && $request->leave_type_id != '') {
                $query->where('leave_type_id', $request->leave_type_id);
            }

            $leaves = $query->orderBy('start_date')->get();

            // Format the response with null checks
            $formattedLeaves = $leaves->filter(function ($leave) {
                // Filter out records with null employee or leaveType
                return $leave->employee && $leave->leaveType;
            })->map(function ($leave) {
                return [
                    'id' => $leave->id,
                    'employee' => [
                        'full_name' => $leave->employee->full_name ?? 'Unknown Employee',
                        'employee_id' => $leave->employee->employee_id ?? 'N/A',
                        'department' => $leave->employee->department ?? null,
                        'working_day_from' => $leave->employee->working_day_from ?? null,
                        'working_day_to' => $leave->employee->working_day_to ?? null,
                    ],
                    'leave_type' => [
                        'id' => $leave->leaveType->id ?? 0,
                        'name' => $leave->leaveType->name ?? 'Unknown Leave Type',
                    ],
                    'start_date' => $leave->start_date->format('Y-m-d'),
                    'end_date' => $leave->end_date->format('Y-m-d'),
                    'half' => $leave->half,
                    'total_days' => $leave->calculateTotalDays(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedLeaves->values(), // Reset keys
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching calendar data: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Error fetching calendar data: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getLeaveDetails(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;

            $leave = LeaveRecord::with(['employee.department', 'leaveType'])
                ->where('company_id', $companyId)
                ->findOrFail($request->id);
            // dd($leave);

            // Generate file URL if exists
            $fileUrl = null;
            if ($leave->file_upload && Storage::disk('public')->exists($leave->file_upload)) {
                $fileUrl = Storage::url($leave->file_upload);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $leave->id,
                    'employee' => [
                        'full_name' => $leave->employee->full_name,
                        'employee_id' => $leave->employee->employee_id,
                        'department' => $leave->employee->department,
                    ],
                    'leave_type' => [
                        'id' => $leave->leaveType->id,
                        'name' => $leave->leaveType->name,
                    ],
                    'start_date' => $leave->start_date->format('Y-m-d'),
                    'end_date' => $leave->end_date->format('Y-m-d'),
                    'half' => $leave->half,
                    'early_leave_time' => $leave->early_leave_time,
                    'adjustment_reason' => $leave->adjustment_reason,
                    'total_days' => $leave->calculateTotalDays(),
                    'file_upload' => $leave->file_upload,
                    'file_url' => $fileUrl,
                    'status' => ucfirst($leave->status)
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching leave details' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified leave record in storage.
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $companyId = Auth::user()->company_id;
            $leaveRecord = LeaveRecord::where('company_id', $companyId)
                ->findOrFail($id);

            $validator = Validator::make(
                $request->all(),
                [
                    'employee_id' => 'required|exists:employees,id',
                    'leave_type_id' => 'required|exists:leave_types,id',
                    'start_date' => 'required|date',
                    'end_date' => 'required|date|after_or_equal:start_date',
                    'half' => 'nullable|in:first_half,second_half',
                    'early_leave_time' => 'nullable|date_format:H:i',
                    'adjustment_reason' => 'nullable|string|max:1000',
                    'file_upload' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
                    'remove_file' => 'nullable|boolean',
                ],
                [
                    'employee_id.required' => 'Please select an employee.',
                    'leave_type_id.required' => 'Please select a leave type.',
                    'start_date.required' => 'Start date is required.',
                    'end_date.required' => 'End date is required.',
                    'end_date.after_or_equal' => 'End date must be after or equal to start date.',
                ]
            );

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Calculate old and new total days
            $oldStartDate = \Carbon\Carbon::parse($leaveRecord->start_date);
            $oldEndDate = \Carbon\Carbon::parse($leaveRecord->end_date);
            $oldTotalDays = $this->calculateLeaveDays($oldStartDate, $oldEndDate, $leaveRecord->half, $leaveRecord->employee_id);

            $newStartDate = \Carbon\Carbon::parse($request->start_date);
            $newEndDate = \Carbon\Carbon::parse($request->end_date);
            $newTotalDays = $this->calculateLeaveDays($newStartDate, $newEndDate, $request->half, $request->employee_id);

            // Get old and new years
            $oldYear = $oldStartDate->year;
            $newYear = $newStartDate->year;

            // Check if leave type changed or employee changed
            $leaveTypeChanged = $leaveRecord->leave_type_id != $request->leave_type_id;
            $employeeChanged = $leaveRecord->employee_id != $request->employee_id;
            $yearChanged = $oldYear != $newYear;

            // Get the new balance that will be used
            $newBalance = LeaveBalance::where('employee_id', $request->employee_id)
                ->where('leave_type_id', $request->leave_type_id)
                ->where('year', $newYear)
                ->first();

            // If no balance exists, create one
            if (!$newBalance) {
                $leaveType = LeaveType::find($request->leave_type_id);

                $newBalance = LeaveBalance::create([
                    'employee_id' => $request->employee_id,
                    'company_id' => $companyId,
                    'leave_type_id' => $request->leave_type_id,
                    'total_leaves' => $leaveType->count ?? 20,
                    'used_leaves' => 0,
                    'remaining_leaves' => $leaveType->count ?? 20,
                    'year' => $newYear,
                ]);
            }

            // Calculate available balance for the new request
            // First, we need to check if we're updating within the same employee/leave_type/year
            // If yes, we need to temporarily add back the old days to check availability
            $tempAvailableBalance = $newBalance->remaining_leaves;

            if (!$leaveTypeChanged && !$employeeChanged && !$yearChanged) {
                // Same employee, leave type, and year - add back old days temporarily
                $tempAvailableBalance += $oldTotalDays;
            }

            // Check if employee has sufficient leave balance for new request
            if ($tempAvailableBalance < $newTotalDays) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient leave balance. Available: ' . $tempAvailableBalance . ' days, Requested: ' . $newTotalDays . ' days.',
                ], 400);
            }

            // Handle file upload
            $filePath = $leaveRecord->file_upload;

            // Check if file should be removed
            if ($request->has('remove_file') && $request->remove_file) {
                if ($filePath && Storage::disk('public')->exists($filePath)) {
                    Storage::disk('public')->delete($filePath);
                    $filePath = null;
                }
            }

            // Handle new file upload
            if ($request->hasFile('file_upload')) {
                // Delete old file if exists
                if ($filePath && Storage::disk('public')->exists($filePath)) {
                    Storage::disk('public')->delete($filePath);
                }

                $file = $request->file('file_upload');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('leave_documents', $fileName, 'public');
            }

            // Update leave record first
            $updateData = [
                'employee_id' => $request->employee_id,
                'leave_type_id' => $request->leave_type_id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'half' => $request->half,
                'early_leave_time' => $request->early_leave_time,
                'adjustment_reason' => $request->adjustment_reason,
                'file_upload' => $filePath,
            ];

            $leaveRecord->update($updateData);

            // Now update the leave balances
            // First, return days to old balance (if different from new)
            if ($leaveTypeChanged || $employeeChanged || $yearChanged) {
                $oldBalance = LeaveBalance::where('employee_id', $leaveRecord->employee_id)
                    ->where('leave_type_id', $leaveRecord->leave_type_id)
                    ->where('year', $oldYear)
                    ->first();

                if ($oldBalance) {
                    $oldBalance->decrementUsedLeaves($oldTotalDays);
                }
            } else {
                // Same employee, leave type, and year - adjust by difference
                $difference = $newTotalDays - $oldTotalDays;

                if ($difference > 0) {
                    // Adding more days
                    $newBalance->incrementUsedLeaves($difference);
                } elseif ($difference < 0) {
                    // Reducing days (returning days to balance)
                    $newBalance->decrementUsedLeaves(abs($difference));
                }
                // If difference is 0, no change needed
            }

            // If we're moving to a different employee/leave_type/year, add to new balance
            if ($leaveTypeChanged || $employeeChanged || $yearChanged) {
                $newBalance->incrementUsedLeaves($newTotalDays);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Leave record updated successfully!',
                'data' => $leaveRecord,
                'redirect' => route('hrm.leave.index'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating leave record: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error updating leave record: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified leave record from storage.
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $companyId = Auth::user()->company_id;
            $leaveRecord = LeaveRecord::where('company_id', $companyId)
                ->findOrFail($id);

            // Calculate total days to return to balance
            $startDate = \Carbon\Carbon::parse($leaveRecord->start_date);
            $totalDays = $this->calculateLeaveDays(
                $startDate,
                \Carbon\Carbon::parse($leaveRecord->end_date),
                $leaveRecord->half,
                $leaveRecord->employee_id
            );

            // Return days to balance
            $balance = LeaveBalance::where('employee_id', $leaveRecord->employee_id)
                ->where('leave_type_id', $leaveRecord->leave_type_id)
                ->where('year', $startDate->year)
                ->first();

            if ($balance) {
                $balance->decrementUsedLeaves($totalDays);
            }

            // Delete file if exists
            if ($leaveRecord->file_upload && Storage::disk('public')->exists($leaveRecord->file_upload)) {
                Storage::disk('public')->delete($leaveRecord->file_upload);
            }

            // Delete the record
            $leaveRecord->delete();

            DB::commit();

            $companyId = Auth::user()->company_id;
            $counts = LeaveRecord::where('company_id', $companyId)
                ->selectRaw("
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count
                ")
                ->first();

            $pendingCount = $counts->pending_count;
            $approvedCount = $counts->approved_count;
            $rejectedCount = $counts->rejected_count;

            return response()->json([
                'success' => true,
                'message' => 'Leave record deleted successfully!',
                'counts' => [
                    'pending' => $pendingCount,
                    'approved' => $approvedCount,
                    'rejected' => $rejectedCount,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting leave record: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error deleting leave record: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calculate total leave days based on dates and half day selection.
     */
    /**
     * Calculate total leave days based on dates and half day selection (AJAX method).
     */
    public function calculateDays(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'half' => 'nullable|in:first_half,second_half',
                'employee_id' => 'nullable|exists:employees,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            $half = $request->half;

            // Calculate total days
            $totalDays = $this->calculateLeaveDays($startDate, $endDate, $half, $request->employee_id);

            return response()->json([
                'success' => true,
                'total_days' => $totalDays,
                'message' => 'Calculation successful',
            ]);
        } catch (\Exception $e) {
            Log::error('Error calculating leave days: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error calculating days: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper method to calculate total leave days based on dates and half day selection.
     */
    private function calculateLeaveDays($startDate, $endDate, $halfDay = null, $employeeId = null)
    {
        $employee = $employeeId ? Employee::find($employeeId) : null;

        $record = new LeaveRecord([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'half' => $halfDay,
        ]);

        if ($employee) {
            $record->setRelation('employee', $employee);
        }

        return $record->calculateTotalDays();
    }

    /**
     * Update leave status (approve/reject).
     */
    public function updateStatus(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $companyId = Auth::user()->company_id;
            $leaveRecord = LeaveRecord::with(['employee', 'leaveType'])
                ->where('company_id', $companyId)
                ->findOrFail($id);

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:approved,rejected',
                'remarks' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $oldStatus = $leaveRecord->status;
            $newStatus = $request->status;
            $remarks = $request->remarks;

            // Calculate total days
            $startDate = \Carbon\Carbon::parse($leaveRecord->start_date);
            $totalDays = $this->calculateLeaveDays(
                $startDate,
                \Carbon\Carbon::parse($leaveRecord->end_date),
                $leaveRecord->half
            );

            // Get balance
            $balance = LeaveBalance::where('employee_id', $leaveRecord->employee_id)
                ->where('leave_type_id', $leaveRecord->leave_type_id)
                ->where('year', $startDate->year)
                ->first();

            // Handle status transitions
            if ($oldStatus === 'pending' && $newStatus === 'approved') {
                // Approve leave
                if ($balance && $balance->remaining_leaves < 1) {
                    // DB::rollBack();
                    if ($balance) {
                        $balance->decrementUsedLeaves($totalDays);
                    }
                    // return response()->json([
                    //     'success' => false,
                    //     'message' => 'Cannot approve: Insufficient leave balance. Available: ' . $balance->remaining_leaves . ' days.',
                    // ], 400);
                }

                if ($balance) {
                    $balance->incrementUsedLeaves($totalDays);
                }
            } elseif ($oldStatus === 'approved' && $newStatus === 'rejected') {
                // Reject previously approved leave
                if ($balance) {
                    $balance->decrementUsedLeaves($totalDays);
                }
            }

            // Update leave record with remarks
            $leaveRecord->update([
                'status' => $newStatus,
                'remarks' => $remarks,
            ]);

            // Get employee details
            $employee = $leaveRecord->employee;
            $leaveTypeName = $leaveRecord->leaveType->name ?? 'Leave';

            // Format dates for notification
            $formattedStartDate = $startDate->format('d M, Y');
            $formattedEndDate = \Carbon\Carbon::parse($leaveRecord->end_date)->format('d M, Y');

            // Create notification title and description based on status
            if ($newStatus === 'approved') {
                $notificationTitle = 'Leave Request Approved';
                $notificationBody = "Your {$leaveTypeName} request from {$formattedStartDate} to {$formattedEndDate} ({$totalDays} days) has been approved.";

                if ($remarks) {
                    $notificationBody .= " Remarks: {$remarks}";
                }
            } else {
                $notificationTitle = 'Leave Request Rejected';
                $notificationBody = "Your {$leaveTypeName} request from {$formattedStartDate} to {$formattedEndDate} ({$totalDays} days) has been rejected.";

                if ($remarks) {
                    $notificationBody .= " Reason: {$remarks}";
                }
            }

            // Create database notification
            Notification::create([
                'title' => $notificationTitle,
                'description' => $notificationBody,
                'employee_id' => $employee->id,
                'is_seen' => false,
            ]);

            // Send FCM push notification if employee has FCM token
            if ($employee->fcm_token) {
                try {
                    \App\Services\FcmService::sendNotification(
                        $employee->fcm_token,
                        [
                            'title' => $notificationTitle,
                            'body' => $notificationBody,
                        ]
                    );
                } catch (\Exception $fcmError) {
                    // Log FCM error but don't fail the request
                    Log::warning('FCM notification failed for leave status update: ' . $fcmError->getMessage());
                }
            }

            DB::commit();

            $counts = LeaveRecord::where('company_id', $companyId)
                ->selectRaw("
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count
                ")
                ->first();

            $pendingCount = $counts->pending_count;
            $approvedCount = $counts->approved_count;
            $rejectedCount = $counts->rejected_count;

            return response()->json([
                'success' => true,
                'message' => 'Leave status updated successfully!',
                'data' => $leaveRecord,
                'counts' => [
                    'pending' => $pendingCount,
                    'approved' => $approvedCount,
                    'rejected' => $rejectedCount,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating leave status: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error updating leave status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get employee leave balance.
     */
    public function getEmployeeBalance(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'employee_id' => 'required|exists:employees,id',
                'leave_type_id' => 'required|exists:leave_types,id',
                'year' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $year = $request->year ?? now()->year;

            $balance = LeaveBalance::where('employee_id', $request->employee_id)
                ->where('leave_type_id', $request->leave_type_id)
                ->where('year', $year)
                ->first();

            // If no balance exists, get the default from leave type
            $leaveType = LeaveType::find($request->leave_type_id);

            if (!$balance) {

                if (!$leaveType) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Leave type not found.',
                    ], 404);
                }

                // Calculate total leaves based on leave type configuration
                $totalLeaves = $leaveType->count ?? 0;

                // Check if it's per year, month, or week and calculate accordingly
                if ($leaveType->per === 'per_month') {
                    // If per month, multiply by 12 for annual total
                    $totalLeaves = ($leaveType->count ?? 0) * 12;
                } elseif ($leaveType->per === 'per_week') {
                    // If per week, multiply by 52 for annual total
                    $totalLeaves = ($leaveType->count ?? 0) * 52;
                }
                // If per_year, use the count as is

                return response()->json([
                    'success' => true,
                    'data' => [
                        'total_leaves' => $totalLeaves,
                        'used_leaves' => 0,
                        'remaining_leaves' => $totalLeaves,
                        'formatted_balance' => "0 / {$totalLeaves}",
                        'is_default' => true, // Flag to indicate this is default from leave type
                        'leave_type_name' => $leaveType->name,
                    ],
                    'message' => 'Using default leave balance from leave type.',
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'total_leaves' => $balance->total_leaves,
                    'used_leaves' => $balance->used_leaves,
                    'remaining_leaves' => ($leaveType->count) - ( $balance->used_leaves),
                    'formatted_balance' => $balance->formatted_balance,
                    'is_default' => false, // Flag to indicate this is actual balance record
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching employee balance: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error fetching balance: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get leave type by category name.
     */
    public function getLeaveTypeByCategory(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'category' => 'required|in:annual,sick,casual',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $companyId = Auth::user()->company_id;
            $category = $request->category;

            // Define search terms for each category
            $searchTerms = [
                'annual' => ['annual', 'vacation', 'annual leave', 'vacation leave'],
                'sick' => ['sick', 'sick leave', 'medical', 'medical leave'],
                'casual' => ['casual', 'casual leave', 'personal', 'personal leave'],
            ];

            $terms = $searchTerms[$category] ?? [];

            // Find leave type by name matching
            $leaveType = LeaveType::where('company_id', $companyId)
                ->where(function ($query) use ($terms) {
                    foreach ($terms as $term) {
                        $query->orWhere('name', 'LIKE', "%{$term}%");
                    }
                })
                ->orderBy('created_at')
                ->first();

            if (!$leaveType) {
                return response()->json([
                    'success' => false,
                    'message' => "No {$category} leave type found.",
                ], 404);
            }

            return response()->json([
                'success' => true,
                'id' => $leaveType->id,
                'name' => $leaveType->name,
                'data' => $leaveType,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching leave type by category: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error fetching leave type: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export all leave records with filters
     */
    public function export(Request $request)
    {
        $filters = [
            'company_id' => Auth::user()->company_id,
            'search' => $request->input('q'),
            'employee_id' => $request->input('employee_id'),
            'leave_type_id' => $request->input('leave_type_id'),
            'status' => $request->input('status'),
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
            'is_manual' => $request->has('is_manual'),
        ];

        $filename = 'leave_records_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new LeaveRecordsExport($filters), $filename);
    }

    /**
     * Export leave records department-wise
     */
    public function exportDepartmentWise(Request $request)
    {
        $filters = [
            'company_id' => Auth::user()->company_id,
            'search' => $request->input('q'),
            'employee_id' => $request->input('employee_id'),
            'leave_type_id' => $request->input('leave_type_id'),
            'status' => $request->input('status'),
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
            'is_manual' => $request->has('is_manual'),
            'department_id' => $request->input('department_id'),
        ];

        $filename = 'leave_records_department_wise_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new LeaveRecordsDepartmentWiseExport($filters), $filename);
    }
}
