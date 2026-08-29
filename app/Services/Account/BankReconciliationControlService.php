<?php

namespace App\Services\Account;

use App\Models\BankReconciliationEvent;
use App\Models\AccountingPeriod;
use App\Models\BankReconciliationMatch;
use App\Models\BankStatementImport;
use App\Models\BankStatementLine;
use App\Models\Bill;
use App\Models\JournalEntry;
use App\Models\PayRun;
use App\Models\Payslip;
use App\Models\Sale;
use App\Models\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BankReconciliationControlService
{
    public function uniqueNumber(int $companyId): string
    {
        do {
            $number = 'BK-' . now()->format('Ymd') . '-' . Str::upper(Str::random(10));
        } while (BankStatementLine::withoutGlobalScopes()->where('company_id', $companyId)->where('unique_no', $number)->exists());

        return $number;
    }

    public function rowHash(int $bankAccountId, array $row): string
    {
        $parts = [
            $bankAccountId,
            trim((string) ($row['date'] ?? '')),
            number_format((float) ($row['amount'] ?? 0), 4, '.', ''),
            mb_strtolower(trim((string) ($row['payee'] ?? ''))),
            mb_strtolower(trim((string) ($row['description'] ?? ''))),
            mb_strtolower(trim((string) ($row['reference'] ?? ''))),
        ];

        return hash('sha256', implode('|', $parts));
    }

    public function assertEditable(BankStatementLine $line): void
    {
        if ($line->is_locked || $line->statementImport?->status === 'finalized') {
            throw ValidationException::withMessages([
                'line_id' => 'This statement has been finalized. Reopen it with a reason before making changes.',
            ]);
        }
    }

    public function reconcileExisting(BankStatementLine $line, array $transactionIds, ?int $confidence = null): BankStatementLine
    {
        return DB::transaction(function () use ($line, $transactionIds, $confidence) {
            $line = BankStatementLine::where('company_id', $line->company_id)->lockForUpdate()->findOrFail($line->id);
            $this->assertEditable($line);

            if (abs((float) $line->remaining_amount) < 0.005) {
                throw ValidationException::withMessages(['line_id' => 'This statement line is already fully reconciled.']);
            }

            $transactions = Transaction::where('company_id', $line->company_id)
                ->whereIn('id', array_values(array_unique($transactionIds)))
                ->lockForUpdate()
                ->get();

            if ($transactions->count() !== count(array_unique($transactionIds))) {
                throw ValidationException::withMessages(['transaction_ids' => 'One or more transactions are unavailable.']);
            }

            $available = abs((float) $line->remaining_amount);
            $matched = 0.0;
            foreach ($transactions as $transaction) {
                if ((int) $transaction->account_id !== (int) $line->bank_account_id) {
                    throw ValidationException::withMessages(['transaction_ids' => 'Every match must use the selected bank account.']);
                }
                if ($transaction->bank_statement_line_id && (int) $transaction->bank_statement_line_id !== (int) $line->id) {
                    throw ValidationException::withMessages(['transaction_ids' => 'A selected transaction is already reconciled elsewhere.']);
                }

                $net = (float) $transaction->debit - (float) $transaction->credit;
                if (abs($net) < 0.005 || ($net > 0) !== ((float) $line->amount > 0)) {
                    throw ValidationException::withMessages(['transaction_ids' => 'Transaction direction does not match the statement line.']);
                }
                $matched += abs($net);
            }

            if ($matched - $available > 0.005) {
                throw ValidationException::withMessages(['transaction_ids' => 'Selected transactions exceed the unreconciled amount.']);
            }

            foreach ($transactions as $transaction) {
                $net = (float) $transaction->debit - (float) $transaction->credit;
                $transaction->update(['bank_statement_line_id' => $line->id]);
                BankReconciliationMatch::firstOrCreate(
                    ['bank_statement_line_id' => $line->id, 'transaction_id' => $transaction->id, 'reversed_at' => null],
                    ['company_id' => $line->company_id, 'amount' => abs($net), 'origin' => 'existing',
                        'posting_unique_no' => $transaction->unique_no, 'confidence' => $confidence,
                        'matched_by' => auth('web')->id(), 'matched_at' => now()]
                );
            }

            $this->refreshLine($line, $confidence);
            $this->event($line, 'matched_existing', ['transaction_ids' => $transactions->pluck('id')->all(), 'amount' => $matched]);
            return $line->fresh();
        }, 3);
    }

    public function recordGeneratedMatch(BankStatementLine $line, Transaction $bankTransaction, float $amount, string $origin, $matchable = null, ?int $confidence = null): void
    {
        BankReconciliationMatch::create([
            'company_id' => $line->company_id,
            'bank_statement_line_id' => $line->id,
            'transaction_id' => $bankTransaction->id,
            'matchable_type' => $matchable ? get_class($matchable) : null,
            'matchable_id' => $matchable?->getKey(),
            'amount' => abs($amount),
            'origin' => $origin,
            'posting_unique_no' => $bankTransaction->unique_no,
            'confidence' => $confidence,
            'matched_by' => auth('web')->id(),
            'matched_at' => now(),
        ]);
    }

    public function refreshLine(BankStatementLine $line, ?int $confidence = null): void
    {
        $net = (float) Transaction::where('company_id', $line->company_id)
            ->where('bank_statement_line_id', $line->id)->sum(DB::raw('debit - credit'));
        $remaining = round((float) $line->amount - $net, 4);
        if (($remaining > 0) !== ((float) $line->amount > 0) && abs($remaining) >= 0.005) {
            throw ValidationException::withMessages(['transaction_ids' => 'The reconciliation would over-allocate this statement line.']);
        }

        $complete = abs($remaining) < 0.005;
        $line->update([
            'remaining_amount' => $complete ? 0 : $remaining,
            'status' => $complete ? 'Reconciled' : ($net == 0.0 ? 'Unreconciled' : 'Partially Reconciled'),
            'reconciled_by' => $complete ? auth('web')->id() : null,
            'reconciled_at' => $complete ? now() : null,
            'match_confidence' => $confidence ?? $line->match_confidence,
        ]);
    }

    public function unreconcile(BankStatementLine $line, string $reason): void
    {
        DB::transaction(function () use ($line, $reason) {
            $line = BankStatementLine::where('company_id', $line->company_id)->lockForUpdate()->findOrFail($line->id);
            $this->assertEditable($line);
            $matches = BankReconciliationMatch::where('bank_statement_line_id', $line->id)->active()->lockForUpdate()->get();
            $reversedPostings = [];

            foreach ($matches as $match) {
                if ($match->origin === 'existing') {
                    Transaction::whereKey($match->transaction_id)->update(['bank_statement_line_id' => null]);
                } elseif ($match->posting_unique_no) {
                    if (! isset($reversedPostings[$match->posting_unique_no])) {
                        $reversalNo = $this->reversePosting($line, $match->posting_unique_no, $reason);
                        $reversedPostings[$match->posting_unique_no] = $reversalNo;
                        $this->closePostingMatches($match->posting_unique_no, $reversalNo, $reason);
                    }
                    continue;
                }

                $this->reverseDocumentAllocation($match);
                $match->update([
                    'reversed_by' => auth('web')->id(), 'reversed_at' => now(),
                    'reversal_reason' => $reason,
                    'reversal_unique_no' => $reversedPostings[$match->posting_unique_no] ?? null,
                ]);
            }

            // Legacy matches have no allocation ledger: detach safely, never delete their postings.
            Transaction::where('company_id', $line->company_id)
                ->where('bank_statement_line_id', $line->id)->update(['bank_statement_line_id' => null]);

            $line->update([
                'status' => 'Unreconciled', 'remaining_amount' => $line->amount,
                'reconciled_by' => null, 'reconciled_at' => null,
                'unreconciled_by' => auth('web')->id(), 'unreconciled_at' => now(),
                'unreconcile_reason' => $reason, 'match_confidence' => null,
            ]);
            $this->event($line, 'unreconciled', ['reason' => $reason, 'reversals' => array_values($reversedPostings)]);
        }, 3);
    }

    private function closePostingMatches(string $postingNo, string $reversalNo, string $reason): void
    {
        $matches = BankReconciliationMatch::where('posting_unique_no', $postingNo)->active()->lockForUpdate()->get();
        foreach ($matches as $relatedMatch) {
            $this->reverseDocumentAllocation($relatedMatch);
            Transaction::whereKey($relatedMatch->transaction_id)->update(['bank_statement_line_id' => null]);
            $relatedMatch->update([
                'reversed_by' => auth('web')->id(), 'reversed_at' => now(),
                'reversal_reason' => $reason, 'reversal_unique_no' => $reversalNo,
            ]);

            $relatedLine = BankStatementLine::whereKey($relatedMatch->bank_statement_line_id)->lockForUpdate()->first();
            if ($relatedLine) {
                $this->assertEditable($relatedLine);
                $relatedLine->update([
                    'status' => 'Unreconciled', 'remaining_amount' => $relatedLine->amount,
                    'reconciled_by' => null, 'reconciled_at' => null,
                    'unreconciled_by' => auth('web')->id(), 'unreconciled_at' => now(),
                    'unreconcile_reason' => $reason, 'match_confidence' => null,
                ]);
                $this->event($relatedLine, 'unreconciled_with_posting', [
                    'reason' => $reason, 'posting' => $postingNo, 'reversal' => $reversalNo,
                ]);
            }
        }
    }

    private function reversePosting(BankStatementLine $line, string $postingNo, string $reason): string
    {
        $reversalNo = 'BRV-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(6));
        $userId = auth('web')->id() ?: 1;
        $reversalDate = now()->toDateString();
        $period = AccountingPeriod::findPeriodForDate($reversalDate, $line->company_id);
        if (! $period || $period->is_locked) {
            throw ValidationException::withMessages(['date' => 'A generated posting can only be reversed into an unlocked accounting period.']);
        }
        $originals = Transaction::where('company_id', $line->company_id)->where('unique_no', $postingNo)->lockForUpdate()->get();

        foreach ($originals as $original) {
            Transaction::create([
                'company_id' => $original->company_id, 'accounting_period_id' => $period->id,
                'account_id' => $original->account_id, 'vendor_id' => $original->vendor_id,
                'customer_id' => $original->customer_id, 'reference_id' => $original->reference_id,
                'unique_no' => $reversalNo, 'invoice_no' => $original->invoice_no, 'date' => $reversalDate,
                'type' => $original->type . '_reversal', 'source_system' => 'bank_reconciliation',
                'source_reference' => $postingNo, 'reversal_of_transaction_id' => $original->id,
                'reversal_of_unique_no' => $postingNo, 'correction_type' => 'reversal',
                'description' => 'Reversal: ' . $reason . ' | ' . $original->description,
                'debit' => $original->credit, 'credit' => $original->debit,
                'created_by' => $userId, 'updated_by' => $userId, 'status' => 1,
                'payable_id' => $original->payable_id, 'payable_type' => $original->payable_type,
            ]);
        }

        $journals = JournalEntry::where('company_id', $line->company_id)->where('unique_no', $postingNo)->get();
        foreach ($journals as $journal) {
            $copy = $journal->replicate();
            $copy->unique_no = $reversalNo;
            $copy->accounting_period_id = $period->id;
            $copy->date = $reversalDate;
            $copy->narration = 'Reversal: ' . $reason;
            $copy->description = 'Reversal of ' . $postingNo . ': ' . $journal->description;
            $copy->debit = $journal->credit;
            $copy->credit = $journal->debit;
            $copy->created_by = $userId;
            $copy->updated_by = $userId;
            $copy->posting_status = 'posted';
            $copy->save();
        }

        return $reversalNo;
    }

    private function reverseDocumentAllocation(BankReconciliationMatch $match): void
    {
        $document = $match->matchable;
        if ($document instanceof PayRun) {
            $document->update(['status' => 'posted']);
            Payslip::where('pay_run_id', $document->id)->update(['status' => 'processed']);
            return;
        }

        if (! $document instanceof Sale && ! $document instanceof Bill) return;

        $paid = max(0, (float) $document->paid_amount - (float) $match->amount);
        $total = (float) ($document->total_after_discount ?? $document->total ?? 0);
        $document->paid_amount = $paid;
        $document->remaining_amount = max(0, $total - $paid);
        $document->approval_status = 'approved';
        $document->payment_status = $paid <= 0.005 ? 'unpaid' : ($document->remaining_amount <= 0.005 ? 'paid' : 'partial');
        $document->save();
    }

    public function event(BankStatementLine $line, string $event, array $metadata = []): void
    {
        BankReconciliationEvent::create([
            'company_id' => $line->company_id, 'bank_statement_line_id' => $line->id,
            'bank_statement_import_id' => $line->bank_statement_import_id,
            'event' => $event, 'metadata' => $metadata, 'user_id' => auth('web')->id(),
        ]);
    }

    public function review(BankStatementImport $statement, ?string $note): void
    {
        if ($statement->status === 'finalized') throw ValidationException::withMessages(['statement' => 'Finalized statements cannot be reviewed again.']);
        if ($statement->imported_by && (int) $statement->imported_by === (int) auth('web')->id()) {
            throw ValidationException::withMessages(['statement' => 'Maker-checker control requires a different user to review the imported statement.']);
        }
        $statement->update(['status' => 'reviewed', 'review_note' => $note, 'reviewed_by' => auth('web')->id(), 'reviewed_at' => now()]);
        BankReconciliationEvent::create(['company_id' => $statement->company_id, 'bank_statement_import_id' => $statement->id,
            'event' => 'reviewed', 'metadata' => ['note' => $note], 'user_id' => auth('web')->id()]);
    }

    public function finalize(BankStatementImport $statement): void
    {
        DB::transaction(function () use ($statement) {
            $statement = BankStatementImport::whereKey($statement->id)->lockForUpdate()->firstOrFail();
            if ($statement->status !== 'reviewed') throw ValidationException::withMessages(['statement' => 'The statement must be reviewed before finalization.']);
            if ($statement->reviewed_by && (int) $statement->reviewed_by === (int) auth('web')->id()) throw ValidationException::withMessages(['statement' => 'A different authorized user must finalize the reviewed statement.']);
            if ($statement->lines()->where('status', '!=', 'Reconciled')->exists()) throw ValidationException::withMessages(['statement' => 'All statement lines must be reconciled before finalization.']);
            $calculated = round((float) $statement->opening_balance + (float) $statement->lines()->sum('amount'), 4);
            if (abs($calculated - (float) $statement->closing_balance) >= 0.005) throw ValidationException::withMessages(['closing_balance' => 'Opening balance plus statement movements does not equal the declared closing balance.']);
            $statement->update(['status' => 'finalized', 'finalized_by' => auth('web')->id(), 'finalized_at' => now()]);
            $statement->lines()->update(['is_locked' => true]);
            BankReconciliationEvent::create(['company_id' => $statement->company_id, 'bank_statement_import_id' => $statement->id,
                'event' => 'finalized', 'metadata' => ['line_count' => $statement->line_count], 'user_id' => auth('web')->id()]);
        }, 3);
    }

    public function reopen(BankStatementImport $statement, string $reason): void
    {
        DB::transaction(function () use ($statement, $reason) {
            if ($statement->status !== 'finalized') throw ValidationException::withMessages(['statement' => 'Only a finalized statement can be reopened.']);
            $statement->update(['status' => 'reopened', 'reopened_by' => auth('web')->id(), 'reopened_at' => now(), 'reopen_reason' => $reason]);
            $statement->lines()->update(['is_locked' => false]);
            BankReconciliationEvent::create(['company_id' => $statement->company_id, 'bank_statement_import_id' => $statement->id,
                'event' => 'reopened', 'metadata' => ['reason' => $reason], 'user_id' => auth('web')->id()]);
        });
    }
}
