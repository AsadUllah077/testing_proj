<?php

namespace App\Services\Payroll;

use App\Models\PayRun;
use App\Models\Payslip;
use App\Models\EmployeePayStructure;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PayrollAnomalyDetector
{
    protected array $config;

    public function __construct()
    {
        $this->config = config('accounting.payroll_anomaly', []);
    }

    /**
     * Run all anomaly checks on a pay run and return structured results.
     *
     * @param  PayRun  $payRun  Must have payslips.employee, payslips.items.payItem loaded.
     * @return array{summary: array, warnings: array}
     */
    public function analyze(PayRun $payRun): array
    {
        $warnings = [];
        $payslips = $payRun->payslips;

        if ($payslips->isEmpty()) {
            return ['summary' => ['critical' => 0, 'warning' => 0], 'warnings' => []];
        }

        // Batch-load historical data for all employees in this pay run.
        $employeeIds = $payslips->pluck('employee_id')->unique();
        $historicalData = $this->loadHistoricalData($employeeIds, $payRun);
        $payStructures = $this->loadPayStructures($employeeIds, $payRun->company_id);
        $previousYtdTotals = $this->loadPreviousYtdTotals($employeeIds, $payRun);

        foreach ($payslips as $payslip) {
            $empId = $payslip->employee_id;
            $empName = $payslip->employee->full_name ?? "Employee #{$empId}";
            $history = $historicalData[$empId] ?? collect();

            // 1. No pay items check
            $noItemWarnings = $this->checkNoPayItems($payslip, $empId, $empName);
            $warnings = array_merge($warnings, $noItemWarnings);

            // 2. Net pay deviation
            $netPayWarnings = $this->checkNetPayDeviation($payslip, $history, $empId, $empName);
            $warnings = array_merge($warnings, $netPayWarnings);

            // 3. Gross pay deviation
            $grossPayWarnings = $this->checkGrossPayDeviation($payslip, $history, $empId, $empName);
            $warnings = array_merge($warnings, $grossPayWarnings);

            // 4. Missing deductions
            $missingDedWarnings = $this->checkMissingDeductions($payslip, $payStructures[$empId] ?? collect(), $empId, $empName);
            $warnings = array_merge($warnings, $missingDedWarnings);

            // 5. Deduction cap exceeded
            $dedCapWarnings = $this->checkDeductionCap($payslip, $empId, $empName);
            $warnings = array_merge($warnings, $dedCapWarnings);

            // 6. Overtime spike
            $otWarnings = $this->checkOvertimeSpike($payslip, $history, $empId, $empName);
            $warnings = array_merge($warnings, $otWarnings);

            // 7. YTD continuity
            $ytdWarnings = $this->checkYtdContinuity($payslip, $empId, $empName, $previousYtdTotals[$empId] ?? null);
            $warnings = array_merge($warnings, $ytdWarnings);

            // 8. Zero/Negative net pay
            $zeroWarnings = $this->checkZeroNetPay($payslip, $empId, $empName);
            $warnings = array_merge($warnings, $zeroWarnings);
        }

        // Build summary
        $critical = collect($warnings)->where('severity', 'critical')->count();
        $warning  = collect($warnings)->where('severity', 'warning')->count();

        return [
            'summary'  => ['critical' => $critical, 'warning' => $warning],
            'warnings' => $warnings,
        ];
    }

    /* ───────────────────────────────────────────────
     * Historical data loading (batch for performance)
     * ─────────────────────────────────────────────── */

    protected function loadHistoricalData(Collection $employeeIds, PayRun $payRun): array
    {
        $lookback = $this->config['lookback_months'] ?? 3;
        $cutoff = Carbon::parse($payRun->period_start)->subMonths($lookback);

        $priorPayslips = Payslip::withoutGlobalScopes()
            ->whereIn('employee_id', $employeeIds->all())
            ->whereHas('payRun', function ($q) use ($cutoff, $payRun) {
                $q->withoutGlobalScopes()
                  ->where('period_end', '>=', $cutoff)
                  ->where('period_end', '<', $payRun->period_start)
                  ->whereNotIn('status', ['cancelled']);
            })
            ->with(['items.payItem', 'payRun:id,period_start,period_end'])
            ->get()
            ->groupBy('employee_id');

        $result = [];
        foreach ($priorPayslips as $empId => $slips) {
            $result[$empId] = $slips;
        }
        return $result;
    }

    protected function loadPayStructures(Collection $employeeIds, int $companyId): array
    {
        return EmployeePayStructure::where('company_id', $companyId)
            ->whereIn('employee_id', $employeeIds->all())
            ->with('payItem')
            ->get()
            ->groupBy('employee_id')
            ->toArray();
    }

    protected function loadPreviousYtdTotals(Collection $employeeIds, PayRun $payRun): array
    {
        if ($employeeIds->isEmpty()) {
            return [];
        }

        $periodEnd = Carbon::parse($payRun->period_end);
        $fiscalYear = app(\App\Services\TaxCalculationService::class)->fiscalYearForDate($payRun->company_id, $periodEnd);
        preg_match('/(20\d{2})-(20\d{2})/', $fiscalYear, $years);
        $fiscalStart = ($years[1] ?? $periodEnd->year) . '-07-01';
        $fiscalEnd = ($years[2] ?? ($periodEnd->year + 1)) . '-06-30';

        return DB::table('payslips')
            ->join('pay_runs', 'payslips.pay_run_id', '=', 'pay_runs.id')
            ->whereIn('payslips.employee_id', $employeeIds->all())
            ->where('pay_runs.company_id', $payRun->company_id)
            ->whereBetween('pay_runs.period_end', [$fiscalStart, $fiscalEnd])
            ->where('pay_runs.period_end', '<', $payRun->period_start)
            ->whereNotIn('payslips.status', ['cancelled'])
            ->select(
                'payslips.employee_id',
                DB::raw('SUM(payslips.gross_earnings) as ytd_gross'),
                DB::raw('SUM(payslips.total_deductions) as ytd_ded'),
                DB::raw('SUM(payslips.net_pay) as ytd_net')
            )
            ->groupBy('payslips.employee_id')
            ->get()
            ->keyBy('employee_id')
            ->map(fn($row) => [
                'ytd_gross' => (float) ($row->ytd_gross ?? 0),
                'ytd_ded' => (float) ($row->ytd_ded ?? 0),
                'ytd_net' => (float) ($row->ytd_net ?? 0),
            ])
            ->all();
    }

    /* ───────────────────────────────────────────────
     * Individual checks
     * ─────────────────────────────────────────────── */

    /**
     * Check 1: Payslip has no items at all.
     */
    protected function checkNoPayItems(Payslip $payslip, int $empId, string $empName): array
    {
        if ($payslip->items->isEmpty()) {
            return [$this->makeWarning($empId, $empName, 'critical', 'NO_PAY_ITEMS',
                'Payslip has no pay items; employee will receive nothing',
                0, null, null
            )];
        }
        return [];
    }

    /**
     * Check 2: Net pay deviates significantly from historical average.
     */
    protected function checkNetPayDeviation(Payslip $payslip, Collection $history, int $empId, string $empName): array
    {
        if (!$this->hasEnoughHistory($history)) return [];

        $threshold = $this->config['net_pay_deviation_pct'] ?? 15;
        $avgNet = $history->avg('net_pay');

        if ($avgNet <= 0) return [];

        $deviation = (($payslip->net_pay - $avgNet) / $avgNet) * 100;

        if (abs($deviation) > $threshold) {
            $direction = $deviation > 0 ? 'higher' : 'lower';
            return [$this->makeWarning($empId, $empName, 'critical', 'NET_PAY_DEVIATION',
                sprintf(
                    'Net pay %s is %s%% %s than %d-month avg %s',
                    number_format($payslip->net_pay, 2),
                    number_format(abs($deviation), 1),
                    $direction,
                    $this->config['lookback_months'] ?? 3,
                    number_format($avgNet, 2)
                ),
                $payslip->net_pay, $avgNet, round($deviation, 1)
            )];
        }
        return [];
    }

    /**
     * Check 3: Gross pay deviates significantly from historical average.
     */
    protected function checkGrossPayDeviation(Payslip $payslip, Collection $history, int $empId, string $empName): array
    {
        if (!$this->hasEnoughHistory($history)) return [];

        $threshold = $this->config['gross_pay_deviation_pct'] ?? 20;
        $avgGross = $history->avg('gross_earnings');

        if ($avgGross <= 0) return [];

        $deviation = (($payslip->gross_earnings - $avgGross) / $avgGross) * 100;

        if (abs($deviation) > $threshold) {
            $direction = $deviation > 0 ? 'higher' : 'lower';
            return [$this->makeWarning($empId, $empName, 'warning', 'GROSS_PAY_DEVIATION',
                sprintf(
                    'Gross pay %s is %s%% %s than %d-month avg %s',
                    number_format($payslip->gross_earnings, 2),
                    number_format(abs($deviation), 1),
                    $direction,
                    $this->config['lookback_months'] ?? 3,
                    number_format($avgGross, 2)
                ),
                $payslip->gross_earnings, $avgGross, round($deviation, 1)
            )];
        }
        return [];
    }

    /**
     * Check 4: Employee has deduction items in pay structure but payslip is missing them.
     */
    protected function checkMissingDeductions(Payslip $payslip, $structures, int $empId, string $empName): array
    {
        $warnings = [];
        $structures = collect($structures);

        // Get deduction pay_item_ids from structure
        $expectedDeductionIds = $structures
            ->filter(fn($s) => isset($s['pay_item']) && strtolower($s['pay_item']['type'] ?? '') === 'deduction')
            ->pluck('pay_item_id')
            ->unique();

        if ($expectedDeductionIds->isEmpty()) return [];

        // Get actual deduction pay_item_ids from payslip
        $actualDeductionIds = $payslip->items
            ->filter(fn($item) => $item->payItem && strtolower($item->payItem->type) === 'deduction')
            ->pluck('pay_item_id')
            ->unique();

        $missing = $expectedDeductionIds->diff($actualDeductionIds);

        foreach ($missing as $missingItemId) {
            $struct = $structures->firstWhere('pay_item_id', $missingItemId);
            $itemName = $struct['pay_item']['name'] ?? "PayItem #{$missingItemId}";

            $warnings[] = $this->makeWarning($empId, $empName, 'critical', 'MISSING_DEDUCTION',
                "Statutory deduction '{$itemName}' is in pay structure but missing from payslip",
                0, null, null
            );
        }

        return $warnings;
    }

    /**
     * Check 5: Total deductions exceed a percentage of gross.
     */
    protected function checkDeductionCap(Payslip $payslip, int $empId, string $empName): array
    {
        $cap = $this->config['deduction_cap_pct'] ?? 50;
        $gross = $payslip->gross_earnings;

        if ($gross <= 0) return [];

        $dedPct = ($payslip->total_deductions / $gross) * 100;

        if ($dedPct > $cap) {
            return [$this->makeWarning($empId, $empName, 'warning', 'DEDUCTION_CAP_EXCEEDED',
                sprintf(
                    'Deductions %s are %s%% of gross %s (threshold: %d%%)',
                    number_format($payslip->total_deductions, 2),
                    number_format($dedPct, 1),
                    number_format($gross, 2),
                    $cap
                ),
                $payslip->total_deductions, $gross, round($dedPct, 1)
            )];
        }
        return [];
    }

    /**
     * Check 6: Overtime amount spikes compared to historical average.
     */
    protected function checkOvertimeSpike(Payslip $payslip, Collection $history, int $empId, string $empName): array
    {
        // Find overtime items in current payslip
        $currentOT = $payslip->items
            ->filter(fn($item) => $item->payItem && stripos($item->payItem->name, 'overtime') !== false)
            ->sum('amount');

        if ($currentOT <= 0) return [];

        // Historical overtime averages
        $historicalOTAmounts = $history->map(function ($slip) {
            return $slip->items
                ->filter(fn($item) => $item->payItem && stripos($item->payItem->name, 'overtime') !== false)
                ->sum('amount');
        });

        $avgOT = $historicalOTAmounts->avg();
        if ($avgOT <= 0) {
            // No historical overtime: flag only if the current amount is meaningful.
            if ($currentOT >= $this->numericConfig('overtime_min_amount', 100) && $this->hasEnoughHistory($history)) {
                return [$this->makeWarning($empId, $empName, 'warning', 'OVERTIME_SPIKE',
                    sprintf('Overtime %s found but no overtime in prior %d months',
                        number_format($currentOT, 2),
                        $this->config['lookback_months'] ?? 3
                    ),
                    $currentOT, 0, null
                )];
            }
            return [];
        }

        $threshold = $this->config['overtime_spike_pct'] ?? 100;
        $spikePercent = (($currentOT - $avgOT) / $avgOT) * 100;

        if ($spikePercent > $threshold) {
            return [$this->makeWarning($empId, $empName, 'warning', 'OVERTIME_SPIKE',
                sprintf(
                    'Overtime %s is %s%% above %d-month avg %s',
                    number_format($currentOT, 2),
                    number_format($spikePercent, 1),
                    $this->config['lookback_months'] ?? 3,
                    number_format($avgOT, 2)
                ),
                $currentOT, $avgOT, round($spikePercent, 1)
            )];
        }
        return [];
    }

    /**
     * Check 7: YTD values don't add up correctly.
     */
    protected function checkYtdContinuity(Payslip $payslip, int $empId, string $empName, ?array $prevYtd = null): array
    {
        $prevYtd = $prevYtd ?? ['ytd_gross' => 0, 'ytd_ded' => 0, 'ytd_net' => 0];

        $expectedYtdGross = ($prevYtd['ytd_gross'] ?? 0) + $payslip->gross_earnings;
        $actualYtdGross = $payslip->ytd_gross ?? 0;

        // Allow ±1.00 for rounding
        $tolerance = $this->numericConfig('ytd_tolerance', 1.00);
        if (abs($expectedYtdGross - $actualYtdGross) > $tolerance && $actualYtdGross > 0) {
            return [$this->makeWarning($empId, $empName, 'warning', 'YTD_MISMATCH',
                sprintf(
                    'YTD gross %s doesn\'t match expected %s (prior YTD %s + current %s)',
                    number_format($actualYtdGross, 2),
                    number_format($expectedYtdGross, 2),
                    number_format($prevYtd['ytd_gross'] ?? 0, 2),
                    number_format($payslip->gross_earnings, 2)
                ),
                $actualYtdGross, $expectedYtdGross, null
            )];
        }
        return [];
    }

    /**
     * Check 8: Net pay is zero or negative.
     */
    protected function checkZeroNetPay(Payslip $payslip, int $empId, string $empName): array
    {
        if ($payslip->net_pay <= 0 && $payslip->items->isNotEmpty()) {
            return [$this->makeWarning($empId, $empName, 'warning', 'ZERO_NET_PAY',
                sprintf(
                    'Net pay is %s (Gross: %s, Deductions: %s)',
                    number_format($payslip->net_pay, 2),
                    number_format($payslip->gross_earnings, 2),
                    number_format($payslip->total_deductions, 2)
                ),
                $payslip->net_pay, null, null
            )];
        }
        return [];
    }

    /* ───────────────────────────────────────────────
     * Helper
     * ─────────────────────────────────────────────── */

    protected function hasEnoughHistory(Collection $history): bool
    {
        return $history->count() >= (int) ($this->config['minimum_history_count'] ?? 2);
    }

    protected function numericConfig(string $key, float $default): float
    {
        return is_numeric($this->config[$key] ?? null) ? (float) $this->config[$key] : $default;
    }

    protected function makeWarning(int $empId, string $empName, string $severity, string $code, string $message, $current, $expected, $deviationPct): array
    {
        return [
            'employee_id'   => $empId,
            'employee_name' => $empName,
            'severity'      => $severity,
            'code'          => $code,
            'message'       => $message,
            'current'       => $current,
            'expected'      => $expected,
            'deviation_pct' => $deviationPct,
        ];
    }
}
