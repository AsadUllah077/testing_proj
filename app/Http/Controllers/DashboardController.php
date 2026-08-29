<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\GatePass;
use App\Models\GatePassItem;
use App\Models\Product;
use App\Models\Project;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Vendor;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $companyId = auth()->user()->company_id;

        // Users
        $totalUsers = User::where('company_id', $companyId)->count();
        $enabledUsers = User::where('company_id', $companyId)->where('status', 1)->count();
        $disabledUsers = $totalUsers - $enabledUsers;
        $totalRoles = Role::count();

        // Customers & Vendors
        $totalCustomers = Customer::where('company_id', $companyId)->count();
        $totalVendors = Vendor::where('company_id', $companyId)->count();

        // Products
        $countColumn = DB::getQueryGrammar()->wrap('count');
        $unitExpression = "CASE WHEN manual_status = 1 THEN COALESCE({$countColumn}, 0) ELSE 1 END";
        $productUnits = function ($query) use ($unitExpression): int {
            return (int) $query
                ->selectRaw("COALESCE(SUM({$unitExpression}), 0) as total")
                ->value('total');
        };
        $productRow = function ($product, ?int $quantity = null, ?string $source = null, $date = null): array {
            return [
                'name' => $product?->title ?? $product?->name ?? 'Product #'.($product?->id ?? ''),
                'unique_id' => $product?->unique_id ?? '-',
                'category' => $product?->category?->name ?? '-',
                'quantity' => $quantity ?? ((int) ($product?->manual_status ?? 0) === 1 ? (int) ($product?->count ?? 0) : 1),
                'status' => ucfirst((string) ($product?->status ?? 'N/A')),
                'source' => $source ?? '-',
                'date' => $date instanceof CarbonInterface ? $date->format('M j, Y') : $date,
            ];
        };

        $inventoryProductsQuery = Product::where('company_id', $companyId)
            ->where('sold_status', 0)
            ->where('scrap_status', 0)
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhereRaw('LOWER(status) != ?', ['issued']);
            });
        $singleSoldProductsQuery = Product::where('company_id', $companyId)
            ->where('sold_status', 1)
            ->where(function ($query) {
                $query->whereNull('manual_status')
                    ->orWhere('manual_status', '!=', 1);
            });
        $manualSoldItemsQuery = GatePassItem::query()
            ->whereNull('returned_at')
            ->whereHas('product', function ($query) use ($companyId) {
                $query->where('company_id', $companyId)
                    ->where('manual_status', 1)
                    ->where('scrap_status', 0);
            })
            ->whereHas('gatePass', function ($query) use ($companyId) {
                $query->where('company_id', $companyId)
                    ->where('status', '!=', 'REJECTED');
            });

        $totalProducts = Product::where('company_id', $companyId)->count();
        $totalInventoryUnits = $productUnits(clone $inventoryProductsQuery);
        $activeProducts = Product::where('company_id', $companyId)->whereRaw("LOWER(status) = 'active'")->count();
        $inactiveProducts = Product::where('company_id', $companyId)->whereRaw("LOWER(status) = 'inactive'")->count();
        $assignedProducts = Product::where('company_id', $companyId)->whereNotNull('assigned_to')->count();
        $unassignedProducts = Product::where('company_id', $companyId)->whereNull('assigned_to')->count();
        $scrapProducts = Product::where('company_id', $companyId)->where('scrap_status', 1)->count();
        $soldProducts = (clone $singleSoldProductsQuery)->count() + (int) (clone $manualSoldItemsQuery)->sum('quantity');
        $soldProductsToday = (clone $singleSoldProductsQuery)->whereDate('updated_at', today())->count()
            + (int) (clone $manualSoldItemsQuery)->whereDate('created_at', today())->sum('quantity');
        $inventoryProductRows = (clone $inventoryProductsQuery)
            ->with(['category', 'warehouseLocation'])
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn ($product) => $productRow($product, null, $product->warehouseLocation?->name ?? 'Inventory', $product->created_at));
        $manualSoldProductRows = (clone $manualSoldItemsQuery)
            ->with(['product.category', 'gatePass'])
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn ($item) => [
                'name' => $item->product?->title ?? $item->product_name ?? 'Product #'.$item->product_id,
                'unique_id' => $item->unique_id ?? $item->product?->unique_id ?? '-',
                'category' => $item->category_name ?? $item->product?->category?->name ?? '-',
                'quantity' => (int) ($item->quantity ?? 1),
                'status' => ucfirst((string) ($item->product?->status ?? 'Sold')),
                'source' => $item->gatePass?->gate_pass_id ?? 'Gate Pass #'.$item->gate_pass_id,
                'date' => $item->created_at?->format('M j, Y'),
            ]);
        $singleSoldLimit = max(0, 50 - $manualSoldProductRows->count());
        $singleSoldProductRows = $singleSoldLimit > 0
            ? (clone $singleSoldProductsQuery)
                ->with('category')
                ->latest('updated_at')
                ->limit($singleSoldLimit)
                ->get()
                ->map(fn ($product) => $productRow($product, 1, 'Sold product', $product->updated_at))
            : collect();
        $soldProductRows = $manualSoldProductRows->concat($singleSoldProductRows)->values();
        $manualSoldTodayRows = (clone $manualSoldItemsQuery)
            ->whereDate('created_at', today())
            ->with(['product.category', 'gatePass'])
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn ($item) => [
                'name' => $item->product?->title ?? $item->product_name ?? 'Product #'.$item->product_id,
                'unique_id' => $item->unique_id ?? $item->product?->unique_id ?? '-',
                'category' => $item->category_name ?? $item->product?->category?->name ?? '-',
                'quantity' => (int) ($item->quantity ?? 1),
                'status' => ucfirst((string) ($item->product?->status ?? 'Sold')),
                'source' => $item->gatePass?->gate_pass_id ?? 'Gate Pass #'.$item->gate_pass_id,
                'date' => $item->created_at?->format('M j, Y'),
            ]);
        $singleSoldTodayLimit = max(0, 50 - $manualSoldTodayRows->count());
        $singleSoldTodayRows = $singleSoldTodayLimit > 0
            ? (clone $singleSoldProductsQuery)
                ->whereDate('updated_at', today())
                ->with('category')
                ->latest('updated_at')
                ->limit($singleSoldTodayLimit)
                ->get()
                ->map(fn ($product) => $productRow($product, 1, 'Sold today', $product->updated_at))
            : collect();
        $soldTodayProductRows = $manualSoldTodayRows->concat($singleSoldTodayRows)->values();
        $scrapProductRows = Product::where('company_id', $companyId)
            ->where('scrap_status', 1)
            ->with('category')
            ->latest('updated_at')
            ->limit(50)
            ->get()
            ->map(fn ($product) => $productRow($product, null, 'Scrap', $product->updated_at));
        $recentProducts = Product::where('company_id', $companyId)
            ->with('category')
            ->latest()
            ->limit(5)
            ->get();

        // Gate Passes
        $totalGatePasses = GatePass::where('company_id', $companyId)->count();
        $pendingQcGatePasses = GatePass::where('company_id', $companyId)->where('status', 'QC_APPROVAL_PENDING')->count();
        $pendingSecGatePasses = GatePass::where('company_id', $companyId)->where('status', 'SECURITY_APPROVAL_PENDING')->count();
        $approvedGatePasses = GatePass::where('company_id', $companyId)->where('status', 'QC_AND_SECURITY_APPROVED')->count();
        $rejectedGatePasses = GatePass::where('company_id', $companyId)->where('status', 'REJECTED')->count();
        $recentGatePasses = GatePass::where('company_id', $companyId)
            ->with(['preparedBy', 'items.product.category', 'warehouseLocation'])
            ->withCount('items')
            ->latest()
            ->limit(5)
            ->get();

        // Accounting totals
        $totalSalesCount = Sale::where('company_id', $companyId)->count();
        $totalBillsCount = Bill::where('company_id', $companyId)->where('approval_status', 'approved')->count();
        $totalSalesAmount = Sale::where('company_id', $companyId)->sum('total');
        $totalBillsAmount = Bill::where('company_id', $companyId)->where('approval_status', 'approved')->sum('total_after_discount');

        // Monthly sales & bills for last 6 months
        $months = collect(range(5, 0))->map(fn ($i) => Carbon::now()->subMonths($i));
        $monthlySalesData = $months->map(fn ($m) => Sale::where('company_id', $companyId)
            ->whereYear('date', $m->year)->whereMonth('date', $m->month)->sum('total'));
        $monthlyBillsData = $months->map(fn ($m) => Bill::where('company_id', $companyId)
            ->where('approval_status', 'approved')->whereYear('date', $m->year)->whereMonth('date', $m->month)->sum('total_after_discount'));
        $monthLabels = $months->map(fn ($m) => $m->format('M Y'));

        // Employees
        $totalEmployees = Employee::where('company_id', $companyId)->count();
        $activeEmployees = Employee::where('company_id', $companyId)->where('status', 'active')->count();
        $inactiveEmployees = Employee::where('company_id', $companyId)->where('status', 'inactive')->count();
        $resignedEmployees = Employee::where('company_id', $companyId)->where('status', 'resigned')->count();
        $terminatedEmployees = Employee::where('company_id', $companyId)->where('status', 'terminated')->count();

        // Today's attendance
        $todayAttendance = EmployeeAttendance::where('company_id', $companyId)
            ->whereDate('attendance_date', today())->count();

        // Projects
        $totalProjects = Project::where('company_id', $companyId)->count();
        $planningProjects = Project::where('company_id', $companyId)->where('status', 'planning')->count();
        $activeProjects = Project::where('company_id', $companyId)->where('status', 'active')->count();
        $completedProjects = Project::where('company_id', $companyId)->where('status', 'completed')->count();
        $onHoldProjects = Project::where('company_id', $companyId)->where('status', 'on_hold')->count();

        // Tickets
        $totalTickets = Ticket::where('company_id', $companyId)->count();
        $openTickets = Ticket::where('company_id', $companyId)->where('status', 'open')->count();
        $inProgressTickets = Ticket::where('company_id', $companyId)->where('status', 'in_progress')->count();
        $closedTickets = Ticket::where('company_id', $companyId)->where('status', 'closed')->count();

        // Recent sales (last 5)
        $recentSales = Sale::where('company_id', $companyId)
            ->with('customer')
            ->latest('date')
            ->limit(5)
            ->get();

        // Recent employees (last 5)
        $recentEmployees = Employee::where('company_id', $companyId)
            ->with('department')
            ->latest()
            ->limit(5)
            ->get();

        return view('pages.dashboard', compact(
            'totalUsers', 'enabledUsers', 'disabledUsers', 'totalRoles',
            'totalCustomers', 'totalVendors',
            'totalProducts', 'totalInventoryUnits', 'activeProducts', 'inactiveProducts', 'assignedProducts', 'unassignedProducts', 'scrapProducts', 'soldProducts', 'soldProductsToday', 'inventoryProductRows', 'soldProductRows', 'soldTodayProductRows', 'scrapProductRows', 'recentProducts',
            'totalSalesCount', 'totalBillsCount', 'totalSalesAmount', 'totalBillsAmount',
            'monthlySalesData', 'monthlyBillsData', 'monthLabels',
            'totalEmployees', 'activeEmployees', 'inactiveEmployees', 'resignedEmployees', 'terminatedEmployees',
            'todayAttendance',
            'totalProjects', 'planningProjects', 'activeProjects', 'completedProjects', 'onHoldProjects',
            'totalTickets', 'openTickets', 'inProgressTickets', 'closedTickets',
            'totalGatePasses', 'pendingQcGatePasses', 'pendingSecGatePasses', 'approvedGatePasses', 'rejectedGatePasses', 'recentGatePasses',
            'recentSales', 'recentEmployees'
        ));
    }
}
