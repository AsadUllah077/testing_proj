<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Mail\OtpMailemployee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    /**
     * Update the authenticated employee's profile.
     */
    public function update(Request $request)
    {
        try {
            /** @var Employee $employee */
            $employee = $request->user();

            if (!$employee instanceof Employee) {
                return response()->json([
                    'success' => false,
                    'message' => ['Unauthorized or invalid user type'],
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'full_name' => 'sometimes|required|string|max:255',
                'f_name' => 'sometimes|required|string|max:255',
                'phone_number' => 'sometimes|nullable|string|max:20',
                'gender' => 'sometimes|nullable|in:male,female,other',
                'date_of_birth' => 'sometimes|nullable|date',
                'residential_address' => 'sometimes|nullable|string',
                'city' => 'sometimes|nullable|string|max:100',
                'state' => 'sometimes|nullable|string|max:100',
                'country' => 'sometimes|nullable|string|max:100',
                'profile_picture' => 'sometimes|nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
                'email' => 'sometimes|required|email|unique:employees,email,' . $employee->id,
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->all(),
                ], 422);
            }

            // Only allow specific personal fields to be updated by the employee
            $allowedFields = [
                'full_name',
                'phone_number',
                'gender',
                'residential_address',
                'email',
            ];

            $data = $request->only($allowedFields);

            $emailChanged = false;
            if ($request->has('email') && $request->email !== $employee->email) {
                $emailChanged = true;
                // Generate OTP (4 digits as per current system)
                $otp = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
                $data['otp'] = $otp;
                $data['otp_verified'] = false;
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

            $employee->update($data);

            if ($emailChanged) {
                // Send OTP via email
                Mail::to($employee->email)->send(new OtpMailemployee($data['otp'], $employee->full_name));

                // Delete all tokens
                $employee->tokens()->delete();

                return response()->json([
                    'success' => true,
                    'message' => ['Email updated! A verification OTP has been sent to your new email. All existing sessions have been terminated.'],
                    'email_changed' => true,
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => ['Profile updated successfully!'],
                'employee' => $employee->fresh(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => ['Error updating profile: ' . $e->getMessage()],
            ], 500);
        }
    }
}
