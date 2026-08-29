<?php

use App\Http\Controllers\HRM\AttendanceController;
use App\Http\Controllers\HRM\CandidateController;
use App\Http\Controllers\HRM\DashboardController as HRMDashboardController;
use App\Http\Controllers\HRM\DepartmentController;
use App\Http\Controllers\HRM\EmployeeController;
use App\Http\Controllers\HRM\EmployeeTypeController;
use App\Http\Controllers\HRM\JobController;
use App\Http\Controllers\HRM\LeaveController;
use App\Http\Controllers\HRM\LeaveTypeController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\HRM\WarningController;
use App\Http\Controllers\HRM\ResignationController;
use App\Http\Controllers\HRM\ComplaintController;
use App\Http\Controllers\HRM\SurveyController;
use App\Http\Controllers\HRM\SurveyParticipationController;
use App\Http\Controllers\HRM\SurveyAnalyticsController;
use App\Http\Controllers\HRM\HrmActivityLogController;
use App\Http\Controllers\HRM\HrmReportController;

use Illuminate\Support\Facades\Route;

// HRM Recruitment - Jobs
Route::prefix('hrm')->name('hrm.')->middleware(['auth', 'verified'])->group(function () {
    Route::prefix('jobs')->name('jobs.')->group(function () {
        Route::get('/', [JobController::class, 'index'])->name('index');
        Route::get('/create', [JobController::class, 'create'])->name('create');
        Route::post('/store', [JobController::class, 'store'])->name('store');
        Route::get('/{id}', [JobController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [JobController::class, 'edit'])->name('edit');
        Route::put('/{id}/update', [JobController::class, 'update'])->name('update');
        Route::delete('/{id}/destroy', [JobController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/update-status', [JobController::class, 'updateStatus'])->name('update-status');
        Route::get('/{id}/clone', [JobController::class, 'clone'])->name('clone');
        Route::get('/statistics', [JobController::class, 'getStatistics'])->name('statistics');
    });
});

Route::prefix('hrm')->name('hrm.')->middleware(['auth', 'verified'])->group(function () {
    Route::prefix('candidates')->name('candidates.')->group(function () {
        Route::get('/', [CandidateController::class, 'index'])->name('index');
        Route::get('/create', [CandidateController::class, 'create'])->name('create');
        Route::post('/store', [CandidateController::class, 'store'])->name('store');
        Route::get('/{id}', [CandidateController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [CandidateController::class, 'edit'])->name('edit');
        Route::put('/{id}/update', [CandidateController::class, 'update'])->name('update');
        Route::delete('/{id}/destroy', [CandidateController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/update-stage', [CandidateController::class, 'updateStage'])->name('update-stage');
        Route::get('/{id}/download-resume', [CandidateController::class, 'downloadResume'])->name('download-resume');
        Route::get('/job/{jobId}', [CandidateController::class, 'getByJob'])->name('by-job');
        Route::get('/export', [CandidateController::class, 'export'])->name('export');
        // Candidate calendar routes
        Route::get('/candidates/calendar', [CandidateController::class, 'calendar'])->name('calendar.data');
        Route::get('/candidates/calendar-data', [CandidateController::class, 'getCalendarData'])->name('calendar-data');
        Route::get('/candidates/interview-details', [CandidateController::class, 'getInterviewDetails'])->name('interview-details');
        Route::post('/candidates/{candidate}/update-interview', [CandidateController::class, 'updateInterviewDetails'])->name('update-interview-details');
    });
});

Route::prefix('hrm')
    ->name('hrm.')
    ->middleware('permission:hrm.dashboard.view')
    ->group(function () {
        // Dashboard
        Route::controller(HRMDashboardController::class)->group(function () {
            Route::get('/dashboard', 'index')->name('index');
        });

        Route::prefix('reports')
            ->name('reports.')
            ->middleware('permission:hrm.reports.view')
            ->group(function () {
                Route::get('/', [HrmReportController::class, 'index'])->name('index');
                Route::get('/export/pdf', [HrmReportController::class, 'exportPdf'])
                    ->middleware('permission:hrm.reports.export')
                    ->name('export.pdf');
                Route::get('/export/csv', [HrmReportController::class, 'exportCsv'])
                    ->middleware('permission:hrm.reports.export')
                    ->name('export.csv');
            });

        // Dashboard AJAX endpoints
        Route::get('/dashboard/metrics', [HRMDashboardController::class, 'getDashboardMetrics1'])->name('dashboard.metrics');
        Route::get('/dashboard/chart-data', [HRMDashboardController::class, 'getDashboardChartData'])->name('dashboard.chart-data');
        Route::get('/dashboard/real-time-stats', [HRMDashboardController::class, 'getRealTimeStats'])->name('dashboard.real-time-stats');
        Route::get('/tenure-milestones', [HRMDashboardController::class, 'tenureMilestones'])->name('tenure_milestones.index');
        Route::post('/tenure-milestones/{id}/read', [HRMDashboardController::class, 'markMilestoneAsRead'])->name('tenure_milestones.read');

        // Route::controller(HRMDashboardController::class)->group(function () {
        //     Route::get('/recruitement', 'recruitment')->name('recruitment');
        // });
        // Recruitment Dashboard
        Route::get('/recruitment/dashboard', [HRMDashboardController::class, 'recruitmentDashboard'])
            ->name('recruitment.dashboard');

        // AJAX endpoints for dashboard
        Route::get('/recruitment/metrics', [HRMDashboardController::class, 'getDashboardMetrics'])
            ->name('recruitment.metrics');

        Route::get('/recruitment/chart-data', [HRMDashboardController::class, 'getRecruitmentChartData'])
            ->name('recruitment.chart-data');

        // Employee Type Routes
        Route::prefix('employee-type')
            ->name('employee-type.')
            ->middleware('permission:hrm.employee-type.view')
            ->group(function () {
            Route::get('/', [EmployeeTypeController::class, 'index'])->name('index');
            Route::get('/create', [EmployeeTypeController::class, 'create'])->middleware('permission:hrm.employee-type.create')->name('create');
            Route::post('/store', [EmployeeTypeController::class, 'store'])->middleware('permission:hrm.employee-type.create')->name('store');
            Route::get('/{id}/edit', [EmployeeTypeController::class, 'edit'])->middleware('permission:hrm.employee-type.edit')->name('edit');
            Route::put('/{id}/update', [EmployeeTypeController::class, 'update'])->middleware('permission:hrm.employee-type.edit')->name('update');
            Route::delete('/{id}/destroy', [EmployeeTypeController::class, 'destroy'])->middleware('permission:hrm.employee-type.delete')->name('destroy');
            Route::get('/{id}/show', [EmployeeTypeController::class, 'show'])->name('show');
        });

        // Department Routes
        Route::prefix('department')
            ->name('department.')
            ->middleware('permission:hrm.department.view')
            ->group(function () {
            Route::get('/', [DepartmentController::class, 'index'])->name('index');
            Route::get('/create', [DepartmentController::class, 'create'])->middleware('permission:hrm.department.create')->name('create');
            Route::post('/store', [DepartmentController::class, 'store'])->middleware('permission:hrm.department.create')->name('store');
            Route::get('/{id}/edit', [DepartmentController::class, 'edit'])->middleware('permission:hrm.department.edit')->name('edit');
            Route::put('/{id}/update', [DepartmentController::class, 'update'])->middleware('permission:hrm.department.edit')->name('update');
            Route::delete('/{id}/destroy', [DepartmentController::class, 'destroy'])->middleware('permission:hrm.department.delete')->name('destroy');
            Route::get('/{id}/show', [DepartmentController::class, 'show'])->name('show');
        });

        // LeaveType Routes
        Route::prefix('leave-types')
            ->name('leave-types.')
            ->middleware('permission:hrm.leave-types.view')
            ->group(function () {
            Route::get('/', [LeaveTypeController::class, 'index'])->name('index');
            Route::get('/create', [LeaveTypeController::class, 'create'])->middleware('permission:hrm.leave-types.create')->name('create');
            Route::post('/store', [LeaveTypeController::class, 'store'])->middleware('permission:hrm.leave-types.create')->name('store');
            Route::get('/{id}/edit', [LeaveTypeController::class, 'edit'])->middleware('permission:hrm.leave-types.edit')->name('edit');
            Route::put('/{id}/update', [LeaveTypeController::class, 'update'])->middleware('permission:hrm.leave-types.edit')->name('update');
            Route::delete('/{id}/destroy', [LeaveTypeController::class, 'destroy'])->middleware('permission:hrm.leave-types.delete')->name('destroy');
            Route::get('/{id}/show', [LeaveTypeController::class, 'show'])->name('show');
        });

        // Leave Routes
        Route::prefix('leave')
            ->name('leave.')
            ->middleware('permission:hrm.leave.view')
            ->group(function () {
            Route::get('/', [LeaveController::class, 'index'])->name('index');
            Route::get('/pending', [LeaveController::class, 'pendingLeaveRequests'])->name('pending');
            Route::get('/create', [LeaveController::class, 'create'])->middleware('permission:hrm.leave.create')->name('create');
            Route::post('/store', [LeaveController::class, 'store'])->middleware('permission:hrm.leave.create')->name('store');
            Route::get('/{id}/edit', [LeaveController::class, 'edit'])->middleware('permission:hrm.leave.edit')->name('edit');
            Route::put('/{id}/update', [LeaveController::class, 'update'])->middleware('permission:hrm.leave.edit')->name('update');
            Route::delete('/{id}/destroy', [LeaveController::class, 'destroy'])->middleware('permission:hrm.leave.delete')->name('destroy');
            Route::get('/{id}/show', [LeaveController::class, 'show'])->name('show');
            Route::post('/calculate-days', [LeaveController::class, 'calculateDays'])->middleware('permission:hrm.leave.calculate-days')->name('calculate-days');
            Route::get('/get-balance', [LeaveController::class, 'getEmployeeBalance'])->middleware('permission:hrm.leave.get-balance')->name('get-balance');
            Route::post('/{id}/update-status', [LeaveController::class, 'updateStatus'])->middleware('permission:hrm.leave.approve')->name('update-status');
            Route::get('/get-leave-type-by-category', [LeaveController::class, 'getLeaveTypeByCategory'])->name('get-leave-type-by-category');
            Route::get('/leave/calendar-data', [LeaveController::class, 'getCalendarData'])->name('calendar-data');
            Route::get('/leave/details', [LeaveController::class, 'getLeaveDetails'])->name('details');
            Route::get('/leave-records/export', [LeaveController::class, 'export'])->name('export');
            Route::get('/leave-records/export-department-wise', [LeaveController::class, 'exportDepartmentWise'])->name('export.department-wise');
        });

        Route::get('/leave/calendar', [LeaveController::class, 'calendar'])
            ->middleware('permission:hrm.leave.calendar.view')
            ->name('calendar');

        // Employee Routes
        Route::prefix('employee')
            ->name('employee.')
            ->middleware('permission:hrm.employee.view')
            ->group(function () {
            Route::get('/', [EmployeeController::class, 'index'])->name('index');
            Route::get('/create', [EmployeeController::class, 'create'])->middleware('permission:hrm.employee.create')->name('create');
            Route::post('/store', [EmployeeController::class, 'store'])->middleware('permission:hrm.employee.create')->name('store');
            Route::get('/{id}/edit', [EmployeeController::class, 'edit'])->middleware('permission:hrm.employee.edit')->name('edit');
            Route::put('/{id}/update', [EmployeeController::class, 'update'])->middleware('permission:hrm.employee.edit')->name('update');
            Route::delete('/{id}/destroy', [EmployeeController::class, 'destroy'])->middleware('permission:hrm.employee.delete')->name('destroy');
            Route::get('/{id}/show', [EmployeeController::class, 'show'])->name('show');
            Route::post('/{id}/change-status', [EmployeeController::class, 'changeStatus'])->middleware('permission:hrm.employee.change-status')->name('change-status');
            Route::get('/get-managers', [EmployeeController::class, 'getManagers'])->name('get-managers');
            Route::delete('/employee/document/{id}', [EmployeeController::class, 'destroyDocument'])->name('document.destroy');
        });

        // Attendance Routes
        Route::prefix('attendance')
            ->name('attendance.')
            ->middleware('permission:hrm.attendance.view')
            ->group(function () {
            Route::get('/', [AttendanceController::class, 'index'])->name('index');
            Route::get('/create', [AttendanceController::class, 'create'])->middleware('permission:hrm.attendance.create')->name('create');
            Route::post('/', [AttendanceController::class, 'store'])->middleware('permission:hrm.attendance.create')->name('store');
            Route::post('/bulk-transfer-company', [AttendanceController::class, 'bulkTransferCompany'])->middleware('permission:hrm.attendance.edit')->name('bulk-transfer-company');
            Route::get('/{id}/show', [AttendanceController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [AttendanceController::class, 'edit'])->middleware('permission:hrm.attendance.edit')->name('edit');
            Route::put('/{id}', [AttendanceController::class, 'update'])->middleware('permission:hrm.attendance.edit')->name('update');
            Route::delete('/{id}', [AttendanceController::class, 'destroy'])->middleware('permission:hrm.attendance.delete')->name('destroy');
            Route::post('/bulk-checkin', [AttendanceController::class, 'bulkCheckIn'])->middleware('permission:hrm.attendance.bulk-checkin')->name('bulk.checkin');
            Route::post('/bulk-checkout', [AttendanceController::class, 'bulkCheckOut'])->middleware('permission:hrm.attendance.bulk-checkout')->name('bulk.checkout');
            Route::post('/import', [AttendanceController::class, 'import'])->middleware('permission:hrm.attendance.import')->name('import');
            Route::get('/export', [AttendanceController::class, 'export'])->middleware('permission:hrm.attendance.export')->name('export');
            Route::get('/download-template', [AttendanceController::class, 'downloadTemplate'])->name('download-template');
            Route::get('/export1', [AttendanceController::class, 'export1'])->name('export1');
            Route::get('/export-department-wise', [AttendanceController::class, 'exportDepartmentWise'])->name('export.department-wise');
            Route::get('/search-employees', [AttendanceController::class, 'searchEmployees'])->name('search-employees');
        });

        Route::prefix('announcements')
            ->name('announcements.')
            ->middleware(['auth', 'verified'])
            ->group(function () {
                Route::get('/', [AnnouncementController::class, 'index'])->name('index');
                Route::get('/create', [AnnouncementController::class, 'create'])->name('create');
                Route::post('/store', [AnnouncementController::class, 'store'])->name('store');
                Route::get('/{id}/show', [AnnouncementController::class, 'show'])->name('show');
                Route::get('/{id}/edit', [AnnouncementController::class, 'edit'])->name('edit');
                Route::put('/{id}/update', [AnnouncementController::class, 'update'])->name('update');
                Route::delete('/{id}/destroy', [AnnouncementController::class, 'destroy'])->name('destroy');
                Route::get('/{id}/download-pdf', [AnnouncementController::class, 'downloadPdf'])->name('download-pdf');
            });

        Route::prefix('policies')
            ->name('policies.')
            ->middleware(['auth', 'verified'])
            ->group(function () {
                Route::get('/', [\App\Http\Controllers\HRM\PolicyController::class, 'index'])->name('index');
                Route::get('/create', [\App\Http\Controllers\HRM\PolicyController::class, 'create'])->name('create');
                Route::post('/store', [\App\Http\Controllers\HRM\PolicyController::class, 'store'])->name('store');
                Route::get('/{id}/show', [\App\Http\Controllers\HRM\PolicyController::class, 'show'])->name('show');
                Route::get('/{id}/edit', [\App\Http\Controllers\HRM\PolicyController::class, 'edit'])->name('edit');
                Route::put('/{id}/update', [\App\Http\Controllers\HRM\PolicyController::class, 'update'])->name('update');
                Route::delete('/{id}/destroy', [\App\Http\Controllers\HRM\PolicyController::class, 'destroy'])->name('destroy');
                Route::get('/{id}/download', [\App\Http\Controllers\HRM\PolicyController::class, 'download'])->name('download');
            });

        // Warnings Routes
        Route::prefix('warnings')
            ->name('warnings.')
            ->middleware('permission:hrm.warnings.view')
            ->group(function () {
                Route::get('/', [WarningController::class, 'index'])->name('index');
                Route::get('/create', [WarningController::class, 'create'])->middleware('permission:hrm.warnings.create')->name('create');
                Route::post('/store', [WarningController::class, 'store'])->middleware('permission:hrm.warnings.create')->name('store');
                Route::get('/{id}/edit', [WarningController::class, 'edit'])->middleware('permission:hrm.warnings.edit')->name('edit');
                Route::put('/{id}/update', [WarningController::class, 'update'])->middleware('permission:hrm.warnings.edit')->name('update');
                Route::delete('/{id}/destroy', [WarningController::class, 'destroy'])->middleware('permission:hrm.warnings.delete')->name('destroy');
            });

        // Resignations Routes
        Route::prefix('resignations')
            ->name('resignations.')
            ->middleware('permission:hrm.resignations.view')
            ->group(function () {
                Route::get('/', [ResignationController::class, 'index'])->name('index');
                Route::get('/create', [ResignationController::class, 'create'])->middleware('permission:hrm.resignations.create')->name('create');
                Route::post('/store', [ResignationController::class, 'store'])->middleware('permission:hrm.resignations.create')->name('store');
                Route::get('/{id}/edit', [ResignationController::class, 'edit'])->middleware('permission:hrm.resignations.edit')->name('edit');
                Route::put('/{id}/update', [ResignationController::class, 'update'])->middleware('permission:hrm.resignations.edit')->name('update');
                Route::delete('/{id}/destroy', [ResignationController::class, 'destroy'])->middleware('permission:hrm.resignations.delete')->name('destroy');
            });

        // Complaints Routes
        Route::prefix('complaints')
            ->name('complaints.')
            ->middleware('permission:hrm.complaints.view')
            ->group(function () {
                Route::get('/', [ComplaintController::class, 'index'])->name('index');
                Route::get('/create', [ComplaintController::class, 'create'])->middleware('permission:hrm.complaints.create')->name('create');
                Route::post('/store', [ComplaintController::class, 'store'])->middleware('permission:hrm.complaints.create')->name('store');
                Route::get('/{id}/edit', [ComplaintController::class, 'edit'])->middleware('permission:hrm.complaints.edit')->name('edit');
                Route::put('/{id}/update', [ComplaintController::class, 'update'])->middleware('permission:hrm.complaints.edit')->name('update');
                Route::delete('/{id}/destroy', [ComplaintController::class, 'destroy'])->middleware('permission:hrm.complaints.delete')->name('destroy');
                Route::get('/{id}/download', [ComplaintController::class, 'download'])->name('download');
            });

        // Surveys Routes
        Route::prefix('surveys')
            ->name('surveys.')
            ->group(function () {
                // Admin management
                Route::get('/', [SurveyController::class, 'index'])->middleware('permission:hrm.surveys.view')->name('index');
                Route::get('/create', [SurveyController::class, 'create'])->middleware('permission:hrm.surveys.create')->name('create');
                Route::post('/store', [SurveyController::class, 'store'])->middleware('permission:hrm.surveys.create')->name('store');
                Route::get('/{id}/edit', [SurveyController::class, 'edit'])->middleware('permission:hrm.surveys.edit')->name('edit');
                Route::put('/{id}/update', [SurveyController::class, 'update'])->middleware('permission:hrm.surveys.edit')->name('update');
                Route::delete('/{id}/destroy', [SurveyController::class, 'destroy'])->middleware('permission:hrm.surveys.delete')->name('destroy');

                // Analytics
                Route::get('/{id}/analytics', [SurveyAnalyticsController::class, 'show'])->middleware('permission:hrm.surveys.analytics')->name('analytics');
                Route::get('/{id}/export', [SurveyAnalyticsController::class, 'export'])->middleware('permission:hrm.surveys.analytics')->name('export');

                // Employee participation
                Route::get('/active', [SurveyParticipationController::class, 'active'])->middleware('permission:hrm.surveys.participate')->name('active');
                Route::get('/{id}/take', [SurveyParticipationController::class, 'take'])->middleware('permission:hrm.surveys.participate')->name('take');
                Route::post('/{id}/submit', [SurveyParticipationController::class, 'submit'])->middleware('permission:hrm.surveys.participate')->name('submit');
            });

        // Activity Logs
        Route::get('/activity-logs', [HrmActivityLogController::class, 'index'])->name('activity-logs.index');

    });
