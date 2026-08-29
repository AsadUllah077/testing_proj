<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\LeaveBalance;
use App\Models\LeaveRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Get employee dashboard/report summary.
     */
    // public function index(Request $request)
    // {
    //     try {
    //         $user = $request->user(); // Employee authenticated via Sanctum

    //         // 1. If HR Admin and no employee_id provided, return list of employees
    //         if ($user->hasPermission('api.report.all') && !$request->has('employee_id')) {
    //             $employees = Employee::with('department')->get()->map(function ($emp) {
    //                 return [
    //                     'id' => $emp->id,
    //                     'name' => $emp->full_name,
    //                     'department' => $emp->department->name ?? 'N/A',
    //                     'designation' => $emp->designation ?? 'N/A',
    //                     'profile_image' => $emp->profile_picture ? asset('storage/' . $emp->profile_picture) : null,
    //                 ];
    //             });

    //             return response()->json([
    //                 'success' => true,
    //                 'type' => 'employee_list',
    //                 'data' => $employees
    //             ], 200);
    //         }

    //         // 2. Otherwise, fetch specific report
    //         if ($request->has('employee_id') && $user->hasPermission('api.report.all')) {
    //             $employee = Employee::find($request->employee_id);
    //         } else {
    //             $employee = $user; // The authenticated employee IS the employee
    //         }

    //         if (!$employee) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => ['Unauthorized or invalid employee record'],
    //             ], 401);
    //         }

    //         if (!$user->hasPermission('api.report.individual') && !$user->hasPermission('api.report.all')) {
    //             return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
    //         }

    //         $currentYear = now()->year;
    //         $currentMonth = now()->month;

    //         // 1. Attendance Statistics (Current Month)
    //         $attendanceStats = [
    //             'present' => EmployeeAttendance::where('employee_id', '=', $employee->id)
    //                 ->whereMonth('attendance_date', $currentMonth)
    //                 ->whereYear('attendance_date', $currentYear)
    //                 ->whereNotNull('check_in')
    //                 ->count(),
    //             'late' => 0, // We could calculate this based on check_in time if needed
    //             'absent' => 0, // Usually calculated as (working days - present days)
    //             'total_this_month' => EmployeeAttendance::where('employee_id', '=', $employee->id)
    //                 ->whereMonth('attendance_date', $currentMonth)
    //                 ->whereYear('attendance_date', $currentYear)
    //                 ->count(),
    //         ];

    //         // 2. Leave Statistics
    //         $leaveStats = [
    //             'approved' => LeaveRecord::where('employee_id', '=', $employee->id)
    //                 ->where('status', '=', 'approved')
    //                 ->count(),
    //             'pending' => LeaveRecord::where('employee_id', '=', $employee->id)
    //                 ->where('status', '=', 'pending')
    //                 ->count(),
    //             'rejected' => LeaveRecord::where('employee_id', '=', $employee->id)
    //                 ->where('status', '=', 'rejected')
    //                 ->count(),
    //             'total_requests' => LeaveRecord::where('employee_id', '=', $employee->id)->count(),
    //         ];

    //         // 3. Leave Balance (Current Year)
    //         $leaveBalances = LeaveBalance::with('leaveType')
    //             ->where('employee_id', $employee->id)
    //             ->where('year', $currentYear)
    //             ->get()
    //             ->map(function ($balance) {
    //                 return [
    //                     'leave_type' => $balance->leaveType->name ?? 'Unknown',
    //                     'total' => $balance->total_leaves,
    //                     'used' => $balance->used_leaves,
    //                     'remaining' => $balance->remaining_leaves,
    //                     'formatted' => $balance->formatted_balance,
    //                 ];
    //             });

    //         // 4. Attendance List (Current Month/Recent)
    //         $attendanceList = EmployeeAttendance::where('employee_id', '=', $employee->id)
    //             ->whereMonth('attendance_date', $currentMonth)
    //             ->whereYear('attendance_date', $currentYear)
    //             ->orderBy('attendance_date', 'desc')
    //             ->get()
    //             ->map(function ($att) {
    //                 return [
    //                     'date' => $att->attendance_date->format('Y-m-d'),
    //                     'check_in' => $att->formatted_check_in,
    //                     'check_out' => $att->formatted_check_out,
    //                     'hours' => $att->formatted_work_hours,
    //                     'status' => $att->status, // uses the getStatusAttribute from model
    //                 ];
    //             });

    //         // 5. Leave Record List (Recent)
    //         $leaveList = LeaveRecord::with('leaveType')
    //             ->where('employee_id', '=', $employee->id)
    //             ->orderBy('start_date', 'desc')
    //             ->limit(20)
    //             ->get()
    //             ->map(function ($leave) {
    //                 return [
    //                     'id' => $leave->id,
    //                     'leave_type' => $leave->leaveType->name ?? 'Unknown',
    //                     'start_date' => $leave->start_date->format('Y-m-d'),
    //                     'end_date' => $leave->end_date->format('Y-m-d'),
    //                     'status' => $leave->status,
    //                     'reason' => $leave->reason,
    //                     'duration' => $leave->duration_description,
    //                 ];
    //             });

    //         return response()->json([
    //             'success' => true,
    //             'type' => 'individual_report',
    //             'data' => [
    //                 'employee_name' => $employee->full_name,
    //                 'attendance' => $attendanceStats,
    //                 'leaves' => $leaveStats,
    //                 'leave_balance' => $leaveBalances,
    //                 'attendance_list' => $attendanceList,
    //                 'leave_list' => $leaveList,
    //                 'summary' => [
    //                     'month' => Carbon::now()->format('F'),
    //                     'year' => $currentYear,
    //                 ]
    //             ]
    //         ], 200);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => ['Error fetching report: ' . $e->getMessage()],
    //         ], 500);
    //     }
    // }

    public function index(Request $request)
    {
        try {
            $user = $request->user();

            // 1. HR Admin with no employee_id → employee list
            if ($user->hasPermission('api.report.all') && ! $request->has('employee_id')) {
                $employees = Employee::with('department')
                    ->where('company_id', $user->company_id)
                    ->where('status', 'active')
                    ->get()
                    ->map(fn ($emp) => [
                        'id' => $emp->id,
                        'name' => $emp->full_name,
                        'department' => $emp->department->department ?? 'N/A',
                        'designation' => $emp->designation ?? 'N/A',
                        'profile_image' => $emp->profile_picture
                            ? asset('storage/'.$emp->profile_picture)
                            : null,
                    ]);

                return response()->json([
                    'success' => true,
                    'type' => 'employee_list',
                    'data' => $employees->values(),   // ensures JSON array, not object
                ], 200);
            }

            // 2. Resolve which employee to show
            if ($request->has('employee_id') && $user->hasPermission('api.report.all')) {
                $employee = Employee::find($request->employee_id);
            } else {
                $employee = $user;   // authenticated employee views own report
            }

            if (! $employee) {
                return response()->json([
                    'success' => false,
                    'message' => ['Employee not found'],
                ], 404);
            }

            if (! $user->hasPermission('api.report.individual') && ! $user->hasPermission('api.report.all')) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $currentYear = now()->year;
            $currentMonth = now()->month;

            // Load attendance records ONCE — reuse for both stats and list
            $attendanceRecords = EmployeeAttendance::where('employee_id', $employee->id)
                ->whereMonth('attendance_date', $currentMonth)
                ->whereYear('attendance_date', $currentYear)
                ->orderBy('attendance_date', 'desc')
                ->get();

            // Derive present/late from the model's computed status accessor (not hardcoded)
            $attendanceStats = [
                'present' => $attendanceRecords->filter(fn ($a) => $a->status === 'present')->count(),
                'late' => $attendanceRecords->filter(fn ($a) => $a->status === 'late')->count(),
                'absent' => 0,
                'total_this_month' => $attendanceRecords->count(),
            ];

            // Leave statistics
            $leaveStats = [
                'approved' => LeaveRecord::where('employee_id', $employee->id)->where('status', 'approved')->count(),
                'pending' => LeaveRecord::where('employee_id', $employee->id)->where('status', 'pending')->count(),
                'rejected' => LeaveRecord::where('employee_id', $employee->id)->where('status', 'rejected')->count(),
                'total_requests' => LeaveRecord::where('employee_id', $employee->id)->count(),
            ];

            // Leave balance — cast to float so Flutter doesn't get string type errors
            $leaveBalances = LeaveBalance::with('leaveType')
                ->where('employee_id', $employee->id)
                ->where('year', $currentYear)
                ->get()
                ->map(fn ($b) => [
                    'leave_type' => $b->leaveType->name ?? 'Unknown',
                    'total' => (float) $b->total_leaves,
                    'used' => (float) $b->used_leaves,
                    'remaining' => (float) $b->remaining_leaves,
                    'formatted' => $b->formatted_balance,
                ]);

            // Attendance list reused from the already-loaded collection
            $attendanceList = $attendanceRecords->map(fn ($att) => [
                'date' => $att->attendance_date->format('Y-m-d'),
                'check_in' => $att->formatted_check_in,
                'check_out' => $att->formatted_check_out,
                'hours' => $att->formatted_work_hours,
                'status' => $att->status,
            ]);

            // Recent leave records
            $leaveList = LeaveRecord::with('leaveType')
                ->where('employee_id', $employee->id)
                ->orderBy('start_date', 'desc')
                ->limit(20)
                ->get()
                ->map(fn ($leave) => [
                    'id' => $leave->id,
                    'leave_type' => $leave->leaveType->name ?? 'Unknown',
                    'start_date' => $leave->start_date->format('Y-m-d'),
                    'end_date' => $leave->end_date->format('Y-m-d'),
                    'status' => $leave->status,
                    'reason' => $leave->reason,
                    'duration' => $leave->duration_description,
                ]);

            return response()->json([
                'success' => true,
                'type' => 'individual_report',
                'data' => [
                    'employee_name' => $employee->full_name,
                    'attendance' => $attendanceStats,
                    'leaves' => $leaveStats,
                    'leave_balance' => $leaveBalances->values(),
                    'attendance_list' => $attendanceList->values(),
                    'leave_list' => $leaveList->values(),
                    'summary' => [
                        'month' => Carbon::now()->format('F'),
                        'year' => $currentYear,
                    ],
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => ['Error fetching report: '.$e->getMessage()],
            ], 500);
        }
    }
}
