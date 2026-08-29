<?php

// namespace App\Http\Controllers\Api\HRM;

// use App\Http\Controllers\Controller;
// use App\Models\Employee;
// use App\Models\LeaveBalance;
// use App\Models\LeaveRecord;
// use App\Models\LeaveType;
// use Carbon\Carbon;
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Log;
// use Illuminate\Support\Facades\Validator;

// class LeaveController extends Controller
// {
//     /**
//      * Store a new leave request.
//      *
//      * @return \Illuminate\Http\JsonResponse
//      */
//     public function store(Request $request)
//     {
//         DB::beginTransaction();
//         try {
//             // 1. Validate incoming request
//             $validator = Validator::make(
//                 $request->all(),
//                 [
//                     'leave_type_id' => 'required|exists:leave_types,id',
//                     'start_date' => 'required|date',
//                     'end_date' => 'required|date|after_or_equal:start_date',
//                     'half' => 'nullable|in:first_half,second_half',
//                     'early_leave_time' => 'nullable|date_format:H:i',
//                     'adjustment_reason' => 'nullable|string|max:1000',
//                     'file_upload' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
//                 ],
//                 [
//                     'leave_type_id.required' => 'Please select a leave type.',
//                     'start_date.required' => 'Start date is required.',
//                     'end_date.required' => 'End date is required.',
//                     'end_date.after_or_equal' => 'End date must be after or equal to start date.',
//                     'file_upload.max' => 'File size must not exceed 5MB.',
//                     'file_upload.mimes' => 'Only PDF, JPG, PNG, DOC, and DOCX files are allowed.',
//                 ]
//             );

//             if ($validator->fails()) {
//                 return response()->json([
//                     'success' => false,
//                     'message' => 'Validation error',
//                     'errors' => $validator->errors(),
//                 ], 422);
//             }

//             // Calculate total leave days
//             $startDate = Carbon::parse($request->start_date);
//             $endDate = Carbon::parse($request->end_date);
//             $year = $startDate->year;
//             $totalDays = $this->calculateLeaveDays($startDate, $endDate, $request->half, $request->user()->id);

//             // Check if leave balance exists for this employee and leave type
//             $balance = LeaveBalance::where('employee_id', $request->user()->id)
//                 ->where('leave_type_id', $request->leave_type_id)
//                 ->where('year', $year)
//                 ->first();

//             // If no balance exists, get default total leaves from leave type
//             if (! $balance) {
//                 $leaveType = LeaveType::find($request->leave_type_id);

//                 // Create a new leave balance record
//                 $balance = LeaveBalance::create([
//                     'employee_id' => $request->user()->id,
//                     'company_id' => Employee::find($request->user()->id)->company_id ?? Auth::user()->company_id,
//                     'leave_type_id' => $request->leave_type_id,
//                     'total_leaves' => $leaveType->count ?? 20, // Default to 20 if not set
//                     'used_leaves' => 0,
//                     'remaining_leaves' => $leaveType->count ?? 20,
//                     'year' => $year,
//                 ]);
//             }

//             // Check if employee has sufficient leave balance
//             if ($balance->remaining_leaves < $totalDays) {
//                 return response()->json([
//                     'success' => false,
//                     'message' => 'Insufficient leave balance. Available: '.$balance->remaining_leaves.' days, Requested: '.$totalDays.' days.',
//                 ], 400);
//             }

//             // Handle file upload
//             $filePath = null;
//             if ($request->hasFile('file_upload')) {
//                 $file = $request->file('file_upload');
//                 $fileName = time().'_'.$file->getClientOriginalName();
//                 $filePath = $file->storeAs('leave_documents', $fileName, 'public');
//             }

//             $employee = Employee::findOrFail($request->employee_id);

//             // Create leave record
//             $leaveRecord = LeaveRecord::create([
//                 'employee_id' => $request->employee_id,
//                 'company_id' => $employee->company_id,
//                 'leave_type_id' => $request->leave_type_id,
//                 'start_date' => $request->start_date,
//                 'end_date' => $request->end_date,
//                 'half' => $request->half,
//                 'early_leave_time' => $request->early_leave_time,
//                 'adjustment_reason' => $request->adjustment_reason,
//                 'file_upload' => $filePath,
//                 'created_by' => Auth::id(),
//             ]);

