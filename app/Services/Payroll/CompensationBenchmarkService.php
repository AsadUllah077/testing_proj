<?php

namespace App\Services\Payroll;

use App\Models\Employee;
use App\Models\EmployeePayStructure;
use App\Models\SalaryBenchmark;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CompensationBenchmarkService
{
    /**
     * Get detailed benchmarking analysis for an employee.
     */
    public function getBenchmarkingData(Employee $employee)
    {
        $totalComp = $this->calculateTotalMonthlyCompensation($employee);
        $designation = $employee->designation;
        $deptId = $employee->department_id;

        $internalStats = $this->getInternalBenchmarks($designation, $deptId, $employee->company_id);
        $marketData = SalaryBenchmark::where('company_id', $employee->company_id)
            ->forDesignation($designation)
            ->first();

        $percentile = $this->calculateInternalPercentile($employee, $totalComp);
        $flightRisk = $this->analyzeFlightRisk($employee, $totalComp, $marketData);

        return [
            'total_monthly_compensation' => $totalComp,
            'internal_percentile' => $percentile,
            'internal_stats' => $internalStats,
            'market_benchmark' => $marketData,
            'comp_ratio' => $marketData ? $marketData->calculateCompRatio($totalComp) : null,
            'flight_risk' => $flightRisk,
            'experience_years' => Carbon::parse($employee->date_of_joining)->diffInYears(now()),
        ];
    }

    /**
     * Calculate total monthly compensation from all active pay items.
     */
    public function calculateTotalMonthlyCompensation(Employee $employee)
    {
        return EmployeePayStructure::where('employee_id', $employee->id)
            ->sum('amount');
    }

    /**
     * Get internal benchmarking stats for a designation/department.
     */
    private function getInternalBenchmarks($designation, $deptId, $companyId)
    {
        $query = Employee::where('company_id', $companyId)
            ->where('status', 'active');

        if ($designation) {
            $query->where('designation', $designation);
        }

        $employeeIds = $query->pluck('id');

        $compData = DB::table('employee_pay_structures')
            ->whereIn('employee_id', $employeeIds)
            ->select('employee_id', DB::raw('SUM(amount) as total_comp'))
            ->groupBy('employee_id')
            ->get();

        if ($compData->isEmpty()) {
            return ['avg' => 0, 'median' => 0, 'min' => 0, 'max' => 0, 'count' => 0];
        }

        $values = $compData->pluck('total_comp')->sort()->values();
        $count = $values->count();

        return [
            'avg' => $values->avg(),
            'median' => $count % 2 == 0 
                ? ($values[$count/2 - 1] + $values[$count/2]) / 2 
                : $values[floor($count/2)],
            'min' => $values->first(),
            'max' => $values->last(),
            'count' => $count
        ];
    }

    /**
     * Calculate where this employee stands in terms of internal percentile.
     */
    private function calculateInternalPercentile(Employee $employee, $totalComp)
    {
        $companyId = $employee->company_id;
        $designation = $employee->designation;

        $peerIds = Employee::where('company_id', $companyId)
            ->where('designation', $designation)
            ->where('status', 'active')
            ->pluck('id');

        $dataset = DB::table('employee_pay_structures')
            ->whereIn('employee_id', $peerIds)
            ->select('employee_id', DB::raw('SUM(amount) as total_comp'))
            ->groupBy('employee_id')
            ->pluck('total_comp')
            ->sort()
            ->values();

        if ($dataset->count() <= 1) return 50; // Neutral if no peers

        $rank = 0;
        foreach ($dataset as $val) {
            if ($val < $totalComp) $rank++;
        }

        return ($rank / ($dataset->count() - 1)) * 100;
    }

    /**
     * Analyze flight risk based on compensation gaps and experience.
     */
    private function analyzeFlightRisk(Employee $employee, $totalComp, $marketData)
    {
        $riskScore = 0;
        $reasons = [];

        // 1. Market Gap Risk
        if ($marketData && $marketData->median_salary > 0) {
            $compRatio = ($totalComp / $marketData->median_salary) * 100;
            if ($compRatio < 80) {
                $riskScore += 40;
                $reasons[] = "Significantly under market rate (Comp-Ratio: " . round($compRatio, 1) . "%)";
            } elseif ($compRatio < 95) {
                $riskScore += 15;
                $reasons[] = "Below market median";
            }
        }

        // 2. Tenure Risk (Employees with 2+ years and low comp ratio are high risk)
        $years = Carbon::parse($employee->date_of_joining)->diffInYears(now());
        if ($years >= 2 && (!isset($compRatio) || $compRatio < 90)) {
            $riskScore += 20;
            $reasons[] = "Long tenure with stagnant compensation";
        }

        // 3. Internal Equity Risk
        $percentile = $this->calculateInternalPercentile($employee, $totalComp);
        if ($percentile < 25) {
            $riskScore += 20;
            $reasons[] = "Bottom 25% of internal peers";
        }

        $level = 'Low';
        if ($riskScore >= 60) $level = 'Critical';
        elseif ($riskScore >= 40) $level = 'High';
        elseif ($riskScore >= 20) $level = 'Medium';

        return [
            'score' => $riskScore,
            'level' => $level,
            'reasons' => $reasons
        ];
    }
}
