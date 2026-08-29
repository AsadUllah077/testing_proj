<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeAttendance;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ScannerController extends Controller
{
    public function index()
    {
        return view('scanner');
    }

    public function processAttendance(Request $request)
    {
        $request->validate([
            'employee_card_id' => 'required|string',
        ]);

        $employeeCardId = trim($request->employee_card_id);

        // Find employee by card ID
        $employee = Employee::where('employee_card_id', $employeeCardId)
            ->where('status', 'active')
            ->first();

        if (! $employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee not found or inactive. Please contact HR.',
                'type' => 'error',
            ], 404);
        }

        $today = Carbon::today();

        // Check if there's already an attendance record for today
        $attendance = EmployeeAttendance::where('employee_id', $employee->id)
            ->whereDate('attendance_date', $today)
            ->first();

        if (! $attendance) {
            // Create new check-in record
            $attendance = EmployeeAttendance::create([
                'employee_id' => $employee->id,
                'attendance_date' => $today,
                'check_in' => Carbon::now()->format('H:i:s'),
                'check_out' => null,
                'total_work_hours' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Check-in successful!',
                'type' => 'check_in',
                'employee' => [
                    'name' => $employee->full_name,
                    'designation' => $employee->designation,
                    'department' => $employee->department->name ?? 'N/A',
                ],
                'check_in' => Carbon::parse($attendance->check_in)->format('h:i A'),
            ]);
        }

        if ($attendance->check_in && ! $attendance->check_out) {
            // Process check-out
            $attendance->check_out = Carbon::now()->format('H:i:s');
            $attendance->save();
            $attendance->calculateWorkHours2();

            return response()->json([
                'success' => true,
                'message' => 'Check-out successful!',
                'type' => 'check_out',
                'employee' => [
                    'name' => $employee->full_name,
                    'designation' => $employee->designation,
                    'department' => $employee->department->name ?? 'N/A',
                ],
                'check_in' => Carbon::parse($attendance->check_in)->format('h:i A'),
                'check_out' => Carbon::parse($attendance->check_out)->format('h:i A'),
                'total_hours' => number_format($attendance->total_work_hours, 2),
            ]);
        }

        if ($attendance->check_in && $attendance->check_out) {
            // Already checked in and out for today
            return response()->json([
                'success' => false,
                'message' => 'You have already completed check-in and check-out for today.',
                'type' => 'completed',
                'employee' => [
                    'name' => $employee->full_name,
                ],
                'check_in' => Carbon::parse($attendance->check_in)->format('h:i A'),
                'check_out' => Carbon::parse($attendance->check_out)->format('h:i A'),
                'total_hours' => number_format($attendance->total_work_hours, 2),
            ], 400);
        }

        return response()->json([
            'success' => false,
            'message' => 'An error occurred. Please try again.',
            'type' => 'error',
        ], 500);
    }
}