//             // Update leave balance
//             $balance->incrementUsedLeaves($totalDays);

//             // Reload relations for the notification message
//             $leaveRecord->load(['employee', 'leaveType']);

//             // Send FCM
//             $recipientEmail = 'john.doe@example.com';
//             $recipient = Employee::where('email', $recipientEmail)->first();

//             if ($recipient && $recipient->fcm_token) {
//                 try {
//                     \App\Services\FcmService::sendNotification(
//                         $recipient->fcm_token,
//                         [
//                             'title' => 'New Leave Request',
//                             'body' => "{$employee->full_name} has submitted a new leave request for {$leaveRecord->leaveType->name}.",
//                         ]
//                     );
//                 } catch (\Exception $e) {
//                     Log::warning('FCM notification failed: '.$e->getMessage());
//                 }
//             }

//             DB::commit();

//             return response()->json([
//                 'success' => true,
//                 'message' => 'Leave record created successfully!',
//                 'data' => $leaveRecord,
//             ], 201);

//         } catch (\Exception $e) {
//             DB::rollBack();
//             Log::error('Error creating leave record via API: '.$e->getMessage());

//             return response()->json([
//                 'success' => false,
//                 'message' => 'Error creating leave record: '.$e->getMessage(),
//             ], 500);
//         }
//     }

//     /**
//      * Helper method to calculate total leave days based on dates and half day selection.
//      */
//     private function calculateLeaveDays($startDate, $endDate, $halfDay = null, $employeeId = null)
//     {
//         $employee = $employeeId ? Employee::find($employeeId) : null;

//         $record = new LeaveRecord([
//             'start_date' => $startDate,
//             'end_date' => $endDate,
//             'half' => $halfDay,
//         ]);

//         if ($employee) {
//             $record->setRelation('employee', $employee);
//         }

//         return $record->calculateTotalDays();
//     }

//     /**
//      * List leave requests for the authenticated employee.
//      *
//      * @return \Illuminate\Http\JsonResponse
//      */
//     public function index(Request $request)
//     {
//         try {
//             $user = $request->user();

//             // If user has permission to view all, get all records. Otherwise, filter by employee ID linked to user.
//             $query = LeaveRecord::with('leaveType', 'employee');

//             // if ($user->hasPermission('api.leave.view_all')) {
//             //     $employee = $user->employee;
//             //     if (! $employee) {
//             //         return response()->json(['success' => false, 'message' => 'Employee record not found for user.'], 404);
//             //     }
//             //     $query->where('employee_id', $employee->id);
//             // }

//             $leaveRequests = $query->orderBy('created_at', 'desc')->get();

//             // Map leave requests to a clean structure
//             $data = $leaveRequests->map(function ($record) {
//                 return [
//                     'id' => $record->id,
//                     'employee_name' => $record->employee->full_name,
//                     'leave_type' => $record->leaveType->name,
//                     'start_date' => $record->start_date->format('d M, Y'),
//                     'end_date' => $record->end_date->format('d M, Y'),
//                     'total_days' => $record->calculateTotalDays(),
//                     'status' => ucfirst($record->status),
//                     'remarks' => $record->remarks,
//                     'attachment' => $record->file_path ? asset('storage/'.$record->file_path) : null,
//                     'created_at' => $record->created_at->format('d M, Y h:i A'),
//                 ];
//             });

//             return response()->json([
//                 'success' => true,
//                 'data' => $data,
//             ], 200);
//         } catch (\Exception $e) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Internal Error: '.$e->getMessage(),
//             ], 500);
//         }
//     }

//     public function getLeaveTypes()
//     {
//         try {
//             return response()->json([
//                 'success' => true,
//                 'data' => LeaveType::all(),
//             ], 200);
//         } catch (\Throwable $th) {
//             return response()->json([
//                 'success' => false,
//                 'message' => $th->getMessage(),
//             ], 500);
//         }
//     }

