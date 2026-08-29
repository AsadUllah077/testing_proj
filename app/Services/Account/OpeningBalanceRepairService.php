<?php

namespace App\Services\Account;

use App\Models\AccountingRepairItem;
use App\Models\AccountingRepairRun;
use App\Models\ChartOfAccount;
use App\Models\OpeningBalanceBatch;
use App\Models\Transaction;
use App\Support\AccountingConstants;
use App\Support\AccountingDate;
use App\Support\DecimalAmount;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OpeningBalanceRepairService
{
    public function __construct(private readonly AccountingPeriodResolver $periodResolver)
    {
    }

    public function audit(int $companyId, string $cutoverDate, array $parameters, ?int $requestedBy = null): AccountingRepairRun
    {
        $cutoverDate = AccountingDate::fromIso($cutoverDate);
        $run = AccountingRepairRun::withoutGlobalScopes()->create([
            'company_id' => $companyId,
            'repair_type' => 'opening_balances',
            'parameters' => array_merge($parameters, ['cutover_date' => $cutoverDate]),
            'dry_run' => true,
            'status' => 'analysing',
            'requested_by' => $requestedBy,
            'started_at' => now(),
        ]);

        $transactions = Transaction::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query->where('is_opening_balance', true)
                    ->orWhere('type', AccountingConstants::TRANSACTION_TYPE_OPENING_BALANCE)
                    ->orWhere('unique_no', 'like', 'OB-%')
                    ->orWhere('unique_no', 'like', 'REV-OB-%');
            })
            ->orderBy('date')->orderBy('id')->get();

        $this->auditJournals($run, $transactions, $cutoverDate);
        $this->auditBatches($run, $companyId, $cutoverDate);
        $this->auditSystemAccounts($run, $companyId);
        $this->auditAccountMetadata($run, $companyId, $transactions);

        $counts = $run->items()->selectRaw('severity, COUNT(*) AS aggregate')->groupBy('severity')->pluck('aggregate', 'severity')->all();
        $blocked = (int) ($counts['blocked'] ?? 0);
        $run->update([
            'status' => $blocked ? 'review_required' : 'ready',
            'completed_at' => now(),
            'summary' => ['issues' => array_sum($counts), 'by_severity' => $counts, 'accounting_rows_changed' => 0],
        ]);

        return $run->fresh('items');
    }

    public function apply(AccountingRepairRun $plan, int $approvedBy, int $executedBy, string $reason): AccountingRepairRun
    {
        if ($approvedBy === $executedBy) {
            throw ValidationException::withMessages(['approved_by' => 'The repair approver and operator must be different users.']);
        }
        if (! $plan->dry_run || ! in_array($plan->status, ['ready', 'partially_repaired'], true)) {
            throw ValidationException::withMessages(['plan' => 'The selected dry-run plan is not approved for automatic repair.']);
        }
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'A correction reason is required.']);
        }
        if ($plan->items()->where('severity', 'blocked')->exists()) {
            throw ValidationException::withMessages(['plan' => 'Blocked issues require accountant review before any automatic repair.']);
        }

        $active = AccountingRepairRun::withoutGlobalScopes()
            ->where('company_id', $plan->company_id)->where('dry_run', false)->where('status', 'running')->exists();
        if ($active) {
            throw ValidationException::withMessages(['plan' => 'Another accounting repair is already active for this company.']);
        }

        $run = AccountingRepairRun::withoutGlobalScopes()->create([
            'company_id' => $plan->company_id,
            'repair_type' => 'opening_balances',
            'parameters' => array_merge($plan->parameters, ['plan_id' => $plan->id, 'reason' => $reason]),
            'dry_run' => false,
            'status' => 'running',
            'backup_reference' => null,
            'requested_by' => $executedBy,
            'approved_by' => $approvedBy,
            'started_at' => now(),
        ]);

        $summary = ['repaired' => 0, 'already_repaired' => 0, 'review_required' => 0, 'failed' => 0];
        foreach ($plan->items()->orderBy('id')->get() as $plannedItem) {
            $item = $run->items()->create($plannedItem->only([
                'issue_type', 'severity', 'entity_type', 'entity_id', 'related_entity_ids', 'before_snapshot',
                'authoritative_target', 'proposed_action', 'idempotency_key',
            ]));
            try {
                $result = DB::transaction(fn () => $this->applyItem($run, $item, $approvedBy, $reason));
                $summary[$result] = ($summary[$result] ?? 0) + 1;
            } catch (\Throwable $exception) {
                $item->update(['result' => 'failed', 'error_message' => $exception->getMessage()]);
                $summary['failed']++;
            }
        }

        $run->update([
            'status' => $summary['failed'] ? 'completed_with_errors' : 'completed',
            'completed_at' => now(),
            'summary' => $summary,
        ]);

        return $run->fresh('items');
    }

    private function auditJournals(AccountingRepairRun $run, Collection $transactions, string $cutoverDate): void
    {
        $journals = $transactions->filter(fn ($line) => $line->unique_no)->groupBy('unique_no');
        $fingerprints = [];

        foreach ($journals as $reference => $lines) {
            $debit = DecimalAmount::sum($lines->pluck('debit'));
            $credit = DecimalAmount::sum($lines->pluck('credit'));
            $snapshot = $this->snapshot($lines);

            $duplicateLines = $lines->groupBy(fn ($line) => implode('|', [
                $line->account_id, DecimalAmount::normalize($line->debit), DecimalAmount::normalize($line->credit), trim((string) $line->description),
            ]))->filter(fn ($matches) => $matches->count() > 1);
            if ($duplicateLines->isNotEmpty()) {
                $this->item($run, 'EXACT_DUPLICATE_JOURNAL_LINES', 'blocked', 'journal', $reference, $snapshot, 'MANUAL_ACCOUNTANT_REVIEW_REQUIRED');
            }

            if (! DecimalAmount::equals($debit, $credit)) {
                $this->item($run, 'POSTED_UNBALANCED_JOURNAL', 'blocked', 'journal', $reference, $snapshot, 'MANUAL_ACCOUNTANT_REVIEW_REQUIRED');
            }
            preg_match_all('/(?:19|20)\d{2}/', (string) $reference, $referenceYears);
            $periodName = optional(\App\Models\AccountingPeriod::withoutGlobalScopes()->find($lines->first()->accounting_period_id))->name;
            if ($referenceYears[0] && $periodName && collect($referenceYears[0])->contains(fn ($year) => ! str_contains((string) $periodName, $year))) {
                $this->item($run, 'JOURNAL_REFERENCE_INDICATES_WRONG_FISCAL_YEAR', 'review_required', 'journal', $reference, $snapshot, 'PRESERVE_REFERENCE_AND_REVIEW');
            }
            foreach ($lines as $line) {
                if ((! DecimalAmount::isZero($line->debit) && ! DecimalAmount::isZero($line->credit)) || (DecimalAmount::isZero($line->debit) && DecimalAmount::isZero($line->credit))) {
                    $this->item($run, 'INVALID_JOURNAL_LINE', 'blocked', Transaction::class, $line->id, [$line->toArray()], 'MANUAL_ACCOUNTANT_REVIEW_REQUIRED');
                }
                if (! $line->company_id || ! $line->account_id) {
                    $this->item($run, 'MISSING_COMPANY_OR_ACCOUNT', 'blocked', Transaction::class, $line->id, [$line->toArray()], 'MANUAL_ACCOUNTANT_REVIEW_REQUIRED');
                }
                if (! $line->opening_balance_batch_id) {
                    $this->item($run, 'OPENING_JOURNAL_WITHOUT_BATCH', 'review_required', Transaction::class, $line->id, [$line->toArray()], 'MANUAL_ACCOUNTANT_REVIEW_REQUIRED');
                }
                $account = ChartOfAccount::withTrashed()->withoutGlobalScopes()->find($line->account_id);
                if ($account && (int) $account->company_id !== (int) $line->company_id) {
                    $this->item($run, 'CROSS_COMPANY_ACCOUNT', 'blocked', Transaction::class, $line->id, [$line->toArray()], 'MANUAL_ACCOUNTANT_REVIEW_REQUIRED');
                }
                if ($account && ($account->system_account_type === 'CURRENT_YEAR_EARNINGS' || in_array($account->category, AccountingConstants::PROFIT_AND_LOSS_CATEGORIES, true))) {
                    $this->item($run, 'OPENING_BALANCE_TO_CURRENT_EARNINGS_OR_PNL', 'blocked', Transaction::class, $line->id, [$line->toArray()], 'MANUAL_ACCOUNTANT_REVIEW_REQUIRED');
                }
                $period = $line->accounting_period_id ? \App\Models\AccountingPeriod::withoutGlobalScopes()->find($line->accounting_period_id) : null;
                $date = $line->date?->toDateString();
                if (! $period || (int) $period->company_id !== (int) $line->company_id || $date < $period->start_date->toDateString() || $date > $period->end_date->toDateString()) {
                    $this->item($run, 'WRONG_ACCOUNTING_PERIOD', 'blocked', Transaction::class, $line->id, [$line->toArray()], 'MANUAL_ACCOUNTANT_REVIEW_REQUIRED');
                }
                if ($line->reversal_of_transaction_id) {
                    $original = $transactions->firstWhere('id', $line->reversal_of_transaction_id)
                        ?: Transaction::withoutGlobalScopes()->find($line->reversal_of_transaction_id);
                    if (! $original) {
                        $this->item($run, 'ORPHAN_REVERSAL', 'blocked', Transaction::class, $line->id, [$line->toArray()], 'MANUAL_ACCOUNTANT_REVIEW_REQUIRED');
                    } elseif ($date < $original->date->toDateString()) {
                        $this->item($run, 'REVERSAL_BEFORE_ORIGINAL', 'blocked', Transaction::class, $line->id, [$line->toArray()], 'MANUAL_ACCOUNTANT_REVIEW_REQUIRED');
                    }
                }
                if ($line->posted_at && $line->updated_at && $line->updated_at->greaterThan($line->posted_at)) {
                    $this->item($run, 'POSTED_JOURNAL_EDITED_AFTER_POSTING', 'blocked', Transaction::class, $line->id, [$line->toArray()], 'MANUAL_ACCOUNTANT_REVIEW_REQUIRED');
                }
            }

            if ($lines->pluck('date')->map->toDateString()->contains(CarbonDate::previousDay($cutoverDate))) {
                $this->item($run, 'OPENING_ENTRY_BEFORE_CUTOVER', 'review_required', 'journal', $reference, $snapshot, 'MANUAL_ACCOUNTANT_REVIEW_REQUIRED');
            }

            $fingerprint = $this->fingerprint($lines);
            $fingerprints[$fingerprint][] = (string) $reference;
        }

        $dates = $transactions->pluck('date')->filter()->map->toDateString()->unique();
        if ($dates->contains($cutoverDate) && $dates->contains(CarbonDate::previousDay($cutoverDate))) {
            $this->item($run, 'OPENING_ENTRIES_SPLIT_ACROSS_CUTOVER', 'blocked', 'company', $run->company_id, ['dates' => $dates->values()->all()], 'MANUAL_ACCOUNTANT_REVIEW_REQUIRED');
        }

        foreach ($fingerprints as $fingerprint => $references) {
            if (count($references) < 2) {
                continue;
            }
            sort($references, SORT_STRING);
            $canonical = array_shift($references);
            $canonicalLines = $journals[$canonical];
            $authoritative = $canonicalLines->contains(fn ($line) => $line->opening_balance_batch_id
                && OpeningBalanceBatch::withoutGlobalScopes()->whereKey($line->opening_balance_batch_id)->whereIn('status', ['Approved', 'Posted', 'Locked'])->exists());
            foreach ($references as $duplicate) {
                $duplicateLines = $journals[$duplicate];
                $isDraft = $duplicateLines->every(fn ($line) => (int) $line->status !== 1);
                $severity = ($isDraft || $authoritative) ? 'safe' : 'blocked';
                $action = $isDraft
                    ? 'SOFT_DELETE_DRAFT_DUPLICATE'
                    : ($authoritative ? 'REVERSE_POSTED_DUPLICATE' : 'MANUAL_ACCOUNTANT_REVIEW_REQUIRED');
                $this->item($run, 'EXACT_DUPLICATE_OPENING_JOURNAL', $severity, 'journal', $duplicate, $this->snapshot($journals[$duplicate]), $action, [$canonical]);
            }
        }

        $multipleReversals = $transactions->whereNotNull('reversal_of_transaction_id')->groupBy('reversal_of_transaction_id')->filter(fn ($lines) => $lines->count() > 1);
        foreach ($multipleReversals as $originalId => $lines) {
            $this->item($run, 'MULTIPLE_ACTIVE_REVERSALS', 'blocked', Transaction::class, $originalId, $this->snapshot($lines), 'MANUAL_ACCOUNTANT_REVIEW_REQUIRED');
        }
    }

    private function auditBatches(AccountingRepairRun $run, int $companyId, string $cutoverDate): void
    {
        $batches = OpeningBalanceBatch::withoutGlobalScopes()->where('company_id', $companyId)->whereDate('conversion_date', $cutoverDate)->get();
        if ($batches->whereIn('status', ['Approved', 'Posted', 'Locked'])->count() > 1) {
            $this->item($run, 'MULTIPLE_ACTIVE_OPENING_BATCHES', 'blocked', OpeningBalanceBatch::class, null, $batches->toArray(), 'MANUAL_ACCOUNTANT_REVIEW_REQUIRED');
        }
        foreach ($batches as $batch) {
            $duplicates = $batch->lines()->selectRaw('account_id, COUNT(*) AS aggregate')->groupBy('account_id')->havingRaw('COUNT(*) > 1')->get();
            if ($duplicates->isNotEmpty()) {
                $this->item($run, 'MULTIPLE_OPENING_LINES_PER_ACCOUNT', 'review_required', OpeningBalanceBatch::class, $batch->id, $duplicates->toArray(), 'MANUAL_ACCOUNTANT_REVIEW_REQUIRED');
            }
            $controlLines = $batch->lines()->whereHas('account', fn ($query) => $query->whereIn('system_account_type', ['ACCOUNTS_RECEIVABLE', 'ACCOUNTS_PAYABLE']))->get();
            if ($controlLines->isNotEmpty()) {
                $this->item($run, 'CONTROL_ACCOUNT_OPENING_REQUIRES_SUBLEDGER_RECONCILIATION', 'review_required', OpeningBalanceBatch::class, $batch->id, $controlLines->toArray(), 'MANUAL_ACCOUNTANT_REVIEW_REQUIRED');
            }
        }

        $obeIds = ChartOfAccount::withoutGlobalScopes()->where('company_id', $companyId)->where('system_account_type', 'OPENING_BALANCE_EQUITY')->pluck('id');
        if ($obeIds->isNotEmpty()) {
            $balance = Transaction::withoutGlobalScopes()->where('company_id', $companyId)->whereIn('account_id', $obeIds)->where('status', 1)->selectRaw('COALESCE(SUM(debit),0) - COALESCE(SUM(credit),0) AS balance')->value('balance');
            if (! DecimalAmount::isSignedZero($balance)) {
                $this->item($run, 'OPENING_BALANCE_EQUITY_REMAINS', 'review_required', ChartOfAccount::class, $obeIds->implode(','), ['balance' => $balance], 'CLEAR_TEMPORARY_OPENING_BALANCE_EQUITY');
            }
        }
    }

    private function auditSystemAccounts(AccountingRepairRun $run, int $companyId): void
    {
        $duplicates = ChartOfAccount::withTrashed()->withoutGlobalScopes()->where('company_id', $companyId)->whereNotNull('system_account_type')
            ->selectRaw('system_account_type, COUNT(*) AS aggregate')->groupBy('system_account_type')->havingRaw('COUNT(*) > 1')->get();
        foreach ($duplicates as $duplicate) {
            $this->item($run, 'DUPLICATE_SYSTEM_ACCOUNT', 'blocked', ChartOfAccount::class, $duplicate->system_account_type, $duplicate->toArray(), 'MANUAL_ACCOUNTANT_REVIEW_REQUIRED');
        }
        $currentEarnings = ChartOfAccount::withTrashed()->withoutGlobalScopes()->where('company_id', $companyId)
            ->where(function ($query) {
                $query->where('system_account_type', 'CURRENT_YEAR_EARNINGS')
                    ->orWhereRaw('LOWER(name) = ?', ['current year earnings']);
            })->get();
        if ($currentEarnings->count() > 1) {
            $this->item($run, 'DUPLICATE_CURRENT_YEAR_EARNINGS_ACCOUNT', 'blocked', ChartOfAccount::class, null, $currentEarnings->toArray(), 'MANUAL_ACCOUNTANT_REVIEW_REQUIRED');
        }
        $misclassifiedObe = ChartOfAccount::withTrashed()->withoutGlobalScopes()->where('company_id', $companyId)
            ->where('system_account_type', 'OPENING_BALANCE_EQUITY')->whereRaw('LOWER(name) = ?', ['current year earnings'])->get();
        if ($misclassifiedObe->isNotEmpty()) {
            $this->item($run, 'OPENING_BALANCE_EQUITY_MISCLASSIFIED_AS_CURRENT_EARNINGS', 'blocked', ChartOfAccount::class, null, $misclassifiedObe->toArray(), 'MANUAL_ACCOUNTANT_REVIEW_REQUIRED');
        }
    }

    private function auditAccountMetadata(AccountingRepairRun $run, int $companyId, Collection $transactions): void
    {
        $accountIds = $transactions->pluck('account_id')->filter()->unique();
        foreach (ChartOfAccount::withoutGlobalScopes()->where('company_id', $companyId)->whereIn('id', $accountIds)->get() as $account) {
            if ((string) $account->initial_balance !== '0.00' && (string) $account->initial_balance !== '0.0000') {
                $this->item($run, 'LEGACY_OPENING_METADATA_PRESENT', 'review_required', ChartOfAccount::class, $account->id, $account->toArray(), 'DEPRECATE_METADATA_AFTER_RECONCILIATION');
            }
        }
    }

    private function applyItem(AccountingRepairRun $run, AccountingRepairItem $item, int $userId, string $reason): string
    {
        if ($item->proposed_action === 'SOFT_DELETE_DRAFT_DUPLICATE') {
            $lines = Transaction::withoutGlobalScopes()->where('company_id', $run->company_id)->where('unique_no', $item->entity_id)
                ->whereNull('deleted_at')->lockForUpdate()->get();
            if ($lines->isEmpty()) {
                $item->update(['result' => 'already_repaired']);
                return 'already_repaired';
            }
            if ($lines->contains(fn ($line) => (int) $line->status === 1)) {
                throw new \RuntimeException('A planned draft duplicate is now posted; automatic deletion is blocked.');
            }
            foreach ($lines as $line) $line->delete();
            $item->update(['result' => 'repaired', 'after_snapshot' => ['soft_deleted_ids' => $lines->pluck('id')->all()]]);
            return 'repaired';
        }
        if ($item->proposed_action !== 'REVERSE_POSTED_DUPLICATE') {
            $item->update(['result' => 'review_required']);
            return 'review_required';
        }
        $planId = $run->parameters['plan_id'];
        $stableSource = 'REPAIR-PLAN:' . $planId . ':KEY:' . $item->idempotency_key;
        $existing = Transaction::withoutGlobalScopes()->where('company_id', $run->company_id)
            ->where('source_reference', $stableSource)->exists();
        if ($existing) {
            $item->update(['result' => 'already_repaired']);
            return 'already_repaired';
        }

        $lines = Transaction::withoutGlobalScopes()->where('company_id', $run->company_id)->where('unique_no', $item->entity_id)
            ->whereNull('deleted_at')->lockForUpdate()->get();
        if ($lines->isEmpty() || $lines->contains(fn ($line) => (int) $line->status !== 1)) {
            throw new \RuntimeException('The planned posted duplicate no longer matches the current database state.');
        }
        $date = $lines->max(fn ($line) => $line->date->toDateString());
        $period = $this->periodResolver->resolve((int) $run->company_id, $date);
        $reference = 'REPAIR-REV-' . $run->id . '-' . $item->id;
        foreach ($lines as $line) {
            Transaction::withoutGlobalScopes()->create([
                'company_id' => $run->company_id,
                'accounting_period_id' => $period->id,
                'account_id' => $line->account_id,
                'unique_no' => $reference,
                'date' => $date,
                'type' => AccountingConstants::TRANSACTION_TYPE_OPENING_BALANCE,
                'source_system' => 'ACCOUNTING_REPAIR',
                'source_reference' => $stableSource,
                'posting_idempotency_key' => 'REPAIR:PLAN:' . $planId . ':KEY:' . $item->idempotency_key . ':LINE:' . $line->id,
                'is_opening_balance' => true,
                'opening_balance_batch_id' => $line->opening_balance_batch_id,
                'reversal_of_transaction_id' => $line->id,
                'reversal_of_unique_no' => $line->unique_no,
                'correction_type' => 'repair_reversal',
                'accounting_repair_run_id' => $run->id,
                'description' => $reason,
                'debit' => $line->credit,
                'credit' => $line->debit,
                'created_by' => $userId,
                'updated_by' => $userId,
                'posted_by' => $userId,
                'posted_at' => now(),
                'status' => 1,
            ]);
        }
        $after = Transaction::withoutGlobalScopes()->where('unique_no', $reference)->get();
        if (! DecimalAmount::equals(DecimalAmount::sum($after->pluck('debit')), DecimalAmount::sum($after->pluck('credit')))) {
            throw new \RuntimeException('Generated correction journal is not balanced.');
        }
        $item->update(['result' => 'repaired', 'after_snapshot' => $this->snapshot($after)]);
        return 'repaired';
    }

    private function fingerprint(Collection $lines): string
    {
        $first = $lines->first();
        $normalized = $lines->map(fn ($line) => [
            'account_id' => (int) $line->account_id,
            'debit' => DecimalAmount::normalize($line->debit),
            'credit' => DecimalAmount::normalize($line->credit),
            'description' => trim((string) $line->description),
        ])->sortBy(fn ($line) => implode('|', $line))->values()->all();
        return hash('sha256', json_encode([
            'company_id' => (int) $first->company_id,
            'date' => $first->date->toDateString(),
            'type' => $first->type,
            'currency' => $first->currency_id ?? null,
            'lines' => $normalized,
        ], JSON_THROW_ON_ERROR));
    }

    private function snapshot(Collection $lines): array
    {
        return $lines->map(fn ($line) => $line->only([
            'id', 'company_id', 'accounting_period_id', 'account_id', 'unique_no', 'date', 'type', 'debit', 'credit',
            'opening_balance_batch_id', 'reversal_of_transaction_id', 'source_system', 'source_reference', 'status', 'deleted_at',
        ]))->values()->all();
    }

    private function item(AccountingRepairRun $run, string $type, string $severity, string $entityType, mixed $entityId, array $snapshot, string $action, array $related = []): void
    {
        $key = hash('sha256', implode('|', [$type, $entityType, (string) $entityId, json_encode($related)]));
        $run->items()->firstOrCreate(['idempotency_key' => $key], [
            'issue_type' => $type,
            'severity' => $severity,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'related_entity_ids' => $related,
            'before_snapshot' => $snapshot,
            'proposed_action' => $action,
        ]);
    }
}

final class CarbonDate
{
    public static function previousDay(string $date): string
    {
        return \Carbon\CarbonImmutable::parse($date)->subDay()->toDateString();
    }
}
