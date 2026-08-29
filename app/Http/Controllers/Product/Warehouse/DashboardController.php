<?php

namespace App\Http\Controllers\Product\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Disposition;
use App\Models\Employee;
use App\Models\GatePass;
use App\Models\GatePassItem;
use App\Models\Part;
use App\Models\Product;
use App\Models\ProductAssignment;
use App\Models\ProductIssue;
use App\Models\ScrapMaterial;
use App\Models\WorkflowStage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Get date range for trends (default: last 30 days)
            $days = $request->get('days', 30);
            $startDate = now()->subDays($days);
            $endDate = now();
            $countColumn = DB::getQueryGrammar()->wrap('count');
            $unitExpression = "CASE WHEN manual_status = 1 THEN CASE WHEN COALESCE({$countColumn}, 0) > 0 THEN {$countColumn} ELSE 1 END ELSE 1 END";
            $productUnits = function ($query) use ($unitExpression): int {
                return (int) $query
                    ->selectRaw("COALESCE(SUM({$unitExpression}), 0) as total")
                    ->value('total');
            };
            $soldProductUnits = $this->soldProductUnits();
            $issuedProductIssueUnits = $this->productIssueUnits('Issued');

            // ========== STATS CARDS ==========
            $stats = [
                // Total Inventory Stats
                'total_products' => $productUnits(
                    Product::where('sold_status', 0)
                        ->where('scrap_status', 0)
                        ->warehouseNotIssued()
                ),
                'total_products_change' => $this->calculateChange(Product::class, 'created_at', $days),

                // Ready For Sale — products with status = 'active' OR 'Ready for Sale'
                'ready_for_sale' => $productUnits(Product::where(function ($q) {
                    $q->whereRaw("LOWER(status) = 'active'")
                        ->orWhereRaw("LOWER(status) = 'ready for sale'");
                })->warehouseNotIssued()->where('sold_status', 0)->where('scrap_status', 0)),
                'ready_for_sale_change' => $this->calculateActiveChange('created_at', $days),

                'not_ready_for_sale' => $productUnits(Product::where(function ($q) {
                    $q->whereRaw("LOWER(status) != 'active'")
                        ->whereRaw("LOWER(status) != 'ready for sale'")
                        ->whereRaw("LOWER(status) != 'issued'")
                        ->whereRaw("LOWER(status) != 'hold'");
                })->warehouseNotIssued()->where('sold_status', 0)->where('scrap_status', 0)),

                'hold_products' => $productUnits(
                    Product::whereRaw("LOWER(status) = 'hold'")
                        ->where('sold_status', 0)
                        ->where('scrap_status', 0)
                ),

                'sold_products' => $soldProductUnits,

                'issued_products' => $issuedProductIssueUnits,

                // Active Technicians
                'active_technicians' => Employee::whereHas('department', function ($q) {
                    $q->whereRaw('LOWER(department) LIKE ?', ['%technician%']);
                })
                    ->whereIn('id', function ($q) {
                        $q->select('technician_id')
                            ->from('product_assignments')
                            ->whereNull('completed_at');
                    })->count(),
                'total_technicians' => Employee::whereHas('department', function ($q) {
                    $q->whereRaw('LOWER(department) LIKE ?', ['%technician%']);
                })->count(),

                // Gate Pass Stats
                'total_gate_passes' => GatePass::count(),
                'pending_gate_passes' => GatePass::whereIn('status', ['QC_APPROVAL_PENDING', 'SECURITY_APPROVAL_PENDING'])->count(),
                'approved_gate_passes' => GatePass::where('status', 'QC_AND_SECURITY_APPROVED')->count(),
                'gate_passes_change' => $this->calculateChange(GatePass::class, 'created_at', $days),
            ];

            // ========== FUNCTIONAL TESTING RESULTS ==========
            $testingResults = [
                'pass' => Product::whereHas('disposition', function ($q) {
                    $q->whereIn('name', ['Pass', 'Approved', 'Completed']);
                })->count(),
                'scrap' => Product::where('scrap_status', 1)->count(),
                'hold' => Product::whereHas('disposition', function ($q) {
                    $q->whereIn('name', ['Hold', 'Pending', 'Waiting']);
                })->count(),
                'fail' => Product::whereHas('disposition', function ($q) {
                    $q->whereIn('name', ['Fail', 'Rejected', 'Failed']);
                })->count(),
            ];

            // ========== PRODUCTS READY FOR SALE TODAY ==========
            $readyForSaleToday = Product::where(function ($q) {
                $q->whereRaw("LOWER(status) = 'active'")
                    ->orWhereRaw("LOWER(status) = 'ready for sale'");
            })
                ->whereDate('created_at', today())
                ->count();

            // ========== TIME SLOT DATA FOR READY FOR SALE PRODUCTS (09:00 - 19:00) ==========
            $timeSlotData = $this->getReadyForSaleByTimeSlot();

            // ========== PENDING QC APPROVALS ==========
            $pendingQcApprovals = Product::whereHas('workFlowStage', function ($q) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%qc%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%quality%']);
            })
                ->whereDoesntHave('disposition', function ($q) {
                    $q->whereIn('name', ['Pass', 'Fail', 'Rejected']);
                })
                ->with(['category', 'workFlowStage'])
                ->latest()
                ->limit(10)
                ->get();

            // ========== PRODUCTS WAITING FOR PARTS ==========
            $productsWaitingForParts = Product::whereHas('workFlowStage', function ($q) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%waiting%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%part%']);
            })
                ->whereDoesntHave('parts')
                ->with(['category', 'assignedTo', 'currentAssignment'])
                ->latest()
                ->limit(10)
                ->get();

            // ========== REPAIRING QUEUE ==========
            $repairingQueue = Product::whereHas('workFlowStage', function ($q) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%repair%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%fix%']);
            })
                ->where('sold_status', 0)
                ->where('scrap_status', 0)
                ->with(['category', 'assignedTo', 'currentAssignment'])
                ->latest()
                ->limit(10)
                ->get();

            // ========== PAINTING QUEUE ==========
            $paintingQueue = Product::whereHas('workFlowStage', function ($q) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%paint%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%color%']);
            })
                ->where('sold_status', 0)
                ->where('scrap_status', 0)
                ->with(['category', 'assignedTo', 'currentAssignment'])
                ->latest()
                ->limit(10)
                ->get();

            // ========== CHART DATA: PRODUCTS BY DISPOSITION ==========
            $dispositionChart = Disposition::select('id', 'name')
                ->get()
                ->map(function ($disp) {
                    return [
                        'name' => $disp->name,
                        'count' => Product::where('disposition_id', $disp->id)->count(),
                        'color' => $this->getDispositionColor($disp->name),
                    ];
                })
                ->filter(fn ($item) => $item['count'] > 0)
                ->sortByDesc('count')
                ->values();

            // ========== CHART DATA: PRODUCTS BY WORKFLOW STAGE ==========
            $workflowChart = WorkflowStage::select('id', 'name')
                ->get()
                ->map(function ($stage) {
                    return [
                        'name' => $stage->name,
                        'count' => Product::where('work_flow_stage_id', $stage->id)->count(),
                    ];
                })
                ->filter(fn ($item) => $item['count'] > 0)
                ->sortByDesc('count')
                ->take(10)
                ->values();

            // ========== CATEGORY WISE PRODUCT COUNT ==========
            $manualSoldByCategory = $this->manualSoldQuantityByCategory();
            $issuedByCategory = $this->productIssueQuantityByCategory('Issued');
            $issuedSql = "(COALESCE(LOWER(status), '') = 'issued' OR (COALESCE(manual_status, 0) = 1 AND assigned_to IS NOT NULL AND COALESCE(LOWER(status), '') != 'hold'))";
            $notIssuedSql = "NOT {$issuedSql}";
            $categoryWiseCount = Product::with('category')
                ->where('scrap_status', 0)
                ->select(
                    'category_id',
                    DB::raw("SUM(CASE WHEN sold_status = 0 AND {$notIssuedSql} THEN {$unitExpression} ELSE 0 END) as total"),
                    DB::raw("SUM(CASE WHEN sold_status = 0 AND {$notIssuedSql} AND (LOWER(status) = 'active' OR LOWER(status) = 'ready for sale') THEN {$unitExpression} ELSE 0 END) as ready_for_sale"),
                    DB::raw("SUM(CASE WHEN sold_status = 0 AND {$notIssuedSql} AND COALESCE(LOWER(status), '') != 'active' AND COALESCE(LOWER(status), '') != 'ready for sale' AND COALESCE(LOWER(status), '') != 'issued' AND COALESCE(LOWER(status), '') != 'hold' THEN {$unitExpression} ELSE 0 END) as not_ready_for_sale"),
                    DB::raw("SUM(CASE WHEN sold_status = 0 AND LOWER(status) = 'hold' THEN {$unitExpression} ELSE 0 END) as hold"),
                    DB::raw("SUM(CASE WHEN sold_status = 1 AND COALESCE(manual_status, 0) != 1 THEN 1 ELSE 0 END) as sold")
                )
                ->groupBy('category_id')
                ->get()
                ->map(function ($item) use ($manualSoldByCategory, $issuedByCategory) {
                    $categoryKey = (string) ($item->category_id ?? 'uncategorized');
                    $sold = (int) $item->sold + (int) ($manualSoldByCategory[$categoryKey] ?? 0);
                    $issued = (int) ($issuedByCategory[$categoryKey] ?? 0);

                    return [
                        'id' => $item->category_id,
                        'name' => $item->category ? $item->category->name : 'Uncategorized',
                        'count' => (int) $item->total,
                        'ready_for_sale' => (int) $item->ready_for_sale,
                        'not_ready_for_sale' => (int) $item->not_ready_for_sale,
                        'hold' => (int) $item->hold,
                        'issued' => $issued,
                        'sold' => $sold,
                    ];
                })
                ->filter(fn ($item) => $item['count'] > 0)
                ->sortByDesc('count')
                ->values();

            // ========== RECENT ACTIVITIES ==========
            $recentActivities = $this->getRecentActivities();

            // ========== LOW STOCK PARTS ==========
            $lowStockParts = Part::where('quantity', '>', 0)
                ->where('reorder_level', '>', 0)
                ->whereColumn('quantity', '<=', 'reorder_level')
                ->with(['createdBy'])
                ->orderBy('quantity', 'asc')
                ->limit(5)
                ->get();

            // ========== INVENTORY HEALTH ==========
            $healthStats = [
                'ready' => $productUnits(Product::where(function ($q) {
                    $q->whereRaw("LOWER(status) = 'active'")
                        ->orWhereRaw("LOWER(status) = 'ready for sale'");
                })->warehouseNotIssued()->where('sold_status', 0)->where('scrap_status', 0)),
                'progress' => $productUnits(Product::where(function ($q) {
                    $q->whereRaw("LOWER(status) != 'active'")
                        ->whereRaw("LOWER(status) != 'ready for sale'")
                        ->whereRaw("LOWER(status) != 'hold'")
                        ->whereRaw("LOWER(status) != 'issued'");
                })->warehouseNotIssued()->where('sold_status', 0)->where('scrap_status', 0)),
                'hold' => $productUnits(Product::whereRaw("LOWER(status) = 'hold'")->where('sold_status', 0)->where('scrap_status', 0)),
                'sold' => $soldProductUnits,
                'scrap' => $productUnits(Product::where('scrap_status', 1)),
            ];

            // ========== SCAN ACTIVITY (LAST 14 DAYS) ==========
            $scanActivity = \App\Models\ProductScanLog::select(
                DB::raw('DATE(scanned_at) as date'),
                DB::raw('count(*) as count')
            )
                ->where('scanned_at', '>=', now()->subDays(14))
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->pluck('count', 'date')
                ->toArray();

            // Fill missing dates with 0
            $scanChartData = [];
            for ($i = 14; $i >= 0; $i--) {
                $date = now()->subDays($i)->format('Y-m-d');
                $scanChartData[] = [
                    'date' => now()->subDays($i)->format('D, d M'),
                    'count' => $scanActivity[$date] ?? 0,
                ];
            }

            // ========== TECHNICIAN PERFORMANCE ==========
            $technicianStats = Employee::whereHas('department', function ($q) {
                $q->whereRaw('LOWER(department) LIKE ?', ['%technician%']);
            })
                ->select(['id', 'full_name'])
                ->withCount(['assignments as completed_count' => function ($q) {
                    $q->whereNotNull('completed_at');
                }])
                ->withCount(['assignments as active_count' => function ($q) {
                    $q->whereNull('completed_at');
                }])
                ->orderBy('completed_count', 'desc')
                ->limit(10)
                ->get();

            // ========== GATE PASS STATUS ==========
            $gatePassStatus = [
                'pending_qc' => \App\Models\GatePass::where('status', 'QC_APPROVAL_PENDING')->count(),
                'pending_security' => \App\Models\GatePass::where('status', 'SECURITY_APPROVAL_PENDING')->count(),
                'approved' => \App\Models\GatePass::where('status', 'QC_AND_SECURITY_APPROVED')->count(),
                'rejected' => \App\Models\GatePass::where('status', 'REJECTED')->count(),
            ];

            // ========== PRODUCT ISSUES ==========
            $productIssueStatus = [
                'total' => $this->productIssueUnits(),
                'issued' => $issuedProductIssueUnits,
                'returned' => $this->productIssueUnits('Returned'),
            ];

            // ========== SCRAP SUMMARY ==========
            $scrapSummary = [
                'total_weight' => \App\Models\ScrapMaterial::sum('weight'),
                'in_storage' => \App\Models\ScrapMaterial::where('status', 'in_storage')->count(),
                'disposed' => \App\Models\ScrapMaterial::where('status', 'disposed')->count(),
            ];

            // ========== SCAN TODAY ==========
            $scansToday = \App\Models\ProductScanLog::whereDate('scanned_at', today())->count();
            $stats['scans_today'] = $scansToday;

            // Return view with all data
            return view('pages.products.warehouse.dashboard.index', compact(
                'stats',
                'testingResults',
                'readyForSaleToday',
                'timeSlotData',
                'pendingQcApprovals',
                'productsWaitingForParts',
                'repairingQueue',
                'paintingQueue',
                'dispositionChart',
                'workflowChart',
                'categoryWiseCount',
                'recentActivities',
                'lowStockParts',
                'gatePassStatus',
                'productIssueStatus',
                'scrapSummary',
                'healthStats',
                'scanChartData',
                'technicianStats',
                'days'
            ));

        } catch (\Exception $e) {
            Log::error('DashboardController@index Error: '.$e->getMessage());

            return view('pages.products.warehouse.dashboard.index', [
                'error' => 'Error loading dashboard data: '.$e->getMessage(),
                'stats' => [],
                'testingResults' => ['pass' => 0, 'scrap' => 0, 'hold' => 0, 'fail' => 0],
                'readyForSaleToday' => 0,
                'timeSlotData' => [],
                'pendingQcApprovals' => collect(),
                'productsWaitingForParts' => collect(),
                'repairingQueue' => collect(),
                'paintingQueue' => collect(),
                'dispositionChart' => collect(),
                'workflowChart' => collect(),
                'categoryWiseCount' => collect(),
                'recentActivities' => [],
                'lowStockParts' => collect(),
                'gatePassStatus' => ['pending_qc' => 0, 'pending_security' => 0, 'approved' => 0, 'rejected' => 0],
                'productIssueStatus' => ['total' => 0, 'issued' => 0, 'returned' => 0],
                'scrapSummary' => ['total_weight' => 0, 'in_storage' => 0, 'disposed' => 0],
                'healthStats' => ['ready' => 0, 'progress' => 0, 'hold' => 0, 'sold' => 0, 'scrap' => 0],
                'scanChartData' => [],
                'technicianStats' => collect(),
                'days' => 30,
            ]);
        }
    }

    /**
     * Get products ready for sale by time slot (09:00 - 19:00)
     */
    private function getReadyForSaleByTimeSlot()
    {
        $timeSlots = [];
        $startTime = strtotime('09:00');
        $endTime = strtotime('19:00');

        for ($time = $startTime; $time <= $endTime; $time = strtotime('+30 minutes', $time)) {
            $timeStr = date('H:i', $time);
            $nextTime = date('H:i', strtotime('+30 minutes', $time));

            // Count active products updated (became ready for sale) during this time slot today
            $count = Product::where(function ($q) {
                $q->whereRaw("LOWER(status) = 'active'")
                    ->orWhereRaw("LOWER(status) = 'ready for sale'");
            })
                ->whereTime('updated_at', '>=', $timeStr)
                ->whereTime('updated_at', '<', $nextTime)
                ->whereDate('updated_at', today())
                ->count();

            $timeSlots[] = [
                'time' => $timeStr,
                'count' => $count,
                'next_time' => $nextTime,
            ];
        }

        return $timeSlots;
    }

    /**
     * Calculate percentage change over period
     */
    private function calculateChange($model, $dateField, $days)
    {
        $currentPeriod = $model::where($dateField, '>=', now()->subDays($days))->count();
        $previousPeriod = $model::whereBetween($dateField, [
            now()->subDays($days * 2),
            now()->subDays($days),
        ])->count();

        if ($previousPeriod == 0) {
            return $currentPeriod > 0 ? '+100%' : '0%';
        }

        $change = (($currentPeriod - $previousPeriod) / $previousPeriod) * 100;
        $prefix = $change >= 0 ? '+' : '';

        return $prefix.number_format($change, 1).'%';
    }

    /**
     * Calculate change for active products (using whereRaw for case-insensitive match)
     */
    private function calculateActiveChange($dateField, $days)
    {
        $currentPeriod = Product::where(function ($q) {
            $q->whereRaw("LOWER(status) = 'active'")
                ->orWhereRaw("LOWER(status) = 'ready for sale'");
        })
            ->where($dateField, '>=', now()->subDays($days))
            ->count();

        $previousPeriod = Product::where(function ($q) {
            $q->whereRaw("LOWER(status) = 'active'")
                ->orWhereRaw("LOWER(status) = 'ready for sale'");
        })
            ->whereBetween($dateField, [
                now()->subDays($days * 2),
                now()->subDays($days),
            ])
            ->count();

        if ($previousPeriod == 0) {
            return $currentPeriod > 0 ? '+100%' : '0%';
        }

        $change = (($currentPeriod - $previousPeriod) / $previousPeriod) * 100;
        $prefix = $change >= 0 ? '+' : '';

        return $prefix.number_format($change, 1).'%';
    }

    /**
     * Calculate change for filtered queries
     */
    private function calculateFilteredChange($model, $filters, $dateField, $days)
    {
        $currentPeriod = $model::where($filters)
            ->where($dateField, '>=', now()->subDays($days))
            ->count();

        $previousPeriod = $model::where($filters)
            ->whereBetween($dateField, [
                now()->subDays($days * 2),
                now()->subDays($days),
            ])
            ->count();

        if ($previousPeriod == 0) {
            return $currentPeriod > 0 ? '+100%' : '0%';
        }

        $change = (($currentPeriod - $previousPeriod) / $previousPeriod) * 100;
        $prefix = $change >= 0 ? '+' : '';

        return $prefix.number_format($change, 1).'%';
    }

    /**
     * Get color for disposition
     */
    private function getDispositionColor($name)
    {
        $name = strtolower($name);

        if (in_array($name, ['pass', 'approved', 'completed'])) {
            return '#10b981'; // green
        } elseif (in_array($name, ['fail', 'rejected', 'failed'])) {
            return '#ef4444'; // red
        } elseif (in_array($name, ['pending', 'waiting', 'hold'])) {
            return '#f59e0b'; // yellow/orange
        } elseif (in_array($name, ['scrap', 'abandoned'])) {
            return '#6b7280'; // gray
        } else {
            return '#3b82f6'; // blue
        }
    }

    /**
     * Get recent activities — sorted by actual timestamp
     */
    private function getRecentActivities($limit = 10)
    {
        $activities = [];

        // Recent products added
        $recentProducts = Product::with(['createdBy', 'category'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($product) {
                return [
                    'type' => 'product_added',
                    'icon' => 'package',
                    'icon_bg' => 'bg-blue-100',
                    'icon_color' => 'text-blue-600',
                    'title' => 'New Product Added',
                    'description' => ($product->title ?? 'N/A').' ('.($product->unique_id ?? '').')',
                    'category' => $product->category?->name,
                    'time' => $product->created_at->diffForHumans(),
                    'timestamp' => $product->created_at->timestamp,
                    'user' => $product->createdBy?->username ?? 'System',
                    'link' => route('warehouse.products.show', $product->id),
                ];
            });

        $activities = array_merge($activities, $recentProducts->toArray());

        // Recent gate passes
        $recentGatePasses = GatePass::with(['preparedBy'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($gatePass) {
                $statusColors = [
                    'QC_APPROVAL_PENDING' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-600'],
                    'SECURITY_APPROVAL_PENDING' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-600'],
                    'QC_AND_SECURITY_APPROVED' => ['bg' => 'bg-green-100',  'text' => 'text-green-600'],
                    'REJECTED' => ['bg' => 'bg-red-100',    'text' => 'text-red-600'],
                ];

                $statusColor = $statusColors[$gatePass->status] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-600'];

                return [
                    'type' => 'gate_pass_created',
                    'icon' => 'truck',
                    'icon_bg' => $statusColor['bg'],
                    'icon_color' => $statusColor['text'],
                    'title' => 'Gate Pass '.$gatePass->gate_pass_id,
                    'description' => $gatePass->source_location.' → Departing '.optional($gatePass->departure_date)->format('M d, Y'),
                    'status' => str_replace('_', ' ', $gatePass->status),
                    'time' => $gatePass->created_at->diffForHumans(),
                    'timestamp' => $gatePass->created_at->timestamp,
                    'user' => $gatePass->preparedBy?->username ?? 'System',
                    'link' => route('warehouse.gate-pass.show', $gatePass->id),
                ];
            });

        $activities = array_merge($activities, $recentGatePasses->toArray());

        // Recent assignments
        $recentAssignments = ProductAssignment::with(['technician', 'product', 'assigner'])
            ->latest('assigned_at')
            ->limit(5)
            ->get()
            ->map(function ($assignment) {
                return [
                    'type' => 'assignment_created',
                    'icon' => 'user-check',
                    'icon_bg' => 'bg-purple-100',
                    'icon_color' => 'text-purple-600',
                    'title' => 'Task Assigned',
                    'description' => ($assignment->product?->title ?? 'N/A').' → '.($assignment->technician?->full_name ?? 'N/A'),
                    'task_type' => $assignment->task_type,
                    'time' => $assignment->assigned_at->diffForHumans(),
                    'timestamp' => $assignment->assigned_at->timestamp,
                    'user' => $assignment->assigner?->username ?? 'System',
                    'link' => route('warehouse.technicians.show', $assignment->id),
                ];
            });

        $activities = array_merge($activities, $recentAssignments->toArray());

        // Recent scrap entries
        $recentScrap = ScrapMaterial::with(['createdBy', 'scrapCategory'])
            ->latest()
            ->limit(3)
            ->get()
            ->map(function ($scrap) {
                return [
                    'type' => 'scrap_added',
                    'icon' => 'trash',
                    'icon_bg' => 'bg-gray-100',
                    'icon_color' => 'text-gray-600',
                    'title' => 'Scrap Added',
                    'description' => $scrap->item_description.' ('.$scrap->weight.' '.$scrap->quantity_type.')',
                    'category' => $scrap->scrapCategory?->name,
                    'time' => $scrap->created_at->diffForHumans(),
                    'timestamp' => $scrap->created_at->timestamp,
                    'user' => $scrap->createdBy?->username ?? 'System',
                    'link' => route('warehouse.scrap.details', $scrap->id),
                ];
            });

        $activities = array_merge($activities, $recentScrap->toArray());

        // Sort by actual timestamp (descending)
        usort($activities, function ($a, $b) {
            return ($b['timestamp'] ?? 0) - ($a['timestamp'] ?? 0);
        });

        return array_slice($activities, 0, $limit);
    }

    private function soldProductUnits(): int
    {
        $singleProductSold = Product::where('sold_status', 1)
            ->where(function ($q) {
                $q->whereNull('manual_status')
                    ->orWhere('manual_status', '!=', 1);
            })
            ->count();

        $manualSold = (int) GatePassItem::whereNull('returned_at')
            ->whereHas('product', fn ($q) => $q->where('manual_status', 1)->where('scrap_status', 0))
            ->whereHas('gatePass', fn ($q) => $q->where('status', '!=', 'REJECTED'))
            ->sum('quantity');

        return $singleProductSold + $manualSold;
    }

    private function manualSoldQuantityByCategory()
    {
        return GatePassItem::select(
            'products.category_id',
            DB::raw('COALESCE(SUM(gate_pass_items.quantity), 0) as sold_quantity')
        )
            ->join('products', 'products.id', '=', 'gate_pass_items.product_id')
            ->join('gate_passes', 'gate_passes.id', '=', 'gate_pass_items.gate_pass_id')
            ->whereNull('gate_pass_items.returned_at')
            ->whereNull('products.deleted_at')
            ->where('products.manual_status', 1)
            ->where('products.scrap_status', 0)
            ->where('gate_passes.status', '!=', 'REJECTED')
            ->groupBy('products.category_id')
            ->get()
            ->mapWithKeys(function ($item) {
                $categoryKey = (string) ($item->category_id ?? 'uncategorized');

                return [$categoryKey => (int) $item->sold_quantity];
            });
    }

    private function productIssueUnits(?string $status = null): int
    {
        $query = ProductIssue::query();

        if ($status) {
            $query->whereRaw('LOWER(status) = ?', [strtolower($status)]);
        }

        return (int) $query
            ->selectRaw('COALESCE(SUM(quantity), 0) as total')
            ->value('total');
    }

    private function productIssueQuantityByCategory(?string $status = null)
    {
        $query = ProductIssue::select(
            'products.category_id',
            DB::raw('COALESCE(SUM(product_issues.quantity), 0) as issue_quantity')
        )
            ->join('products', 'products.id', '=', 'product_issues.product_id')
            ->whereNull('products.deleted_at');

        if ($status) {
            $query->whereRaw('LOWER(product_issues.status) = ?', [strtolower($status)]);
        }

        return $query
            ->groupBy('products.category_id')
            ->get()
            ->mapWithKeys(function ($item) {
                $categoryKey = (string) ($item->category_id ?? 'uncategorized');

                return [$categoryKey => (int) $item->issue_quantity];
            });
    }

    /**
     * API endpoint for real-time stats
     */
    public function getStats()
    {
        try {
            $countColumn = DB::getQueryGrammar()->wrap('count');
            $unitExpression = "CASE WHEN manual_status = 1 THEN CASE WHEN COALESCE({$countColumn}, 0) > 0 THEN {$countColumn} ELSE 1 END ELSE 1 END";
            $productUnits = function ($query) use ($unitExpression): int {
                return (int) $query
                    ->selectRaw("COALESCE(SUM({$unitExpression}), 0) as total")
                    ->value('total');
            };

            $stats = [
                'total_products' => $productUnits(
                    Product::where('sold_status', 0)
                        ->where('scrap_status', 0)
                        ->warehouseNotIssued()
                ),
                'ready_for_sale' => $productUnits(Product::where(function ($q) {
                    $q->whereRaw("LOWER(status) = 'active'")
                        ->orWhereRaw("LOWER(status) = 'ready for sale'");
                })->warehouseNotIssued()->where('sold_status', 0)->where('scrap_status', 0)),
                'not_ready_for_sale' => $productUnits(Product::where(function ($q) {
                    $q->whereRaw("LOWER(status) != 'active'")
                        ->whereRaw("LOWER(status) != 'ready for sale'")
                        ->whereRaw("LOWER(status) != 'issued'")
                        ->whereRaw("LOWER(status) != 'hold'");
                })->warehouseNotIssued()->where('sold_status', 0)->where('scrap_status', 0)),
                'hold_products' => $productUnits(Product::whereRaw("LOWER(status) = 'hold'")->where('sold_status', 0)->where('scrap_status', 0)),
                'sold_products' => $this->soldProductUnits(),
                'issued_products' => $this->productIssueUnits('Issued'),
                'active_technicians' => Employee::whereHas('department', function ($q) {
                    $q->whereRaw('LOWER(department) LIKE ?', ['%technician%']);
                })
                    ->whereIn('id', function ($q) {
                        $q->select('technician_id')
                            ->from('product_assignments')
                            ->whereNull('completed_at');
                    })->count(),
                'pending_gate_passes' => GatePass::whereIn('status', ['QC_APPROVAL_PENDING', 'SECURITY_APPROVAL_PENDING'])->count(),
                'low_stock_parts' => Part::where('quantity', '>', 0)
                    ->where('reorder_level', '>', 0)
                    ->whereColumn('quantity', '<=', 'reorder_level')
                    ->count(),
            ];

            return response()->json(['success' => true, 'data' => $stats]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
