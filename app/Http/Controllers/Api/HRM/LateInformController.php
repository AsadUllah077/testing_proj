<?php

namespace App\Http\Controllers\Api\HRM;

use App\Http\Controllers\Controller;
use App\Models\LateInform;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class LateInformController extends Controller
{
    /**
     * Store a new late inform record.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'reason' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->all(),
                ], 422);
            }

            $employee = Employee::findOrFail(Auth::user()->id);

            $lateInform = LateInform::create([
                'employee_id' => $employee->id,
                'company_id' => $employee->company_id,
                'date' => now()->format('Y-m-d'),
                'reason' => $request->reason,
                'status' => 'pending',
            ]);

            // Create Database Notification
            Notification::create([
                'title' => 'Late Inform Submitted',
                'description' => "You have submitted a late inform for today. Reason: {$request->reason}",
                'employee_id' => $employee->id,
                'is_seen' => false,
            ]);

            // Notify all employees who are 'hradmin' or have 'api.late.inform' permission
            $recipients = Employee::where('company_id', $employee->company_id)
                ->where('id', '!=', $employee->id)
                ->get()
                ->filter(fn ($e) =>  $e->hasPermission('api.late.inform'));

            foreach ($recipients as $recipient) {
                Notification::create([
                    'title' => 'Late Inform Submitted',
                    'description' => "{$employee->full_name} has submitted a new late inform for today. Reason: {$request->reason}.",
                    'employee_id' => $recipient->id,
                    'is_seen' => false,
                ]);
                Log::info($recipients);

                if ($recipient->fcm_token) {
                    \App\Services\FcmService::sendNotification(
                        $recipient->fcm_token,
                        [
                            'title' => 'Late Inform Submitted',
                            'body' => "{$employee->full_name} has submitted a new late inform for today. Reason: {$request->reason}.",
                        ]
                    );
                }
            }



            // // Send FCM Notification
            // if ($employee->fcm_token) {
            //     \App\Services\FcmService::sendNotification(
            //         $employee->fcm_token,
            //         [
            //             'title' => 'Late Inform Submitted',
            //             'body' => "Your late inform has been recorded successfully.",
            //         ]
            //     );
            // }

            return response()->json([
                'success' => true,
                'message' => 'Late inform submitted successfully.',
                'data' => $lateInform,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Internal Error: " . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List late inform records.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);

            $lateInforms = LateInform::with('employee:id,full_name,email')
                ->where('employee_id', Auth::user()->id)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $lateInforms->items(),
                'meta' => [
                    'current_page' => $lateInforms->currentPage(),
                    'last_page'    => $lateInforms->lastPage(),
                    'per_page'     => $lateInforms->perPage(),
                    'total'        => $lateInforms->total(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Internal Error: " . $e->getMessage(),
            ], 500);
        }
    }
}
