<?php

namespace App\Services;

use App\Models\PayRun;
use App\Models\Payslip;
use App\Models\PayslipItem;
use App\Models\Employee;
use App\Models\EmployeePayStructure;
use App\Models\EmployeeSalaryAdvance;
use App\Models\JournalEntry;
use App\Models\ChartOfAccount;
use App\Models\AccountingPeriod;
use App\Models\Transaction;
use App\Models\PayItem;
use App\Models\ComplianceEvent;
use App\Models\PayrollRemittanceBatch;
use App\Models\PayrollRemittanceAllocation;
use Illuminate\Support\Facades\DB;
use Exception;

class PayrollService
{
    public function createSupplementaryPayRun(PayRun $original, array $employeeIds = []): PayRun
    {
        if (!in_array($original->status, ['posted', 'paid'], true)) {
            throw new Exception('A supplementary payroll can only be linked to a posted or paid pay run.');
        }

        $original->loadMissing('payslips');
        $allowedIds = $original->payslips->pluck('employee_id')->unique();
        $selectedIds = empty($employeeIds) ? $allowedIds : $allowedIds->intersect($employeeIds);
        if ($selectedIds->isEmpty()) {
            throw new Exception('Select at least one employee from the original pay run.');
        }

        return DB::transaction(function () use ($original, $selectedIds) {
            $payRun = PayRun::create([
                'company_id' => $original->company_id,
                'period_start' => $original->period_start,
                'period_end' => $original->period_end,
                'payment_date' => now()->toDateString(),
                'status' => 'draft',
                'approval_status' => 'pending',
                'created_by' => auth('web')->id() ?: 1,
                'frequency' => $original->frequency,
                'run_type' => 'supplementary',
                'original_pay_run_id' => $original->id,
            ]);

            foreach ($selectedIds as $employeeId) {
                Payslip::create([
                    'company_id' => $original->company_id,
                    'pay_run_id' => $payRun->id,
                    'employee_id' => $employeeId,
                    'status' => 'draft',
                    'gross_earnings' => 0,
                    'total_deductions' => 0,
                    'net_pay' => 0,
                    'ytd_gross' => 0,
                    'ytd_deductions' => 0,
                    'ytd_net' => 0,
                ]);
            }

            return $payRun;
        });
    }

    public function reversePostedPayRun(PayRun $payRun, $reversalDate, ?string $reason = null): PayRun
    {
        if (!in_array($payRun->status, ['posted', 'paid'], true) || $payRun->reversed_at) {
            throw new Exception('Only an unreversed posted or paid pay run can be reversed.');
        }
        if (PayslipItem::whereHas('payslip', fn ($query) => $query->where('pay_run_id', $payRun->id))->whereNotNull('paid_at')->exists()) {
            throw new Exception('Reverse or void paid statutory liabilities before reversing this payroll.');
        }

        return DB::transaction(function () use ($payRun, $reversalDate, $reason) {
            $period = AccountingPeriod::findPeriodForDate($reversalDate, $payRun->company_id);
            if (!$period || !$period->isOpen()) {
                throw new Exception('The reversal date must fall in an open accounting period.');
            }

            $reversalNo = 'PR-REV-' . str_pad($payRun->id, 6, '0', STR_PAD_LEFT) . '-' . now()->format('YmdHis');
            $authId = auth('web')->id() ?: 1;
            $now = now();
            $originalTransactions = Transaction::withoutGlobalScopes()
                ->where('company_id', $payRun->company_id)
                ->where('reference_id', $payRun->id)
                ->where('type', 'Payroll')
                ->whereNull('deleted_at')
                ->get();

            if ($originalTransactions->isEmpty()) {
                throw new Exception('No payroll ledger transactions were found to reverse.');
            }

            foreach ($originalTransactions as $transaction) {
                Transaction::create([
                    'company_id' => $payRun->company_id,
                    'accounting_period_id' => $period->id,
                    'account_id' => $transaction->account_id,
                    'payable_id' => $transaction->payable_id,
                    'payable_type' => $transaction->payable_type,
                    'date' => $reversalDate,
                    'unique_no' => $reversalNo,
                    'reference_id' => $payRun->id,
                    'description' => 'Payroll reversal: ' . ($reason ?: $transaction->description),
                    'type' => 'Payroll',
                    'debit' => (float) $transaction->credit,
                    'credit' => (float) $transaction->debit,
                    'reversal_of_transaction_id' => $transaction->id,
                    'reversal_of_unique_no' => $transaction->unique_no,
                    'status' => 1,
                    'created_by' => $authId,
                ]);
            }

            $journalTotals = $originalTransactions->groupBy('account_id')->map(fn ($rows) => [
                'debit' => (float) $rows->sum('credit'),
                'credit' => (float) $rows->sum('debit'),
            ]);
            foreach ($journalTotals as $accountId => $amounts) {
                JournalEntry::create([
                    'company_id' => $payRun->company_id,
                    'accounting_period_id' => $period->id,
                    'account_id' => $accountId,
                    'date' => $reversalDate,
                    'unique_no' => $reversalNo,
                    'narration' => 'Reversal of ' . $payRun->unique_no,
                    'description' => $reason ?: 'Controlled payroll reversal',
                    'debit' => $amounts['debit'],
                    'credit' => $amounts['credit'],
                    'created_by' => $authId,
                ]);
            }

            $this->reverseSalaryAdvanceRecoveries($payRun);
            $payRun->payslips()->update(['status' => 'reversed']);
            $payRun->update([
                'status' => 'cancelled',
                'reversed_at' => $now,
                'reversed_by' => $authId,
                'reversal_unique_no' => $reversalNo,
            ]);

            return $payRun;
        });
    }

    /**
     * Generate a new Pay Run and its payslips.
     */
    public function generatePayRun($companyId, $periodStart, $periodEnd, $paymentDate, $employeeIds = [], $frequency = 'monthly', $overtimeHours = [])
    {
        return DB::transaction(function () use ($companyId, $periodStart, $periodEnd, $paymentDate, $employeeIds, $frequency, $overtimeHours) {
            $payRun = PayRun::create([
                'company_id' => $companyId,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'payment_date' => $paymentDate,
                'status' => 'draft',
                'approval_status' => 'pending',
                'created_by' => auth('web')->id() ?: 1,
                'frequency' => $frequency,
            ]);
            $query = Employee::where('company_id', $companyId);
            if (!empty($employeeIds)) {
                $query->whereIn('id', $employeeIds);
            }
            $employees = $query->get();

            $employeeIdsForRun = $employees->pluck('id');

            // Optimization: Batch load pay structures and reusable payroll lookups.
            $allStructures = EmployeePayStructure::whereIn('employee_id', $employeeIdsForRun)
                ->with('payItem')
                ->orderBy('employee_id')
                ->orderBy('sort_order', 'asc')
                ->get()
                ->groupBy('employee_id');

            $taxItemId = PayItem::where('company_id', $companyId)
                ->where('type', 'deduction')
                ->whereRaw('LOWER(name) LIKE ?', ['%income tax%'])
                ->value('id');

            $overtimeItem = PayItem::where('company_id', $companyId)
                ->where('name', 'Overtime')
                ->where('type', 'earning')
                ->first();

            $periodEndDate = \Carbon\Carbon::parse($periodEnd);
            $ytdTotals = $this->loadYtdTotals($employeeIdsForRun->all(), $periodEndDate, $companyId);
            $taxCalculationService = app(TaxCalculationService::class);

            foreach ($employees as $employee) {
                $structures = $allStructures->get($employee->id) ?? collect();
                $empOvertimeHours = $overtimeHours[$employee->id] ?? 0;
                $this->createPayslipForEmployee(
                    $payRun,
                    $employee,
                    $structures,
                    $empOvertimeHours,
                    $taxItemId,
                    $overtimeItem,
                    $ytdTotals[$employee->id] ?? null,
                    $taxCalculationService
                );
            }
            return $payRun;
        });
    }

