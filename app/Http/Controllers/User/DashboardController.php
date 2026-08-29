<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Models\UserLoginLogs;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Get all latest activity logs with user relationship
        $activity_logs = ActivityLog::with('user')->latest()->paginate(10);

        // Get latest login for each user
        $login_logs = UserLoginLogs::orderBy('id', 'DESC')->get()->groupBy('user_id');

        // Combine login + activity for each user
        $user_activity = collect();

        foreach ($login_logs as $user_id => $logins) {
            $user = User::find($user_id);
            if (!$user) continue;

            foreach ($logins as $login) {
                // Get activities after this login
                $activities = $activity_logs->filter(function ($log) use ($user_id, $login) {
                    return $log->user_id == $user_id && $log->created_at >= $login->created_at;
                });

                $user_activity->push([
                    'user_id' => $user_id,
                    'user_name' => $user->full_name,
                    'login_time' => $login->created_at,
                    'activities' => $activities->values(),
                ]);
            }
        }

        // Activity Trend (Last 7 days)
        $trend_data = ActivityLog::where('created_at', '>=', Carbon::now()->subDays(7))
            ->selectRaw('DATE(created_at) as date, count(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top Active Users (Who did the most?)
        $top_contributors = ActivityLog::with('user')
            ->selectRaw('user_id, count(*) as count')
            ->groupBy('user_id')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        $userCount = User::count();
        $startOfDay = Carbon::now()->startOfDay()->format('Y-m-d');
        $endOfDay = Carbon::now()->endOfDay()->format('Y-m-d');
        $activeUserCount = UserLoginLogs::whereDate('created_at', '>=', $startOfDay)->whereDate('created_at', '<=', $endOfDay)->distinct('user_id')->count();

        $startOfWeek = Carbon::now()->startOfWeek()->format('Y-m-d');
        $endOfWeek = Carbon::now()->endOfWeek()->format('Y-m-d');
        $newUserCount = User::whereDate('created_at', '>=', $startOfWeek)->whereDate('created_at', '<=', $endOfWeek)->count();

        return view('pages.users.dashboard', compact(
            'activity_logs', 
            'user_activity', 
            'userCount', 
            'activeUserCount', 
            'newUserCount',
            'trend_data',
            'top_contributors'
        ));
    }
}
