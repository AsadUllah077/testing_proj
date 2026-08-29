<?php

namespace App\Http\Controllers\Product\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class WarehouseActivityLogController extends Controller
{
    /**
     * List of models related to Warehouse module
     */
    public static $warehouseModels = [
        \App\Models\Product::class,
        \App\Models\GatePass::class,
        \App\Models\WarehouseLocation::class,
        \App\Models\ScrapMaterial::class,
        \App\Models\ScrapCategory::class,
        \App\Models\ScrapType::class,
        \App\Models\ProductIssue::class,
        \App\Models\ProgressSheet::class,
        \App\Models\Category::class,
        \App\Models\Brand::class,
        \App\Models\Disposition::class,
        \App\Models\WorkflowStage::class,
        \App\Models\VisualInspection::class,
        \App\Models\FunctionalTesting::class,
        \App\Models\VisualInspectionResult::class,
        \App\Models\FunctionalTestingResult::class,
    ];

    public function index(Request $request)
    {
        $query = ActivityLog::whereIn('loggable_type', self::$warehouseModels)
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

        // Get unique Warehouse models actually present in logs for filter dropdown
        $models = ActivityLog::whereIn('loggable_type', self::$warehouseModels)
            ->select('loggable_type')
            ->distinct()
            ->pluck('loggable_type')
            ->sort();

        $users = User::where('company_id', loggedCompanyId()['company_id'])->get();

        // Paginate
        $perPage = $request->input('per_page', 20);
        $logs = $query->paginate($perPage)->withQueryString();

        return view('pages.products.warehouse.activity_logs.index', compact('logs', 'models', 'users'));
    }
}
