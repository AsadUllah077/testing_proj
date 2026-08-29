<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Models\GatePass;
use App\Models\LogisticsBankCharge;
use App\Models\LogisticsBookingV2;
use App\Models\LogisticsGoodsDeclaration;
use App\Models\LogisticsLetterOfCredit;
use App\Models\LogisticsShipment;
use App\Models\LogisticsTradeDocument;
use App\Models\Part;
use App\Models\Product;
use App\Models\ProductAssignment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        $warehouse = $this->warehouseData();
        $logistics = $this->logisticsData();
        $actionItems = $this->actionItems($warehouse, $logistics);
        $updatedAt = now()->format('M d, Y h:i A');

        return view('pages.products.dashboard.index', compact(
            'warehouse',
            'logistics',
            'actionItems',
            'updatedAt'
        ));
    }

    private function warehouseData(): array
    {
        $totalProducts = Product::count();
        $readyForSale = $this->readyForSaleQuery()->count();
        $issuedProducts = Product::whereRaw("LOWER(status) = 'issued'")->count();
        $soldProducts = Product::where('sold_status', 1)->count();
        $scrapProducts = Product::where('scrap_status', 1)->count();
        $notReady = Product::where('sold_status', 0)
            ->where('scrap_status', 0)
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere(function ($statusQuery) {
                        $statusQuery->whereRaw("LOWER(status) != 'active'")
                            ->whereRaw("LOWER(status) != 'ready for sale'")
                            ->whereRaw("LOWER(status) != 'issued'");
                    });
            })
            ->count();

        $pendingGatePasses = GatePass::whereIn('status', [
            'QC_APPROVAL_PENDING',
            'SECURITY_APPROVAL_PENDING',
        ])->count();

        $lowStockParts = Part::where('reorder_level', '>', 0)
            ->whereColumn('quantity', '<=', 'reorder_level')
            ->count();

        $stats = [
            'total_products' => $totalProducts,
            'ready_for_sale' => $readyForSale,
            'not_ready' => $notReady,
            'issued' => $issuedProducts,
            'sold' => $soldProducts,
            'scrap' => $scrapProducts,
            'assigned' => Product::whereNotNull('assigned_to')->count(),
            'unassigned' => Product::whereNull('assigned_to')->count(),
            'active_assignments' => ProductAssignment::whereNull('completed_at')->count(),
            'pending_gate_passes' => $pendingGatePasses,
            'approved_gate_passes' => GatePass::where('status', 'QC_AND_SECURITY_APPROVED')->count(),
            'low_stock_parts' => $lowStockParts,
        ];

        $health = [
            ['label' => 'Ready', 'value' => $readyForSale, 'color' => '#10b981'],
            ['label' => 'In Progress', 'value' => $notReady + $issuedProducts, 'color' => '#0078C8'],
            ['label' => 'Sold', 'value' => $soldProducts, 'color' => '#6366f1'],
            ['label' => 'Scrap', 'value' => $scrapProducts, 'color' => '#ef4444'],
        ];

        return [
            'stats' => $stats,
            'health' => $health,
            'health_total' => array_sum(array_column($health, 'value')),
            'category_chart' => Product::with('category')
                ->select('category_id', DB::raw('COUNT(*) as total'))
                ->groupBy('category_id')
                ->orderByDesc('total')
                ->limit(8)
                ->get()
                ->map(fn ($row) => [
                    'label' => $row->category?->name ?? 'Uncategorized',
                    'value' => (int) $row->total,
                ])
                ->values(),
            'location_chart' => Product::with('warehouseLocation')
                ->select('warehouse_location_id', DB::raw('COUNT(*) as total'))
                ->whereNotNull('warehouse_location_id')
                ->groupBy('warehouse_location_id')
                ->orderByDesc('total')
                ->limit(8)
                ->get()
                ->map(fn ($row) => [
                    'label' => $row->warehouseLocation?->name ?? 'Unknown',
                    'value' => (int) $row->total,
                ])
                ->values(),
            'workflow_chart' => Product::with('workflowStage')
                ->select('work_flow_stage_id', DB::raw('COUNT(*) as total'))
                ->whereNotNull('work_flow_stage_id')
                ->groupBy('work_flow_stage_id')
                ->orderByDesc('total')
                ->limit(6)
                ->get()
                ->map(fn ($row) => [
                    'label' => $row->workflowStage?->name ?? 'Unknown',
                    'value' => (int) $row->total,
                ])
                ->values(),
            'daily_trend' => $this->dailyTrend(Product::class, 'created_at', 14),
            'recent_products' => Product::with(['category', 'warehouseLocation', 'assignedTo'])
                ->latest()
                ->limit(6)
                ->get(),
        ];
    }

    private function logisticsData(): array
    {
        $activeShipments = LogisticsShipment::whereNotIn('status', ['Delivered'])->count();
        $delayedShipments = LogisticsShipment::where('status', 'Delayed')->count();
        $pendingCustoms = LogisticsGoodsDeclaration::whereIn('status', ['Draft', 'Filed'])->count();
        $totalBookings = LogisticsBookingV2::count();
        $totalShipments = LogisticsShipment::count();
        $bookingTypeColumn = $this->firstExistingColumn('logistics_bookings', ['booking_type', 'service_type']);

        $bookingTypeCounts = $bookingTypeColumn
            ? LogisticsBookingV2::select($bookingTypeColumn, DB::raw('COUNT(*) as total'))
                ->whereNotNull($bookingTypeColumn)
                ->groupBy($bookingTypeColumn)
                ->pluck('total', $bookingTypeColumn)
                ->toArray()
            : [];

        $freightCounts = LogisticsShipment::select('freight_mode', DB::raw('COUNT(*) as total'))
            ->whereNotNull('freight_mode')
            ->groupBy('freight_mode')
            ->pluck('total', 'freight_mode')
            ->toArray();

        if (empty($freightCounts) && $bookingTypeColumn) {
            $freightCounts = $bookingTypeCounts;
        }

        $shipmentStatusCounts = LogisticsShipment::select('status', DB::raw('COUNT(*) as total'))
            ->whereNotNull('status')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        return [
            'stats' => [
                'total_bookings' => $totalBookings,
                'imports' => $this->countColumnValues(LogisticsBookingV2::class, $bookingTypeColumn, ['import', 'Import']),
                'exports' => $this->countColumnValues(LogisticsBookingV2::class, $bookingTypeColumn, ['export', 'Export']),
                'active_shipments' => $activeShipments,
                'total_shipments' => $totalShipments,
                'delayed_shipments' => $delayedShipments,
                'pending_customs' => $pendingCustoms,
                'cleared_gds' => LogisticsGoodsDeclaration::where('status', 'Cleared')->count(),
                'active_lcs' => LogisticsLetterOfCredit::where('record_type', 'lc')->where('status', 'Active')->count(),
                'total_lc_value' => (float) LogisticsLetterOfCredit::where('record_type', 'lc')
                    ->whereIn('status', ['Active', 'Expiring Soon'])
                    ->sum('amount'),
                'total_duty' => (float) LogisticsGoodsDeclaration::sum('total_duty'),
                'bank_charges' => (float) LogisticsBankCharge::sum('amount_pkr'),
                'missing_documents' => LogisticsTradeDocument::where('status', 'Missing')->count(),
            ],
            'booking_split' => $this->splitData($bookingTypeCounts, [
                'import' => '#0078C8',
                'export' => '#10b981',
            ]),
            'freight_split' => $this->splitData($freightCounts, [
                'sea' => '#0078C8',
                'air' => '#8b5cf6',
                'road' => '#f59e0b',
                'rail' => '#10b981',
            ]),
            'shipment_status' => $this->splitData($shipmentStatusCounts, [
                'Booked' => '#8b5cf6',
                'In-Transit' => '#0078C8',
                'Arrived' => '#06b6d4',
                'Cleared' => '#10b981',
                'Delivered' => '#22c55e',
                'Delayed' => '#ef4444',
            ]),
            'monthly_bookings' => $this->monthlyBookingTrend($bookingTypeColumn),
            'recent_bookings' => LogisticsBookingV2::latest()->limit(6)->get(),
            'recent_shipments' => LogisticsShipment::latest()->limit(6)->get(),
        ];
    }

    private function readyForSaleQuery(): Builder
    {
        return Product::where(function ($query) {
            $query->whereRaw("LOWER(status) = 'active'")
                ->orWhereRaw("LOWER(status) = 'ready for sale'");
        });
    }

    private function actionItems(array $warehouse, array $logistics): array
    {
        $items = [];

        if ($warehouse['stats']['pending_gate_passes'] > 0) {
            $items[] = [
                'title' => 'Gate passes awaiting approval',
                'value' => $warehouse['stats']['pending_gate_passes'],
                'label' => 'Warehouse',
                'tone' => 'amber',
                'icon' => 'fa-clipboard-check',
                'href' => route('warehouse.gate-pass.index'),
            ];
        }

        if ($warehouse['stats']['low_stock_parts'] > 0) {
            $items[] = [
                'title' => 'Parts below reorder level',
                'value' => $warehouse['stats']['low_stock_parts'],
                'label' => 'Inventory',
                'tone' => 'red',
                'icon' => 'fa-screwdriver-wrench',
                'href' => route('warehouse.parts.index'),
            ];
        }

        if ($logistics['stats']['delayed_shipments'] > 0) {
            $items[] = [
                'title' => 'Delayed shipments need attention',
                'value' => $logistics['stats']['delayed_shipments'],
                'label' => 'Logistics',
                'tone' => 'red',
                'icon' => 'fa-triangle-exclamation',
                'href' => route('logistics.shipments.delayed'),
            ];
        }

        if ($logistics['stats']['missing_documents'] > 0) {
            $items[] = [
                'title' => 'Missing trade documents',
                'value' => $logistics['stats']['missing_documents'],
                'label' => 'Documentation',
                'tone' => 'blue',
                'icon' => 'fa-file-circle-exclamation',
                'href' => route('logistics.manage-booking.v2.documents'),
            ];
        }

        if ($logistics['stats']['pending_customs'] > 0) {
            $items[] = [
                'title' => 'Customs declarations pending',
                'value' => $logistics['stats']['pending_customs'],
                'label' => 'Customs',
                'tone' => 'purple',
                'icon' => 'fa-file-invoice',
                'href' => route('logistics.gd.index'),
            ];
        }

        return array_slice($items, 0, 5);
    }

    private function dailyTrend(string $model, string $dateColumn, int $days): array
    {
        $trend = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);

            $trend[] = [
                'label' => $date->format('M d'),
                'value' => $model::whereDate($dateColumn, $date->toDateString())->count(),
            ];
        }

        return $trend;
    }

    private function monthlyBookingTrend(?string $typeColumn): array
    {
        $trend = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();

            $trend[] = [
                'label' => $month->format('M'),
                'total' => LogisticsBookingV2::whereBetween('created_at', [$start, $end])->count(),
                'imports' => $typeColumn
                    ? LogisticsBookingV2::whereBetween('created_at', [$start, $end])
                        ->whereIn($typeColumn, ['import', 'Import'])
                        ->count()
                    : 0,
                'exports' => $typeColumn
                    ? LogisticsBookingV2::whereBetween('created_at', [$start, $end])
                        ->whereIn($typeColumn, ['export', 'Export'])
                        ->count()
                    : 0,
            ];
        }

        return $trend;
    }

    private function countColumnValues(string $model, ?string $column, array $values): int
    {
        if (!$column) {
            return 0;
        }

        return $model::whereIn($column, $values)->count();
    }

    private function firstExistingColumn(string $table, array $columns): ?string
    {
        foreach ($columns as $column) {
            if (Schema::hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    private function splitData(array $counts, array $colors): array
    {
        $total = array_sum($counts);
        $data = [];

        foreach ($counts as $label => $value) {
            $key = is_string($label) ? $label : (string) $label;
            $color = $colors[$key] ?? $colors[strtolower($key)] ?? '#64748b';

            $data[] = [
                'label' => $key ?: 'Unknown',
                'value' => (int) $value,
                'pct' => $total > 0 ? round(((int) $value / $total) * 100) : 0,
                'color' => $color,
            ];
        }

        usort($data, fn ($a, $b) => $b['value'] <=> $a['value']);

        return $data;
    }
}
