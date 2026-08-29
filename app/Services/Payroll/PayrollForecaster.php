<?php

namespace App\Services\Payroll;

use App\Models\Employee;
use App\Models\EmployeePayStructure;
use App\Models\PayRun;
use App\Models\PayslipItem;
use App\Models\Budget;
use App\Models\PayItem;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PayrollForecaster
{
    /**
     * Generate payroll cost projections for the next N months.
     */
    public function getForecast(int $companyId, int $months = 12, float $annualGrowthRate = 0.05): array
    {
        // 1. Calculate Baseline (Current Fixed Pay)
        $monthlyBaseline = EmployeePayStructure::join('employees', 'employee_pay_structures.employee_id', '=', 'employees.id')
            ->join('pay_items', 'employee_pay_structures.pay_item_id', '=', 'pay_items.id')
            ->where('employees.company_id', $companyId)
            ->where('employees.status', 'active')
            ->whereIn('pay_items.type', ['earning', 'reimbursement'])
            ->sum('employee_pay_structures.amount');

        // 2. Calculate Variable (Average Historical Overtime - last 6 months)
        $sixMonthsAgo = now()->subMonths(6)->startOfMonth();
        $avgMonthlyOT = PayslipItem::join('payslips', 'payslip_items.payslip_id', '=', 'payslips.id')
            ->join('pay_runs', 'payslips.pay_run_id', '=', 'pay_runs.id')
            ->join('pay_items', 'payslip_items.pay_item_id', '=', 'pay_items.id')
            ->where('pay_runs.company_id', $companyId)
            ->whereIn('pay_runs.status', ['posted', 'paid'])
            ->where('pay_runs.period_end', '>=', $sixMonthsAgo)
            ->where('pay_items.name', 'like', '%Overtime%')
            ->sum('payslip_items.amount') / 6;

        $currentTotalMonthly = $monthlyBaseline + $avgMonthlyOT;

        // 3. Get Budgets for Payroll Accounts
        $payrollAccountIds = PayItem::where('company_id', $companyId)
            ->whereIn('type', ['earning', 'reimbursement'])
            ->pluck('account_id')
            ->unique()
            ->toArray();

        $budgets = Budget::where('company_id', $companyId)
            ->whereIn('account_id', $payrollAccountIds)
            ->select('account_id', DB::raw('SUM(amount) as total_budget'))
            ->groupBy('account_id')
            ->get()
            ->pluck('total_budget', 'account_id');

        $totalAnnualBudget = $budgets->sum();
        $monthlyBudget = $totalAnnualBudget > 0 ? $totalAnnualBudget / 12 : null;

        // 4. Generate Projections
        $projections = [];
        $monthlyGrowthFactor = 1 + ($annualGrowthRate / 12);

        for ($i = 1; $i <= $months; $i++) {
            $projectionDate = now()->addMonths($i);
            $projectedAmount = $currentTotalMonthly * pow($monthlyGrowthFactor, $i);
            
            $projections[] = [
                'month' => $projectionDate->format('M Y'),
                'amount' => round($projectedAmount, 2),
                'is_over_budget' => $monthlyBudget ? ($projectedAmount > $monthlyBudget) : false,
                'variance' => $monthlyBudget ? ($projectedAmount - $monthlyBudget) : 0
            ];
        }

        // 5. Budget Alerts
        $alerts = [];
        if ($monthlyBudget && $currentTotalMonthly > $monthlyBudget) {
            $alerts[] = [
                'title' => 'Budget Variance Detected',
                'message' => 'Current projected monthly payroll exceeds the allocated budget by ' . number_format($currentTotalMonthly - $monthlyBudget, 2),
                'severity' => 'critical'
            ];
        } elseif ($monthlyBudget && end($projections)['amount'] > $monthlyBudget) {
            $alerts[] = [
                'title' => 'Future Budget Risk',
                'message' => 'Based on ' . ($annualGrowthRate * 100) . '% growth, payroll will exceed budget in ' . Carbon::parse(end($projections)['month'])->format('F Y'),
                'severity' => 'warning'
            ];
        }

        return [
            'baseline' => round($monthlyBaseline, 2),
            'ot_avg' => round($avgMonthlyOT, 2),
            'monthly_total' => round($currentTotalMonthly, 2),
            'annual_projection' => round($currentTotalMonthly * 12 * (1 + ($annualGrowthRate / 2)), 2), // rough annual est
            'projections' => $projections,
            'alerts' => $alerts,
            'monthly_budget' => $monthlyBudget ? round($monthlyBudget, 2) : null,
            'growth_rate' => $annualGrowthRate
        ];
    }
}
