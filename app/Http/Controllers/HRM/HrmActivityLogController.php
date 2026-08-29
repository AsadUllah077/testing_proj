<?php

namespace App\Http\Controllers\HRM;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class HrmActivityLogController extends Controller
{
    /**
     * List of models related to HRM module
     */
    public static $hrmModels = [
        \App\Models\Employee::class,
        \App\Models\EmployeeAttendance::class,
        \App\Models\LeaveRecord::class,
        \App\Models\LeaveType::class,
        \App\Models\Warning::class,
        \App\Models\Resignation::class,
        \App\Models\Complaint::class,
        \App\Models\Survey::class,
        \App\Models\SurveyQuestion::class,
        \App\Models\SurveyResponse::class,
        \App\Models\Policy::class,
        \App\Models\Announcement::class,
        \App\Models\Department::class,
        \App\Models\EmployeeType::class,
        \App\Models\HrmJob::class,
        \App\Models\HrmCandidate::class,
    ];

    public function index(Request $request)
    {
        $query = ActivityLog::whereIn('loggable_type', self::$hrmModels)
            ->with(['user', 'employee'])
            ->latest();

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by model
        if ($request->filled('model')) {
            $query->where('loggable_type', $request->model);
        }

        // Filter by event/action
        if ($request->filled('event')) {
            $query->where('action', $request->event);
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Get unique HRM models actually present in logs for filter dropdown
        $models = ActivityLog::whereIn('loggable_type', self::$hrmModels)
            ->select('loggable_type')
            ->distinct()
            ->pluck('loggable_type')
            ->sort();

        $users = User::where('company_id', loggedCompanyId()['company_id'])->get();

        // Paginate
        $perPage = $request->input('per_page', 20);
        $logs = $query->paginate($perPage)->withQueryString();

        return view('pages.hrm.activity_logs.index', compact('logs', 'models', 'users'));
    }
}
