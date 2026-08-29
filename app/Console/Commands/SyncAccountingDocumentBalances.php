<?php

namespace App\Console\Commands;

use App\Models\AccountingBalanceSyncRun;
use App\Models\Bill;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class SyncAccountingDocumentBalances extends Command
{
    protected $signature = 'accounting:sync-document-balances
        {--company_id= : Required company to synchronize}
        {--user_id= : User who initiated the sync}
        {--source=console : Sync source: web, console, or system}
        {--dry-run : Show changes without updating records}';

    protected $description = 'Synchronize sale and bill paid/remaining amounts from AR/AP ledger balances.';

    public function handle(): int
    {
        $companyId = $this->option('company_id') ? (int) $this->option('company_id') : null;
        $userId = $this->option('user_id') ? (int) $this->option('user_id') : null;
        $source = in_array($this->option('source'), ['web', 'console', 'system'], true) ? $this->option('source') : 'console';
        $dryRun = (bool) $this->option('dry-run');

        if (! $companyId) {
            $this->error('The --company_id option is required. No companies were synchronized.');
            return self::FAILURE;
        }
        if (! Company::query()->whereKey($companyId)->exists()) {
            $this->error("Company {$companyId} does not exist. No documents were synchronized.");
            return self::FAILURE;
        }

        $companyIds = collect([$companyId]);
        $saleUpdates = 0;
        $billUpdates = 0;

        foreach ($companyIds as $syncCompanyId) {
            $syncCompanyId = (int) $syncCompanyId;
            $details = [];
            $run = null;

            if (! $dryRun) {
                $validUserId = $userId && User::query()->where('id', $userId)->exists()
                    ? $userId
                    : null;
                $run = AccountingBalanceSyncRun::withoutGlobalScopes()->create([
                    'company_id' => $syncCompanyId,
                    'initiated_by' => $validUserId,
                    'source' => $source,
                    'status' => 'running',
                    'started_at' => now(),
                ]);
            }

            try {
                [$companySales, $companyBills] = DB::transaction(function () use ($syncCompanyId, $dryRun, &$details) {
                    return [
                        $this->syncSales($syncCompanyId, $dryRun, $details),
                        $this->syncBills($syncCompanyId, $dryRun, $details),
                    ];
                });
                $saleUpdates += $companySales;
                $billUpdates += $companyBills;

                $run?->update([
                    'status' => 'completed',
                    'sales_updated' => $companySales,
                    'bills_updated' => $companyBills,
                    'details' => $details,
                    'completed_at' => now(),
                ]);
            } catch (Throwable $exception) {
                $run?->update([
                    'status' => 'failed',
                    'details' => $details,
                    'error_message' => mb_substr($exception->getMessage(), 0, 5000),
                    'completed_at' => now(),
                ]);
                throw $exception;
            }
        }

        $mode = $dryRun ? 'would update' : 'updated';
        $this->info("Sales {$mode}: {$saleUpdates}");
        $this->info("Bills {$mode}: {$billUpdates}");

        return self::SUCCESS;
    }

    private function syncSales(int $companyId, bool $dryRun, array &$details): int
    {
        $updated = 0;
        $sales = Sale::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->orderBy('company_id')
            ->orderBy('id')
            ->get();

        foreach ($sales as $sale) {
            $arAccountIds = $this->controlAccountIds($sale->company_id, 'ACCOUNTS_RECEIVABLE', 'Accounts Receivable');

            if ($arAccountIds->isEmpty()) {
                $this->warn("Sale {$sale->id} skipped: AR account missing for company {$sale->company_id}.");
                continue;
            }

            $hasLedger = DB::table('transactions')
                ->where('company_id', $sale->company_id)
                ->where('reference_id', $sale->id)
                ->whereNull('deleted_at')
                ->exists();

            if (! $hasLedger) {
                continue;
            }

            $ledger = DB::table('transactions')
                ->where('company_id', $sale->company_id)
                ->where('reference_id', $sale->id)
                ->whereIn('account_id', $arAccountIds)
                ->whereNull('deleted_at')
                ->selectRaw('
                    COALESCE(SUM(COALESCE(debit, 0)), 0) as debit_total,
                    COALESCE(SUM(COALESCE(credit, 0)), 0) as credit_total
                ')
                ->first();

            $originalReceivable = $this->money((float) $ledger->debit_total);
            $ledgerOutstanding = $this->money(max(0, (float) $ledger->debit_total - (float) $ledger->credit_total));
            $allocatedCreditNotes = $this->allocatedAmount('credit_note_allocations', 'sale_id', $sale->id, $sale->company_id);
            $remaining = $this->money(max(0, $ledgerOutstanding - $allocatedCreditNotes));
            $paid = $this->money(max(0, $originalReceivable - $ledgerOutstanding));
            $legacyTotalsReversed = $this->legacyTotalsAreReversed($sale, $originalReceivable);

            if (! $legacyTotalsReversed && $this->isSameMoney($sale->paid_amount, $paid) && $this->isSameMoney($sale->remaining_amount, $remaining)) {
                continue;
            }

            $updated++;
            $this->line("Sale {$sale->id} {$sale->unique_no}: paid {$sale->paid_amount} -> {$paid}, remaining {$sale->remaining_amount} -> {$remaining}");
            if ($legacyTotalsReversed) {
                $this->line("Sale {$sale->id}: repaired legacy total columns {$sale->total}/{$sale->total_after_discount} -> {$sale->total_after_discount}/{$sale->total}.");
            }

            if (! $dryRun) {
                $updates = [
                    'paid_amount' => $paid,
                    'remaining_amount' => $remaining,
                    'approval_status' => $sale->approval_status === 'paid' ? 'approved' : $sale->approval_status,
                    'payment_status' => $remaining <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'),
                ];
                if ($legacyTotalsReversed) {
                    $updates['total'] = $this->money((float) $sale->total_after_discount);
                    $updates['total_after_discount'] = $this->money((float) $sale->total);
                }
                $details[] = $this->detail('sale', $sale, $updates, $originalReceivable, $paid, $remaining, $legacyTotalsReversed);
                $sale->forceFill($updates)->save();
            } else {
                $updates = [
                    'paid_amount' => $paid,
                    'remaining_amount' => $remaining,
                    'approval_status' => $sale->approval_status === 'paid' ? 'approved' : $sale->approval_status,
                    'payment_status' => $remaining <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'),
                ];
                if ($legacyTotalsReversed) {
                    $updates['total'] = $this->money((float) $sale->total_after_discount);
                    $updates['total_after_discount'] = $this->money((float) $sale->total);
                }
                $details[] = $this->detail('sale', $sale, $updates, $originalReceivable, $paid, $remaining, $legacyTotalsReversed);
            }
        }

        return $updated;
    }

    private function syncBills(int $companyId, bool $dryRun, array &$details): int
    {
        $updated = 0;
        $bills = Bill::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->orderBy('company_id')
            ->orderBy('id')
            ->get();

        foreach ($bills as $bill) {
            $apAccountIds = $this->controlAccountIds($bill->company_id, 'ACCOUNTS_PAYABLE', 'Accounts Payable');

            if ($apAccountIds->isEmpty()) {
                $this->warn("Bill {$bill->id} skipped: AP account missing for company {$bill->company_id}.");
                continue;
            }

            $hasLedger = DB::table('transactions')
                ->where('company_id', $bill->company_id)
                ->where('reference_id', $bill->id)
                ->where('status', 1)
                ->whereNull('deleted_at')
                ->exists();

            if (! $hasLedger) {
                continue;
            }

            $ledger = DB::table('transactions')
                ->where('company_id', $bill->company_id)
                ->where('reference_id', $bill->id)
                ->whereIn('account_id', $apAccountIds)
                ->where('status', 1)
                ->whereNull('deleted_at')
                ->selectRaw('
                    COALESCE(SUM(COALESCE(debit, 0)), 0) as debit_total,
                    COALESCE(SUM(COALESCE(credit, 0)), 0) as credit_total
                ')
                ->first();

            $originalPayable = $this->money((float) $ledger->credit_total);
            $ledgerOutstanding = $this->money(max(0, (float) $ledger->credit_total - (float) $ledger->debit_total));
            $allocatedDebitNotes = $this->allocatedAmount('debit_note_allocations', 'bill_id', $bill->id, $bill->company_id);
            $remaining = $this->money(max(0, $ledgerOutstanding - $allocatedDebitNotes));
            $paid = $this->money(max(0, $originalPayable - $ledgerOutstanding));
            $legacyTotalsReversed = $this->legacyTotalsAreReversed($bill, $originalPayable);

            if (! $legacyTotalsReversed && $this->isSameMoney($bill->paid_amount, $paid) && $this->isSameMoney($bill->remaining_amount, $remaining)) {
                continue;
            }

            $updated++;
            $this->line("Bill {$bill->id} {$bill->unique_no}: paid {$bill->paid_amount} -> {$paid}, remaining {$bill->remaining_amount} -> {$remaining}");
            if ($legacyTotalsReversed) {
                $this->line("Bill {$bill->id}: repaired legacy total columns {$bill->total}/{$bill->total_after_discount} -> {$bill->total_after_discount}/{$bill->total}.");
            }

            if (! $dryRun) {
                $updates = [
                    'paid_amount' => $paid,
                    'remaining_amount' => $remaining,
                    'approval_status' => $bill->approval_status === 'paid' ? 'approved' : $bill->approval_status,
                    'payment_status' => app(\App\Services\BillService::class)->paymentStatus($paid, $remaining),
                ];
                if ($legacyTotalsReversed) {
                    $updates['total'] = $this->money((float) $bill->total_after_discount);
                    $updates['total_after_discount'] = $this->money((float) $bill->total);
                }
                $details[] = $this->detail('bill', $bill, $updates, $originalPayable, $paid, $remaining, $legacyTotalsReversed);
                $bill->forceFill($updates)->save();
            } else {
                $updates = [
                    'paid_amount' => $paid,
                    'remaining_amount' => $remaining,
                    'approval_status' => $bill->approval_status === 'paid' ? 'approved' : $bill->approval_status,
                    'payment_status' => app(\App\Services\BillService::class)->paymentStatus($paid, $remaining),
                ];
                if ($legacyTotalsReversed) {
                    $updates['total'] = $this->money((float) $bill->total_after_discount);
                    $updates['total_after_discount'] = $this->money((float) $bill->total);
                }
                $details[] = $this->detail('bill', $bill, $updates, $originalPayable, $paid, $remaining, $legacyTotalsReversed);
            }
        }

        return $updated;
    }

    private function controlAccountIds(int $companyId, string $systemType, string $name): \Illuminate\Support\Collection
    {
        return ChartOfAccount::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where(function ($query) use ($systemType, $name) {
                $query->where('system_account_type', $systemType)
                    ->orWhere('name', 'like', "%{$name}%");
            })
            ->pluck('id');
    }

    private function money(float $amount): float
    {
        return round($amount, 2);
    }

    private function allocatedAmount(string $table, string $documentColumn, int $documentId, int $companyId): float
    {
        return $this->money((float) DB::table($table)
            ->where($documentColumn, $documentId)
            ->where('company_id', $companyId)
            ->sum('amount'));
    }

    private function isSameMoney(float|string|null $left, float $right): bool
    {
        return abs((float) $left - $right) < 0.01;
    }

    private function legacyTotalsAreReversed(Sale|Bill $document, float $ledgerGross): bool
    {
        $storedTotal = $this->money((float) $document->total);
        $storedFinal = $this->money((float) $document->total_after_discount);
        $tax = $this->money((float) $document->tax);

        return ! $this->isSameMoney($storedFinal, $ledgerGross)
            && $this->isSameMoney($storedTotal, $ledgerGross)
            && $this->isSameMoney($storedFinal + $tax, $storedTotal);
    }

    private function detail(
        string $type,
        Sale|Bill $document,
        array $updates,
        float $ledgerGross,
        float $ledgerPaid,
        float $ledgerRemaining,
        bool $legacyTotalsReversed
    ): array {
        $fields = ['total', 'total_after_discount', 'paid_amount', 'remaining_amount', 'approval_status'];
        $before = collect($document->only($fields))->map(fn ($value, $field) => $field === 'approval_status' ? $value : $this->money((float) $value))->all();
        $after = $before;
        foreach ($updates as $field => $value) {
            $after[$field] = $field === 'approval_status' ? $value : $this->money((float) $value);
        }

        return [
            'company_id' => (int) $document->company_id,
            'document_type' => $type,
            'document_id' => $document->id,
            'reference' => $document->unique_no ?: $document->invoice_no,
            'invoice_no' => $document->invoice_no,
            'document_date' => optional($document->date)->toDateString(),
            'reason' => $legacyTotalsReversed ? 'Legacy total columns repaired and ledger balances synchronized.' : 'Paid and remaining balances synchronized from the ledger.',
            'before' => $before,
            'after' => $after,
            'ledger' => [
                'gross' => $ledgerGross,
                'paid' => $ledgerPaid,
                'remaining' => $ledgerRemaining,
            ],
        ];
    }
}
