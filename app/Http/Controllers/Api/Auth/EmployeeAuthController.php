<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Mail\OtpMailemployee;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

class EmployeeAuthController extends Controller
{
    /**
     * Handle employee login
     */
    public function login(Request $request)
    {
        try {
            $valdiator = Validator::make($request->all(), [
                'email' => 'required|email|exists:employees,email',
            ]);

            if ($valdiator->fails()) {
                return response()->json([
                    'message' => $valdiator->errors()->all(),
                    'success' => false,
                ], 422);
            }

            // Rate limiting
            // $executed = RateLimiter::attempt(
            //     'send-otp:'.$request->ip(),
            //     $perMinute = 3,
            //     function () {
            //         // Callback when not rate limited
            //     }
            // );

            // if (! $executed) {
            //     return response()->json([
            //         'message' => 'Too many OTP requests. Please try again later.',
            //         'success' => false,
            //     ], 429);
            // }

            $employee = Employee::where('email', $request->email)->first();

            if (! $employee) {
                return response()->json([
                    'success' => false,
                    'message' => ['Employee not found'],
                ]);
            }
            // Generate OTP (6 digits)
            $otp = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);

            // Save OTP to employee
            $employee->otp = $otp;
            $employee->save();

            // Send OTP via email
            Mail::to($employee->email)->send(new OtpMailemployee($otp, $employee->full_name));

            return response()->json([
                'message' => ['OTP sent successfully to your email.'],
                'success' => true,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => [$e->getMessage()],
                'success' => false,
            ], 500);
        }
    }

    /**
     * Verify OTP and login
     */
    public function verifyOtp(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:employees,email',
                'otp' => 'required|string|size:4|exists:employees,otp',
                'fcm_token' => 'nullable|string',
            ]);
            \Log::info($request->all());

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors()->all(),
                    'success' => false,
                ], 422);
            }

            $employee = Employee::whereEmailAndOtp($request->email, $request->otp)->first();

            if (! $employee) {
                return response()->json([
                    'message' => ['Invalid OTP. Please try again.'],
                    'success' => false,
                ], 400);
            }

            // Save FCM Token if provided
            if ($request->has('fcm_token')) {
                $employee->fcm_token = $request->fcm_token;
            }

            $employee->otp = null; // Clear OTP after verification
            $employee->otp_verified = true;
            $employee->save();

            // Create authentication token
            $token = $employee->createToken('employee-api-token')->plainTextToken;

            return response()->json([
                'message' => ['OTP verified successfully.'],
                'success' => true,
                'token' => $token,
                'employee' => [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'email' => $employee->email,
                    'permissions' => $employee->getPermissions(),
                ],
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => [$th->getMessage()],
            ], 500);
        }
    }

    /**
     * Resend OTP
     */
    public function resendOtp(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:employees,email',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors()->all(),
                    'success' => false,
                ], 422);
            }

            // // Rate limiting for resend
            // $executed = RateLimiter::attempt(
            //     'resend-otp:'.$request->ip(),
            //     $perMinute = 2,
            //     function () {
            //         // Callback when not rate limited
            //     }
            // );

            // if (! $executed) {
            //     return response()->json([
            //         'message' => 'Too many resend requests. Please try again later.',
            //         'success' => false,
            //     ], 429);
            // }
            $employee = Employee::where('email', $request->email)->first();

            if (! $employee) {
                return response()->json([
                    'message' => ['Employee not found.'],
                    'success' => false,
                ], 404);
            }

            // Generate new OTP
            $otp = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            // $otpExpiresAt = now()->addMinutes(10);

            // Save new OTP
            $employee->otp = $otp;
            $employee->otp_verified = false;
            $employee->save();

            // Send new OTP via email
            Mail::to($employee->email)->send(new OtpMailemployee($otp, $employee->name));

            return response()->json([
                'message' => ['New OTP sent successfully.'],
                'success' => true,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'message' => [$th->getMessage()],
                'success' => false,
            ], 500);
        }
    }

    /**
     * Handle employee logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => ['Logged out successfully'],
            'success' => true,
        ], 200);
    }

    /**
     * Get authenticated employee details with role and permissions
     */
    public function me(Request $request)
    {
        $employee = $request->user()->load('role', 'department', 'employmentType');

        return response()->json([
            'success' => true,
            'employee' => [
                'id' => $employee->id,
                'employee_card_id' => $employee->employee_card_id,
                // Personal Information
                'full_name' => $employee->full_name,
                'gender' => $employee->gender,
                'date_of_birth' => $employee->date_of_birth,
                'marital_status' => $employee->marital_status,
                'national_id' => $employee->national_id,
                // Contact Information
                'email' => $employee->email,
                'phone_number' => $employee->phone_number,
                'residential_address' => $employee->residential_address,
                'city' => $employee->city,
                'state' => $employee->state,
                'country' => $employee->country,
                'emergency_contact_name' => $employee->emergency_contact_name,
                'emergency_contact_phone' => $employee->emergency_contact_phone,
                'emergency_contact_relationship' => $employee->emergency_contact_relationship,
                // Job Information
                'designation' => $employee->designation,
                'reporting_to' => $employee->reporting_to,
                'date_of_joining' => $employee->date_of_joining,
                'date_of_confirmation' => $employee->date_of_confirmation,
                'work_location' => $employee->work_location,
                'job_description' => $employee->job_description,
                'salary' => $employee->salary,
                'working_day_from' => $employee->working_day_from,
                'working_day_to' => $employee->working_day_to,
                'start_time' => $employee->start_time,
                'end_time' => $employee->end_time,
                // Status & Media
                'status' => $employee->status,
                'profile_picture' => $employee->profile_picture,
                // Relationships
                'department' => $employee->department ? [
                    'id' => $employee->department->id,
                    'name' => $employee->department->name,
                ] : null,
                'employment_type' => $employee->employmentType ? [
                    'id' => $employee->employmentType->id,
                    'name' => $employee->employmentType->name,
                ] : null,
                'role' => $employee->role ? [
                    'id' => $employee->role->id,
                    'name' => $employee->role->name,
                ] : null,
                'permissions' => $employee->getPermissions(),
                'created_at' => $employee->created_at,
                'updated_at' => $employee->updated_at,
            ],
        ], 200);
    }
}