//     public function approvedLeaveRequest($id)
//     {
//         try {
//             if (! Auth::user()->hasPermission('api.leave.approve')) {
//                 return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
//             }

//             $leaveRecord = LeaveRecord::where('id', $id)->where('status', 'pending')->first();
//             if (! $leaveRecord) {
//                 return response()->json([
//                     'success' => false,
//                     'message' => 'Leave request not found',
//                 ]);
//             }
//             $leaveRecord->status = 'approved';
//             $leaveRecord->save();

//             return response()->json([
//                 'success' => true,
//                 'message' => 'Leave request approved successfully',
//             ]);
//         } catch (\Throwable $th) {
//             return response()->json([
//                 'success' => false,
//                 'message' => $th->getMessage(),
//             ]);
//         }
//     }

//     public function rejectLeaveRequest($id)
//     {
//         try {
//             if (! Auth::user()->hasPermission('api.leave.reject')) {
//                 return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
//             }

//             $leaveRecord = LeaveRecord::where('id', $id)->where('status', 'pending')->first();
//             if (! $leaveRecord) {
//                 return response()->json([
//                     'success' => false,
//                     'message' => 'Leave request not found',
//                 ]);
//             }
//             $leaveRecord->status = 'rejected';
//             $leaveRecord->save();

//             return response()->json([
//                 'success' => true,
//                 'message' => 'Leave request rejected successfully',
//             ]);
//         } catch (\Throwable $th) {
//             return response()->json([
//                 'success' => false,
//                 'message' => $th->getMessage(),
//             ]);
//         }
//     }
// }

namespace App\Http\Controllers\Api\HRM;

use App\Http\Controllers\Controller;
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
use Illuminate\Support\Facades\Validator;

class LeaveController extends Controller
{
    /**
     * Store a new leave request.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            // 1. Validate incoming request
            $validator = Validator::make(
                $request->all(),
                [
                    'leave_type_id' => 'required|exists:leave_types,id',
                    'start_date' => 'required|date',
                    'end_date' => 'required|date|after_or_equal:start_date',
                    'half' => 'nullable|in:first_half,second_half',
                    'early_leave_time' => 'nullable|date_format:H:i',
                    'adjustment_reason' => 'nullable|string|max:1000',
                    'file_upload' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
                ],
                [
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
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            $year = $startDate->year;
            $totalDays = $this->calculateLeaveDays($startDate, $endDate, $request->half, $request->user()->id);

            // Check if leave balance exists for this employee and leave type
            $balance = LeaveBalance::where('employee_id', $request->user()->id)
                ->where('leave_type_id', $request->leave_type_id)
                ->where('year', $year)
                ->first();

            // If no balance exists, get default total leaves from leave type
            if (! $balance) {
                $leaveType = LeaveType::find($request->leave_type_id);

                // Create a new leave balance record
                $balance = LeaveBalance::create([
                    'employee_id' => $request->user()->id,
                    'company_id' => Employee::find($request->user()->id)->company_id ?? Auth::user()->company_id,
                    'leave_type_id' => $request->leave_type_id,
                    'total_leaves' => $leaveType->count ?? 20,
                    'used_leaves' => 0,
                    'remaining_leaves' => $leaveType->count ?? 20,
                    'year' => $year,
                ]);
            }

            // Check if employee has sufficient leave balance
            if ($balance->remaining_leaves < $totalDays) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient leave balance. Available: '.$balance->remaining_leaves.' days, Requested: '.$totalDays.' days.',
                ], 400);
            }

            // Handle file upload
            $filePath = null;
            if ($request->hasFile('file_upload')) {
                $file = $request->file('file_upload');
                $fileName = time().'_'.$file->getClientOriginalName();
                $filePath = $file->storeAs('leave_documents', $fileName, 'public');
            }

            $employee = Employee::findOrFail($request->employee_id);

            // Create leave record
            $leaveRecord = LeaveRecord::create([
                'employee_id' => $request->employee_id,
                'company_id' => $employee->company_id,
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
            $balance->incrementUsedLeaves($totalDays);

            // Reload relations for the notification message
            $leaveRecord->load(['employee', 'leaveType']);

            // Notify all employees with api.leave.view_all permission (same company)
            $notifTitle = 'New Leave Request';
            $notifBody = "{$employee->full_name} has submitted a new leave request for {$leaveRecord->leaveType->name}.";

            $recipients = Employee::where('company_id', $employee->company_id)
                ->where('id', '!=', $employee->id)
                ->get()
                ->filter(fn ($e) => $e->hasPermission('api.leave.view_all'));

            foreach ($recipients as $recipient) {
                Notification::create([
                    'title' => $notifTitle,
                    'description' => $notifBody,
                    'employee_id' => $recipient->id,
                    'is_seen' => false,
                ]);

                if ($recipient->fcm_token) {
                    try {
                        \App\Services\FcmService::sendNotification(
                            $recipient->fcm_token,
                            ['title' => $notifTitle, 'body' => $notifBody]
                        );
                    } catch (\Exception $e) {
                        Log::warning('FCM notification failed for leave request: '.$e->getMessage());
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Leave record created successfully!',
                'data' => $leaveRecord,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating leave record via API: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error creating leave record: '.$e->getMessage(),
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
     * List leave requests.
     * - HR Admin (api.leave.view_all): returns ALL employees' leaves
     * - Regular Employee: returns only their own leaves
     *
     * @return \Illuminate\Http\JsonResponse
     */
    // public function index(Request $request)
    // {
    //     try {
    //         $user = $request->user();

