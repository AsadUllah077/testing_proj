<?php

namespace App\Services\Payroll;

use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\EmployeePayStructure;
use App\Models\EmployeeSalaryAdvance;
use App\Models\PayRun;
use App\Models\PayrollPreflightOverride;
use Illuminate\Support\Collection;

class PayrollPreflightService
{
    public function __construct(private PayrollAnomalyDetector $anomalyDetector)
    {
    }

    public function run(PayRun $payRun, bool $persist = false): array
    {
        $payRun->loadMissing(['payslips.employee', 'payslips.items.payItem.account', 'createdBy']);
        $checks = collect();

        $this->employeeChecks($payRun, $checks);
        $journal = $this->calculationAndAccountingChecks($payRun, $checks);
        $this->approvalChecks($payRun, $checks);

        $anomalies = $this->anomalyDetector->analyze($payRun);
        foreach ($anomalies['warnings'] as $warning) {
            $checks->push($this->check(
                'ANOMALY_' . ($warning['code'] ?? 'UNKNOWN') . '_' . ($warning['employee_id'] ?? 'RUN'),
                'Anomaly detection',
                $warning['message'] ?? 'Payroll anomaly detected.',
                ($warning['severity'] ?? 'warning') === 'critical' ? 'blocker' : 'warning',
                $warning['employee_id'] ?? null
            ));
        }

        $overrides = PayrollPreflightOverride::withoutGlobalScopes()
            ->where('company_id', $payRun->company_id)
            ->where('pay_run_id', $payRun->id)
            ->get()
            ->keyBy('check_code');

        $checks = $checks->map(function (array $check) use ($overrides) {
            $override = $overrides->get($check['code']);
            $check['overridden'] = $check['severity'] === 'warning' && (bool) $override;
            $check['override_reason'] = $override?->reason;
            return $check;
        });

        $blockers = $checks->where('severity', 'blocker')->count();
        $unreviewedWarnings = $checks->where('severity', 'warning')->where('overridden', false)->count();
        $status = $blockers > 0 ? 'failed' : ($unreviewedWarnings > 0 ? 'review' : 'passed');

        if ($persist) {
            $payRun->update([
                'preflight_status' => $status,
                'preflight_checked_at' => now(),
                'preflight_checked_by' => auth('web')->id(),
            ]);
        }

        return [
            'status' => $status,
            'checks' => $checks->values()->all(),
            'summary' => [
                'blockers' => $blockers,
                'warnings' => $checks->where('severity', 'warning')->count(),
                'unreviewed_warnings' => $unreviewedWarnings,
                'passed' => $checks->where('severity', 'pass')->count(),
            ],
            'can_approve' => $blockers === 0 && $unreviewedWarnings === 0,
            'can_post' => $blockers === 0 && $unreviewedWarnings === 0 && $payRun->approval_status === 'approved',
            'journal' => $journal,
        ];
    }

    private function employeeChecks(PayRun $payRun, Collection $checks): void
    {
        if ($payRun->payslips->isEmpty()) {
            $checks->push($this->check('NO_PAYSLIPS', 'Employee readiness', 'The pay run contains no employees.', 'blocker'));
            return;
        }

        $employeeIds = $payRun->payslips->pluck('employee_id');
        $structures = EmployeePayStructure::withoutGlobalScopes()
            ->where('company_id', $payRun->company_id)
            ->whereIn('employee_id', $employeeIds)
            ->whereHas('payItem', fn ($query) => $query->where('company_id', $payRun->company_id))
            ->pluck('employee_id')->unique();

        $missingBankDetails = [];
        foreach ($payRun->payslips as $payslip) {
            $employee = $payslip->employee;
            $name = $employee?->full_name ?? "Employee #{$payslip->employee_id}";
            if (!$employee || (int) $employee->company_id !== (int) $payRun->company_id) {
                $checks->push($this->check("EMPLOYEE_COMPANY_{$payslip->employee_id}", 'Employee readiness', "{$name} does not belong to this company.", 'blocker', $payslip->employee_id));
                continue;
            }
            if ($employee->date_of_joining && $employee->date_of_joining > $payRun->period_end) {
                $checks->push($this->check("JOIN_DATE_{$employee->id}", 'Employee readiness', "{$name} joined after this pay period.", 'blocker', $employee->id));
            }
            if ($employee->resign_date && $employee->resign_date < $payRun->period_start) {
                $checks->push($this->check("LEAVE_DATE_{$employee->id}", 'Employee readiness', "{$name} left before this pay period.", 'blocker', $employee->id));
            }
            if (!$structures->contains($employee->id) && $payRun->run_type === 'regular') {
                $checks->push($this->check("PAY_STRUCTURE_{$employee->id}", 'Employee readiness', "{$name} has no company-owned pay structure.", 'blocker', $employee->id));
            }
            if (!$employee->bank_name || !$employee->bank_account_number) {
                $missingBankDetails[] = $name;
            }
        }
        if ($missingBankDetails) {
            $sample = implode(', ', array_slice($missingBankDetails, 0, 5));
            $more = count($missingBankDetails) > 5 ? ' and ' . (count($missingBankDetails) - 5) . ' more' : '';
            $checks->push($this->check('BANK_DETAILS_INCOMPLETE', 'Employee readiness', count($missingBankDetails) . " employees have incomplete bank details: {$sample}{$more}.", 'warning'));
        }

        $overlap = PayRun::withoutGlobalScopes()
            ->where('company_id', $payRun->company_id)
            ->where('id', '!=', $payRun->id)
            ->whereNotIn('status', ['cancelled'])
            ->whereDate('period_start', '<=', $payRun->period_end)
            ->whereDate('period_end', '>=', $payRun->period_start)
            ->whereHas('payslips', fn ($query) => $query->whereIn('employee_id', $employeeIds))
            ->exists();
        if ($overlap && $payRun->run_type === 'regular') {
            $checks->push($this->check('OVERLAPPING_PAY_RUN', 'Employee readiness', 'Another active regular pay run overlaps one or more employees.', 'blocker'));
        }
    }

