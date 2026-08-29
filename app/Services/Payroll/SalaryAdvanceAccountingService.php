<?php

namespace App\Services\Payroll;

use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\EmployeeSalaryAdvance;
use App\Models\JournalEntry;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class SalaryAdvanceAccountingService
{
    public function settleEarly(EmployeeSalaryAdvance $advance, int $bankAccountId, string $paymentDate, ?string $description = null): EmployeeSalaryAdvance
    {
        if (!$advance->payment_reference || (float) $advance->remaining_amount <= 0) {
            throw new \Exception('Only an issued advance with an outstanding balance can be settled.');
        }
        $bankAccount = ChartOfAccount::forCompany($advance->company_id)->where('type', 'Account')->find($bankAccountId);
        $advanceAccount = ChartOfAccount::getEmployeeAdvancesAccount($advance->company_id);
        $period = AccountingPeriod::findPeriodForDate($paymentDate, $advance->company_id);
        if (!$bankAccount || !$advanceAccount || !$period || !$period->isOpen()) {
            throw new \Exception('Select a valid company bank/cash account and an open accounting date.');
        }

        return DB::transaction(function () use ($advance, $bankAccount, $advanceAccount, $period, $paymentDate, $description) {
            $amount = (float) $advance->remaining_amount;
            $reference = 'ADV-SET-' . str_pad($advance->id, 6, '0', STR_PAD_LEFT) . '-' . now()->format('YmdHis');
            $userId = auth('web')->id() ?: 1;
            $narration = $description ?: 'Early settlement of salary advance ' . $advance->payment_reference;
            foreach ([
                [$bankAccount->id, $amount, 0],
                [$advanceAccount->id, 0, $amount],
            ] as [$accountId, $debit, $credit]) {
                JournalEntry::create(['company_id' => $advance->company_id, 'accounting_period_id' => $period->id, 'account_id' => $accountId, 'date' => $paymentDate, 'unique_no' => $reference, 'narration' => $narration, 'description' => 'Salary advance settlement', 'debit' => $debit, 'credit' => $credit, 'created_by' => $userId]);
                Transaction::create(['company_id' => $advance->company_id, 'accounting_period_id' => $period->id, 'account_id' => $accountId, 'payable_id' => $advance->employee_id, 'payable_type' => 'employee', 'reference_id' => $advance->id, 'date' => $paymentDate, 'unique_no' => $reference, 'invoice_no' => $advance->payment_reference, 'description' => $narration, 'type' => 'SalaryAdvance', 'debit' => $debit, 'credit' => $credit, 'status' => 1, 'created_by' => $userId]);
            }
            $advance->update(['remaining_amount' => 0, 'status' => 'paid', 'recovered_at' => now()]);
            return $advance->refresh();
        });
    }

    public function issueAdvance(
        EmployeeSalaryAdvance $advance,
        int $bankAccountId,
        string $paymentDate,
        ?string $description = null
    ): EmployeeSalaryAdvance {
        if ($advance->status !== 'approved') {
            throw new \Exception('Only approved salary advances can be issued.');
        }

        if ($advance->payment_reference) {
            throw new \Exception("This salary advance has already been issued as {$advance->payment_reference}.");
        }

        if ((float) $advance->remaining_amount <= 0) {
            throw new \Exception('This salary advance has no remaining balance to issue.');
        }

        $bankAccount = ChartOfAccount::forCompany($advance->company_id)
            ->where('type', 'Account')
            ->where('sub_type', 'BANK')
            ->find($bankAccountId);

        if (! $bankAccount) {
            throw new \Exception('Please select a valid bank or cash account.');
        }

        $employeeAdvancesAccount = ChartOfAccount::getEmployeeAdvancesAccount($advance->company_id);

        if (! $employeeAdvancesAccount) {
            throw new \Exception("Accounting failure: 'Employee Advances' account not found. Please run migrations or create it in Chart of Accounts.");
        }

        $period = AccountingPeriod::findPeriodForDate($paymentDate, $advance->company_id);

        if (! $period) {
            throw new \Exception('The payment date does not fall within any unlocked accounting period.');
        }

        return DB::transaction(function () use ($advance, $bankAccount, $employeeAdvancesAccount, $period, $paymentDate, $description) {
            $advance->refresh();

            if ($advance->payment_reference) {
                throw new \Exception("This salary advance has already been issued as {$advance->payment_reference}.");
            }

            $reference = $this->nextReference();
            $amount = (float) $advance->remaining_amount;
            $authId = auth('web')->id() ?: 1;
            $now = now();
            $employeeName = $advance->employee?->full_name ?: 'Employee';
            $narration = "Salary advance issued to {$employeeName}";
            $lineDescription = $description ?: $narration;

            JournalEntry::insert([
                [
                    'company_id' => $advance->company_id,
                    'accounting_period_id' => $period->id,
                    'account_id' => $employeeAdvancesAccount->id,
                    'date' => $paymentDate,
                    'unique_no' => $reference,
                    'narration' => $narration,
                    'description' => $lineDescription,
                    'debit' => $amount,
                    'credit' => 0,
                    'created_by' => $authId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'company_id' => $advance->company_id,
                    'accounting_period_id' => $period->id,
                    'account_id' => $bankAccount->id,
                    'date' => $paymentDate,
                    'unique_no' => $reference,
                    'narration' => $narration,
                    'description' => $lineDescription,
                    'debit' => 0,
                    'credit' => $amount,
                    'created_by' => $authId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);

            Transaction::insert([
                [
                    'company_id' => $advance->company_id,
                    'accounting_period_id' => $period->id,
                    'account_id' => $employeeAdvancesAccount->id,
                    'payable_id' => $advance->employee_id,
                    'payable_type' => 'employee',
                    'reference_id' => $advance->id,
                    'date' => $paymentDate,
                    'unique_no' => $reference,
                    'invoice_no' => $reference,
                    'description' => $lineDescription,
                    'type' => 'SalaryAdvance',
                    'debit' => $amount,
                    'credit' => 0,
                    'status' => 1,
                    'created_by' => $authId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'company_id' => $advance->company_id,
                    'accounting_period_id' => $period->id,
                    'account_id' => $bankAccount->id,
                    'payable_id' => $advance->employee_id,
                    'payable_type' => 'employee',
                    'reference_id' => $advance->id,
                    'date' => $paymentDate,
                    'unique_no' => $reference,
                    'invoice_no' => $reference,
                    'description' => $lineDescription,
                    'type' => 'SalaryAdvance',
                    'debit' => 0,
                    'credit' => $amount,
                    'status' => 1,
                    'created_by' => $authId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);

            $advance->update([
                'payment_reference' => $reference,
                'bank_account_id' => $bankAccount->id,
                'issued_at' => $now,
                'issued_by' => $authId,
            ]);

            return $advance->fresh(['employee', 'approvedBy']);
        });
    }

    private function nextReference(): string
    {
        $latest = EmployeeSalaryAdvance::withTrashed()
            ->whereNotNull('payment_reference')
            ->where('payment_reference', 'like', 'ADV-%')
            ->orderByDesc('id')
            ->value('payment_reference');

        $number = $latest && preg_match('/ADV-(\d+)/', $latest, $matches)
            ? ((int) $matches[1]) + 1
            : 1;

        do {
            $reference = 'ADV-' . str_pad($number, 6, '0', STR_PAD_LEFT);
            $number++;
        } while (EmployeeSalaryAdvance::withTrashed()->where('payment_reference', $reference)->exists());

        return $reference;
    }
}