    //         $query = LeaveRecord::with('leaveType', 'employee');

    //         // If the user does NOT have view_all permission, restrict to their own records
    //         if (! $user->hasPermission('api.leave.view_all')) {
    //             $query->where('employee_id', $user->id);
    //         }

    //         $leaveRequests = $query->orderBy('created_at', 'desc')->get();

    //         // Map leave requests to a clean structure
    //         $data = $leaveRequests->map(function ($record) {
    //             return [
    //                 'id' => $record->id,
    //                 'employee_name' => $record->employee->full_name ?? 'N/A',
    //                 'leave_type' => $record->leaveType->name ?? 'N/A',
    //                 'start_date' => $record->start_date->format('d M, Y'),
    //                 'end_date' => $record->end_date->format('d M, Y'),
    //                 'total_days' => $record->calculateTotalDays(),
    //                 'status' => ucfirst($record->status),
    //                 'remarks' => $record->remarks,
    //                 'attachment' => $record->file_path ? asset('storage/'.$record->file_path) : null,
    //                 'created_at' => $record->created_at->format('d M, Y h:i A'),
    //             ];
    //         });

    //         // Also return the caller's permissions so Flutter knows what actions to show
    //         $permissions = $user->permissions()->pluck('name')->toArray();

    //         return response()->json([
    //             'success' => true,
    //             'data' => $data,
    //             'permissions' => $permissions,
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Internal Error: '.$e->getMessage(),
    //         ], 500);
    //     }
    // }

    // public function index(Request $request)
    // {
    //     try {
    //         $user = $request->user();

    //         if (! $user) {
    //             return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
    //         }

    //         $query = LeaveRecord::with(['leaveType', 'employee']);

    //         // If the user does NOT have view_all permission, restrict to their own records
    //         if (! $user->hasPermission('api.leave.view_all')) {
    //             // Employee ID is the same as the authenticated user's ID
    //             $query->where('employee_id', $user->id);
    //         }

    //         $leaveRequests = $query->orderBy('created_at', 'desc')->get();

    //         $data = $leaveRequests->map(function ($record) {
    //             // Guard against missing relations
    //             $employeeName = optional($record->employee)->full_name ?? 'N/A';
    //             $leaveTypeName = optional($record->leaveType)->name ?? 'N/A';