    private function calculationAndAccountingChecks(PayRun $payRun, Collection $checks): array
    {
        $debit = 0.0;
        $deductionCredit = 0.0;
        $netCredit = 0.0;
        foreach ($payRun->payslips as $payslip) {
            $earnings = (float) $payslip->items->filter(fn ($item) => in_array(strtolower((string) $item->payItem?->type), ['earning', 'reimbursement'], true))->sum('amount');
            $deductions = (float) $payslip->items->filter(fn ($item) => strtolower((string) $item->payItem?->type) === 'deduction')->sum('amount');
            $expectedNet = $earnings - $deductions;
            $employeeName = $payslip->employee?->full_name ?? "Employee #{$payslip->employee_id}";

            if (abs($earnings - (float) $payslip->gross_earnings) > 0.01 || abs($deductions - (float) $payslip->total_deductions) > 0.01 || abs($expectedNet - (float) $payslip->net_pay) > 0.01) {
                $checks->push($this->check("PAYSLIP_MATH_{$payslip->id}", 'Calculation readiness', "{$employeeName}'s payslip totals do not match its line items.", 'blocker', $payslip->employee_id));
            }
            if ((float) $payslip->net_pay < 0) {
                $checks->push($this->check("NEGATIVE_NET_{$payslip->id}", 'Calculation readiness', "{$employeeName} has negative net pay.", 'blocker', $payslip->employee_id));
            }

            foreach ($payslip->items as $item) {
                $payItem = $item->payItem;
                if (!$payItem || !$payItem->account_id || (int) $payItem->company_id !== (int) $payRun->company_id || (int) ($payItem->account?->company_id) !== (int) $payRun->company_id) {
                    $checks->push($this->check("ACCOUNT_MAPPING_{$item->id}", 'Accounting readiness', ($payItem?->name ?? "Payslip item #{$item->id}") . ' has no valid company-owned ledger account.', 'blocker', $payslip->employee_id));
                    continue;
                }
                if (strtolower((string) $payItem->name) === 'salary advance') {
                    if (!preg_match('/Salary Advance #(\d+)/i', (string) $item->description, $matches)
                        || !EmployeeSalaryAdvance::where('company_id', $payRun->company_id)
                            ->where('employee_id', $payslip->employee_id)->where('id', (int) ($matches[1] ?? 0))->exists()) {
                        $checks->push($this->check("ADVANCE_LINK_{$item->id}", 'Calculation readiness', "{$employeeName}'s salary advance deduction is not linked to a valid advance.", 'blocker', $payslip->employee_id));
                    }
                }
                if (in_array(strtolower((string) $payItem->type), ['earning', 'reimbursement'], true)) {
                    $debit += (float) $item->amount;
                } elseif (strtolower((string) $payItem->type) === 'deduction') {
                    $deductionCredit += (float) $item->amount;
                }
            }
            $netCredit += (float) $payslip->net_pay;
        }

        if (!ChartOfAccount::getWagesPayableAccount($payRun->company_id)) {
            $checks->push($this->check('WAGES_PAYABLE', 'Accounting readiness', 'The company has no Wages Payable system account.', 'blocker'));
        }
        if (!ChartOfAccount::getEmployeeAdvancesAccount($payRun->company_id)) {
            $checks->push($this->check('EMPLOYEE_ADVANCES', 'Accounting readiness', 'The company has no Employee Advances system account.', 'blocker'));
        }

        $period = AccountingPeriod::withoutGlobalScopes()
            ->where('company_id', $payRun->company_id)
            ->whereDate('start_date', '<=', $payRun->payment_date)
            ->whereDate('end_date', '>=', $payRun->payment_date)
            ->where('is_active', true)->where('is_locked', false)->first();
        if (!$period) {
            $checks->push($this->check('ACCOUNTING_PERIOD', 'Accounting readiness', 'No open accounting period covers the payroll posting/payment date.', 'blocker'));
        }

        $credits = $deductionCredit + $netCredit;
        if (abs($debit - $credits) > 0.01) {
            $checks->push($this->check('JOURNAL_BALANCE', 'Accounting readiness', 'The proposed payroll journal is out of balance.', 'blocker'));
        } else {
            $checks->push($this->check('JOURNAL_BALANCE', 'Accounting readiness', 'The proposed payroll journal balances.', 'pass'));
        }

        return ['debit' => round($debit, 2), 'credit' => round($credits, 2), 'deductions' => round($deductionCredit, 2), 'net_pay' => round($netCredit, 2)];
    }

    private function approvalChecks(PayRun $payRun, Collection $checks): void
    {
        if ($payRun->approval_status !== 'approved' && $payRun->created_by && $payRun->created_by === auth('web')->id()) {
            $checks->push($this->check('MAKER_CHECKER', 'Approval readiness', 'The pay-run creator cannot approve this pay run.', 'blocker'));
        }
    }

    private function check(string $code, string $group, string $message, string $severity, ?int $employeeId = null): array
    {
        return compact('code', 'group', 'message', 'severity', 'employeeId') + ['employee_id' => $employeeId];
    }
}
