<?php

use App\Http\Controllers\Api\Auth\EmployeeAuthController;

use App\Http\Controllers\Api\Employee\ProfileController;
use App\Http\Controllers\Api\Employee\ReportController;
use App\Http\Controllers\Api\HRM\AnnouncementController as ApiAnnouncementController;
use App\Http\Controllers\Api\HRM\AttendanceController;
use App\Http\Controllers\Api\HRM\LateInformController;
use App\Http\Controllers\Api\HRM\LeaveController;
use App\Http\Controllers\Api\HRM\NotificationController;
use App\Http\Controllers\HRM\EmployeeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
// return $request->user();
// })->middleware('auth:sanctum');
Route::prefix('employee')->group(function () {
    // Public routes
    Route::post('/login', [EmployeeAuthController::class, 'login']);
    Route::post('/verify-otp', [EmployeeAuthController::class, 'verifyOtp']);
    Route::post('/resend-otp', [EmployeeAuthController::class, 'resendOtp']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [EmployeeAuthController::class, 'logout']);
        Route::get('/me', [EmployeeAuthController::class, 'me']);
        Route::post('/profile-update', [ProfileController::class, 'update']);
        Route::get('/report', [ReportController::class, 'index'])->middleware('permission:api.report.individual|api.report.all');
        // Attendance API
        Route::post('/checkin', [AttendanceController::class, 'checkIn'])->middleware('permission:api.attendance.checkin');
        Route::post('/checkout', [AttendanceController::class, 'checkOut'])->middleware('permission:api.attendance.checkout');
        Route::get('/checkAttendance', [AttendanceController::class, 'checkAttendance']);
        Route::get('/attendance-history', [AttendanceController::class, 'attendanceHistory']);

        // Notifications API
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::post('/notifications/mark-as-read', [NotificationController::class, 'markAsRead']);
        Route::post('/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
        Route::post('/late-inform', [LateInformController::class, 'store']);
        Route::get('/late-informs', [LateInformController::class, 'index']);

        // Leave API
        Route::get('/leave-requests', [LeaveController::class, 'index']);
        Route::get('/leave-requests/{id}', [LeaveController::class, 'show']);
        Route::post('/leave-requests/approve/{id}', [LeaveController::class, 'approvedLeaveRequest'])->middleware('permission:api.leave.approve');
        Route::post('/leave-requests/reject/{id}', [LeaveController::class, 'rejectLeaveRequest'])->middleware('permission:api.leave.reject');

        // Announcements API
        Route::get('/announcements', [ApiAnnouncementController::class, 'index'])->middleware('permission:api.announcement.view');
        Route::get('/announcements/{id}', [ApiAnnouncementController::class, 'show'])->middleware('permission:api.announcement.view');
        Route::post('/leave/request', [LeaveController::class, 'store'])->middleware('permission:api.leave.request');
    });
});

// Power Automate → ERP performance sync (API key auth, no session needed)


Route::post('/employees/register-fingerprint', [EmployeeController::class, 'registerFingerprint']);
Route::post('/attendance/punch', [AttendanceController::class, 'fingerprintPunch']);
Route::get('/employees/templates', [AttendanceController::class, 'getTemplates']);
Route::get('/all-employees', [AttendanceController::class, 'getAllEmployee']);
Route::get('/today-attendance', [AttendanceController::class, 'getAllAttendance']);
Route::get('/leave/type', [LeaveController::class, 'getLeaveTypes']);
Route::post('/fcm/send', [NotificationController::class, 'sendFcm']);
