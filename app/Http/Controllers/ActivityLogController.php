<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user', 'employee')->latest();

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

        // Get unique models for filter dropdown
        $models = ActivityLog::select('loggable_type')
            ->distinct()
            ->pluck('loggable_type')
            ->sort();

        // Paginate with per_page option
        $perPage = $request->per_page ?? 20;
        $logs = $query->paginate($perPage)->withQueryString();

        return view('pages.activity_logs.index', compact('logs', 'models'));
    }
}