    //             // Guard against null dates
    //             $startDate = $record->start_date
    //                 ? \Carbon\Carbon::parse($record->start_date)->format('d M, Y')
    //                 : '';
    //             $endDate = $record->end_date
    //                 ? \Carbon\Carbon::parse($record->end_date)->format('d M, Y')
    //                 : '';
    //             $createdAt = $record->created_at
    //                 ? $record->created_at->format('d M, Y h:i A')
    //                 : '';

    //             $totalDays = 0;
    //             try {
    //                 $totalDays = $record->calculateTotalDays();
    //             } catch (\Exception $e) {
    //                 \Illuminate\Support\Facades\Log::warning('calculateTotalDays failed for record '.$record->id.': '.$e->getMessage());
    //             }

    //             return [
    //                 'id' => $record->id,
    //                 'employee_name' => $employeeName,
    //                 'leave_type' => $leaveTypeName,
    //                 'start_date' => $startDate,
    //                 'end_date' => $endDate,
    //                 'total_days' => $totalDays,
    //                 'status' => ucfirst($record->status ?? 'pending'),
    //                 'remarks' => $record->remarks ?? null,
    //                 'attachment' => $record->file_upload
    //                     ? asset('storage/'.$record->file_upload)
    //                     : null,
    //                 'created_at' => $createdAt,
    //             ];
    //         });

    //         return response()->json([
    //             'success' => true,
    //             'data' => $data,
    //         ], 200);

