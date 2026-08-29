<?php

namespace App\Services\Account;

use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\Transaction;
use App\Support\AccountingConstants;
use App\Support\DecimalAmount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class OpeningBalanceService
{
    /**
     * Return the current, unreversed opening balance posted directly for an
     * account from the Chart of Accounts screen.
     *
     * @return array{amount: float, side: ?string, date: ?string}
     */
    public function accountOpeningBalance(ChartOfAccount $account, int $companyId): array
    {
        $aggregate = Transaction::withoutGlobalScope('uniqueNoNotNull')
            ->where('company_id', $companyId)
            ->where('type', AccountingConstants::TRANSACTION_TYPE_OPENING_BALANCE)
            ->where('account_id', $account->id)
            ->where('status', 1)
            ->selectRaw('COALESCE(SUM(debit), 0) AS debit, COALESCE(SUM(credit), 0) AS credit, MIN(date) AS date')
            ->first();

        if (! $aggregate || DecimalAmount::equals($aggregate->debit, $aggregate->credit)) {
            return [
                'amount' => 0.0,
                'side' => null,
                'date' => null,
            ];
        }

        $debit = (string) $aggregate->debit;
        $credit = (string) $aggregate->credit;
        $debitBalance = (float) $debit - (float) $credit;

        return [
            'amount' => abs($debitBalance),
            'side' => $debitBalance > 0 ? AccountingConstants::OPENING_SIDE_DEBIT : AccountingConstants::OPENING_SIDE_CREDIT,
            'date' => $aggregate->date,
        ];
    }

    public function defaultOpeningDate(int $companyId): ?string
    {
        return AccountingPeriod::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('start_date')
            ->value('start_date');
    }

    public function replaceAccountOpeningBalance(ChartOfAccount $account, ?float $amount, ?string $side, ?string $openingDate, int $companyId): void
    {
        throw ValidationException::withMessages([
            'opening_balance' => 'Account-level opening balance posting is retired. Use an approved Opening Balance Batch.',
        ]);

        $amount = round((float) ($amount ?? 0), 4);
        $openingDate = $openingDate ?: $this->defaultOpeningDate($companyId);

        $this->reverseExistingAccountOpeningBalance($account, $companyId, $openingDate);

        if ($amount <= 0) {
            return;
        }

        $this->validateAccountOpeningBalance($account, $amount, $side, $openingDate, $companyId);

        $period = $this->resolveOpenPeriod($openingDate, $companyId);
        $obe = $this->openingBalanceEquityAccount($companyId);
        $reference = $this->accountOpeningReference($companyId, $period, $account);

        Transaction::insert([
            $this->transactionLine($reference, $openingDate, $period->id, $account->id, $side === AccountingConstants::OPENING_SIDE_DEBIT ? $amount : 0, $side === AccountingConstants::OPENING_SIDE_CREDIT ? $amount : 0, $companyId, 'Opening balance - ' . $account->name),
            $this->transactionLine($reference, $openingDate, $period->id, $obe->id, $side === AccountingConstants::OPENING_SIDE_CREDIT ? $amount : 0, $side === AccountingConstants::OPENING_SIDE_DEBIT ? $amount : 0, $companyId, 'Opening balance offset - ' . $account->name),
        ]);
    }

    public function validateAccountOpeningBalance(ChartOfAccount $account, float $amount, ?string $side, ?string $openingDate, int $companyId): void
    {
        $errors = [];

        if ($account->type !== 'Account') {
            $errors['type'] = 'Opening balances cannot be posted to group accounts.';
        }

        if (in_array($account->category, AccountingConstants::PROFIT_AND_LOSS_CATEGORIES, true)) {
            $errors['category'] = 'Opening balances for Revenue, Expenses and COGS are not allowed at the start of a new fiscal year. Bring prior-year profit or loss forward through Retained Earnings.';
        }

        if ($amount <= 0) {
            $errors['opening_balance_amount'] = 'Opening balance amount must be greater than zero.';
        }

        if (! in_array($side, [AccountingConstants::OPENING_SIDE_DEBIT, AccountingConstants::OPENING_SIDE_CREDIT], true)) {
            $errors['opening_balance_side'] = 'Choose whether the opening balance is Debit or Credit.';
        }

        if (! $openingDate || ! AccountingPeriod::findPeriodForDate($openingDate, $companyId)) {
            $errors['opening_date'] = 'The selected opening date does not belong to an active accounting period for this company.';
        } else {
            $period = AccountingPeriod::findPeriodForDate($openingDate, $companyId);
            if ($period->is_locked) {
                $errors['opening_date'] = 'Opening balance cannot be posted to a locked accounting period.';
            }
        }

        if ($this->chartHasCompanyColumn() && (int) $account->company_id !== $companyId) {
            $errors['account_id'] = 'The selected account does not belong to the current company.';
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function reverseExistingAccountOpeningBalance(ChartOfAccount $account, int $companyId, ?string $openingDate): void
    {
        $references = Transaction::withoutGlobalScope('uniqueNoNotNull')
            ->where('company_id', $companyId)
            ->where('type', AccountingConstants::TRANSACTION_TYPE_OPENING_BALANCE)
            ->whereNull('opening_balance_batch_id')
            ->whereNull('reversal_of_transaction_id')
            ->where('account_id', $account->id)
            ->whereDoesntHave('auditAction')
            ->pluck('unique_no')
            ->filter()
            ->unique();

        if ($references->isEmpty()) {
            return;
        }

        $period = $openingDate ? $this->resolveOpenPeriod($openingDate, $companyId) : null;

        foreach ($references as $reference) {
            $lines = Transaction::withoutGlobalScope('uniqueNoNotNull')
                ->where('company_id', $companyId)
                ->where('type', AccountingConstants::TRANSACTION_TYPE_OPENING_BALANCE)
                ->where('unique_no', $reference)
                ->whereNull('opening_balance_batch_id')
                ->whereNull('reversal_of_transaction_id')
                ->get();

            foreach ($lines as $line) {
                if (Transaction::withoutGlobalScope('uniqueNoNotNull')->where('reversal_of_transaction_id', $line->id)->exists()) {
                    continue;
                }

                Transaction::create([
                    'unique_no' => 'REV-' . $reference,
                    'date' => $openingDate ?: now()->toDateString(),
                    'account_id' => $line->account_id,
                    'debit' => (float) $line->credit,
                    'credit' => (float) $line->debit,
                    'description' => 'Reversal of ' . $reference . ' - opening balance changed',
                    'accounting_period_id' => $period?->id,
                    'type' => AccountingConstants::TRANSACTION_TYPE_OPENING_BALANCE,
                    'source_system' => AccountingConstants::SOURCE_CHART_OF_ACCOUNT_OPENING,
                    'source_reference' => 'CHART_OF_ACCOUNT:' . $account->id,
                    'is_opening_balance' => true,
                    'reversal_of_transaction_id' => $line->id,
                    'reversal_of_unique_no' => $reference,
                    'company_id' => $companyId,
                    'created_by' => auth('web')->id(),
                    'updated_by' => auth('web')->id(),
                    'status' => 1,
                ]);
            }
        }
    }

    private function resolveOpenPeriod(string $date, int $companyId): AccountingPeriod
    {
        $period = AccountingPeriod::findPeriodForDate($date, $companyId);

        if (! $period) {
            throw ValidationException::withMessages([
                'opening_date' => 'No active accounting period exists for the selected opening date.',
            ]);
        }

        if ($period->is_locked) {
            throw ValidationException::withMessages([
                'opening_date' => 'Opening balance cannot be posted to a locked accounting period.',
            ]);
        }

        return $period;
    }

    private function openingBalanceEquityAccount(int $companyId): ChartOfAccount
    {
        $account = ChartOfAccount::forCompany($companyId)
            ->where('system_account_type', AccountingConstants::SYSTEM_OPENING_BALANCE_EQUITY)
            ->first();

        if (! $account) {
            throw ValidationException::withMessages([
                'opening_balance' => 'Opening Balance Equity account was not found for the current company.',
            ]);
        }

        return $account;
    }

    private function transactionLine(string $reference, string $date, int $periodId, int $accountId, float $debit, float $credit, int $companyId, string $description): array
    {
        return [
            'unique_no' => $reference,
            'date' => $date,
            'account_id' => $accountId,
            'debit' => $debit,
            'credit' => $credit,
            'description' => $description,
            'accounting_period_id' => $periodId,
            'type' => AccountingConstants::TRANSACTION_TYPE_OPENING_BALANCE,
            'source_system' => AccountingConstants::SOURCE_CHART_OF_ACCOUNT_OPENING,
            'source_reference' => 'CHART_OF_ACCOUNT:' . $accountId,
            'is_opening_balance' => true,
            'company_id' => $companyId,
            'created_by' => auth('web')->id(),
            'updated_by' => auth('web')->id(),
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function accountOpeningReference(int $companyId, AccountingPeriod $period, ChartOfAccount $account): string
    {
        $year = preg_replace('/[^A-Za-z0-9]/', '', (string) $period->name) ?: $period->start_date->format('Y');

        return 'OB-' . $companyId . '-' . $year . '-A' . $account->id . '-' . now()->format('YmdHis');
    }

    private function chartHasCompanyColumn(): bool
    {
        return Schema::hasColumn('chart_of_accounts', 'company_id');
    }
}
