<?php

namespace App\Services\Account;

use App\Models\JournalEntry;
use App\Models\OpeningBalanceBatch;
use App\Models\Transaction;
use App\Support\AccountingDate;
use App\Support\DecimalAmount;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JournalCorrectionService
{
    public function __construct(private readonly AccountingPeriodResolver $periodResolver)
    {
    }

    public function reverse(int $companyId, string $reference, string $date, string $reason, int $userId): string
    {
        $date = AccountingDate::fromIso($date);
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'A correction reason is required.']);
        }

        return DB::transaction(function () use ($companyId, $reference, $date, $reason, $userId) {
            $original = JournalEntry::withoutGlobalScopes()->where('company_id', $companyId)->where('unique_no', $reference)->lockForUpdate()->get();
            if ($original->isEmpty()) {
                throw ValidationException::withMessages(['journal' => 'Journal not found for this company.']);
            }

            $isOpeningBalanceBatch = OpeningBalanceBatch::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('journal_unique_no', $reference)
                ->exists();
            $isOpeningBalanceTransaction = Transaction::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('unique_no', $reference)
                ->where('is_opening_balance', true)
                ->exists();
            if ($isOpeningBalanceBatch || $isOpeningBalanceTransaction) {
                throw ValidationException::withMessages([
                    'journal' => 'Opening balance journals must be reversed from Accounting > Conversion > Opening Balances so the batch and ledger remain consistent.',
                ]);
            }
            $originalDate = $original->min(fn ($line) => $line->date->toDateString());
            if ($date < $originalDate) {
                throw ValidationException::withMessages(['reversal_date' => 'A reversal date cannot be earlier than the original journal date.']);
            }
            $existing = JournalEntry::withoutGlobalScopes()->where('company_id', $companyId)->where('reversal_of_unique_no', $reference)->where('posting_status', 'posted')->value('unique_no');
            if ($existing) return $existing;

            $period = $this->periodResolver->resolve($companyId, $date);
            $reversalReference = 'REV-' . $reference;
            $journalRows = [];
            foreach ($original as $line) {
                $journalRows[] = [
                    'company_id' => $companyId, 'accounting_period_id' => $period->id, 'account_id' => $line->account_id,
                    'unique_no' => $reversalReference, 'narration' => $reason, 'date' => $date,
                    'description' => 'Reversal of ' . $reference, 'debit' => $line->credit, 'credit' => $line->debit,
                    'posting_status' => 'posted', 'reversal_of_unique_no' => $reference, 'correction_type' => 'reversal',
                    'created_by' => $userId, 'updated_by' => $userId, 'posted_by' => $userId, 'posted_at' => now(),
                    'created_at' => now(), 'updated_at' => now(),
                ];
            }
            if (! DecimalAmount::equals(DecimalAmount::sum(collect($journalRows)->pluck('debit'), 2), DecimalAmount::sum(collect($journalRows)->pluck('credit'), 2), 2)) {
                throw new \RuntimeException('The reversal journal would not balance.');
            }
            $ledgerOriginal = Transaction::withoutGlobalScopes()->where('company_id', $companyId)->where('unique_no', $reference)->lockForUpdate()->get();
            if ($ledgerOriginal->isEmpty()) {
                throw ValidationException::withMessages([
                    'journal' => 'The posted journal has no matching ledger transactions. Repair the journal-ledger link before reversing it.',
                ]);
            }

            $journalByAccount = $original->groupBy('account_id')->map(fn ($lines) => [
                'debit' => DecimalAmount::sum($lines->pluck('debit'), 2),
                'credit' => DecimalAmount::sum($lines->pluck('credit'), 2),
            ])->sortKeys()->all();
            $ledgerByAccount = $ledgerOriginal->groupBy('account_id')->map(fn ($lines) => [
                'debit' => DecimalAmount::sum($lines->pluck('debit'), 2),
                'credit' => DecimalAmount::sum($lines->pluck('credit'), 2),
            ])->sortKeys()->all();
            if ($journalByAccount !== $ledgerByAccount) {
                throw ValidationException::withMessages([
                    'journal' => 'The journal and ledger do not agree by account. Repair the inconsistency before posting a reversal.',
                ]);
            }

            JournalEntry::insert($journalRows);

            foreach ($ledgerOriginal as $line) {
                Transaction::withoutGlobalScopes()->create([
                    'company_id' => $companyId, 'accounting_period_id' => $period->id, 'account_id' => $line->account_id,
                    'unique_no' => $reversalReference, 'date' => $date, 'type' => 'Journal_Entry', 'description' => $reason,
                    'debit' => $line->credit, 'credit' => $line->debit, 'reversal_of_transaction_id' => $line->id,
                    'reversal_of_unique_no' => $reference, 'correction_type' => 'reversal', 'source_system' => 'CONTROLLED_REVERSAL',
                    'source_reference' => $reference, 'posting_idempotency_key' => 'JOURNAL-REV:' . $companyId . ':' . $line->id,
                    'created_by' => $userId, 'updated_by' => $userId, 'posted_by' => $userId, 'posted_at' => now(), 'status' => 1,
                ]);
            }
            return $reversalReference;
        });
    }
}
