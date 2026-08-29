<?php

namespace App\Services\Payroll;

use App\Models\PayslipItem;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;

class OvertimeAnalyzer
{
    /**
     * Analyze overtime patterns for a company over the last N months.
     */
    public function analyze(int $companyId, int $months = 6, int $perPage = 10): array
    {
        $startDate = now()->subMonths($months)->startOfMonth();

        // 1. Monthly Overtime Trend
        $monthlyTrend = PayslipItem::join('payslips', 'payslip_items.payslip_id', '=', 'payslips.id')
            ->join('pay_runs', 'payslips.pay_run_id', '=', 'pay_runs.id')
            ->join('pay_items', 'payslip_items.pay_item_id', '=', 'pay_items.id')
            ->where('pay_runs.company_id', $companyId)
            ->whereIn('pay_runs.status', ['posted', 'paid'])
            ->where('pay_runs.period_end', '>=', $startDate)
            ->where('pay_items.name', 'like', '%Overtime%')
            ->select(
                DB::raw("TO_CHAR(pay_runs.period_end, 'YYYY-MM') as month"),
                DB::raw("SUM(payslip_items.quantity) as total_hours"),
                DB::raw("SUM(payslip_items.amount) as total_amount")
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // 2. Overtime by Department
        $deptBreakdown = PayslipItem::join('payslips', 'payslip_items.payslip_id', '=', 'payslips.id')
            ->join('pay_runs', 'payslips.pay_run_id', '=', 'pay_runs.id')
            ->join('pay_items', 'payslip_items.pay_item_id', '=', 'pay_items.id')
            ->join('employees', 'payslips.employee_id', '=', 'employees.id')
            ->leftJoin('departments', 'employees.department_id', '=', 'departments.id')
            ->where('pay_runs.company_id', $companyId)
            ->whereIn('pay_runs.status', ['posted', 'paid'])
            ->where('pay_runs.period_end', '>=', $startDate)
            ->where('pay_items.name', 'like', '%Overtime%')
            ->select(
                'departments.department as dept_name',
                DB::raw("SUM(payslip_items.quantity) as total_hours"),
                DB::raw("SUM(payslip_items.amount) as total_amount"),
                DB::raw("COUNT(DISTINCT employees.id) as employee_count")
            )
            ->groupBy('departments.id', 'departments.department')
            ->orderByDesc('total_hours')
            ->get();

        // 3. Top OT Earners (Employees with most OT)
        $topEarners = PayslipItem::join('payslips', 'payslip_items.payslip_id', '=', 'payslips.id')
            ->join('pay_runs', 'payslips.pay_run_id', '=', 'pay_runs.id')
            ->join('pay_items', 'payslip_items.pay_item_id', '=', 'pay_items.id')
            ->join('employees', 'payslips.employee_id', '=', 'employees.id')
            ->where('pay_runs.company_id', $companyId)
            ->whereIn('pay_runs.status', ['posted', 'paid'])
            ->where('pay_runs.period_end', '>=', $startDate)
            ->where('pay_items.name', 'like', '%Overtime%')
            ->select(
                'employees.id',
                'employees.full_name',
                DB::raw("SUM(payslip_items.quantity) as total_hours"),
                DB::raw("SUM(payslip_items.amount) as total_amount"),
                DB::raw("AVG(payslip_items.quantity) as avg_monthly_hours")
            )
            ->groupBy('employees.id', 'employees.full_name')
            ->orderByDesc('total_hours')
            ->paginate($perPage, ['*'], 'top_earners_page')
            ->withQueryString();

        // 4. Efficiency Alerts
        $alerts = [];
        $excessiveThreshold = 40; // Default 40 hours/month

        foreach ($topEarners as $earner) {
            if ($earner->avg_monthly_hours > $excessiveThreshold) {
                $alerts[] = [
                    'type' => 'excessive_ot',
                    'title' => 'High Overtime Alert',
                    'employee' => $earner->full_name,
                    'message' => "{$earner->full_name} is averaging " . round($earner->avg_monthly_hours, 1) . " hours of OT per month. This may indicate understaffing or burnout risk.",
                    'severity' => 'critical'
                ];
            }
        }

        // Detect upward trend
        if ($monthlyTrend->count() >= 2) {
            $last = $monthlyTrend->last()->total_hours;
            $prev = $monthlyTrend->get($monthlyTrend->count() - 2)->total_hours;
            if ($prev > 0 && ($last / $prev) > 1.25) {
                $alerts[] = [
                    'type' => 'trend',
                    'title' => 'Upward OT Trend Detected',
                    'message' => "Company-wide overtime has increased by " . round((($last / $prev) - 1) * 100) . "% compared to last month.",
                    'severity' => 'warning'
                ];
            }
        }

        return [
            'monthly_trend' => $monthlyTrend,
            'dept_breakdown' => $deptBreakdown,
            'top_earners' => $topEarners,
            'alerts' => $alerts,
            'threshold' => $excessiveThreshold
        ];
    }
}
