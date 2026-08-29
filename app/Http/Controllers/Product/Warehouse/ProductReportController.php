<?php

namespace App\Http\Controllers\Product\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Disposition;
use App\Models\Employee;
use App\Models\ProductCategory;
use App\Models\WarehouseLocation;
use App\Models\WorkflowStage;
use App\Services\Warehouse\ProductReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ProductReportController extends Controller
{
    public function index(Request $request, ProductReportService $reports)
    {
        $filters = $this->filters($request);
        $allProducts = $reports->collection($filters);
        $summary = $reports->summary($allProducts);
        $exceptions = $reports->exceptions($allProducts)->take(50);
        $categoriesChart = $reports->categoryBreakdown($allProducts)->take(12)->values();

        $products = $reports->query($filters)
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 25))
            ->withQueryString();
        $products->setCollection($reports->decorate($products->getCollection()));

        return view('pages.products.warehouse.products.reports.index', [
            'products' => $products,
            'summary' => $summary,
            'exceptions' => $exceptions,
            'categoriesChart' => $categoriesChart,
            'categories' => ProductCategory::select('id', 'name')->orderBy('name')->get(),
            'locations' => WarehouseLocation::select('id', 'name')->orderBy('name')->get(),
            'workflowStages' => WorkflowStage::select('id', 'name')->orderBy('name')->get(),
            'dispositions' => Disposition::select('id', 'name')->orderBy('name')->get(),
            'technicians' => Employee::select('id', 'full_name')->orderBy('full_name')->get(),
        ]);
    }

    public function exportPdf(Request $request, ProductReportService $reports)
    {
        $filters = $this->filters($request);
        $products = $reports->collection($filters);
        $summary = $reports->summary($products);

        return Pdf::loadView('pdf.warehouse-product-report', compact('products', 'summary', 'filters'))
            ->setPaper('A4', 'landscape')
            ->download('warehouse-product-report-'.now()->format('Y-m-d').'.pdf');
    }

    public function exportCsv(Request $request, ProductReportService $reports)
    {
        $filters = $this->filters($request);
        $products = $reports->collection($filters);
        $filename = 'warehouse-product-report-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($products) {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, [
                'Unique ID', 'Title', 'Category', 'Location', 'Technician', 'Workflow',
                'Disposition', 'Reporting Status', 'Source', 'Total Units', 'Sold Units',
                'Remaining Units', 'Age Days', 'Days Since Update', 'Last Action', 'Created At',
            ]);

            foreach ($products as $product) {
                fputcsv($stream, [
                    $product->unique_id,
                    $product->title,
                    $product->category?->name,
                    $product->warehouseLocation?->name,
                    $product->assignedTo?->full_name,
                    $product->workflowStage?->name,
                    $product->disposition?->name,
                    $product->reporting_status_label.($product->is_partially_sold ? ' (Partially Sold)' : ''),
                    $product->manual_status ? 'Manual' : 'Barcode',
                    $product->total_units,
                    $product->sold_units,
                    $product->remaining_units,
                    $product->age_days,
                    $product->days_since_update,
                    $product->last_action,
                    optional($product->created_at)->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filters(Request $request): array
    {
        return $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'category' => ['nullable', 'integer'],
            'location' => ['nullable', 'integer'],
            'technician' => ['nullable', 'integer'],
            'work_flow_stage' => ['nullable', 'integer'],
            'disposition' => ['nullable', 'integer'],
            'manual_status' => ['nullable', 'in:0,1'],
            'assigned' => ['nullable', 'in:assigned,unassigned'],
            'status' => ['nullable', 'in:ready,not_ready,hold,issued,sold,scrap'],
            'age_bucket' => ['nullable', 'in:0_7,8_15,16_30,31_60,61_90,90_plus'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
        ]);
    }
}
