<?php

namespace App\Services\Account;

use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\OpeningBalanceBatch;
use App\Models\Transaction;
use App\Models\User;
use App\Support\AccountingConstants;
use App\Support\AccountingDate;
use App\Support\DecimalAmount;
use App\Support\OpeningBalanceAccountEligibility;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class OpeningBalanceConversionService
{
    public function __construct(
        private readonly AccountingPeriodResolver $periodResolver,
        private readonly OpeningBalanceControlReconciliationService $controlReconciliation
    ) {
    }

    public function recalculateTotals(OpeningBalanceBatch $batch): void
    {
        $totals = $batch->lines()
            ->selectRaw('COALESCE(SUM(debit), 0) as debit_total, COALESCE(SUM(credit), 0) as credit_total')
            ->first();

        $batch->update([
            'total_debit' => $totals?->debit_total ?? 0,
            'total_credit' => $totals?->credit_total ?? 0,
        ]);
    }

    public function validateBatch(OpeningBalanceBatch $batch): array
    {
        $errors = [];
        $batch->load('lines.account');

        foreach ($batch->lines->groupBy('account_id')->filter(fn ($lines) => $lines->count() > 1) as $accountId => $lines) {
            $errors[] = 'Account ' . $accountId . ' appears more than once. Each conversion account must have one target balance.';
        }

        if ($batch->lines->isEmpty()) {
            $errors[] = 'At least one opening balance line is required.';
        }

        if (! $batch->supersedes_batch_id && ($batch->batch_kind ?: 'conversion') === 'conversion') {
            $hasPostedSet = OpeningBalanceBatch::query()
                ->where('company_id', $batch->company_id)
                ->whereKeyNot($batch->id)
                ->whereDate('closing_date', $batch->closing_date)
                ->where('status', AccountingConstants::BATCH_STATUS_POSTED)
                ->whereIn('batch_kind', ['conversion', 'revision'])
                ->exists();

            if ($hasPostedSet) {
                $errors[] = 'Conversion balances already exist for this balance date. Continue or revise the existing conversion balance set; each save replaces its previous conversion journal.';
            }
        }

        foreach ($batch->lines as $line) {
            $debit = DecimalAmount::normalize($line->debit);
            $credit = DecimalAmount::normalize($line->credit);

            if ((! DecimalAmount::isZero($debit) && ! DecimalAmount::isZero($credit)) || (DecimalAmount::isZero($debit) && DecimalAmount::isZero($credit))) {
                $errors[] = "Line {$line->id}: enter either a debit or a credit amount greater than zero.";
            }

            if (! $line->account) {
                $errors[] = "Line {$line->id}: account must be an active posting account.";
            } elseif ($reason = OpeningBalanceAccountEligibility::reason($line->account)) {
                $errors[] = "Line {$line->id}: {$reason}";
            }

            if ($line->account && Schema::hasColumn('chart_of_accounts', 'company_id') && (int) $line->account->company_id !== (int) $batch->company_id) {
                $errors[] = "Line {$line->id}: account belongs to another company.";
            }
        }

        if (! DecimalAmount::equals($batch->total_debit, $batch->total_credit)) {
            $errors[] = 'Total Debit must equal Total Credit before posting.';
        }

        $errors = array_merge($errors, $this->controlReconciliation->validationErrors($batch));

        try {
            $this->periodResolver->resolve((int) $batch->company_id, $batch->conversion_date->toDateString());
        } catch (ValidationException $exception) {
            $errors = array_merge($errors, $exception->errors()['date'] ?? ['Invalid conversion accounting period.']);
        }

        return $errors;
    }

    public function markValidated(OpeningBalanceBatch $batch): void
    {
        $this->applyHistoricalAdjustment($batch);
        $this->recalculateTotals($batch);
        $errors = $this->validateBatch($batch->refresh());

        if ($errors) {
            throw ValidationException::withMessages(['batch' => $errors]);
        }

        $batch->update([
            'status' => AccountingConstants::BATCH_STATUS_VALIDATED,
            'reviewed_by' => auth('web')->id(),
            'reviewed_at' => now(),
            'approved_by' => null,
            'approved_at' => null,
            'updated_by' => auth('web')->id(),
        ]);
    }

    public function applyHistoricalAdjustment(OpeningBalanceBatch $batch): void
    {
        if ($batch->status !== AccountingConstants::BATCH_STATUS_DRAFT) {
            return;
        }

        $historicalAdjustment = ChartOfAccount::query()
            ->forCompany((int) $batch->company_id)
            ->where('account_number', '399')
            ->where('system_account_type', AccountingConstants::SYSTEM_OPENING_BALANCE_EQUITY)
            ->first();

        $totals = $batch->lines()
            ->when($historicalAdjustment, fn ($query) => $query->where('account_id', '<>', $historicalAdjustment->id))
            ->selectRaw('COALESCE(SUM(debit), 0) AS debit_total, COALESCE(SUM(credit), 0) AS credit_total')
            ->first();
        $debits = DecimalAmount::normalize($totals?->debit_total ?? 0);
        $credits = DecimalAmount::normalize($totals?->credit_total ?? 0);

        if ($historicalAdjustment) {
            $batch->lines()->where('account_id', $historicalAdjustment->id)->delete();
        }

        $comparison = bccomp($debits, $credits, 4);
        if ($comparison === 0) {
            return;
        }
        if (! $historicalAdjustment) {
            throw ValidationException::withMessages([
                'batch' => 'Account 399 must exist and be designated as Opening Balance Equity so conversion differences can post to Historical Adjustment.',
            ]);
        }

        $difference = $comparison > 0
            ? bcsub($debits, $credits, 4)
            : bcsub($credits, $debits, 4);
        $batch->lines()->create([
            'company_id' => $batch->company_id,
            'account_id' => $historicalAdjustment->id,
            'debit' => $comparison < 0 ? $difference : '0.0000',
            'credit' => $comparison > 0 ? $difference : '0.0000',
            'description' => 'System-calculated historical adjustment',
            'source_reference' => $batch->reference,
        ]);
    }

    public function approve(OpeningBalanceBatch $batch, int $userId): void
    {
        if ($batch->status !== AccountingConstants::BATCH_STATUS_VALIDATED) {
            throw ValidationException::withMessages(['status' => 'Only a validated opening balance batch can be approved.']);
        }

        $approver = User::withoutGlobalScopes()
            ->setEagerLoads([])
            ->with('role')
            ->whereKey($userId)
            ->where('company_id', $batch->company_id)
            ->first();
        if (! $approver) {
            throw ValidationException::withMessages(['status' => 'The approver must be an active user of the same company.']);
        }

        $roleName = strtolower(trim((string) $approver->role?->name));
        $canApprove = in_array($roleName, ['super admin', 'accountant manager'], true);
        if ($batch->created_by && (int) $batch->created_by === $userId && ! $canApprove) {
            throw ValidationException::withMessages(['status' => 'The preparer cannot approve the same opening balance batch unless they are a Super Admin or Accountant Manager.']);
        }
        if (! $canApprove) {
            throw ValidationException::withMessages(['status' => 'Only a Super Admin or Accountant Manager can approve an opening balance batch.']);
        }
        $batch->update([
            'status' => AccountingConstants::BATCH_STATUS_APPROVED,
            'approved_by' => $userId,
            'approved_at' => now(),
            'updated_by' => $userId,
        ]);
    }

    public function post(OpeningBalanceBatch $batch): void
    {
        DB::transaction(function () use ($batch) {
            $lockedBatch = OpeningBalanceBatch::where('company_id', $batch->company_id)
                ->whereKey($batch->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedBatch->status !== AccountingConstants::BATCH_STATUS_APPROVED) {
                throw ValidationException::withMessages(['status' => 'Only an approved opening balance batch can be posted.']);
            }

            if ($lockedBatch->transactions()->exists()) {
                throw ValidationException::withMessages(['status' => 'This conversion batch has already created ledger transactions.']);
            }

            $this->recalculateTotals($lockedBatch);
            $lockedBatch->refresh();
            $errors = $this->validateBatch($lockedBatch);

            if ($errors) {
                throw ValidationException::withMessages(['batch' => $errors]);
            }

            $postingDate = AccountingDate::fromIso($lockedBatch->conversion_date->toDateString());
            $period = $this->periodResolver->resolve((int) $lockedBatch->company_id, $postingDate);
            if ($lockedBatch->supersedes_batch_id) {
                $this->reverseSupersededBatch($lockedBatch, $postingDate, (int) auth('web')->id(), $period->id);
            }
            $journalReference = $lockedBatch->journal_unique_no ?: $lockedBatch->reference;
            $targetLines = $lockedBatch->lines()->get()->keyBy('account_id');
            $currentBalances = DB::table('transactions')
                ->where('company_id', $lockedBatch->company_id)
                ->where('status', 1)
                ->whereNull('deleted_at')
                ->whereDate('date', '<=', $postingDate)
                ->whereNotNull('account_id')
                ->groupBy('account_id')
                ->selectRaw('account_id, COALESCE(SUM(debit), 0) - COALESCE(SUM(credit), 0) AS net_debit')
                ->pluck('net_debit', 'account_id');
            $accounts = ChartOfAccount::query()
                ->forCompany((int) $lockedBatch->company_id)
                ->where('type', 'Account')
                ->orderBy('id')
                ->get()
                ->filter(fn (ChartOfAccount $account) => OpeningBalanceAccountEligibility::isEligible($account));
            $rows = [];

            foreach ($accounts as $account) {
                $line = $targetLines->get($account->id);
                $targetNetDebit = bcsub((string) ($line?->debit ?? 0), (string) ($line?->credit ?? 0), 4);
                $currentNetDebit = (string) ($currentBalances[$account->id] ?? '0');
                $delta = bcsub($targetNetDebit, $currentNetDebit, 4);
                if (bccomp($delta, '0', 4) === 0) {
                    continue;
                }
                if (in_array($account->system_account_type, ['ACCOUNTS_RECEIVABLE', 'ACCOUNTS_PAYABLE'], true)) {
                    throw ValidationException::withMessages([
                        'batch' => $account->name . ' no longer reconciles to its outstanding documents. Revalidate the batch before posting.',
                    ]);
                }

                $rows[] = [
                    'company_id' => $lockedBatch->company_id,
                    'accounting_period_id' => $period->id,
                    'account_id' => $account->id,
                    'reference_id' => (string) $lockedBatch->id,
                    'unique_no' => $journalReference,
                    'date' => $postingDate,
                    'type' => AccountingConstants::TRANSACTION_TYPE_OPENING_BALANCE,
                    'source_system' => $lockedBatch->source_system ?: AccountingConstants::SOURCE_XERO_CONVERSION,
                    'source_reference' => $line?->source_reference ?: $lockedBatch->reference,
                    'posting_idempotency_key' => 'OB:B' . $lockedBatch->id . ':A' . $account->id,
                    'is_opening_balance' => true,
                    'opening_balance_batch_id' => $lockedBatch->id,
                    'description' => $line?->description ?: 'Conversion adjustment to target - ' . $account->name,
                    'debit' => bccomp($delta, '0', 4) > 0 ? $delta : '0.0000',
                    'credit' => bccomp($delta, '0', 4) < 0 ? bcmul($delta, '-1', 4) : '0.0000',
                    'created_by' => auth('web')->id(),
                    'updated_by' => auth('web')->id(),
                    'posted_by' => auth('web')->id(),
                    'posted_at' => now(),
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $rowDebits = DecimalAmount::sum(array_column($rows, 'debit'));
            $rowCredits = DecimalAmount::sum(array_column($rows, 'credit'));
            if (! DecimalAmount::equals($rowDebits, $rowCredits)) {
                throw ValidationException::withMessages([
                    'batch' => 'Conversion adjustments are not balanced (debit ' . $rowDebits . '; credit ' . $rowCredits . '). Review the complete target trial balance.',
                ]);
            }
            if ($rows) {
                Transaction::insert($rows);
            }

            $lockedBatch->update([
                'status' => AccountingConstants::BATCH_STATUS_POSTED,
                'journal_unique_no' => $journalReference,
                'fiscal_year' => $period->name,
                'posted_at' => now(),
                'locked_at' => now(),
                'posted_by' => auth('web')->id(),
                'updated_by' => auth('web')->id(),
            ]);
        });
    }

    public function createRevisionDraft(OpeningBalanceBatch $batch, string $reason, int $userId): OpeningBalanceBatch
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages(['reason' => 'A revision reason is required.']);
        }

        return DB::transaction(function () use ($batch, $reason, $userId) {
            $posted = OpeningBalanceBatch::where('company_id', $batch->company_id)->whereKey($batch->id)->lockForUpdate()->firstOrFail();
            if ($posted->status !== AccountingConstants::BATCH_STATUS_POSTED) {
                throw ValidationException::withMessages(['status' => 'Only a Posted conversion balance set can be revised.']);
            }

            $activePostedCount = OpeningBalanceBatch::where('company_id', $posted->company_id)
                ->whereDate('closing_date', $posted->closing_date)
                ->where('status', AccountingConstants::BATCH_STATUS_POSTED)
                ->whereIn('batch_kind', ['conversion', 'revision'])
                ->lockForUpdate()
                ->get(['id'])
                ->count();
            if ($activePostedCount > 1) {
                throw ValidationException::withMessages([
                    'status' => "{$activePostedCount} legacy Posted conversion sets exist for this balance date. They require an accountant-approved consolidation repair before target balances can be revised safely.",
                ]);
            }

            $existing = OpeningBalanceBatch::where('company_id', $posted->company_id)->where('supersedes_batch_id', $posted->id)->first();
            if ($existing) {
                return $existing;
            }

            $revision = OpeningBalanceBatch::create([
                'company_id' => $posted->company_id,
                'reference' => 'REVISION-OB-' . $posted->id . '-' . now()->format('YmdHis'),
                'idempotency_key' => 'OB-REVISION:B' . $posted->id,
                'source_system' => $posted->source_system,
                'source_file_reference' => $posted->source_file_reference,
                'source_file_hash' => $posted->source_file_hash,
                'batch_kind' => 'revision',
                'supersedes_batch_id' => $posted->id,
                'closing_date' => $posted->closing_date,
                'conversion_date' => $posted->conversion_date,
                'go_live_date' => $posted->go_live_date,
                'fiscal_year' => $posted->fiscal_year,
                'notes' => $reason . ' Supersedes ' . $posted->reference . '.',
                'status' => AccountingConstants::BATCH_STATUS_DRAFT,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            foreach ($posted->lines()->orderBy('id')->get() as $line) {
                $revision->lines()->create([
                    'company_id' => $posted->company_id,
                    'account_id' => $line->account_id,
                    'debit' => $line->debit,
                    'credit' => $line->credit,
                    'description' => $line->description,
                    'source_account_code' => $line->source_account_code,
                    'source_account_name' => $line->source_account_name,
                    'source_reference' => $posted->reference,
                ]);
            }
            $this->recalculateTotals($revision);

            return $revision->refresh();
        });
    }

    public function mergeImportedDraftIntoCurrentSet(OpeningBalanceBatch $importedDraft, int $userId): OpeningBalanceBatch
    {
        $postedSets = OpeningBalanceBatch::where('company_id', $importedDraft->company_id)
            ->whereDate('closing_date', $importedDraft->closing_date)
            ->where('status', AccountingConstants::BATCH_STATUS_POSTED)
            ->whereIn('batch_kind', ['conversion', 'revision'])
            ->orderBy('id')
            ->get();

        if ($postedSets->isEmpty()) {
            return $importedDraft;
        }
        if ($postedSets->count() > 1) {
            throw ValidationException::withMessages([
                'import_file' => $postedSets->count() . ' legacy Posted conversion sets exist for this balance date. Complete an approved consolidation repair before importing revised targets.',
            ]);
        }

        $revision = $this->createRevisionDraft(
            $postedSets->first(),
            'Continue conversion balances from import ' . $importedDraft->reference . '.',
            $userId
        );
        if ($revision->status !== AccountingConstants::BATCH_STATUS_DRAFT) {
            throw ValidationException::withMessages([
                'import_file' => 'An existing conversion revision is already under review. Return it to Draft before merging another import.',
            ]);
        }

        return DB::transaction(function () use ($revision, $importedDraft, $userId) {
            $lockedRevision = OpeningBalanceBatch::whereKey($revision->id)->lockForUpdate()->firstOrFail();
            $lockedImport = OpeningBalanceBatch::whereKey($importedDraft->id)->lockForUpdate()->firstOrFail();

            foreach ($lockedImport->lines()->orderBy('id')->get() as $line) {
                $lockedRevision->lines()->where('account_id', $line->account_id)->delete();
                $lockedRevision->lines()->create([
                    'company_id' => $lockedRevision->company_id,
                    'account_id' => $line->account_id,
                    'debit' => $line->debit,
                    'credit' => $line->credit,
                    'description' => $line->description,
                    'source_account_code' => $line->source_account_code,
                    'source_account_name' => $line->source_account_name,
                    'source_reference' => $line->source_reference ?: $lockedImport->reference,
                ]);
            }

            $lockedRevision->update([
                'source_file_reference' => $lockedImport->source_file_reference,
                'source_file_hash' => $lockedImport->source_file_hash,
                'notes' => trim(($lockedRevision->notes ? $lockedRevision->notes . ' ' : '') . 'Merged import ' . $lockedImport->reference . '.'),
                'updated_by' => $userId,
            ]);
            $lockedImport->update([
                'status' => 'Merged',
                'batch_kind' => 'import_staging',
                'notes' => trim(($lockedImport->notes ? $lockedImport->notes . ' ' : '') . 'Merged into ' . $lockedRevision->reference . '; no ledger journal was posted from this staging batch.'),
                'updated_by' => $userId,
            ]);
            $this->recalculateTotals($lockedRevision);

            return $lockedRevision->refresh();
        });
    }

    private function reverseSupersededBatch(OpeningBalanceBatch $replacement, string $postingDate, int $userId, int $periodId): void
    {
        $original = OpeningBalanceBatch::where('company_id', $replacement->company_id)
            ->whereKey($replacement->supersedes_batch_id)->lockForUpdate()->firstOrFail();
        if ($original->status !== AccountingConstants::BATCH_STATUS_POSTED) {
            throw ValidationException::withMessages(['status' => 'The conversion set being replaced is no longer Posted.']);
        }
        if ($original->conversion_date->toDateString() !== $postingDate) {
            throw ValidationException::withMessages(['conversion_date' => 'A target-balance revision must use the same effective reporting date as the version it replaces.']);
        }

        $transactions = $original->transactions()->whereNull('reversal_of_transaction_id')->where('status', 1)->lockForUpdate()->orderBy('id')->get();
        foreach ($transactions as $line) {
            if (Transaction::withoutGlobalScopes()->where('reversal_of_transaction_id', $line->id)->whereNull('deleted_at')->exists()) {
                throw ValidationException::withMessages(['status' => 'The conversion set being replaced already has reversal transactions.']);
            }
            Transaction::create([
                'company_id' => $original->company_id,
                'accounting_period_id' => $periodId,
                'account_id' => $line->account_id,
                'reference_id' => (string) $original->id,
                'unique_no' => 'REV-' . $original->reference . '-SUPERSEDED',
                'date' => $postingDate,
                'type' => AccountingConstants::TRANSACTION_TYPE_OPENING_BALANCE,
                'source_system' => 'CONVERSION_REVISION',
                'source_reference' => $replacement->reference,
                'posting_idempotency_key' => 'OB-SUPERSEDE:B' . $replacement->id . ':T' . $line->id,
                'is_opening_balance' => true,
                'opening_balance_batch_id' => $original->id,
                'reversal_of_transaction_id' => $line->id,
                'reversal_of_unique_no' => $line->unique_no,
                'correction_type' => 'conversion_revision_reversal',
                'description' => 'Superseded by ' . $replacement->reference,
                'debit' => $line->credit,
                'credit' => $line->debit,
                'created_by' => $userId,
                'updated_by' => $userId,
                'posted_by' => $userId,
                'posted_at' => now(),
                'status' => 1,
            ]);
        }

        $original->update(['status' => AccountingConstants::BATCH_STATUS_REVERSED, 'updated_by' => $userId]);
    }

    public function reverse(OpeningBalanceBatch $batch, string $reversalDate, string $reason): void
    {
        DB::transaction(function () use ($batch, $reversalDate, $reason) {
            $lockedBatch = OpeningBalanceBatch::where('company_id', $batch->company_id)
                ->whereKey($batch->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedBatch->status !== AccountingConstants::BATCH_STATUS_POSTED) {
                throw ValidationException::withMessages(['status' => 'Only Posted conversion batches can be reversed.']);
            }

            $reversalDate = AccountingDate::fromIso($reversalDate);
            if ($reversalDate < $lockedBatch->conversion_date->toDateString()) {
                throw ValidationException::withMessages(['reversal_date' => 'A reversal date cannot be earlier than the original journal date.']);
            }
            if (trim($reason) === '') {
                throw ValidationException::withMessages(['reason' => 'A correction reason is required.']);
            }
            $period = $this->periodResolver->resolve((int) $lockedBatch->company_id, $reversalDate);

            $originalLines = $lockedBatch->transactions()
                ->whereNull('reversal_of_transaction_id')
                ->whereDoesntHave('auditAction')
                ->get();

            foreach ($originalLines as $line) {
                if (Transaction::withoutGlobalScope('uniqueNoNotNull')->where('reversal_of_transaction_id', $line->id)->exists()) {
                    continue;
                }

                Transaction::create([
                    'company_id' => $lockedBatch->company_id,
                    'accounting_period_id' => $period->id,
                    'account_id' => $line->account_id,
                    'reference_id' => (string) $lockedBatch->id,
                    'unique_no' => 'REV-' . $lockedBatch->reference,
                    'date' => $reversalDate,
                    'type' => AccountingConstants::TRANSACTION_TYPE_OPENING_BALANCE,
                    'source_system' => AccountingConstants::SOURCE_XERO_CONVERSION,
                    'source_reference' => $lockedBatch->reference,
                    'posting_idempotency_key' => 'REV:OB:B' . $lockedBatch->id . ':L' . $line->id,
                    'is_opening_balance' => true,
                    'opening_balance_batch_id' => $lockedBatch->id,
                    'reversal_of_transaction_id' => $line->id,
                    'reversal_of_unique_no' => $line->unique_no,
                    'correction_type' => 'reversal',
                    'description' => $reason,
                    'debit' => $line->credit,
                    'credit' => $line->debit,
                    'created_by' => auth('web')->id(),
                    'updated_by' => auth('web')->id(),
                    'posted_by' => auth('web')->id(),
                    'posted_at' => now(),
                    'status' => 1,
                ]);
            }

            $lockedBatch->update([
                'status' => AccountingConstants::BATCH_STATUS_REVERSED,
                'updated_by' => auth('web')->id(),
            ]);
        });
    }

    public function correctPostingDate(OpeningBalanceBatch $batch, string $targetDate, string $reason, int $userId): OpeningBalanceBatch
    {
        $targetDate = AccountingDate::fromIso($targetDate);
        $reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages(['reason' => 'A correction reason is required.']);
        }

        return DB::transaction(function () use ($batch, $targetDate, $reason, $userId) {
            $lockedBatch = OpeningBalanceBatch::where('company_id', $batch->company_id)
                ->whereKey($batch->id)
                ->lockForUpdate()
                ->firstOrFail();
            $originalDate = AccountingDate::fromIso($lockedBatch->conversion_date->toDateString());
            $idempotencyKey = "OB-DATE-CORRECTION:B{$lockedBatch->id}:{$targetDate}";

            $existing = OpeningBalanceBatch::where('company_id', $lockedBatch->company_id)
                ->where('idempotency_key', $idempotencyKey)
                ->first();
            if ($existing) {
                return $existing;
            }
            if ($lockedBatch->status !== AccountingConstants::BATCH_STATUS_POSTED) {
                throw ValidationException::withMessages(['status' => 'Only a Posted opening balance batch can have its posting date corrected.']);
            }
            if ($targetDate === $originalDate) {
                throw ValidationException::withMessages(['target_date' => 'The corrected posting date must differ from the original date.']);
            }

            $originalPeriod = $this->periodResolver->resolve((int) $lockedBatch->company_id, $originalDate);
            $targetPeriod = $this->periodResolver->resolve((int) $lockedBatch->company_id, $targetDate);
            $originalTransactions = $lockedBatch->transactions()
                ->whereNull('reversal_of_transaction_id')
                ->where('status', 1)
                ->lockForUpdate()
                ->orderBy('id')
                ->get();
            if ($originalTransactions->isEmpty()) {
                throw ValidationException::withMessages(['status' => 'The posted batch has no original ledger transactions to correct.']);
            }
            if (! DecimalAmount::equals(
                DecimalAmount::sum($originalTransactions->pluck('debit')),
                DecimalAmount::sum($originalTransactions->pluck('credit'))
            )) {
                throw ValidationException::withMessages(['status' => 'The original batch is not balanced and cannot be date-corrected automatically.']);
            }
            if (Transaction::withoutGlobalScopes()->whereIn('reversal_of_transaction_id', $originalTransactions->pluck('id'))->whereNull('deleted_at')->exists()) {
                throw ValidationException::withMessages(['status' => 'The original batch already has reversal transactions.']);
            }

            $reference = 'DATE-CORR-' . $lockedBatch->id . '-' . str_replace('-', '', $targetDate);
            $replacement = OpeningBalanceBatch::create([
                'company_id' => $lockedBatch->company_id,
                'reference' => $reference,
                'idempotency_key' => $idempotencyKey,
                'journal_unique_no' => $reference,
                'source_system' => 'DATE_CORRECTION',
                'batch_kind' => 'date_correction',
                'source_file_reference' => $lockedBatch->reference,
                'conversion_date' => $targetDate,
                'closing_date' => $targetDate,
                'fiscal_year' => $targetPeriod->name,
                'status' => AccountingConstants::BATCH_STATUS_POSTED,
                'notes' => "{$reason} Original batch: {$lockedBatch->reference}; original date: {$originalDate}.",
                'total_debit' => DecimalAmount::sum($originalTransactions->pluck('debit')),
                'total_credit' => DecimalAmount::sum($originalTransactions->pluck('credit')),
                'reviewed_by' => $userId,
                'approved_by' => $userId,
                'posted_by' => $userId,
                'posted_at' => now(),
                'locked_at' => now(),
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            foreach ($originalTransactions as $original) {
                Transaction::create([
                    'company_id' => $lockedBatch->company_id,
                    'accounting_period_id' => $originalPeriod->id,
                    'account_id' => $original->account_id,
                    'reference_id' => (string) $lockedBatch->id,
                    'unique_no' => 'REV-' . $lockedBatch->reference . '-DATE-CORR',
                    'date' => $originalDate,
                    'type' => AccountingConstants::TRANSACTION_TYPE_OPENING_BALANCE,
                    'source_system' => 'DATE_CORRECTION',
                    'source_reference' => $lockedBatch->reference,
                    'posting_idempotency_key' => "OB-DATE-CORR:REV:T{$original->id}:{$targetDate}",
                    'is_opening_balance' => true,
                    'opening_balance_batch_id' => $lockedBatch->id,
                    'reversal_of_transaction_id' => $original->id,
                    'reversal_of_unique_no' => $original->unique_no,
                    'correction_type' => 'date_correction_reversal',
                    'description' => $reason,
                    'debit' => $original->credit,
                    'credit' => $original->debit,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                    'posted_by' => $userId,
                    'posted_at' => now(),
                    'status' => 1,
                ]);

                $line = $replacement->lines()->create([
                    'company_id' => $lockedBatch->company_id,
                    'account_id' => $original->account_id,
                    'debit' => $original->debit,
                    'credit' => $original->credit,
                    'description' => $original->description,
                    'source_reference' => $lockedBatch->reference,
                ]);
                Transaction::create([
                    'company_id' => $lockedBatch->company_id,
                    'accounting_period_id' => $targetPeriod->id,
                    'account_id' => $original->account_id,
                    'reference_id' => (string) $replacement->id,
                    'unique_no' => $reference,
                    'date' => $targetDate,
                    'type' => AccountingConstants::TRANSACTION_TYPE_OPENING_BALANCE,
                    'source_system' => 'DATE_CORRECTION',
                    'source_reference' => $lockedBatch->reference,
                    'posting_idempotency_key' => "OB-DATE-CORR:B{$replacement->id}:L{$line->id}",
                    'is_opening_balance' => true,
                    'opening_balance_batch_id' => $replacement->id,
                    'correction_type' => 'date_correction_replacement',
                    'description' => $original->description ?: $reason,
                    'debit' => $original->debit,
                    'credit' => $original->credit,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                    'posted_by' => $userId,
                    'posted_at' => now(),
                    'status' => 1,
                ]);
            }

            $lockedBatch->update([
                'status' => AccountingConstants::BATCH_STATUS_REVERSED,
                'updated_by' => $userId,
            ]);

            return $replacement;
        });
    }

    public function copyReversedBatchToDraft(OpeningBalanceBatch $batch, string $targetDate, string $reason, int $userId): OpeningBalanceBatch
    {
        $targetDate = AccountingDate::fromIso($targetDate);
        $reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages(['reason' => 'A replacement reason is required.']);
        }

        return DB::transaction(function () use ($batch, $targetDate, $reason, $userId) {
            $lockedBatch = OpeningBalanceBatch::where('company_id', $batch->company_id)
                ->whereKey($batch->id)
                ->lockForUpdate()
                ->firstOrFail();
            if ($lockedBatch->status !== AccountingConstants::BATCH_STATUS_REVERSED) {
                throw ValidationException::withMessages(['status' => 'Only a Reversed opening balance batch can be copied as a replacement draft.']);
            }

            $idempotencyKey = "OB-REPLACEMENT-DRAFT:B{$lockedBatch->id}:{$targetDate}";
            $existing = OpeningBalanceBatch::where('company_id', $lockedBatch->company_id)
                ->where('idempotency_key', $idempotencyKey)
                ->first();
            if ($existing) {
                return $existing;
            }

            $lines = $lockedBatch->lines()->with('account')->orderBy('id')->get();
            if ($lines->isEmpty()) {
                throw ValidationException::withMessages(['status' => 'The reversed batch has no lines to copy.']);
            }
            $period = AccountingPeriod::withoutGlobalScopes()
                ->where('company_id', $lockedBatch->company_id)
                ->whereDate('start_date', '<=', $targetDate)
                ->whereDate('end_date', '>=', $targetDate)
                ->first();

            $replacement = OpeningBalanceBatch::create([
                'company_id' => $lockedBatch->company_id,
                'reference' => 'REPL-OB-' . $lockedBatch->id . '-' . now()->format('YmdHis'),
                'idempotency_key' => $idempotencyKey,
                'source_system' => 'REVERSAL_REPLACEMENT',
                'batch_kind' => 'replacement',
                'source_file_reference' => $lockedBatch->reference,
                'conversion_date' => $targetDate,
                'closing_date' => $targetDate,
                'fiscal_year' => $period?->name,
                'status' => AccountingConstants::BATCH_STATUS_DRAFT,
                'notes' => "{$reason} Replacement for reversed batch {$lockedBatch->reference}.",
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
            foreach ($lines as $line) {
                $replacement->lines()->create([
                    'company_id' => $lockedBatch->company_id,
                    'account_id' => $line->account_id,
                    'debit' => $line->debit,
                    'credit' => $line->credit,
                    'description' => $line->description,
                    'source_account_code' => $line->source_account_code,
                    'source_account_name' => $line->source_account_name,
                    'source_reference' => $lockedBatch->reference,
                ]);
            }
            $this->recalculateTotals($replacement);

            return $replacement->refresh();
        });
    }
}
