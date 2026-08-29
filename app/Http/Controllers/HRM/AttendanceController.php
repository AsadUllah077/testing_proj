<?php

namespace App\Http\Controllers\HRM;

use App\Exports\AttendanceDepartmentWiseExport;
use App\Exports\AttendanceExport;
use App\Exports\AttendanceTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\AttendanceImport;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\Company;
use Carbon\Carbon;
use App\Models\LeaveRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Excel as ExcelExcel;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = EmployeeAttendance::with('employee.department');
        $today = Carbon::now()->format('Y-m-d');
        $companyId = Auth::user()->company_id;
        // Search filter (name or employee ID)
        if ($request->filled('q')) {
            $search = trim($request->q);
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('full_name', 'ILIKE', "%{$search}%")
                    ->orWhere('employee_card_id', 'ILIKE', "%{$search}%");
            });
        }

        // Employee ID filter
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        // Date filters - only default to today if no other major filters are set
        // to make searching more intuitive.
        $hasSignificantFilter = $request->filled('q') || $request->filled('employee_id') || $request->filled('department_id');

        if ($request->filled('from_date')) {
            $query->whereDate('attendance_date', '>=', $request->from_date);
        } elseif (!$hasSignificantFilter) {
            $query->whereDate('attendance_date', '>=', $today);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('attendance_date', '<=', $request->to_date);
        } elseif (!$hasSignificantFilter) {
            $query->whereDate('attendance_date', '<=', $today);
        }

        // Department filter
        if ($request->filled('department_id')) {
            $query->whereHas('employee.department', function ($q) use ($request) {
                $q->where('id', $request->department_id);
            });
        }

        // Status filter
        if ($request->filled('status')) {
            switch ($request->status) {
                case 'present':
                    $query->whereNotNull('check_in');
                    break;
                case 'absent':
                    $query->whereNull('check_in');
                    break;
                case 'late':
                    $base = clone $query;
                    $lateIds = $base->with('employee')
                        ->whereNotNull('check_in')
                        ->get()
                        ->filter(function ($a) {
                            $start = $a->employee?->start_time ?? '09:00:00';
                            $ci = \Carbon\Carbon::parse($a->check_in);
                            $ciMin = $ci->hour * 60 + $ci->minute;
                            $s = explode(':', $start);
                            $sMin = (isset($s[0]) ? (int) $s[0] : 0) * 60 + (isset($s[1]) ? (int) $s[1] : 0);

                            // Check for overnight shifts if end_time exists
                            $end = $a->employee?->end_time ?? $start;
                            $e = explode(':', $end);
                            $eMin = (isset($e[0]) ? (int) $e[0] : 0) * 60 + (isset($e[1]) ? (int) $e[1] : 0);
                            $overnight = $eMin < $sMin;

                            if ($overnight && $ciMin < $sMin) {
                                $ciMin += 1440;
                            }
                            $grace = $sMin + 15;
                            return $ciMin > $grace;
                        })
                        ->pluck('id')
                        ->all();
                    $query->whereIn('id', $lateIds);
                    break;
            }
        }


        // Get statistics for today
        $today = Carbon::today()->toDateString();

        $totalEmployees = Employee::where('company_id', $companyId)->where('status', 'active')->count();
        $presentToday = EmployeeAttendance::whereDate('attendance_date', $today)
            ->whereNotNull('check_in')
            ->count();
        $absentToday = count(EmployeeAttendance::whereDate('attendance_date', $today)
            ->whereNull('check_in')
            ->pluck('employee_id')
            ->toArray());
        $lateToday = EmployeeAttendance::with('employee')
            ->whereDate('attendance_date', $today)
            ->whereNotNull('check_in')
            ->get()
            ->filter(function ($a) {
                $start = $a->employee?->start_time ?? '09:00:00';
                $end = $a->employee?->end_time ?? $start;
                $ci = \Carbon\Carbon::parse($a->check_in);
                $ciMin = $ci->hour * 60 + $ci->minute;
                $s = explode(':', $start);
                $e = explode(':', $end);
                $sMin = (isset($s[0]) ? (int) $s[0] : 0) * 60 + (isset($s[1]) ? (int) $s[1] : 0);
                $eMin = (isset($e[0]) ? (int) $e[0] : 0) * 60 + (isset($e[1]) ? (int) $e[1] : 0);
                $overnight = $eMin < $sMin;
                if ($overnight && $ciMin < $sMin) {
                    $ciMin += 1440;
                }
                $grace = $sMin + 15;
                return $ciMin > $grace;
            })
            ->count();

        $attendanceRate = $totalEmployees > 0 ? round(($presentToday / $totalEmployees) * 100) : 0;

        $totalLeaves = LeaveRecord::where('company_id', Auth::user()->company_id)->whereDate('start_date', now()->format('Y-m-d'))->count();

        // Get departments for filter
        $departments = Department::orderBy('department')->get();

        // Companies the current user is allowed to move records into.
        $companiesQuery = Auth::user()->isSuperAdmin()
            ? Company::query()
            : Auth::user()->companies();
        $companies = $companiesQuery
            ->where('companies.id', '!=', $companyId)
            ->orderBy('companies.name')
            ->get(['companies.id', 'companies.name']);

        // Pagination
        $perPage = $request->get('per_page', 20);
        $attendances = $query->sortable()->paginate($perPage)->onEachSide(1);

        if ($request->ajax()) {
            return response()->json([
                'html' => view('pages.hrm.attendance.partials.table', compact('attendances'))->render(),
                'pagination' => $attendances->links()->toHtml(),
            ]);
        }

        return view('pages.hrm.attendance.index', compact(
            'attendances',
            'departments',
            'companies',
            'totalEmployees',
            'presentToday',
            'absentToday',
            'lateToday',
            'attendanceRate',
            'totalLeaves'
        ));
    }

    /**
     * Transfer selected attendance records to another company.
     *
     * The destination employee is matched by email, then company_id and
     * employee_id are updated on each attendance record.
     */
    public function bulkTransferCompany(Request $request)
    {
        $validated = $request->validate([
            'attendance_ids' => ['required', 'array', 'min:1', 'max:500'],
            'attendance_ids.*' => ['required', 'integer', 'distinct'],
            'company_id' => [
                'required',
                'integer',
                Rule::exists('companies', 'id')->whereNull('deleted_at'),
            ],
        ]);

        $user = Auth::user();
        $currentCompanyId = (int) $user->company_id;
        $targetCompanyId = (int) $validated['company_id'];
        $attendanceIds = array_values(array_unique(array_map('intval', $validated['attendance_ids'])));

        if ($currentCompanyId <= 0) {
            return response()->json([
                'message' => 'No active company is selected.',
            ], 422);
        }

        if ($targetCompanyId === $currentCompanyId) {
            return response()->json([
                'message' => 'Please select a different company.',
            ], 422);
        }

        if (! $user->isSuperAdmin()
            && ! $user->companies()->where('companies.id', $targetCompanyId)->exists()) {
            return response()->json([
                'message' => 'You do not have access to the selected company.',
            ], 403);
        }

        $attendanceRecords = DB::table('employee_attendances as attendance')
            ->leftJoin('employees as source_employee', function ($join) use ($currentCompanyId) {
                $join->on('source_employee.id', '=', 'attendance.employee_id')
                    ->where('source_employee.company_id', '=', $currentCompanyId);
            })
            ->where('attendance.company_id', $currentCompanyId)
            ->whereNull('attendance.deleted_at')
            ->whereIn('attendance.id', $attendanceIds)
            ->select([
                'attendance.id',
                'attendance.check_in',
                'attendance.check_out',
                'source_employee.email',
            ])
            ->get();

        if ($attendanceRecords->count() !== count($attendanceIds)) {
            return response()->json([
                'message' => 'Some selected attendance records are unavailable or do not belong to the active company.',
            ], 422);
        }

        $incompleteAttendanceIds = $attendanceRecords
            ->filter(fn ($attendance) => blank($attendance->check_in) || blank($attendance->check_out))
            ->pluck('id')
            ->all();

        if (! empty($incompleteAttendanceIds)) {
            return response()->json([
                'message' => 'Only attendance records with both check-in and check-out can be transferred. Incomplete record(s): '.implode(', ', $incompleteAttendanceIds),
            ], 422);
        }

        $recordsWithoutEmail = $attendanceRecords
            ->filter(fn ($attendance) => blank($attendance->email))
            ->pluck('id')
            ->all();

        if (! empty($recordsWithoutEmail)) {
            return response()->json([
                'message' => 'Attendance record(s) '.implode(', ', $recordsWithoutEmail).' are not linked to an employee with an email address.',
            ], 422);
        }

        $normalizedEmails = $attendanceRecords
            ->map(fn ($attendance) => strtolower(trim($attendance->email)))
            ->unique()
            ->values();

        $targetEmployeesByEmail = Employee::withoutGlobalScope('company')
            ->where('company_id', $targetCompanyId)
            ->whereNotNull('email')
            ->whereIn(DB::raw('LOWER(TRIM(email))'), $normalizedEmails->all())
            ->get(['id', 'email'])
            ->groupBy(fn ($employee) => strtolower(trim($employee->email)));

        $missingEmails = $normalizedEmails
            ->reject(fn ($email) => $targetEmployeesByEmail->has($email))
            ->values();

        if ($missingEmails->isNotEmpty()) {
            return response()->json([
                'message' => 'No employee with the following email address exists in the destination company: '.$missingEmails->join(', '),
            ], 422);
        }

        $duplicateEmails = $targetEmployeesByEmail
            ->filter(fn ($employees) => $employees->count() > 1)
            ->keys();

        if ($duplicateEmails->isNotEmpty()) {
            return response()->json([
                'message' => 'Multiple destination employees use the following email address: '.$duplicateEmails->join(', '),
            ], 422);
        }

        $attendanceIdsByTargetEmployee = $attendanceRecords
            ->groupBy(function ($attendance) use ($targetEmployeesByEmail) {
                $email = strtolower(trim($attendance->email));

                return (int) $targetEmployeesByEmail->get($email)->first()->id;
            })
            ->map(fn ($records) => $records->pluck('id')->map(fn ($id) => (int) $id)->all());

        try {
            $updated = DB::transaction(function () use ($attendanceIdsByTargetEmployee, $currentCompanyId, $targetCompanyId) {
                $updatedCount = 0;

                foreach ($attendanceIdsByTargetEmployee as $targetEmployeeId => $ids) {
                    $affected = DB::table('employee_attendances')
                        ->where('company_id', $currentCompanyId)
                        ->whereNull('deleted_at')
                        ->whereIn('id', $ids)
                        ->update([
                            'company_id' => $targetCompanyId,
                            'employee_id' => (int) $targetEmployeeId,
                        ]);

                    if ($affected !== count($ids)) {
                        throw new \RuntimeException('One or more attendance records changed during the transfer.');
                    }

                    $updatedCount += $affected;
                }

                return $updatedCount;
            });
        } catch (\RuntimeException $exception) {
            Log::warning('Attendance bulk company transfer was rolled back.', [
                'attendance_ids' => $attendanceIds,
                'target_company_id' => $targetCompanyId,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'The attendance records changed during the transfer. Please refresh and try again.',
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => "{$updated} attendance record(s) transferred successfully.",
            'updated' => $updated,
        ]);
    }

    /**
     * Download attendance import template
     */
    public function downloadTemplate()
    {
        $date = Carbon::now()->format('Y-m-d');

        return Excel::download(
            new AttendanceTemplateExport,
            "attendance-template-{$date}.csv",
            ExcelExcel::CSV
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $employees = Employee::with('department')
            ->where('status', 'active')
            ->orderBy('full_name')
            ->get();

        $today = Carbon::today()->format('Y-m-d');

        return view('pages.hrm.attendance.create', compact('employees', 'today'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Validate input
            $validator = Validator::make($request->all(), [
                'employee_id' => 'required|exists:employees,id',
                'attendance_date' => 'required|date',
                'check_in' => 'nullable|date_format:H:i',
                'check_out' => 'nullable|date_format:H:i|after:check_in',
                'working_type' => 'nullable|in:on-site,remote',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->all(),
                ]);
            }

            $data = $request->only(['employee_id', 'attendance_date', 'check_in', 'check_out', 'working_type']);
            $data['working_type'] = $data['working_type'] ?? 'on-site';
            $data['company_id'] = Auth::user()->company_id;

            $employee = Employee::findOrFail($request->employee_id);
            $data['start_time'] = $employee->start_time;
            $data['end_time'] = $employee->end_time;

            // Check for duplicate attendance
            $existing = EmployeeAttendance::where('employee_id', $request->employee_id)
                ->whereDate('attendance_date', $request->attendance_date)
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Attendance already recorded for this date.',
                ]);
            }

            $data['working_type'] = $request->working_type;
            $attendance = EmployeeAttendance::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Attendance recorded successfully.',
                'data' => $attendance,
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $attendance = EmployeeAttendance::with(['employee.department'])->findOrFail($id);

        return view('pages.hrm.attendance.show', compact('attendance'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $attendance = EmployeeAttendance::with('employee')->findOrFail($id);
        $employees = Employee::where('status', 'active')->orderBy('full_name')->get();

        return view('pages.hrm.attendance.edit', compact('attendance', 'employees'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $attendance = EmployeeAttendance::findOrFail($id);

        // Validate input
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'attendance_date' => 'required|date',
            'check_in' => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i|after:check_in',
            'working_type' => 'nullable|in:on-site,remote',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->all(),
            ]);
        }

        $data = $request->only(['employee_id', 'attendance_date', 'check_in', 'check_out', 'working_type']);
        $data['working_type'] = $data['working_type'] ?? 'on-site';
        $data['company_id'] = Auth::user()->company_id;

        // Check for duplicate attendance (excluding current record)
        $existing = EmployeeAttendance::where('employee_id', $request->employee_id)
            ->whereDate('attendance_date', $request->attendance_date)
            ->where('id', '!=', $id)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance already recorded for this date.',
            ]); // 409 Conflict
        }

        try {
            $data['working_type'] = $request->working_type;
            $attendance->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Attendance updated successfully.',
                'data' => $attendance,
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $attendance = EmployeeAttendance::findOrFail($id);
            $attendance->delete();

            return response()->json([
                'success' => true,
                'message' => 'Attendance record deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete attendance record.',
            ]);
        }
    }

    /**
     * Bulk check-in employees
     */
    public function bulkCheckIn(Request $request)
    {
        $request->validate([
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,id',
            'check_in_time' => 'required|date_format:H:i',
            'attendance_date' => 'required|date',
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->employee_ids as $employeeId) {
                // Check if attendance already exists for today
                $existing = EmployeeAttendance::where('employee_id', $employeeId)
                    ->whereDate('attendance_date', $request->attendance_date)
                    ->first();

                if ($existing) {
                    $existing->update(['check_in' => $request->check_in_time]);
                } else {
                    EmployeeAttendance::create([
                        'employee_id' => $employeeId,
                        'attendance_date' => $request->attendance_date,
                        'check_in' => $request->check_in_time,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('hrm.attendance.index')
                ->with('success', 'Bulk check-in completed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Failed to process bulk check-in.'])->withInput();
        }
    }

    /**
     * Bulk check-out employees
     */
    public function bulkCheckOut(Request $request)
    {
        $request->validate([
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,id',
            'check_out_time' => 'required|date_format:H:i',
            'attendance_date' => 'required|date',
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->employee_ids as $employeeId) {
                $attendance = EmployeeAttendance::where('employee_id', $employeeId)
                    ->whereDate('attendance_date', $request->attendance_date)
                    ->first();

                if ($attendance) {
                    $attendance->update(['check_out' => $request->check_out_time]);
                }
            }

            DB::commit();

            return redirect()->route('hrm.attendance.index')
                ->with('success', 'Bulk check-out completed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Failed to process bulk check-out.'])->withInput();
        }
    }

    /**
     * Import attendance from Excel
     */
    public function import(Request $request)
    {
        // Validate input - using mimes for better compatibility
        $validator = Validator::make($request->all(), [
            'import_file' => 'required|file|mimes:csv,xls,xlsx,txt',
        ]);

        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->all(),
                ]);
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            Excel::import(new AttendanceImport, $request->file('import_file'));

            return redirect()
                ->route('hrm.attendance.index')
                ->with('success', 'Attendance imported successfully.');
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ]);
            }
            return back()->with('error', 'Error importing file: ' . $e->getMessage());
        }
    }


    /**
     * Export attendance to Excel
     */
    public function export(Request $request)
    {
        $date = Carbon::now()->format('Y-m-d');

        return Excel::download(
            new AttendanceExport,
            "attendance-{$date}.csv",
            ExcelExcel::CSV
        );
    }

    public function export1(Request $request)
    {
        $filters = [
            'from_date' => $request->from_date,
            'to_date' => $request->to_date,
            'department_id' => $request->department_id,
            'status' => $request->status,
            'search' => $request->q,
        ];

        $filename = 'attendance_report_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new AttendanceExport($filters), $filename);
    }

    public function exportDepartmentWise(Request $request)
    {
        $filters = [
            'from_date' => $request->from_date,
            'to_date' => $request->to_date,
            'department_id' => $request->department_id,
            'status' => $request->status,
            'search' => $request->q,
        ];

        $filename = 'attendance_department_wise_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new AttendanceDepartmentWiseExport($filters), $filename);
    }

    /**
     * Search employees for export (AJAX)
     */
    public function searchEmployees(Request $request)
    {
        $q = trim($request->input('q', ''));

        $employees = Employee::where('status', 'active')
            ->where(function ($query) use ($q) {
                $query->where('full_name', 'ILIKE', "%{$q}%")
                    ->orWhere('employee_card_id', 'ILIKE', "%{$q}%");
            })
            ->orderBy('full_name')
            ->limit(15)
            ->get(['id', 'full_name', 'employee_card_id', 'email']);

        return response()->json($employees);
    }
}
