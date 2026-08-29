<?php

namespace App\Services\Payroll;

use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\PayrollFinalSettlement;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class PayrollFinalSettlementService
{
    public function post(PayrollFinalSettlement $settlement): PayrollFinalSettlement
    {
        if ($settlement->status !== 'approved') {
            throw new \RuntimeException('Approve the final settlement before posting.');
        }

        return DB::transaction(function () use ($settlement) {
            $period = AccountingPeriod::findPeriodForDate($settlement->settlement_date, $settlement->company_id);
            if (!$period || !$period->isOpen()) {
                throw new \RuntimeException('The settlement date must fall in an open accounting period.');
            }

            $wagesPayable = ChartOfAccount::getWagesPayableAccount($settlement->company_id);
            $employeeAdvances = ChartOfAccount::getEmployeeAdvancesAccount($settlement->company_id);
            if (!$wagesPayable || ((float) $settlement->net_amount < 0 && !$employeeAdvances)) {
                throw new \RuntimeException('Required Wages Payable or Employee Advances system account is missing.');
            }

            $entries = [];
            foreach ($settlement->lines as $line) {
                $account = ChartOfAccount::forCompany($settlement->company_id)->find($line['account_id'] ?? null);
                if (!$account) {
                    throw new \RuntimeException('Every settlement line must use a company-owned ledger account.');
                }
                $accountId = $account->id;
                $entries[$accountId] ??= ['debit' => 0.0, 'credit' => 0.0];
                if (($line['type'] ?? '') === 'earning') {
                    $entries[$accountId]['debit'] += (float) $line['amount'];
                } else {
                    $entries[$accountId]['credit'] += (float) $line['amount'];
                }
            }

            if ((float) $settlement->net_amount >= 0) {
                $entries[$wagesPayable->id] ??= ['debit' => 0.0, 'credit' => 0.0];
                $entries[$wagesPayable->id]['credit'] += (float) $settlement->net_amount;
            } else {
                $entries[$employeeAdvances->id] ??= ['debit' => 0.0, 'credit' => 0.0];
                $entries[$employeeAdvances->id]['debit'] += abs((float) $settlement->net_amount);
            }

            $debit = collect($entries)->sum('debit');
            $credit = collect($entries)->sum('credit');
            if (abs($debit - $credit) > 0.01) {
                throw new \RuntimeException("Final settlement journal is out of balance: debit {$debit}, credit {$credit}.");
            }

            $uniqueNo = 'FS-' . str_pad($settlement->id, 6, '0', STR_PAD_LEFT);
            $userId = auth('web')->id() ?: 1;
            foreach ($entries as $accountId => $amounts) {
                JournalEntry::create([
                    'company_id' => $settlement->company_id,
                    'accounting_period_id' => $period->id,
                    'account_id' => $accountId,
                    'date' => $settlement->settlement_date,
                    'unique_no' => $uniqueNo,
                    'narration' => 'Final settlement for ' . ($settlement->employee?->full_name ?? "employee #{$settlement->employee_id}"),
                    'description' => 'Payroll final settlement',
                    'debit' => $amounts['debit'],
                    'credit' => $amounts['credit'],
                    'created_by' => $userId,
                ]);
                Transaction::create([
                    'company_id' => $settlement->company_id,
                    'accounting_period_id' => $period->id,
                    'account_id' => $accountId,
                    'payable_id' => $settlement->employee_id,
                    'payable_type' => 'employee',
                    'date' => $settlement->settlement_date,
                    'unique_no' => $uniqueNo,
                    'reference_id' => $settlement->id,
                    'description' => 'Payroll final settlement',
                    'type' => 'Payroll',
                    'debit' => $amounts['debit'],
                    'credit' => $amounts['credit'],
                    'status' => 1,
                    'created_by' => $userId,
                ]);
            }

            $settlement->update(['status' => 'posted', 'unique_no' => $uniqueNo, 'posted_by' => $userId, 'posted_at' => now()]);
            $settlement->employee()->update(['status' => 'resigned', 'resign_date' => $settlement->last_working_date]);
            return $settlement->refresh();
        });
    }
}
