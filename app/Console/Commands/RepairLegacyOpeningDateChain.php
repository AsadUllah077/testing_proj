<?php

namespace App\Console\Commands;

use App\Models\AccountingPeriod;
use App\Models\AccountingRepairRun;
use App\Models\ChartOfAccount;
use App\Models\OpeningBalanceBatch;
use App\Models\Transaction;
use App\Models\User;
use App\Support\AccountingConstants;
use App\Support\AccountingDate;
use App\Support\DecimalAmount;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RepairLegacyOpeningDateChain extends Command
{
    protected $signature = 'accounting:repair-legacy-opening-date-chain
        {--company= : Company ID}
        {--original-date= : Date of the original journals}
        {--target-date= : Accountant-approved earlier opening date}
        {--approved-by= : Approving user ID}
        {--executed-by= : Repair operator user ID}
        {--reason= : Required correction reason}
        {--clear-duplicate-obe-account= : Optional account ID whose duplicate OBE system role must be cleared}
        {--apply : Required to write corrections}
        {--force : Suppress interactive confirmation}';

    protected $description = 'Repair the legacy reverse-before-original opening-date edit chain without changing posted rows.';

    public function handle(): int
    {
        foreach (['company', 'original-date', 'target-date', 'approved-by', 'executed-by', 'reason'] as $required) {
            if (! $this->option($required)) {
                $this->error("--{$required} is required. No records changed.");
                return self::INVALID;
            }
        }
        if (! $this->option('apply')) {
            $this->error('--apply is required. No records changed.');
            return self::INVALID;
        }

        $companyId = (int) $this->option('company');
        $approverId = (int) $this->option('approved-by');
        $operatorId = (int) $this->option('executed-by');
        $originalDate = AccountingDate::fromIso((string) $this->option('original-date'));
        $targetDate = AccountingDate::fromIso((string) $this->option('target-date'));
        if ($targetDate >= $originalDate || $approverId === $operatorId) {
            $this->error('The target date must precede the original date, and approver/operator must differ. No records changed.');
            return self::INVALID;
        }
        foreach ([$approverId, $operatorId] as $userId) {
            if (! User::withoutGlobalScopes()->whereKey($userId)->where('company_id', $companyId)->exists()) {
                $this->error("User {$userId} does not belong to company {$companyId}. No records changed.");
                return self::INVALID;
            }
        }

        $stableKey = "LEGACY-OB-DATE:C{$companyId}:{$targetDate}:{$originalDate}";
        if (OpeningBalanceBatch::withoutGlobalScopes()->where('company_id', $companyId)->where('idempotency_key', $stableKey)->exists()) {
            $this->warn('This legacy date-chain repair has already been applied. No records changed.');
            return self::SUCCESS;
        }
        if (! $this->option('force') && ! $this->confirm("Post the approved opening repair for company {$companyId}?")) {
            $this->warn('No records changed.');
            return self::SUCCESS;
        }

        try {
            $result = DB::transaction(fn () => $this->applyRepair(
                $companyId,
                $originalDate,
                $targetDate,
                $approverId,
                $operatorId,
                $stableKey,
                trim((string) $this->option('reason')),
                $this->option('clear-duplicate-obe-account') ? (int) $this->option('clear-duplicate-obe-account') : null,
            ));
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage() . ' Transaction rolled back; no partial repair was retained.');
            return self::FAILURE;
        }

        $this->info("Repair run {$result['run_id']} completed; batch {$result['batch_id']} posted.");
        $this->line("Created {$result['transaction_count']} linked accounting rows. Existing posted rows changed: 0.");
        return self::SUCCESS;
    }

    private function applyRepair(
        int $companyId,
        string $originalDate,
        string $targetDate,
        int $approverId,
        int $operatorId,
        string $stableKey,
        string $reason,
        ?int $duplicateObeAccountId,
    ): array {
        $originals = $this->openingLines($companyId, $originalDate)
            ->whereNull('reversal_of_transaction_id')->values();
        if ($originals->isEmpty()) {
            throw new \RuntimeException('No unbatched original opening journals were found on the original date.');
        }

        $reversals = $this->openingLines($companyId, $targetDate)
            ->whereIn('reversal_of_transaction_id', $originals->pluck('id'))->values();
        $replacements = $this->openingLines($companyId, $targetDate)
            ->whereNull('reversal_of_transaction_id')->values();

        if ($reversals->count() !== $originals->count() || $replacements->count() !== $originals->count()) {
            throw new \RuntimeException('The legacy chain is incomplete or contains additional target-date opening rows.');
        }
        if ($reversals->pluck('reversal_of_transaction_id')->unique()->count() !== $originals->count()) {
            throw new \RuntimeException('Every original line must have exactly one invalid reversal.');
        }
        $this->assertBalancedJournals($originals, 'original');
        $this->assertBalancedJournals($reversals, 'invalid reversal');
        $this->assertBalancedJournals($replacements, 'replacement');

        $expected = $this->vector($originals);
        if ($this->vector($replacements) !== $expected || $this->vector($reversals) !== $this->negate($expected)) {
            throw new \RuntimeException('The reversal/replacement vectors do not exactly match the original journals.');
        }

        $targetPeriod = $this->period($companyId, $targetDate);
        $originalPeriod = $this->period($companyId, $originalDate);
        $targetWasLocked = (bool) $targetPeriod->is_locked;
        if ($targetWasLocked) {
            DB::table('accounting_periods')->where('id', $targetPeriod->id)->update(['is_locked' => false, 'updated_by' => $operatorId, 'updated_at' => now()]);
        }

        $run = AccountingRepairRun::withoutGlobalScopes()->create([
            'company_id' => $companyId,
            'repair_type' => 'legacy_opening_date_chain',
            'parameters' => ['target_date' => $targetDate, 'original_date' => $originalDate],
            'dry_run' => false,
            'status' => 'running',
            'backup_reference' => null,
            'requested_by' => $operatorId,
            'approved_by' => $approverId,
            'started_at' => now(),
        ]);

        $reference = "OB-REPAIR-C{$companyId}-" . str_replace('-', '', $targetDate) . "-R{$run->id}";
        $batch = OpeningBalanceBatch::withoutGlobalScopes()->create([
            'company_id' => $companyId,
            'reference' => $reference,
            'idempotency_key' => $stableKey,
            'journal_unique_no' => $reference,
            'source_system' => 'ACCOUNTING_REPAIR',
            'conversion_date' => $targetDate,
            'closing_date' => $targetDate,
            'fiscal_year' => $targetPeriod->name,
            'status' => AccountingConstants::BATCH_STATUS_POSTED,
            'notes' => $reason,
            'total_debit' => DecimalAmount::sum($replacements->pluck('debit')),
            'total_credit' => DecimalAmount::sum($replacements->pluck('credit')),
            'reviewed_by' => $operatorId,
            'approved_by' => $approverId,
            'posted_by' => $operatorId,
            'posted_at' => now(),
            'locked_at' => now(),
            'created_by' => $operatorId,
            'updated_by' => $operatorId,
        ]);

        $newIds = [];
        foreach ($this->amountsByAccount($replacements) as $accountId => $amounts) {
            $line = $batch->lines()->create([
                'company_id' => $companyId,
                'account_id' => $accountId,
                'debit' => $amounts['debit'],
                'credit' => $amounts['credit'],
                'description' => 'Approved legacy opening-date repair',
                'source_reference' => $stableKey,
            ]);
            $newIds[] = Transaction::withoutGlobalScopes()->create([
                'company_id' => $companyId,
                'accounting_period_id' => $targetPeriod->id,
                'account_id' => $accountId,
                'reference_id' => (string) $batch->id,
                'unique_no' => $reference,
                'date' => $targetDate,
                'type' => AccountingConstants::TRANSACTION_TYPE_OPENING_BALANCE,
                'source_system' => 'ACCOUNTING_REPAIR',
                'source_reference' => $stableKey,
                'posting_idempotency_key' => "{$stableKey}:BATCH:L{$line->id}",
                'is_opening_balance' => true,
                'opening_balance_batch_id' => $batch->id,
                'accounting_repair_run_id' => $run->id,
                'correction_type' => 'authoritative_replacement',
                'description' => $reason,
                'debit' => $amounts['debit'],
                'credit' => $amounts['credit'],
                'created_by' => $operatorId,
                'updated_by' => $operatorId,
                'posted_by' => $operatorId,
                'posted_at' => now(),
                'status' => 1,
            ])->id;
        }

        $correctionReference = "REPAIR-REV-R{$run->id}-LEGACY-REPLACEMENTS";
        foreach ($replacements as $replacement) {
            $newIds[] = Transaction::withoutGlobalScopes()->create([
                'company_id' => $companyId,
                'accounting_period_id' => $originalPeriod->id,
                'account_id' => $replacement->account_id,
                'unique_no' => $correctionReference,
                'date' => $originalDate,
                'type' => AccountingConstants::TRANSACTION_TYPE_OPENING_BALANCE,
                'source_system' => 'ACCOUNTING_REPAIR',
                'source_reference' => $stableKey,
                'posting_idempotency_key' => "{$stableKey}:REV:T{$replacement->id}",
                'is_opening_balance' => true,
                'reversal_of_transaction_id' => $replacement->id,
                'reversal_of_unique_no' => $replacement->unique_no,
                'correction_type' => 'repair_reversal',
                'accounting_repair_run_id' => $run->id,
                'description' => $reason,
                'debit' => $replacement->credit,
                'credit' => $replacement->debit,
                'created_by' => $operatorId,
                'updated_by' => $operatorId,
                'posted_by' => $operatorId,
                'posted_at' => now(),
                'status' => 1,
            ])->id;
        }

        if ($this->effectiveVector($companyId, array_keys($expected), $targetDate) !== $expected
            || $this->effectiveVector($companyId, array_keys($expected), $originalDate) !== $expected) {
            throw new \RuntimeException('Post-repair balance verification failed.');
        }

        $metadataChanged = false;
        if ($duplicateObeAccountId) {
            $duplicate = ChartOfAccount::withoutGlobalScopes()->whereKey($duplicateObeAccountId)->where('company_id', $companyId)->lockForUpdate()->firstOrFail();
            if ($duplicate->system_account_type === 'OPENING_BALANCE_EQUITY') {
                $duplicate->update(['system_account_type' => null, 'updated_by' => $operatorId]);
                $metadataChanged = true;
            }
        }
        if ($targetWasLocked) {
            DB::table('accounting_periods')->where('id', $targetPeriod->id)->update(['is_locked' => true, 'updated_by' => $operatorId, 'updated_at' => now()]);
        }

        $run->items()->create([
            'issue_type' => 'LEGACY_OPENING_DATE_CHAIN',
            'severity' => 'approved_correction',
            'entity_type' => 'company',
            'entity_id' => (string) $companyId,
            'related_entity_ids' => $originals->pluck('id')->merge($reversals->pluck('id'))->merge($replacements->pluck('id'))->all(),
            'before_snapshot' => ['target_vector' => $this->negate($this->vector($reversals->merge($replacements))), 'original_vector' => $expected],
            'authoritative_target' => $expected,
            'proposed_action' => 'POST_BATCH_AND_LINKED_CORRECTIONS',
            'after_snapshot' => ['batch_id' => $batch->id, 'transaction_ids' => $newIds, 'duplicate_obe_role_cleared' => $metadataChanged],
            'result' => 'repaired',
            'idempotency_key' => hash('sha256', $stableKey),
        ]);
        $run->update([
            'status' => 'completed',
            'completed_at' => now(),
            'summary' => ['repaired' => 1, 'accounting_rows_created' => count($newIds), 'existing_posted_rows_changed' => 0],
        ]);

        return ['run_id' => $run->id, 'batch_id' => $batch->id, 'transaction_count' => count($newIds)];
    }

    private function openingLines(int $companyId, string $date): Collection
    {
        return Transaction::withoutGlobalScopes()->where('company_id', $companyId)->whereDate('date', $date)
            ->whereNull('deleted_at')->where('status', 1)->where('is_opening_balance', true)
            ->whereNull('opening_balance_batch_id')->where('source_system', 'CHART_OF_ACCOUNT_OPENING')
            ->lockForUpdate()->orderBy('id')->get();
    }

    private function period(int $companyId, string $date): AccountingPeriod
    {
        $periods = AccountingPeriod::withoutGlobalScopes()->where('company_id', $companyId)
            ->whereDate('start_date', '<=', $date)->whereDate('end_date', '>=', $date)->lockForUpdate()->get();
        if ($periods->count() !== 1) {
            throw new \RuntimeException("Exactly one configured accounting period must contain {$date}.");
        }
        return $periods->first();
    }

    private function assertBalancedJournals(Collection $lines, string $label): void
    {
        foreach ($lines->groupBy('unique_no') as $reference => $journal) {
            if (! $reference || ! DecimalAmount::equals(DecimalAmount::sum($journal->pluck('debit')), DecimalAmount::sum($journal->pluck('credit')))) {
                throw new \RuntimeException("The {$label} journal {$reference} is not balanced.");
            }
        }
    }

    private function vector(Collection $lines): array
    {
        $vector = [];
        foreach ($lines->groupBy('account_id') as $accountId => $accountLines) {
            $vector[(int) $accountId] = bcsub(DecimalAmount::sum($accountLines->pluck('debit')), DecimalAmount::sum($accountLines->pluck('credit')), 4);
        }
        ksort($vector);
        return $vector;
    }

    private function negate(array $vector): array
    {
        return array_map(fn ($amount) => bcmul($amount, '-1', 4), $vector);
    }

    private function amountsByAccount(Collection $lines): array
    {
        $amounts = [];
        foreach ($lines->groupBy('account_id') as $accountId => $accountLines) {
            $amounts[(int) $accountId] = [
                'debit' => DecimalAmount::sum($accountLines->pluck('debit')),
                'credit' => DecimalAmount::sum($accountLines->pluck('credit')),
            ];
        }
        ksort($amounts);
        return $amounts;
    }

    private function effectiveVector(int $companyId, array $accountIds, string $date): array
    {
        $rows = Transaction::withoutGlobalScopes()->where('company_id', $companyId)->whereIn('account_id', $accountIds)
            ->whereDate('date', '<=', $date)->whereNull('deleted_at')->where('status', 1)->get();
        return $this->vector($rows);
    }
}
