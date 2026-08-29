<?php

namespace App\Http\Controllers\Api\HRM;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Notification;

class AttendanceController extends Controller
{
    /**
     * Check in employee.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkIn(Request $request)
    {
        // Get current date and time
        $currentDateTime = now();

        // Check for duplicate attendance
        $existing = EmployeeAttendance::where('employee_id', Auth::user()->id)
            ->whereDate('attendance_date', $currentDateTime->format('Y-m-d'))
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => ['Attendance already recorded for today.'],
            ], 409);
        }

        // Validate working_type
        $request->validate([
            'working_type' => 'nullable|in:on-site,remote',
        ]);

        try {

            $employee = Employee::where('id', Auth::user()->id)->first();
            $attendance = EmployeeAttendance::create([
                'employee_id' => Auth::user()->id,
                'company_id' => Auth::user()->company_id,
                'attendance_date' => $currentDateTime->format('Y-m-d'),
                'check_in' => $currentDateTime->format('H:i'),
                'working_type' => $request->working_type ?? 'on-site',
                'start_time' => $employee->start_time,
                'end_time' => $employee->end_time,
            ]);

            Notification::create([
                'title' => 'Check-in Successful',
                'description' => 'You have successfully checked in at ' . $currentDateTime->format('h:i A'),
                'employee_id' => Auth::user()->id,
                'is_seen' => false,
            ]);

            // Send FCM Notification
            \App\Services\FcmService::sendNotification(
                Auth::user()->fcm_token,
                [
                    'title' => 'Check-in Successful',
                    'body' => 'You have successfully checked in at ' . $currentDateTime->format('h:i A'),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => ['Check in successful.'],
                'data' => $attendance,
            ], 201);

        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return response()->json([
                'success' => false,
                'message' => [$e->getMessage()],
            ], 500);
        }
    }

    /**
     * Check out employee.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkOut(Request $request)
    {
        // Calculate check out time
        $checkOut = now()->format('H:i');

        // Find existing attendance record
        $attendance = EmployeeAttendance::where('employee_id', Auth::user()->id)
            ->whereDate('attendance_date', now()->format('Y-m-d'))
            ->first();

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => ['No check-in record found for today.'],
            ], 404);
        }

        if ($attendance->check_out) {
            return response()->json([
                'success' => false,
                'message' => ['Already checked out for today.'],
            ], 409);
        }

        $checkInTime = Carbon::parse($attendance->check_in);
        $checkOutTime = Carbon::parse($checkOut);
        $duration = $checkOutTime->diffInSeconds($checkInTime);

        try {
            $attendance->update([
                'check_out' => $checkOut,
            ]);

            $formattedTime = Carbon::parse($checkOut)->format('h:i A');

            Notification::create([
                'title' => 'Check-out Successful',
                'description' => 'You have successfully checked out at ' . $formattedTime,
                'employee_id' => Auth::user()->id,
                'is_seen' => false,
            ]);

            // Send FCM Notification
            \App\Services\FcmService::sendNotification(
                Auth::user()->fcm_token,
                [
                    'title' => 'Check-out Successful',
                    'body' => 'You have successfully checked out at ' . $formattedTime,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => ['Check out successful.'],
                'data' => array_merge($attendance->toArray(), [
                    'overtime' => $attendance->formatted_overtime,
                ]),
            ], 200);

        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return response()->json([
                'success' => false,
                'message' => [$e->getMessage()],
            ], 500);
        }
    }

    /**
     * Check attendance for the day
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkAttendance(Request $request)
    {
        // Get current date
        $currentDate = now()->format('Y-m-d');

        // Find attendance record for the day
        $attendance = EmployeeAttendance::where('employee_id', Auth::user()->id)
            ->whereDate('attendance_date', $currentDate)
            ->first();

        if (!$attendance) {
            return response()->json([
                'check_in' => false,
                'check_out' => false,
            ], 200);
        }

        $response = [
            'check_in' => (bool) $attendance->check_in,
            'check_out' => (bool) $attendance->check_out,
            'date' => Carbon::parse($attendance->attendance_date)->format('Y-m-d'),
            'check_in_time' => $attendance->check_in,
            'check_out_time' => $attendance->check_out,
            'working_type' => $attendance->working_type,
        ];

        return response()->json($response, 200);
    }

    /**
     * Get employee attendance history
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function attendanceHistory()
    {
        try {
            // Get current date
            $currentDate = now()->format('Y-m-d');

            // Find attendance record for the current date
            $attendance = EmployeeAttendance::where('employee_id', Auth::user()->id)
                ->whereDate('attendance_date', $currentDate)
                ->first(['attendance_date', 'check_in', 'check_out']);

            // Format the response to match your Flutter app expectation
            $formattedData = [
                'date' => $attendance->attendance_date,
                'check_in_time' => $attendance->check_in,
                'check_out_time' => $attendance->check_out,
            ];

            return response()->json([
                'success' => true,
                'data' => $formattedData,
            ], 200);

        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return response()->json([
                'success' => false,
                'message' => [$e->getMessage()],
            ], 500);
        }
    }

    // public function fingerprintPunch(Request $request)
    // {
    //     // 1. Receive the scanned template from C#
    //     $scannedBase64 = $request->fingerprint_template;
    //     $employee = Employee::where('fingerprint_template', $scannedBase64)->first();

    //     if (! $employee) {
    //         return response()->json(['success' => false, 'message' => ['User not recognized']], 404);
    //     }

    //     $currentDate = now()->format('Y-m-d');
    //     $currentTime = now()->format('H:i');

    //     // 3. Find today's record
    //     $attendance = EmployeeAttendance::where('employee_id', $employee->id)
    //         ->whereDate('attendance_date', $currentDate)
    //         ->first();

    //     // LOGIC: If no record -> Check In. If record exists and no check_out -> Check Out.
    //     if (! $attendance) {
    //         // CHECK IN
    //         $newRecord = EmployeeAttendance::create([
    //             'employee_id' => $employee->id,
    //             'company_id' => $employee->company_id,
    //             'attendance_date' => $currentDate,
    //             'check_in' => $currentTime,
    //         ]);

    //         return response()->json(['success' => true, 'message' => 'Check-in successful', 'data' => $newRecord]);
    //     }

    //     if (! $attendance->check_out) {
    //         // CHECK OUT
    //         $attendance->update(['check_out' => $currentTime]);

    //         return response()->json(['success' => true, 'message' => 'Check-out successful', 'data' => $attendance]);
    //     }

    //     return response()->json(['success' => false, 'message' => 'Already finished for today.'], 409);
    // }

    // public function fingerprintPunch(Request $request)
    // {
    //     try {
    //         $scannedData = $request->email;
    //         $employee = Employee::where('email', $scannedData)->first();

    //         if (! $employee) {
    //             return response()->json(['success' => false, 'message' => 'Fingerprint not recognized in system.']);
    //         }

    //         $today = now()->format('Y-m-d');
    //         $now = now()->format('H:i');

    //         $attendance = EmployeeAttendance::where('employee_id', $employee->id)
    //             ->whereDate('attendance_date', $today)
    //             ->first();

    //         if (! $attendance) {
    //             EmployeeAttendance::create([
    //                 'employee_id' => $employee->id,
    //                 'company_id' => $employee->company_id,
    //                 'attendance_date' => $today,
    //                 'check_in' => $now,
    //             ]);

    //             return response()->json(['success' => true, 'message' => 'SUCCESS: Check-in for '.$employee->full_name.' at '.$now]);
    //         }

    //         if (! $attendance->check_out) {
    //             $attendance->update(['check_out' => $now]);

    //             return response()->json(['success' => true, 'message' => 'SUCCESS: Check-out for '.$employee->full_name.' at '.$now]);
    //         }

    //         return response()->json(['success' => false, 'message' => 'ERROR: '.$employee->full_name.' has already completed their shift today.']);

    //     } catch (\Throwable $th) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => $th->getMessage(),
    //         ]);
    //     }
    // }

    public function fingerprintPunch(Request $request)
    {
        try {
            $scannedData = $request->email;
            $employee = Employee::where('email', $scannedData)->first();

            if (!$employee) {
                return response()->json(['success' => false, 'message' => 'Fingerprint not recognized.']);
            }

            $now = now(); // Current Carbon instance
            $today = $now->format('Y-m-d');
            $currentTime = $now->format('H:i');

            $attendance = EmployeeAttendance::where('employee_id', $employee->id)
                ->whereDate('attendance_date', $today)
                ->first();

            // 1. Handle Initial Check-in
            if (!$attendance) {
                EmployeeAttendance::create([
                    'employee_id' => $employee->id,
                    'company_id' => $employee->company_id,
                    'attendance_date' => $today,
                    'check_in' => $currentTime,
                    'start_time' => $employee->start_time,
                    'end_time' => $employee->end_time
                ]);

                Notification::create([
                    'title' => 'Check-in Successful',
                    'description' => "You have successfully checked in via fingerprint at {$currentTime}",
                    'employee_id' => $employee->id,
                    'is_seen' => false,
                ]);

                // Send FCM Notification
                \App\Services\FcmService::sendNotification(
                    $employee->fcm_token,
                    [
                        'title' => 'Check-in Successful',
                        'body' => "You have successfully checked in via fingerprint at {$currentTime}",
                    ]
                );

                return response()->json(['success' => true, 'message' => "SUCCESS: Check-in for {$employee->full_name} at {$currentTime}"]);
            }

            // 2. Handle Check-out with 10-minute gap check
            if (!$attendance->check_out) {
                $checkInTime = Carbon::parse($attendance->check_in);

                // Calculate difference in minutes
                $minutesSinceCheckIn = $checkInTime->diffInMinutes($now);

                if ($minutesSinceCheckIn < 10) {
                    $waitRows = 10 - $minutesSinceCheckIn;

                    return response()->json([
                        'success' => false,
                        'message' => "TOO SOON: Please wait {$waitRows} more minute(s) before checking out.",
                    ]);
                }

                $attendance->update(['check_out' => $currentTime]);

                Notification::create([
                    'title' => 'Check-out Successful',
                    'description' => "You have successfully checked out via fingerprint at {$currentTime}",
                    'employee_id' => $employee->id,
                    'is_seen' => false,
                ]);

                // Send FCM Notification
                \App\Services\FcmService::sendNotification(
                    $employee->fcm_token,
                    [
                        'title' => 'Check-out Successful',
                        'body' => "You have successfully checked out via fingerprint at {$currentTime}",
                    ]
                );

                return response()->json(['success' => true, 'message' => "SUCCESS: Check-out for {$employee->full_name} at {$currentTime}"]);
            }

            // 3. Already Checked Out
            return response()->json(['success' => false, 'message' => "ERROR: {$employee->full_name} has already completed their shift today."]);

        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()]);
        }
    }

    public function getTemplates()
    {
        // Get only employees with a fingerprint
        $employees = Employee::whereNotNull('fingerprint_template')
            ->select('id', 'full_name', 'email', 'fingerprint_template')
            ->get();

        return response()->json($employees);
    }

    public function getAllEmployee()
    {
        try {
            // $employees = Employee::whereNull('fingerprint_template')->get();
            $employees = Employee::select('id', 'full_name', 'email', 'fingerprint_template')
                ->where('company_id', 1)->get();

            return response()->json([
                'success' => true,
                'data' => $employees,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ]);
        }
    }

    public function getTodayAttendance()
    {
        try {
            $today = now()->format('Y-m-d');

            $attendance = EmployeeAttendance::where('employee_id', Auth::user()->id)
                ->whereDate('attendance_date', $today)
                ->first();

            if (!$attendance) {
                return response()->json([
                    'success' => false,
                    'message' => 'No attendance record found for today.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'date' => $attendance->attendance_date,
                    'check_in' => $attendance->check_in,
                    'check_out' => $attendance->check_out,
                    'working_type' => $attendance->working_type,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getAllAttendance()
    {
        try {
            $today = now()->format('Y-m-d');
            $attendance = EmployeeAttendance::with('employee')
                ->where('company_id', 1)
                ->whereDate('attendance_date', $today)
                ->orderBy('attendance_date', 'desc')
                ->get();

            $attendance = $attendance->map(function ($record) {
                return [
                    'employee_name' => $record->employee->full_name,
                    'email'         => $record->employee->email,
                    'check_in'      => $record->check_in,
                    'check_out'     => $record->check_out,
                    'overtime'      => $record->formatted_overtime,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $attendance,
            ], 200);

        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
