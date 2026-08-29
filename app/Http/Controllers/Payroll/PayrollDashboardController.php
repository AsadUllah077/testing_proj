<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\PayRun;
use App\Models\Payslip;
use App\Services\Payroll\PayrollForecaster;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PayrollDashboardController extends Controller
{
    protected $forecaster;

    public function __construct(PayrollForecaster $forecaster)
    {
        $this->forecaster = $forecaster;
    }

    public function index()
    {
        $companyId = Auth::user()->company_id;

        // ── KPI: Latest posted/paid pay run totals ──────────────────────────
        $latestRun = PayRun::where('company_id', $companyId)
            ->whereIn('status', ['posted', 'paid'])
            ->orderByDesc('period_end')
            ->first();

        $latestGross      = 0;
        $latestDeductions = 0;
        $latestNet        = 0;
        $latestHeadcount  = 0;

        if ($latestRun) {
            $latestGross      = $latestRun->payslips()->sum('gross_earnings');
            $latestDeductions = $latestRun->payslips()->sum('total_deductions');
            $latestNet        = $latestRun->payslips()->sum('net_pay');
            $latestHeadcount  = $latestRun->payslips()->count();
        }

        // ── KPI: Active employees ───────────────────────────────────────────
        $activeEmployees = Employee::where('company_id', $companyId)
            ->where('status', 'active')
            ->count();

        // ── KPI: Pending approvals ──────────────────────────────────────────
        $pendingApprovals = PayRun::where('company_id', $companyId)
            ->where('approval_status', 'pending')
            ->where('status', 'draft')
            ->count();

        // ── KPI: Total pay runs this year ───────────────────────────────────
        $payRunsThisYear = PayRun::where('company_id', $companyId)
            ->whereYear('period_end', now()->year)
            ->count();

        // ── Monthly payroll trend (last 6 months) ───────────────────────────
        $months      = [];
        $grossTrend  = [];
        $netTrend    = [];

        for ($i = 5; $i >= 0; $i--) {
            $date  = now()->subMonths($i);
            $month = $date->format('M Y');
            $months[] = $month;

            $agg = Payslip::join('pay_runs', 'payslips.pay_run_id', '=', 'pay_runs.id')
                ->where('pay_runs.company_id', $companyId)
                ->whereIn('pay_runs.status', ['posted', 'paid'])
                ->whereYear('pay_runs.period_end', $date->year)
                ->whereMonth('pay_runs.period_end', $date->month)
                ->selectRaw('SUM(gross_earnings) as gross, SUM(net_pay) as net')
                ->first();

            $grossTrend[] = round($agg->gross ?? 0, 2);
            $netTrend[]   = round($agg->net ?? 0, 2);
        }

        // ── Pay run status breakdown (all time for company) ─────────────────
        $statusCounts = PayRun::where('company_id', $companyId)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $statusLabels = array_keys($statusCounts);
        $statusValues = array_values($statusCounts);

        // ── Recent pay runs (last 8) ─────────────────────────────────────────
        $recentPayRuns = PayRun::where('company_id', $companyId)
            ->withCount('payslips')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        // ── Pending approval list (up to 5) ─────────────────────────────────
        $pendingRuns = PayRun::where('company_id', $companyId)
            ->where('approval_status', 'pending')
            ->withCount('payslips')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        // ── Top 5 pay items by total amount (last pay run) ──────────────────
        $topPayItems = [];
        if ($latestRun) {
            $topPayItems = DB::table('payslip_items')
                ->join('payslips', 'payslip_items.payslip_id', '=', 'payslips.id')
                ->join('pay_items', 'payslip_items.pay_item_id', '=', 'pay_items.id')
                ->where('payslips.pay_run_id', $latestRun->id)
                ->select('pay_items.name', 'pay_items.type', DB::raw('SUM(payslip_items.amount) as total'))
                ->groupBy('pay_items.id', 'pay_items.name', 'pay_items.type')
                ->orderByDesc('total')
                ->limit(5)
                ->get();
        }

        // ── Forward Forecasting ─────────────────────────────────────────────
        $forecastData = $this->forecaster->getForecast($companyId);

        return view('pages.payroll.dashboard', compact(
            'latestRun',
            'latestGross',
            'latestDeductions',
            'latestNet',
            'latestHeadcount',
            'activeEmployees',
            'pendingApprovals',
            'payRunsThisYear',
            'months',
            'grossTrend',
            'netTrend',
            'statusLabels',
            'statusValues',
            'recentPayRuns',
            'pendingRuns',
            'topPayItems',
            'forecastData'
        ));
    }
}