    //     } catch (\Exception $e) {
    //         \Illuminate\Support\Facades\Log::error('LeaveController@index error: '.$e->getMessage().' | Line: '.$e->getLine().' | File: '.$e->getFile());

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Internal Error: '.$e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function index(Request $request)
    {
        try {
            $user = $request->user();

            if (! $user) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
            }

            $query = LeaveRecord::with(['leaveType', 'employee']);

            // Scope to own records unless HR admin
            if (! $user->hasPermission('api.leave.view_all')) {
                $query->where('employee_id', $user->id);
            }

            // Status filter
            $status = $request->query('status');
            if ($status && in_array(strtolower($status), ['pending', 'approved', 'rejected'])) {
                $query->where('status', strtolower($status));
            }

            $perPage = (int) $request->query('per_page', 20);
            $perPage = max(1, min($perPage, 100)); // clamp between 1-100
            $paginated = $query->orderBy('created_at', 'desc')->paginate($perPage);
            $data = collect($paginated->items())->map(function ($record) {
                $employeeName = optional($record->employee)->full_name ?? 'N/A';
                $leaveTypeName = optional($record->leaveType)->name ?? 'N/A';

                $startDate = $record->start_date
                    ? \Carbon\Carbon::parse($record->start_date)->format('d M, Y') : '';
                $endDate = $record->end_date
                    ? \Carbon\Carbon::parse($record->end_date)->format('d M, Y') : '';
                $createdAt = $record->created_at
                    ? $record->created_at->format('d M, Y h:i A') : '';

                $totalDays = 0;
                try {
                    $totalDays = $record->calculateTotalDays();
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning('calculateTotalDays failed for record '.$record->id.': '.$e->getMessage());
                }

                return [
                    'id' => $record->id,
                    'employee_name' => $employeeName,
                    'leave_type' => $leaveTypeName,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'total_days' => $totalDays,
                    'status' => ucfirst($record->status ?? 'pending'),
                    'remarks' => $record->remarks ?? null,
                    'attachment' => $record->file_upload
                        ? asset('storage/'.$record->file_upload) : null,
                    'created_at' => $createdAt,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'totalPending' => ! $user->hasPermission('api.leave.view_all') ? LeaveRecord::where('employee_id', $user->id)->where('status', 'pending')->count() : LeaveRecord::where('status', 'pending')->count(),
                'totalApproved' => ! $user->hasPermission('api.leave.view_all') ? LeaveRecord::where('employee_id', $user->id)->where('status', 'approved')->count() : LeaveRecord::where('status', 'approved')->count(),
                'totalRejected' => ! $user->hasPermission('api.leave.view_all') ? LeaveRecord::where('employee_id', $user->id)->where('status', 'rejected')->count() : LeaveRecord::where('status', 'rejected')->count(),
                'pagination' => [
                    'current_page' => $paginated->currentPage(),
                    'last_page' => $paginated->lastPage(),
                    'per_page' => $paginated->perPage(),
                    'total' => $paginated->total(),
                    'has_more' => $paginated->hasMorePages(),
                ],
            ], 200);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error(
                'LeaveController@index error: '.$e->getMessage().
                ' | Line: '.$e->getLine().' | File: '.$e->getFile()
            );

            return response()->json([
                'success' => false,
                'message' => 'Internal Error: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get details of a single leave request.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();

            $query = LeaveRecord::with(['leaveType', 'employee', 'createdBy'])
                ->where('id', $id);

            // Non-admin users can only view their own records
            if (! $user->hasPermission('api.leave.view_all')) {
                $query->where('employee_id', $user->id);
            }

            $record = $query->first();

            if (! $record) {
                return response()->json([
                    'success' => false,
                    'message' => 'Leave record not found.',
                ], 404);
            }

            $totalDays = 0;
            try {
                $totalDays = $record->calculateTotalDays();
            } catch (\Exception $e) {
                Log::warning('calculateTotalDays failed for record '.$record->id.': '.$e->getMessage());
            }

            // Load leave balance for this employee/leave type/year
            $balance = LeaveBalance::where('employee_id', $record->employee_id)
                ->where('leave_type_id', $record->leave_type_id)
                ->where('year', $record->start_date->year)
                ->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $record->id,
                    'employee_name' => optional($record->employee)->full_name ?? 'N/A',
                    'employee_id' => $record->employee_id,
                    'leave_type' => optional($record->leaveType)->name ?? 'N/A',
                    'leave_type_id' => $record->leave_type_id,
                    'start_date' => $record->start_date ? $record->start_date->format('d M, Y') : '',
                    'end_date' => $record->end_date ? $record->end_date->format('d M, Y') : '',
                    'total_days' => $totalDays,
                    'half_day' => $record->half,
                    'duration_description' => $record->duration_description,
                    'early_leave_time' => $record->formatted_early_leave_time,
                    'adjustment_reason' => $record->adjustment_reason,
                    'status' => ucfirst($record->status ?? 'pending'),
                    'remarks' => $record->remarks,
                    'attachment' => $record->file_upload ? asset('storage/'.$record->file_upload) : null,
                    'created_by' => optional($record->createdBy)->name ?? 'N/A',
                    'created_at' => $record->created_at ? $record->created_at->format('d M, Y h:i A') : '',
                    'leave_balance' => $balance ? [
                        'total_leaves' => $balance->total_leaves,
                        'used_leaves' => $balance->used_leaves,
                        'remaining_leaves' => $balance->remaining_leaves,
                        'year' => $balance->year,
                    ] : null,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('LeaveController@show error: '.$e->getMessage().' | Line: '.$e->getLine());

            return response()->json([
                'success' => false,
                'message' => 'Internal Error: '.$e->getMessage(),
            ], 500);
        }
    }

    public function getLeaveTypes()
    {
        try {
            return response()->json([
                'success' => true,
                'data' => LeaveType::all(),
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function approvedLeaveRequest(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            if (! Auth::user()->hasPermission('api.leave.approve')) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $leaveRecord = LeaveRecord::with(['employee', 'leaveType'])
                ->where('id', $id)
                ->where('status', 'pending')
                ->first();

            if (! $leaveRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pending leave request not found',
                ], 404);
            }

            $remarks = $request->input('remarks');

            // Calculate total days
            $startDate = Carbon::parse($leaveRecord->start_date);
            $totalDays = $this->calculateLeaveDays(
                $startDate,
                Carbon::parse($leaveRecord->end_date),
                $leaveRecord->half
            );

            // Check and update leave balance
            $balance = LeaveBalance::where('employee_id', $leaveRecord->employee_id)
                ->where('leave_type_id', $leaveRecord->leave_type_id)
                ->where('year', $startDate->year)
                ->first();

            if ($balance && $balance->remaining_leaves < $totalDays) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Cannot approve: Insufficient leave balance. Available: '.$balance->remaining_leaves.' days.',
                ], 400);
            }

            if ($balance) {
                $balance->incrementUsedLeaves($totalDays);
            }

            $leaveRecord->update([
                'status' => 'approved',
                'remarks' => $remarks,
            ]);

            // Build notification content
            $employee = $leaveRecord->employee;
            $leaveTypeName = $leaveRecord->leaveType->name ?? 'Leave';
            $formattedStart = $startDate->format('d M, Y');
            $formattedEnd = Carbon::parse($leaveRecord->end_date)->format('d M, Y');
            $notificationTitle = 'Leave Request Approved';
            $notificationBody = "Your {$leaveTypeName} request from {$formattedStart} to {$formattedEnd} ({$totalDays} days) has been approved.";
            if ($remarks) {
                $notificationBody .= " Remarks: {$remarks}";
            }

            // Database notification
            Notification::create([
                'title' => $notificationTitle,
                'description' => $notificationBody,
                'employee_id' => $employee->id,
                'is_seen' => false,
            ]);

            // FCM push notification
            if ($employee->fcm_token) {
                try {
                    \App\Services\FcmService::sendNotification(
                        $employee->fcm_token,
                        ['title' => $notificationTitle, 'body' => $notificationBody]
                    );
                } catch (\Exception $e) {
                    Log::warning('FCM notification failed for leave approval: '.$e->getMessage());
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Leave request approved successfully',
                'data' => $leaveRecord,
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Error approving leave request: '.$th->getMessage());

            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function rejectLeaveRequest(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            if (! Auth::user()->hasPermission('api.leave.reject')) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $leaveRecord = LeaveRecord::with(['employee', 'leaveType'])
                ->where('id', $id)
                ->whereIn('status', ['pending', 'approved'])
                ->first();

            if (! $leaveRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'Leave request not found or already rejected',
                ], 404);
            }

            $oldStatus = $leaveRecord->status;
            $remarks = $request->input('remarks');

            // Calculate total days
            $startDate = Carbon::parse($leaveRecord->start_date);
            $totalDays = $this->calculateLeaveDays(
                $startDate,
                Carbon::parse($leaveRecord->end_date),
                $leaveRecord->half
            );

            // If previously approved, restore the leave balance
            if ($oldStatus === 'approved') {
                $balance = LeaveBalance::where('employee_id', $leaveRecord->employee_id)
                    ->where('leave_type_id', $leaveRecord->leave_type_id)
                    ->where('year', $startDate->year)
                    ->first();

                if ($balance) {
                    $balance->decrementUsedLeaves($totalDays);
                }
            }

            $leaveRecord->update([
                'status' => 'rejected',
                'remarks' => $remarks,
            ]);

            // Build notification content
            $employee = $leaveRecord->employee;
            $leaveTypeName = $leaveRecord->leaveType->name ?? 'Leave';
            $formattedStart = $startDate->format('d M, Y');
            $formattedEnd = Carbon::parse($leaveRecord->end_date)->format('d M, Y');
            $notificationTitle = 'Leave Request Rejected';
            $notificationBody = "Your {$leaveTypeName} request from {$formattedStart} to {$formattedEnd} ({$totalDays} days) has been rejected.";
            if ($remarks) {
                $notificationBody .= " Reason: {$remarks}";
            }

            // Database notification
            Notification::create([
                'title' => $notificationTitle,
                'description' => $notificationBody,
                'employee_id' => $employee->id,
                'is_seen' => false,
            ]);

            // FCM push notification
            if ($employee->fcm_token) {
                try {
                    \App\Services\FcmService::sendNotification(
                        $employee->fcm_token,
                        ['title' => $notificationTitle, 'body' => $notificationBody]
                    );
                } catch (\Exception $e) {
                    Log::warning('FCM notification failed for leave rejection: '.$e->getMessage());
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Leave request rejected successfully',
                'data' => $leaveRecord,
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Error rejecting leave request: '.$th->getMessage());

            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