    protected function loadYtdTotals(array $employeeIds, \Carbon\Carbon $periodEnd, int $companyId): array
    {
        if (empty($employeeIds)) {
            return [];
        }

        $fiscalYear = app(TaxCalculationService::class)->fiscalYearForDate($companyId, $periodEnd);
        preg_match('/(20\d{2})-(20\d{2})/', $fiscalYear, $years);
        $start = ($years[1] ?? $periodEnd->year) . '-07-01';
        $end = ($years[2] ?? ($periodEnd->year + 1)) . '-06-30';

        return DB::table('payslips')
            ->join('pay_runs', 'payslips.pay_run_id', '=', 'pay_runs.id')
            ->whereIn('payslips.employee_id', $employeeIds)
            ->where('pay_runs.company_id', $companyId)
            ->whereBetween('pay_runs.period_end', [$start, $end])
            ->where('pay_runs.period_end', '<', $periodEnd)
            ->whereNotIn('pay_runs.status', ['cancelled'])
            ->whereNull('payslips.deleted_at')
            ->whereNull('pay_runs.deleted_at')
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

    protected function createPayslipForEmployee(
        PayRun $payRun,
        Employee $employee,
        $structures,
        float $overtimeHours = 0,
        ?int $taxItemId = null,
        ?PayItem $overtimeItem = null,
        ?array $ytdPrevious = null,
        ?TaxCalculationService $taxCalculationService = null
    )
    {
        $grossEarnings = 0;
        $taxableEarnings = 0;
        $totalDeductions = 0;
        $processedAmounts = [];
        $itemRows = [];
        $now = now();

        $payslip = Payslip::create([
            'company_id' => $payRun->company_id,
            'pay_run_id' => $payRun->id,
            'employee_id' => $employee->id,
            'status' => 'draft',
        ]);

        // Track hourly rate for overtime calculation
        $hourlyRate = null;

        $taxPayslipItemIndex = null;

        foreach ($structures as $struct) {
            $payItem = $struct->payItem;
            if (!$payItem) continue;

            // Capture hourly rate if set
            if ($struct->hourly_rate > 0) {
                $hourlyRate = $struct->hourly_rate;
            }

            $amount = $struct->amount;

            if ($payItem->calculation_type === 'percentage' && $payItem->base_item_id) {
                $baseAmount = $processedAmounts[$payItem->base_item_id] ?? 0;
                $amount = ($baseAmount * $payItem->percentage_value) / 100;
            }

            $processedAmounts[$payItem->id] = $amount;

            $itemRows[] = [
                'payslip_id' => $payslip->id,
                'pay_item_id' => $payItem->id,
                'quantity' => null,
                'amount' => $amount,
                'description' => $payItem->name,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if ($payItem->id === $taxItemId) {
                $taxPayslipItemIndex = count($itemRows) - 1;
            }

            if ($payItem->type === 'earning' || $payItem->type === 'reimbursement') {
                $grossEarnings += $amount;
                if ($payItem->is_taxable) {
                    $taxableEarnings += $amount;
                }
            } elseif ($payItem->type === 'deduction') {
                $totalDeductions += $amount;
            }
        }

        // Add overtime if hours and hourly rate are provided
        if ($overtimeHours > 0 && $hourlyRate > 0) {
            $overtimeAmount = round($hourlyRate * $overtimeHours * 1.5, 2);

            if ($overtimeItem) {
                $itemRows[] = [
                    'payslip_id' => $payslip->id,
                    'pay_item_id' => $overtimeItem->id,
                    'quantity' => $overtimeHours,
                    'amount' => $overtimeAmount,
                    'description' => "Overtime ({$overtimeHours} hrs @ " . number_format($hourlyRate * 1.5, 2) . "/hr)",
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $grossEarnings += $overtimeAmount;
                if ($overtimeItem->is_taxable) {
                    $taxableEarnings += $overtimeAmount;
                }
            }
        }

        // Add salary advances as deduction
        $employeeAdvancesAccount = ChartOfAccount::getEmployeeAdvancesAccount($payRun->company_id);

        if (!$employeeAdvancesAccount) {
            throw new Exception("Accounting failure: 'Employee Advances' account not found. Please run migrations or create one in Chart of Accounts.");
        }

        $salaryAdvanceItem = PayItem::firstOrCreate(
            ['company_id' => $payRun->company_id, 'name' => 'Salary Advance', 'type' => 'deduction'],
            ['account_id' => $employeeAdvancesAccount->id, 'is_system' => true, 'status' => true]
        );

        if (!$salaryAdvanceItem->account_id) {
            $salaryAdvanceItem->update(['account_id' => $employeeAdvancesAccount->id]);
        }
        
        // Get issued, unrecovered salary advances for the employee.
        $advances = EmployeeSalaryAdvance::where('company_id', $payRun->company_id)
            ->where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereNotNull('payment_reference')
            ->where('remaining_amount', '>', 0)
            ->get();
        
        foreach ($advances as $advance) {
            if ($advance->recovery_paused_at
                || ($advance->recovery_start_date && $advance->recovery_start_date->gt($payRun->period_end))) {
                continue;
            }
            if ($advance->recovery_frequency === 'monthly'
                && $advance->recoveries()->whereHas('payRun', fn ($query) => $query
                    ->whereYear('period_end', $payRun->period_end->year)
                    ->whereMonth('period_end', $payRun->period_end->month)
                    ->whereNotIn('status', ['cancelled']))->exists()) {
                continue;
            }

            $plannedAmount = match ($advance->recovery_method) {
                'percentage' => $grossEarnings * ((float) $advance->recovery_percentage / 100),
                default => (float) ($advance->installment_amount ?: $advance->remaining_amount),
            };
            $deductionLimit = $grossEarnings * ((float) ($advance->max_net_deduction_percentage ?: 50) / 100);
            $availableDeduction = max(0, $deductionLimit - $totalDeductions);
            $deductionAmount = round(min((float) $advance->remaining_amount, $plannedAmount, $availableDeduction), 2);
            if ($deductionAmount <= 0) {
                continue;
            }
            $itemRows[] = [
                'payslip_id' => $payslip->id,
                'pay_item_id' => $salaryAdvanceItem->id,
                'quantity' => null,
                'amount' => $deductionAmount,
                'description' => "Salary Advance #{$advance->id} ({$advance->advance_date->format('Y-m-d')})",
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $totalDeductions += $deductionAmount;
        }

        // Recalculate tax dynamically based on final taxable earnings.
        if ($taxPayslipItemIndex !== null) {
            $taxCalculationService = $taxCalculationService ?: app(TaxCalculationService::class);
            $newTax = $taxCalculationService->calculateMonthlyTaxForDate(
                $payRun->company_id,
                $taxableEarnings,
                $payRun->period_end
            );

            if ($newTax !== (float) $itemRows[$taxPayslipItemIndex]['amount']) {
                // Adjust deductions with the new tax amount
                $totalDeductions = $totalDeductions - (float) $itemRows[$taxPayslipItemIndex]['amount'] + $newTax;
                $itemRows[$taxPayslipItemIndex]['amount'] = $newTax;
            }
        }

        if (!empty($itemRows)) {
            PayslipItem::insert($itemRows);
        }

        // Update salary advances remaining amount when payslip is processed
        // if (isset($advances) && $advances->isNotEmpty()) {
        //     foreach ($advances as $advance) {
        //         $advance->update([
        //             'remaining_amount' => 0,
        //             'status' => 'paid'
        //         ]);
        //     }
        // }

        $netPay = $grossEarnings - $totalDeductions;
        $ytdPrevious = $ytdPrevious ?? ['ytd_gross' => 0, 'ytd_ded' => 0, 'ytd_net' => 0];

        $payslip->update([
            'gross_earnings' => $grossEarnings,
            'total_deductions' => $totalDeductions,
            'net_pay' => $netPay,
            'ytd_gross' => ($ytdPrevious['ytd_gross'] ?? 0) + $grossEarnings,
            'ytd_deductions' => ($ytdPrevious['ytd_ded'] ?? 0) + $totalDeductions,
            'ytd_net' => ($ytdPrevious['ytd_net'] ?? 0) + $netPay,
            'tax_fiscal_year' => app(TaxCalculationService::class)->fiscalYearForDate($payRun->company_id, $payRun->period_end),
            'tax_calculation_snapshot' => app(TaxCalculationService::class)->auditSnapshotForDate($payRun->company_id, $taxableEarnings, $payRun->period_end),
        ]);
    }
    /**
     * Post the Pay Run to Accounting.
     */
    public function postToAccounting($payRunId, ?int $companyId = null)
    {
        $companyId = $companyId ?? auth('web')->user()?->company_id;

        $payRun = PayRun::with('payslips.items.payItem')->withoutGlobalScopes()->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->findOrFail($payRunId);

        $employeeAdvancesAccount = ChartOfAccount::getEmployeeAdvancesAccount($payRun->company_id);
        if (!$employeeAdvancesAccount) {
            throw new Exception("Accounting failure: 'Employee Advances' account not found. Please run migrations or create one in Chart of Accounts.");
        }

        $salaryAdvanceItem = PayItem::where('company_id', $payRun->company_id)
            ->where('name', 'Salary Advance')
            ->where('type', 'deduction')
            ->first();

        if ($salaryAdvanceItem && !$salaryAdvanceItem->account_id) {
            $salaryAdvanceItem->update(['account_id' => $employeeAdvancesAccount->id]);
            $payRun->load('payslips.items.payItem');
        }

        $missingAccountItems = [];

        foreach ($payRun->payslips as $payslip) {
            foreach ($payslip->items as $item) {
                $payItem = $item->payItem;

                if (!$payItem) {
                    $missingAccountItems[] = "PayslipItem#{$item->id} has no payItem relation";
                    continue;
                }

                if (empty($payItem->account_id)) {
                    $missingAccountItems[] = "{$payItem->name} (PayItem ID: {$payItem->id})";
                }
            }
        }

        if (!empty($missingAccountItems)) {
            throw new Exception(
                "Payroll posting blocked: These Pay Items are missing account_id: " . implode(', ', array_unique($missingAccountItems))
            );
        }

        if ($payRun->status !== 'draft') {
            throw new Exception("Only draft pay runs can be posted.");
        }

        if ($payRun->approval_status !== 'approved') {
            throw new Exception("This pay run must be approved before posting. Current approval status: {$payRun->approval_status}.");
        }

        $preflight = app(\App\Services\Payroll\PayrollPreflightService::class)->run($payRun, true);
        if (!$preflight['can_post']) {
            throw new Exception('Payroll preflight failed. Correct blockers and review all warnings before posting.');
        }

        $anomalies = app(\App\Services\Payroll\PayrollAnomalyDetector::class)->analyze($payRun);
        if (($anomalies['summary']['critical'] ?? 0) > 0) {
            throw new Exception("Payroll posting blocked: resolve all critical payroll anomalies first.");
        }
        return DB::transaction(function () use ($payRun) {
            $uniqueNo = 'PR-' . str_pad($payRun->id, 6, '0', STR_PAD_LEFT);
            $employeeAdvancesAccount = ChartOfAccount::getEmployeeAdvancesAccount($payRun->company_id);

            if (!$employeeAdvancesAccount) {
                throw new Exception("Accounting failure: 'Employee Advances' account not found. Please run migrations or create one in Chart of Accounts.");
            }

            $salaryAdvanceItem = PayItem::where('company_id', $payRun->company_id)
                ->where('name', 'Salary Advance')
                ->where('type', 'deduction')
                ->first();

            if ($salaryAdvanceItem && !$salaryAdvanceItem->account_id) {
                $salaryAdvanceItem->update(['account_id' => $employeeAdvancesAccount->id]);
                $payRun->load('payslips.items.payItem');
            }

            // Look for Wages Payable / Accrued Salaries account
            $wagesPayableAccount = ChartOfAccount::getWagesPayableAccount($payRun->company_id);

            if (!$wagesPayableAccount) {
                throw new Exception("Accounting failure: 'Wages Payable' or 'Accrued Salaries' account not found. Please create one in Chart of Accounts.");
            }

            // ============================
            // ✅ CHANGED: Build employee-wise accounting entries
            // ============================
            $employeeEntries = []; // [employeeId][accountId] => ['debit' => x, 'credit' => y]
            $employeeNetPay = [];  // [employeeId] => net_pay

            foreach ($payRun->payslips as $payslip) {
                $employeeId = $payslip->employee_id;
                $employeeNetPay[$employeeId] = ($employeeNetPay[$employeeId] ?? 0) + $payslip->net_pay;

                foreach ($payslip->items as $item) {
                    $payItem = $item->payItem;
                    if (!$payItem || !$payItem->account_id) continue;

                    $accountId = $payItem->account_id;

                    if (!isset($employeeEntries[$employeeId][$accountId])) {
                        $employeeEntries[$employeeId][$accountId] = ['debit' => 0, 'credit' => 0];
                    }

                    $type = strtolower((string) $payItem->type);

                    if ($type === 'earning' || $type === 'reimbursement') {
                        $employeeEntries[$employeeId][$accountId]['debit'] += $item->amount;
                    } elseif ($type === 'deduction') {
                        $employeeEntries[$employeeId][$accountId]['credit'] += $item->amount;
                    } else {
                        throw new Exception("Unknown pay item type '{$payItem->type}' for PayItem: {$payItem->name} (ID: {$payItem->id})");
                    }
                }
            }

            // Add wages payable credit per employee (net pay)
            foreach ($employeeNetPay as $employeeId => $netPay) {
                if (!isset($employeeEntries[$employeeId][$wagesPayableAccount->id])) {
                    $employeeEntries[$employeeId][$wagesPayableAccount->id] = ['debit' => 0, 'credit' => 0];
                }
                $employeeEntries[$employeeId][$wagesPayableAccount->id]['credit'] += $netPay;
            }

            // ============================
            // Build batch totals for JournalEntry (still totals per account)
            // ============================
            $accountingEntries = []; // totals per account for JournalEntry

            foreach ($employeeEntries as $employeeId => $accounts) {
                foreach ($accounts as $accountId => $amounts) {
                    if (!isset($accountingEntries[$accountId])) {
                        $accountingEntries[$accountId] = ['debit' => 0, 'credit' => 0];
                    }
                    $accountingEntries[$accountId]['debit'] += $amounts['debit'];
                    $accountingEntries[$accountId]['credit'] += $amounts['credit'];
                }
            }

            // Final check: Verifying that Debit == Credit (batch totals)
            $batchTotalDebit = 0;
            $batchTotalCredit = 0;
            foreach ($accountingEntries as $entry) {
                $batchTotalDebit += $entry['debit'];
                $batchTotalCredit += $entry['credit'];
            }

            if (round($batchTotalDebit, 2) !== round($batchTotalCredit, 2)) {
                throw new Exception("Accounting failure: The payroll entry is out of balance (Debit: $batchTotalDebit, Credit: $batchTotalCredit). Please check if all pay items have valid accounts assigned.");
            }
            // Create Journal and Transaction Entries
            $journalEntries = [];
            $transactions = [];
            $now = now();
            $authId = auth('web')->id() ?: 1; // Fallback to system user
            $period = AccountingPeriod::findPeriodForDate($payRun->payment_date, $payRun->company_id);

            if (!$period) {
                throw new Exception("The payment date does not fall within any unlocked accounting period.");
            }

            $narration = "Payroll for period "
                . (\Carbon\Carbon::parse($payRun->period_start)->format('Y-m-d'))
                . " to "
                . (\Carbon\Carbon::parse($payRun->period_end)->format('Y-m-d'));

            // Journal Entries (batch totals per account)
            foreach ($accountingEntries as $accountId => $amounts) {
                if ($amounts['debit'] == 0 && $amounts['credit'] == 0) continue;

                $journalEntries[] = [
                    'company_id' => $payRun->company_id,
                    'accounting_period_id' => $period->id,
                    'account_id' => $accountId,
                    'date' => $payRun->payment_date,
                    'unique_no' => $uniqueNo,
                    'narration' => $narration,
                    'description' => "Payroll Processing",
                    'debit' => $amounts['debit'] ?: 0,
                    'credit' => $amounts['credit'] ?: 0,
                    'created_by' => $authId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // ============================
            // ✅ CHANGED: Transactions per employee (payable_id = employee_id)
            // ============================
            foreach ($employeeEntries as $employeeId => $accounts) {
                foreach ($accounts as $accountId => $amounts) {
                    if ($amounts['debit'] == 0 && $amounts['credit'] == 0) continue;

                    $transactions[] = [
                        'company_id' => $payRun->company_id,
                        'accounting_period_id' => $period->id,
                        'account_id' => $accountId,
                        'payable_id' => $employeeId, // ✅ employee id stored in payable_id
                        'payable_type' => 'employee',
                        'date' => $payRun->payment_date,
                        'unique_no' => $uniqueNo,
                        'reference_id' => $payRun->id,
                        'description' => "Payroll: " . $narration,
                        'type' => 'Payroll',
                        'debit' => $amounts['debit'] ?: 0,
                        'credit' => $amounts['credit'] ?: 0,
                        'status' => 1,
                        'created_by' => $authId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            if (!empty($journalEntries)) {
                JournalEntry::insert($journalEntries);
                Transaction::insert($transactions);
            }

            $this->applySalaryAdvanceRecoveries($payRun);

            $payRun->payslips()->update(['status' => 'processed']);

            $payRun->update([
                'status' => 'posted',
                'unique_no' => $uniqueNo,
                'accounting_notification_status' => 'posted',
                'accounting_posted_at' => $now,
                'accounting_posted_by' => $authId,
            ]);
            return $payRun;
        });
    }

    /**
     * Record payment for a Posted Pay Run (Reversal of Liability).
     */
    public function recordPayment($payRunId, $bankAccountId, $paymentDate, $reference = null)
    {
        $payRun = PayRun::with('payslips')->findOrFail($payRunId);

        if ($payRun->status !== 'posted') {
            throw new Exception("Only posted pay runs can be paid.");
        }

        $bankAccount = ChartOfAccount::forCompany($payRun->company_id)->find($bankAccountId);

        if (!$bankAccount) {
            throw new Exception("Please select a valid bank or cash account for this company.");
        }

        return DB::transaction(function () use ($payRun, $bankAccount, $paymentDate, $reference) {
            $totalNetPay = $payRun->payslips->sum('net_pay');
            $uniqueNo = 'PR-PAY-' . str_pad($payRun->id, 6, '0', STR_PAD_LEFT);
            $authId = auth('web')->id() ?: 1;
            $now = now();

            $period = AccountingPeriod::findPeriodForDate($paymentDate, $payRun->company_id);
            if (!$period) {
                throw new Exception("The payment date does not fall within any unlocked accounting period.");
            }

            // Look for the same Payable account used during posting
            $wagesPayableAccount = ChartOfAccount::getWagesPayableAccount($payRun->company_id);

            if (!$wagesPayableAccount) {
                throw new Exception("Accounting failure: 'Wages Payable' or 'Accrued Salaries' account not found.");
            }

            $journalEntries = [];

            $startDateStr = $payRun->period_start ? \Carbon\Carbon::parse($payRun->period_start)->format('Y-m-d') : '';
            $endDateStr = $payRun->period_end ? \Carbon\Carbon::parse($payRun->period_end)->format('Y-m-d') : '';
            $narration = "Payment of Payroll for period " . $startDateStr . " to " . $endDateStr;

            // 1. Debit Accounts Payable (Decrease Liability)
            $journalEntries[] = [
                'company_id' => $payRun->company_id,
                'accounting_period_id' => $period->id,
                'account_id' => $wagesPayableAccount->id,
                'date' => $paymentDate,
                'unique_no' => $uniqueNo,
                'narration' => $narration,
                'description' => $reference ?: "Payroll Payment",
                'debit' => $totalNetPay,
                'credit' => 0,
                'created_by' => $authId,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // 2. Credit Bank/Cash (Decrease Asset)
            $journalEntries[] = [
                'company_id' => $payRun->company_id,
                'accounting_period_id' => $period->id,
                'account_id' => $bankAccount->id,
                'date' => $paymentDate,
                'unique_no' => $uniqueNo,
                'narration' => $narration,
                'description' => $reference ?: "Payroll Payment",
                'debit' => 0,
                'credit' => $totalNetPay,
                'created_by' => $authId,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // ============================
            // ✅ Transactions per employee (payable_id = employee_id)
            // ============================
            $transactions = [];

            foreach ($payRun->payslips as $payslip) {
                $employeeId = $payslip->employee_id;
                $amount = $payslip->net_pay;

                if ($amount <= 0) continue;

                // Debit wages payable
                $transactions[] = [
                    'company_id' => $payRun->company_id,
                    'accounting_period_id' => $period->id,
                    'account_id' => $wagesPayableAccount->id,
                    'payable_id' => $employeeId,
                    'payable_type' => 'employee',
                    'date' => $paymentDate,
                    'unique_no' => $uniqueNo,
                    'reference_id' => $payRun->id,
                    'description' => "Payroll PMT: " . ($reference ?: $narration),
                    'type' => 'Payroll',
                    'debit' => $amount,
                    'credit' => 0,
                    'status' => 1,
                    'created_by' => $authId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                // Credit bank/cash
                $transactions[] = [
                    'company_id' => $payRun->company_id,
                    'accounting_period_id' => $period->id,
                    'account_id' => $bankAccount->id,
                    'payable_id' => $employeeId,
                    'payable_type' => 'employee',
                    'date' => $paymentDate,
                    'unique_no' => $uniqueNo,
                    'reference_id' => $payRun->id,
                    'description' => "Payroll PMT: " . ($reference ?: $narration),
                    'type' => 'Payroll',
                    'debit' => 0,
                    'credit' => $amount,
                    'status' => 1,
                    'created_by' => $authId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            JournalEntry::insert($journalEntries);
            Transaction::insert($transactions);

            $payRun->payslips()->update(['status' => 'paid']);
            $payRun->update(['status' => 'paid']);
            return true;
        });
    }

    /**
     * Delete a Pay Run and all its associated records.
     */
    public function deletePayRun($payRunId)
    {
        $payRun = PayRun::findOrFail($payRunId);
        if ($payRun->status !== 'draft') {
            throw new Exception('Only draft pay runs can be deleted. Use the controlled reversal workflow for posted or paid payroll.');
        }

        return DB::transaction(function () use ($payRun) {
            $uniqueNo = 'PR-' . str_pad($payRun->id, 6, '0', STR_PAD_LEFT);
            $payUniqueNo = 'PR-PAY-' . str_pad($payRun->id, 6, '0', STR_PAD_LEFT);

            // Delete Transactions
            Transaction::where('company_id', $payRun->company_id)
                ->where('reference_id', $payRun->id)
                ->where('type', 'Payroll')
                ->delete();

            $this->reverseSalaryAdvanceRecoveries($payRun);

            // Delete Journal Entries
            JournalEntry::where('company_id', $payRun->company_id)
                ->whereIn('unique_no', [$uniqueNo, $payUniqueNo])
                ->delete();

            // Delete Payslip Items and Payslips
            foreach ($payRun->payslips as $payslip) {
                $payslip->items()->delete();
                $payslip->delete();
            }

            // Finally, delete the Pay Run
            return $payRun->delete();
        });
    }

    /**
     * Pay specific deduction items (e.g., Salary Tax) from one or more Pay Runs.
     */
    public function payDeduction($payslipItemIds, $bankAccountId, $paymentDate, $reference = null)
    {
        $items = PayslipItem::whereIn('id', $payslipItemIds)->get();
        $allocations = $items->mapWithKeys(fn ($item) => [$item->id => $item->outstanding_amount])->all();
        $body = $items->first()?->payItem?->statutoryBody;

        return $this->createDeductionRemittance($allocations, $bankAccountId, $paymentDate, [
            'external_reference' => $reference,
            'payee' => $body?->name,
        ]);
    }

    public function createDeductionRemittance(array $allocationAmounts, $bankAccountId, $paymentDate, array $details = []): PayrollRemittanceBatch
    {
        $companyId = (int) (loggedCompanyId()['company_id'] ?? 0);
        if (!$companyId || empty($allocationAmounts)) throw new Exception('Select at least one payroll liability.');

        return DB::transaction(function () use ($allocationAmounts, $bankAccountId, $paymentDate, $details, $companyId) {
            $ids = collect($allocationAmounts)->keys()->map(fn ($id) => (int) $id)->filter()->values();
            $items = PayslipItem::with(['payItem.statutoryBody', 'payItem.account', 'payslip.payRun'])
                ->whereIn('id', $ids)
                ->whereHas('payslip', fn ($q) => $q->where('company_id', $companyId))
                ->whereHas('payslip.payRun', fn ($q) => $q->whereIn('status', ['posted', 'paid']))
                ->whereHas('payItem', fn ($q) => $q->where('company_id', $companyId)->where('type', 'deduction')->whereNotNull('account_id')->whereNotNull('statutory_body_id'))
                ->lockForUpdate()
                ->get();
            if ($items->count() !== $ids->count()) throw new Exception('Every selected row must be an unpaid statutory deduction from this company and a posted pay run.');

            $bodyIds = $items->pluck('payItem.statutory_body_id')->unique();
            if ($bodyIds->count() !== 1) throw new Exception('Create a separate remittance for each statutory authority.');
            $body = $items->first()->payItem->statutoryBody;
            if (!$body || !$body->is_active) throw new Exception('The statutory authority must be active before remittance.');

            $latestPeriodEnd = $items->map(fn ($item) => $item->payslip?->payRun?->period_end)->filter()->max();
            if ($latestPeriodEnd && \Carbon\Carbon::parse($paymentDate)->startOfDay()->lt(\Carbon\Carbon::parse($latestPeriodEnd)->startOfDay())) {
                throw new Exception('Remittance date cannot precede the latest selected pay-period end date. Earliest allowed date: ' . \Carbon\Carbon::parse($latestPeriodEnd)->format('d M Y') . '.');
            }

            $bank = ChartOfAccount::forCompany($companyId)->where('type', 'Account')->where('sub_type', 'BANK')->find($bankAccountId);
            if (!$bank) throw new Exception('Select a valid bank account belonging to this company.');

            $reserved = PayrollRemittanceAllocation::withoutGlobalScopes()
                ->where('company_id', $companyId)->whereIn('payslip_item_id', $ids)
                ->whereIn('status', ['pending', 'approved'])->selectRaw('payslip_item_id, SUM(amount) amount')
                ->groupBy('payslip_item_id')->pluck('amount', 'payslip_item_id');

            $rows = [];
            $total = 0.0;
            foreach ($items as $item) {
                $amount = round((float) ($allocationAmounts[$item->id] ?? 0), 4);
                $available = round($item->outstanding_amount - (float) ($reserved[$item->id] ?? 0), 4);
                if ($amount <= 0 || $amount > $available + 0.0001) throw new Exception("Remittance for payslip item #{$item->id} must be positive and cannot exceed {$available}.");
                if ($item->payItem->account?->category !== 'Liabilities') throw new Exception("Pay item '{$item->payItem->name}' must use a liability account.");
                $rows[] = ['item' => $item, 'amount' => $amount];
                $total += $amount;
            }

            $batch = PayrollRemittanceBatch::create([
                'company_id' => $companyId,
                'statutory_body_id' => $body->id,
                'bank_account_id' => $bank->id,
                'reference' => 'PR-REM-' . strtoupper(bin2hex(random_bytes(6))),
                'status' => 'submitted',
                'payment_date' => $paymentDate,
                'total_amount' => round($total, 4),
                'currency_code' => $details['currency_code'] ?? (currency()?->code ?? null),
                'payee' => ($details['payee'] ?? null) ?: $body->name,
                'external_reference' => $details['external_reference'] ?? null,
                'filing_reference' => $details['filing_reference'] ?? null,
                'attachment' => $details['attachment'] ?? null,
                'notes' => $details['notes'] ?? null,
                'created_by' => auth('web')->id() ?: 1,
            ]);

            foreach ($rows as $row) {
                $item = $row['item'];
                PayrollRemittanceAllocation::create([
                    'company_id' => $companyId,
                    'payroll_remittance_batch_id' => $batch->id,
                    'payslip_item_id' => $item->id,
                    'pay_item_id' => $item->pay_item_id,
                    'liability_account_id' => $item->payItem->account_id,
                    'original_amount' => $item->amount,
                    'amount' => $row['amount'],
                    'status' => 'pending',
                ]);
            }

            return $batch;
        });
    }

    public function updateDeductionRemittancePaymentDate(PayrollRemittanceBatch $batch, $paymentDate): PayrollRemittanceBatch
    {
        return DB::transaction(function () use ($batch, $paymentDate) {
            $batch = PayrollRemittanceBatch::withoutGlobalScopes()->whereKey($batch->id)->lockForUpdate()->firstOrFail();
            $this->assertRemittanceCompany($batch);

            if (!in_array($batch->status, ['submitted', 'approved'], true)) {
                throw new Exception('Only submitted or approved remittances can have their payment date corrected.');
            }
            if ((int) $batch->created_by !== (int) auth('web')->id()) {
                throw new Exception('Only the remittance creator can correct its payment date.');
            }

            $allocations = PayrollRemittanceAllocation::withoutGlobalScopes()
                ->where('company_id', $batch->company_id)
                ->where('payroll_remittance_batch_id', $batch->id)
                ->with('payslipItem.payslip.payRun')
                ->lockForUpdate()
                ->get();
            if ($allocations->isEmpty()) throw new Exception('This remittance has no allocations.');

            $latestPeriodEnd = $allocations
                ->map(fn ($allocation) => $allocation->payslipItem?->payslip?->payRun?->period_end)
                ->filter()
                ->max();
            $newDate = \Carbon\Carbon::parse($paymentDate)->startOfDay();
            if ($latestPeriodEnd && $newDate->lt(\Carbon\Carbon::parse($latestPeriodEnd)->startOfDay())) {
                throw new Exception('Remittance date cannot precede the latest selected pay-period end date. Earliest allowed date: ' . \Carbon\Carbon::parse($latestPeriodEnd)->format('d M Y') . '.');
            }

            $wasApproved = $batch->status === 'approved';
            $batch->update([
                'payment_date' => $newDate->toDateString(),
                'status' => 'submitted',
                'approved_by' => null,
                'approved_at' => null,
            ]);
            if ($wasApproved) {
                PayrollRemittanceAllocation::withoutGlobalScopes()
                    ->where('company_id', $batch->company_id)
                    ->where('payroll_remittance_batch_id', $batch->id)
                    ->update(['status' => 'pending']);
            }

            return $batch->fresh();
        });
    }
    public function approveDeductionRemittance(PayrollRemittanceBatch $batch): PayrollRemittanceBatch
    {
        return DB::transaction(function () use ($batch) {
            $batch = PayrollRemittanceBatch::withoutGlobalScopes()->whereKey($batch->id)->lockForUpdate()->firstOrFail();
            $this->assertRemittanceCompany($batch);
            if ($batch->status !== 'submitted') throw new Exception('Only submitted remittances can be approved.');
            if ((int) $batch->created_by === (int) auth('web')->id()) throw new Exception('The remittance creator cannot approve the same remittance.');
            $batch->update(['status' => 'approved', 'approved_by' => auth('web')->id(), 'approved_at' => now()]);
            $batch->allocations()->update(['status' => 'approved']);
            return $batch;
        });
    }

    public function postDeductionRemittance(PayrollRemittanceBatch $batch): PayrollRemittanceBatch
    {
        return DB::transaction(function () use ($batch) {
            $batch = PayrollRemittanceBatch::withoutGlobalScopes()->whereKey($batch->id)->lockForUpdate()->firstOrFail();
            $this->assertRemittanceCompany($batch);
            if ($batch->status !== 'approved') throw new Exception('Only approved remittances can be posted.');
            $allocations = PayrollRemittanceAllocation::withoutGlobalScopes()->where('payroll_remittance_batch_id', $batch->id)->with(['payslipItem.payslip.payRun', 'payItem'])->lockForUpdate()->get();
            if ($allocations->isEmpty()) throw new Exception('This remittance has no allocations.');

            $items = PayslipItem::whereIn('id', $allocations->pluck('payslip_item_id'))->lockForUpdate()->get()->keyBy('id');
            foreach ($allocations as $allocation) {
                $item = $items->get($allocation->payslip_item_id);
                if (!$item || (float) $allocation->amount > $item->outstanding_amount + 0.0001) throw new Exception('One or more allocations exceed the current outstanding liability.');
            }

            $latestPeriodEnd = $allocations->map(fn ($a) => $a->payslipItem?->payslip?->payRun?->period_end)->filter()->max();
            if ($latestPeriodEnd && $batch->payment_date->lt($latestPeriodEnd)) throw new Exception('Remittance date cannot precede the latest selected pay-period end date.');
            $period = AccountingPeriod::findPeriodForDate($batch->payment_date, $batch->company_id);
            if (!$period) throw new Exception('The payment date does not fall within an unlocked accounting period.');
            $bank = ChartOfAccount::forCompany($batch->company_id)->where('type', 'Account')->where('sub_type', 'BANK')->find($batch->bank_account_id);
            if (!$bank) throw new Exception('The configured payment bank account is invalid.');

            $byAccount = $allocations->groupBy('liability_account_id')->map(fn ($rows) => round((float) $rows->sum('amount'), 4));
            foreach ($byAccount as $accountId => $required) {
                $balance = (float) Transaction::withoutGlobalScopes()->where('company_id', $batch->company_id)->where('account_id', $accountId)->where('status', 1)->whereNull('deleted_at')->selectRaw('COALESCE(SUM(credit),0)-COALESCE(SUM(debit),0) balance')->value('balance');
                if ($balance + 0.0001 < $required) throw new Exception("Liability account #{$accountId} has {$balance} available but {$required} is required.");
            }

            $now = now();
            $userId = auth('web')->id() ?: 1;
            $journals = [];
            $transactions = [];
            foreach ($byAccount as $accountId => $amount) {
                $common = ['company_id' => $batch->company_id, 'accounting_period_id' => $period->id, 'account_id' => $accountId, 'date' => $batch->payment_date, 'unique_no' => $batch->reference, 'created_by' => $userId, 'updated_by' => $userId, 'created_at' => $now, 'updated_at' => $now];
                $journals[] = $common + ['narration' => "Payroll remittance to {$batch->payee}", 'description' => $batch->external_reference ?: 'Payroll liability remittance', 'debit' => $amount, 'credit' => 0];
                $transactions[] = $common + ['reference_id' => $batch->id, 'type' => 'Payroll Liability Payment', 'source_system' => 'PAYROLL', 'source_reference' => $batch->reference, 'description' => "Payroll remittance to {$batch->payee}", 'debit' => $amount, 'credit' => 0, 'status' => 1, 'posted_by' => $userId, 'posted_at' => $now];
            }
            $bankCommon = ['company_id' => $batch->company_id, 'accounting_period_id' => $period->id, 'account_id' => $bank->id, 'date' => $batch->payment_date, 'unique_no' => $batch->reference, 'created_by' => $userId, 'updated_by' => $userId, 'created_at' => $now, 'updated_at' => $now];
            $journals[] = $bankCommon + ['narration' => "Payroll remittance to {$batch->payee}", 'description' => $batch->external_reference ?: 'Payroll liability remittance', 'debit' => 0, 'credit' => $batch->total_amount];
            $transactions[] = $bankCommon + ['reference_id' => $batch->id, 'type' => 'Payroll Liability Payment', 'source_system' => 'PAYROLL', 'source_reference' => $batch->reference, 'description' => "Payroll remittance bank payment to {$batch->payee}", 'debit' => 0, 'credit' => $batch->total_amount, 'status' => 1, 'posted_by' => $userId, 'posted_at' => $now];
            JournalEntry::insert($journals);
            Transaction::insert($transactions);

            foreach ($allocations as $allocation) {
                $item = $items->get($allocation->payslip_item_id);
                $paid = round((float) $item->paid_amount + (float) $allocation->amount, 4);
                $item->update(['paid_amount' => $paid, 'paid_at' => $paid >= (float) $item->amount - 0.0001 ? $now : null, 'payment_reference' => $batch->reference]);
                $allocation->update(['status' => 'posted']);
            }
            $batch->update(['status' => 'posted', 'posted_by' => $userId, 'posted_at' => $now]);
            $this->reconcileComplianceEvents($batch, $allocations);
            return $batch;
        });
    }

    public function reverseDeductionRemittance(PayrollRemittanceBatch $batch, string $reason): PayrollRemittanceBatch
    {
        return DB::transaction(function () use ($batch, $reason) {
            $batch = PayrollRemittanceBatch::withoutGlobalScopes()->whereKey($batch->id)->lockForUpdate()->firstOrFail();
            $this->assertRemittanceCompany($batch);
            if ($batch->status !== 'posted') throw new Exception('Only posted remittances can be reversed.');
            $date = now()->toDateString();
            $period = AccountingPeriod::forCompany($batch->company_id)->where('start_date', '<=', $date)->where('end_date', '>=', $date)->where('is_active', true)->where('is_locked', false)->first();
            if (!$period) throw new Exception('Reversal requires an active, unlocked accounting period for today.');
            $originals = Transaction::withoutGlobalScopes()->where('company_id', $batch->company_id)->where('unique_no', $batch->reference)->where('type', 'Payroll Liability Payment')->where('status', 1)->get();
            if ($originals->isEmpty()) throw new Exception('Original remittance journal lines were not found.');
            $reference = 'PR-REM-REV-' . strtoupper(bin2hex(random_bytes(6)));
            $userId = auth('web')->id() ?: 1;
            foreach ($originals as $line) {
                Transaction::create(['company_id' => $batch->company_id, 'accounting_period_id' => $period->id, 'account_id' => $line->account_id, 'reference_id' => $batch->id, 'unique_no' => $reference, 'date' => $date, 'type' => 'Payroll Liability Reversal', 'source_system' => 'PAYROLL', 'source_reference' => $batch->reference, 'reversal_of_transaction_id' => $line->id, 'reversal_of_unique_no' => $batch->reference, 'description' => $reason, 'debit' => $line->credit, 'credit' => $line->debit, 'status' => 1, 'posted_by' => $userId, 'posted_at' => now(), 'created_by' => $userId, 'updated_by' => $userId]);
                JournalEntry::create(['company_id' => $batch->company_id, 'accounting_period_id' => $period->id, 'account_id' => $line->account_id, 'unique_no' => $reference, 'date' => $date, 'narration' => 'Payroll liability remittance reversal', 'description' => $reason, 'debit' => $line->credit, 'credit' => $line->debit, 'reversal_of_unique_no' => $batch->reference, 'created_by' => $userId, 'updated_by' => $userId]);
            }
            $allocations = $batch->allocations()->with('payslipItem.payslip.payRun')->lockForUpdate()->get();
            foreach ($allocations as $allocation) {
                $item = $allocation->payslipItem;
                $paid = max(0, round((float) $item->paid_amount - (float) $allocation->amount, 4));
                $item->update(['paid_amount' => $paid, 'paid_at' => $paid >= (float) $item->amount - 0.0001 ? $item->paid_at : null, 'payment_reference' => null]);
                $allocation->update(['status' => 'reversed']);
            }
            $batch->update(['status' => 'reversed', 'reversal_reason' => $reason, 'reversal_reference' => $reference, 'reversed_by' => $userId, 'reversed_at' => now()]);
            $reversedPayItems = $allocations->pluck('pay_item_id')->map(fn ($id) => (int) $id)->unique();
            $reversedPeriodEnds = $allocations->map(fn ($allocation) => $allocation->payslipItem?->payslip?->payRun?->period_end?->format('Y-m-d'))->filter()->unique();
            ComplianceEvent::withoutGlobalScopes()->where('company_id', $batch->company_id)->where('statutory_body_id', $batch->statutory_body_id)->where('event_type', 'payment')->where('status', 'completed')->get()->each(function ($event) use ($reversedPayItems, $reversedPeriodEnds) {
                $eventPayItems = collect(data_get($event->metadata, 'pay_item_ids', [data_get($event->metadata, 'pay_item_id')]))->filter()->map(fn ($id) => (int) $id);
                $eventPeriodEnd = data_get($event->metadata, 'period_end');
                if ($eventPayItems->intersect($reversedPayItems)->isNotEmpty() && (!$eventPeriodEnd || $reversedPeriodEnds->contains($eventPeriodEnd))) {
                    $event->update(['status' => 'pending', 'completed_at' => null]);
                }
            });
            return $batch;
        });
    }

    private function assertRemittanceCompany(PayrollRemittanceBatch $batch): void
    {
        if ((int) $batch->company_id !== (int) (loggedCompanyId()['company_id'] ?? 0)) throw new Exception('This remittance does not belong to the active company.');
    }

    private function reconcileComplianceEvents(PayrollRemittanceBatch $batch, $allocations): void
    {
        $payItemIds = $allocations->pluck('pay_item_id')->unique()->map(fn ($id) => (int) $id);
        ComplianceEvent::withoutGlobalScopes()->where('company_id', $batch->company_id)->where('statutory_body_id', $batch->statutory_body_id)->where('event_type', 'payment')->whereIn('status', ['pending', 'overdue'])->get()->each(function ($event) use ($payItemIds) {
            $eventPayItems = collect(data_get($event->metadata, 'pay_item_ids', [data_get($event->metadata, 'pay_item_id')]))->filter()->map(fn ($id) => (int) $id);
            if ($eventPayItems->intersect($payItemIds)->isEmpty()) return;
            $eventPeriodEnd = data_get($event->metadata, 'period_end');
            $outstanding = PayslipItem::whereIn('pay_item_id', $eventPayItems)
                ->whereHas('payslip', fn ($q) => $q->where('company_id', $event->company_id))
                ->when($eventPeriodEnd, fn ($q) => $q->whereHas('payslip.payRun', fn ($payRun) => $payRun->whereDate('period_end', $eventPeriodEnd)))
                ->get()->sum->outstanding_amount;
            if ($outstanding <= 0.0001) $event->markCompleted();
        });
    }

    private function payDeductionLegacy($payslipItemIds, $bankAccountId, $paymentDate, $reference = null)
    {
        if (empty($payslipItemIds)) {
            throw new Exception("No deduction items selected for payment.");
        }

        $items = PayslipItem::with(['payItem', 'payslip.payRun'])
            ->whereIn('id', $payslipItemIds)
            ->whereNull('paid_at')
            ->get();

        if ($items->count() !== count(array_unique($payslipItemIds))) {
            throw new Exception("One or more deduction items are invalid or already paid.");
        }

        $invalidPayRun = $items->first(fn ($item) => ! $item->payslip?->payRun
            || ! in_array($item->payslip->payRun->status, ['posted', 'paid'], true));
        if ($invalidPayRun) {
            throw new Exception("Payroll deductions can only be paid after their pay run is posted.");
        }

        $companyIds = $items
            ->map(fn ($item) => $item->payItem?->company_id)
            ->filter()
            ->unique()
            ->values();

        if ($companyIds->count() !== 1) {
            throw new Exception("Deduction payment must contain items from one company only.");
        }

        $companyId = (int) $companyIds->first();
        $bankAccount = ChartOfAccount::forCompany($companyId)->find($bankAccountId);

        if (!$bankAccount) {
            throw new Exception("Please select a valid bank or cash account for this company.");
        }

        return DB::transaction(function () use ($items, $bankAccount, $paymentDate, $reference, $companyId) {
            $expectedItemCount = $items->count();
            $items = PayslipItem::with(['payItem', 'payslip.payRun'])
                ->whereIn('id', $items->pluck('id'))
                ->whereNull('paid_at')
                ->lockForUpdate()
                ->get();
            if ($items->count() !== $expectedItemCount) {
                throw new Exception('One or more deduction items have already been paid.');
            }

            $requiredByAccount = $items->groupBy(fn ($item) => $item->payItem->account_id)
                ->map(fn ($rows) => (float) $rows->sum('amount'));
            foreach ($requiredByAccount as $accountId => $requiredAmount) {
                $balance = Transaction::withoutGlobalScopes()
                    ->where('company_id', $companyId)->where('account_id', $accountId)
                    ->where('status', 1)->whereNull('deleted_at')
                    ->selectRaw('COALESCE(SUM(credit), 0) - COALESCE(SUM(debit), 0) AS balance')
                    ->value('balance');
                if ((float) $balance + 0.01 < $requiredAmount) {
                    throw new Exception("The linked liability account balance is insufficient for this payment (available {$balance}, required {$requiredAmount}).");
                }
            }

            $authId = auth('web')->id() ?: 1;
            $now = now();
            $period = AccountingPeriod::findPeriodForDate($paymentDate, $companyId);

            if (!$period) {
                throw new Exception("The payment date does not fall within any unlocked accounting period.");
            }

            $uniqueNo = 'PR-DED-B' . time(); // Batch deduction payment
            $journalEntries = [];
            $transactions = [];

            // Group items by account and employee to keep transaction granularity
            $entries = [];
            foreach ($items as $item) {
                $payItem = $item->payItem;
                if (!$payItem->account_id) {
                    throw new Exception("The Pay Item '{$payItem->name}' does not have a linked account.");
                }
                $accountId = $payItem->account_id;
                $employeeId = $item->payslip->employee_id;
                $key = "{$accountId}-{$employeeId}";

                if (!isset($entries[$key])) {
                    $entries[$key] = [
                        'account_id' => $accountId,
                        'employee_id' => $employeeId,
                        'company_id' => $payItem->company_id,
                        'name' => $payItem->name,
                        'amount' => 0
                    ];
                }
                $entries[$key]['amount'] += $item->amount;
            }

            $totalBatchAmount = 0;
            foreach ($entries as $entry) {
                $amount = $entry['amount'];
                if ($amount <= 0) continue;
                $totalBatchAmount += $amount;

                $narration = "Payment of {$entry['name']} (Employee: {$entry['employee_id']})";

                $journalEntries[] = [
                    'company_id' => $entry['company_id'],
                    'accounting_period_id' => $period->id,
                    'account_id' => $entry['account_id'],
                    'date' => $paymentDate,
                    'unique_no' => $uniqueNo,
                    'narration' => $narration,
                    'description' => $reference ?: "Deduction Batch Payment",
                    'debit' => $amount,
                    'credit' => 0,
                    'created_by' => $authId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $transactions[] = [
                    'company_id' => $entry['company_id'],
                    'accounting_period_id' => $period->id,
                    'account_id' => $entry['account_id'],
                    'payable_id' => $entry['employee_id'],
                    'payable_type' => 'employee',
                    'date' => $paymentDate,
                    'unique_no' => $uniqueNo,
                    'reference_id' => null, // Batch payment
                    'description' => $narration,
                    'type' => 'Payroll',
                    'debit' => $amount,
                    'credit' => 0,
                    'status' => 1,
                    'created_by' => $authId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if ($totalBatchAmount > 0) {
                $firstItem = $items->first();
                // Credit Bank Account
                $journalEntries[] = [
                    'company_id' => $firstItem->payItem->company_id,
                    'accounting_period_id' => $period->id,
                    'account_id' => $bankAccount->id,
                    'date' => $paymentDate,
                    'unique_no' => $uniqueNo,
                    'narration' => "Payroll Deduction Batch Payment",
                    'description' => $reference ?: "Deduction Payment",
                    'debit' => 0,
                    'credit' => $totalBatchAmount,
                    'created_by' => $authId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $transactions[] = [
                    'company_id' => $firstItem->payItem->company_id,
                    'accounting_period_id' => $period->id,
                    'account_id' => $bankAccount->id,
                    'payable_id' => null, // Summary entry for bank payout
                    'payable_type' => null,
                    'date' => $paymentDate,
                    'unique_no' => $uniqueNo,
                    'reference_id' => null,
                    'description' => "Payroll Deduction Batch Payment",
                    'type' => 'Payroll',
                    'debit' => 0,
                    'credit' => $totalBatchAmount,
                    'status' => 1,
                    'created_by' => $authId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                JournalEntry::insert($journalEntries);
                Transaction::insert($transactions);

                // Update items status
                PayslipItem::whereIn('id', $items->pluck('id'))
                    ->update([
                        'paid_at' => $now,
                        'payment_reference' => $uniqueNo
                    ]);
            }

            return true;
        });
    }

    private function applySalaryAdvanceRecoveries(PayRun $payRun): void
    {
        $now = now();

        foreach ($payRun->payslips as $payslip) {
            $recoveryItems = $payslip->items
                ->filter(function ($item) {
                    $payItem = $item->payItem;

                    return $payItem
                        && $payItem->type === 'deduction'
                        && strtolower((string) $payItem->name) === 'salary advance';
                });

            if ($recoveryItems->isEmpty()) {
                continue;
            }

            $recoveriesByAdvance = [];
            foreach ($recoveryItems as $item) {
                if (!preg_match('/Salary Advance #(\d+)/i', (string) $item->description, $matches)) {
                    throw new Exception("Salary advance deduction on payslip #{$payslip->id} is not linked to an advance ID.");
                }
                $advanceId = (int) $matches[1];
                $recoveriesByAdvance[$advanceId] = ($recoveriesByAdvance[$advanceId] ?? 0) + (float) $item->amount;
            }

            foreach ($recoveriesByAdvance as $advanceId => $recoveryAmount) {
                $advance = EmployeeSalaryAdvance::where('company_id', $payRun->company_id)
                    ->where('employee_id', $payslip->employee_id)
                    ->where('status', 'approved')
                    ->whereNotNull('payment_reference')
                    ->where('remaining_amount', '>', 0)
                    ->find($advanceId);
                if (!$advance) {
                    throw new Exception("Salary advance #{$advanceId} is not an outstanding issued advance for this employee.");
                }
                $remaining = (float) $advance->remaining_amount;
                $applied = min($remaining, $recoveryAmount);
                $newRemaining = round($remaining - $applied, 2);

                $advance->update([
                    'remaining_amount' => $newRemaining,
                    'status' => $newRemaining <= 0 ? 'paid' : 'approved',
                    'recovered_pay_run_id' => $newRemaining <= 0 ? $payRun->id : null,
                    'recovered_at' => $newRemaining <= 0 ? $now : null,
                    'installments_recovered' => (int) $advance->installments_recovered + 1,
                ]);

                \App\Models\EmployeeSalaryAdvanceRecovery::create([
                    'company_id' => $payRun->company_id,
                    'employee_salary_advance_id' => $advance->id,
                    'pay_run_id' => $payRun->id,
                    'payslip_id' => $payslip->id,
                    'amount' => $applied,
                    'recovered_at' => $now,
                ]);
            }
        }
    }

    private function reverseSalaryAdvanceRecoveries(PayRun $payRun): void
    {
        \App\Models\EmployeeSalaryAdvanceRecovery::where('pay_run_id', $payRun->id)
            ->with('advance')
            ->get()
            ->each(function ($recovery) {
                $advance = $recovery->advance;
                if ($advance) {
                    $advance->update([
                        'remaining_amount' => min((float) $advance->amount, (float) $advance->remaining_amount + (float) $recovery->amount),
                        'status' => 'approved',
                        'recovered_pay_run_id' => null,
                        'recovered_at' => null,
                        'installments_recovered' => max(0, (int) $advance->installments_recovered - 1),
                    ]);
                }
                $recovery->delete();
            });
    }
}
