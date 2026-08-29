<?php

namespace App\Http\Controllers\Product\Warehouse;

use App\Http\Controllers\Controller;

class MasterDataController extends Controller
{
    public function index()
    {
        // Fetch counts for all master data modules
        $categoryCount = \App\Models\ProductCategory::count();
        $locationCount = \App\Models\WarehouseLocation::count();
        $dispositionCount = \App\Models\Disposition::count();
        $workflowStageCount = \App\Models\WorkflowStage::count();
        $scrapTypeCount = \App\Models\ScrapType::count();
        $scrapCategoryCount = \App\Models\ScrapCategory::count();

        // Fetch status-wise counts for each module (optional - uncomment if you have these relationships)
        // $categoryStatusCounts = \App\Models\Category::selectRaw("
        //     SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
        //     SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive,
        //     SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) as archived
        // ")->first();

        return view('pages.products.warehouse.masterdata.index', compact(
            'categoryCount',
            'locationCount',
            'dispositionCount',
            'workflowStageCount',
            'scrapTypeCount',
            'scrapCategoryCount'
        ));
    }
}
