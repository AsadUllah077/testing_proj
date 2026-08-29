<?php

namespace App\Services\Account;

use App\Models\AuditAction;
use App\Models\Bill;
use App\Models\JournalEntry;
use App\Models\Sale;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuditActionService
{
    /**
     * Freeze a transaction.
     * Sets status to 0, which hides it via global scope.
     */
    public function freeze(int $transactionId, ?string $notes = null): AuditAction
    {
        return DB::transaction(function () use ($transactionId, $notes) {
            $txn = $this->findTransaction($transactionId);
            $transactionIds = $this->relatedTransactionIds($txn);
            $originalState = $this->transactionState($transactionIds);
            $invoiceAdjustments = $this->paymentAdjustments($txn);

            $affected = $this->relatedTransactions($txn)->update(['status' => 0]);
            $invoiceUpdates = $this->applyInvoicePaymentAdjustments($invoiceAdjustments, subtract: true);

            return AuditAction::create([
                'company_id'      => $txn->company_id,
                'transaction_id' => $transactionId,
                'action'         => 'freeze',
                'status'         => 'executed',
                'performed_by'   => Auth::id(),
                'notes'          => $notes,
                'meta'           => $this->meta($txn, $transactionIds, [
                    'affected_transactions' => $affected,
                    'invoice_updates' => $invoiceUpdates,
                    'original_state' => $originalState,
                ]),
            ]);
        });
    }

    /**
     * Reverse a transaction.
     * Soft deletes the transaction.
     */
    public function reverse(int $transactionId, ?string $notes = null): AuditAction
    {
        return DB::transaction(function () use ($transactionId, $notes) {
            $txn = $this->findTransaction($transactionId);
            $transactionIds = $this->relatedTransactionIds($txn);
            $journalEntryIds = $this->relatedJournalEntryIds($txn);
            $originalState = $this->transactionState($transactionIds);
            $invoiceAdjustments = $this->paymentAdjustments($txn);

            $affectedTransactions = $this->relatedTransactions($txn)->delete();
            $affectedJournalEntries = $this->relatedJournalEntries($txn)->delete();
            $invoiceUpdates = $this->applyInvoicePaymentAdjustments($invoiceAdjustments, subtract: true);

            return AuditAction::create([
                'company_id'      => $txn->company_id,
                'transaction_id' => $transactionId,
                'action'         => 'reverse',
                'status'         => 'executed',
                'performed_by'   => Auth::id(),
                'notes'          => $notes,
                'meta'           => $this->meta($txn, $transactionIds, [
                    'affected_transactions' => $affectedTransactions,
                    'affected_journal_entries' => $affectedJournalEntries,
                    'invoice_updates' => $invoiceUpdates,
                    'journal_entry_ids' => $journalEntryIds,
                    'original_state' => $originalState,
                ]),
            ]);
        });
    }

    /**
     * Monitor a transaction.
     * Just logs the intent to monitor.
     */
    public function monitor(int $transactionId, ?string $notes = null): AuditAction
    {
        return DB::transaction(function () use ($transactionId, $notes) {
            $txn = $this->findTransaction($transactionId);
            $transactionIds = $this->relatedTransactionIds($txn);

            return AuditAction::create([
                'company_id'      => $txn->company_id,
                'transaction_id' => $transactionId,
                'action'         => 'monitor',
                'status'         => 'executed',
                'performed_by'   => Auth::id(),
                'notes'          => $notes,
                'meta'           => $this->meta($txn, $transactionIds),
            ]);
        });
    }

    /**
     * Dismiss/Unfreeze a transaction.
     */
    public function dismiss(int $transactionId, ?string $notes = null): AuditAction
    {
        return DB::transaction(function () use ($transactionId, $notes) {
            $txn = $this->findTransaction($transactionId, includeTrashed: true);
            $transactionIds = $this->relatedTransactionIds($txn, includeTrashed: true);
            $journalEntryIds = $this->relatedJournalEntryIds($txn, includeTrashed: true);
            $invoiceAdjustments = $this->paymentAdjustments($txn, inactiveOnly: true, includeTrashed: true);

            $restoredTransactions = $this->relatedTransactions($txn, includeTrashed: true)->restore();
            $activatedTransactions = $this->relatedTransactions($txn, includeTrashed: true)->update(['status' => 1]);
            $restoredJournalEntries = $this->relatedJournalEntries($txn, includeTrashed: true)->restore();
            $invoiceUpdates = $this->applyInvoicePaymentAdjustments($invoiceAdjustments, subtract: false);

            return AuditAction::create([
                'company_id'      => $txn->company_id,
                'transaction_id' => $transactionId,
                'action'         => 'dismiss',
                'status'         => 'executed',
                'performed_by'   => Auth::id(),
                'notes'          => $notes,
                'meta'           => $this->meta($txn, $transactionIds, [
                    'activated_transactions' => $activatedTransactions,
                    'restored_transactions' => $restoredTransactions,
                    'restored_journal_entries' => $restoredJournalEntries,
                    'invoice_updates' => $invoiceUpdates,
                    'journal_entry_ids' => $journalEntryIds,
                ]),
            ]);
        });
    }

    private function findTransaction(int $transactionId, bool $includeTrashed = false): Transaction
    {
        $query = Transaction::withoutGlobalScopes(['transactionStatus', 'uniqueNoNotNull']);

        if ($includeTrashed) {
            $query->withTrashed();
        }

        return $query->findOrFail($transactionId);
    }

    private function relatedTransactions(Transaction $txn, bool $includeTrashed = false)
    {
        $query = Transaction::withoutGlobalScopes(['transactionStatus', 'uniqueNoNotNull']);

        if ($includeTrashed) {
            $query->withTrashed();
        }

        if ($txn->unique_no) {
            $query->where('unique_no', $txn->unique_no)
                ->where('company_id', $txn->company_id);
        } else {
            $query->whereKey($txn->id);
        }

        return $query;
    }

    private function relatedJournalEntries(Transaction $txn, bool $includeTrashed = false)
    {
        $query = JournalEntry::query();

        if ($includeTrashed) {
            $query->withTrashed();
        }

        if ($txn->unique_no) {
            $query->where('unique_no', $txn->unique_no)
                ->where('company_id', $txn->company_id);
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    private function relatedTransactionIds(Transaction $txn, bool $includeTrashed = false): array
    {
        return $this->relatedTransactions($txn, $includeTrashed)
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();
    }

    private function relatedJournalEntryIds(Transaction $txn, bool $includeTrashed = false): array
    {
        return $this->relatedJournalEntries($txn, $includeTrashed)
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();
    }

    private function transactionState(array $transactionIds): array
    {
        if (empty($transactionIds)) {
            return [];
        }

        return Transaction::withoutGlobalScopes(['transactionStatus', 'uniqueNoNotNull'])
            ->withTrashed()
            ->whereIn('id', $transactionIds)
            ->get(['id', 'status', 'deleted_at'])
            ->map(fn(Transaction $txn) => [
                'id' => $txn->id,
                'status' => $txn->status,
                'deleted_at' => optional($txn->deleted_at)->toDateTimeString(),
            ])
            ->all();
    }

    private function paymentAdjustments(
        Transaction $txn,
        bool $inactiveOnly = false,
        bool $includeTrashed = false
    ): array {
        $rows = $this->relatedTransactions($txn, $includeTrashed)
            ->get(['id', 'reference_id', 'unique_no', 'type', 'debit', 'credit', 'status', 'deleted_at']);

        $adjustments = [
            'sales' => [],
            'bills' => [],
        ];

        foreach ($rows->groupBy(fn(Transaction $row) => $row->type . ':' . $row->reference_id) as $group) {
            $first = $group->first();

            if (!$first?->reference_id || !$this->isPaymentTransactionGroup($first)) {
                continue;
            }

            if ($inactiveOnly && !$group->contains(fn(Transaction $row) => (int) $row->status !== 1 || $row->trashed())) {
                continue;
            }

            if (!$inactiveOnly && !$group->contains(fn(Transaction $row) => (int) $row->status === 1 && !$row->trashed())) {
                continue;
            }

            $amount = (float) $group->max(fn(Transaction $row) => max(abs((float) $row->debit), abs((float) $row->credit)));

            if ($amount <= 0) {
                continue;
            }

            if ($this->isSalePayment($first)) {
                $adjustments['sales'][(int) $first->reference_id] = ($adjustments['sales'][(int) $first->reference_id] ?? 0) + $amount;
            }

            if ($this->isBillPayment($first)) {
                $adjustments['bills'][(int) $first->reference_id] = ($adjustments['bills'][(int) $first->reference_id] ?? 0) + $amount;
            }
        }

        return $adjustments;
    }

    private function isPaymentTransactionGroup(Transaction $txn): bool
    {
        return $this->isSalePayment($txn) || $this->isBillPayment($txn);
    }

    private function isSalePayment(Transaction $txn): bool
    {
        if (!in_array($txn->type, ['Sale', 'sale_payment', 'receipt'], true)) {
            return false;
        }

        $sale = Sale::withoutGlobalScopes()->find($txn->reference_id);

        return $sale && $txn->unique_no !== $sale->unique_no;
    }

    private function isBillPayment(Transaction $txn): bool
    {
        if (!in_array($txn->type, ['Bill', 'BillPayment', 'bill_payment', 'payment'], true)) {
            return false;
        }

        $bill = Bill::withoutGlobalScopes()->find($txn->reference_id);

        return $bill && $txn->unique_no !== $bill->unique_no;
    }

    private function applyInvoicePaymentAdjustments(array $adjustments, bool $subtract): array
    {
        $updates = [
            'sales' => [],
            'bills' => [],
        ];

        foreach ($adjustments['sales'] as $saleId => $amount) {
            $sale = Sale::withoutGlobalScopes()->find($saleId);
            if (!$sale) {
                continue;
            }

            $updates['sales'][] = $this->applyPaymentDelta($sale, (float) $amount, $subtract);
        }

        foreach ($adjustments['bills'] as $billId => $amount) {
            $bill = Bill::withoutGlobalScopes()->find($billId);
            if (!$bill) {
                continue;
            }

            $updates['bills'][] = $this->applyPaymentDelta($bill, (float) $amount, $subtract);
        }

        return $updates;
    }

    private function applyPaymentDelta(Sale|Bill $invoice, float $amount, bool $subtract): array
    {
        $old = [
            'id' => $invoice->id,
            'status' => (int) $invoice->status,
            'paid_amount' => (float) $invoice->paid_amount,
            'remaining_amount' => (float) $invoice->remaining_amount,
            'approval_status' => $invoice->approval_status,
        ];

        $total = (float) ($invoice->total_after_discount ?? $invoice->total ?? 0);
        $currentPaid = (float) $invoice->paid_amount;
        $currentRemaining = (float) $invoice->remaining_amount;

        if ($subtract) {
            $paid = max(0, round($currentPaid - $amount, 2));
            $remaining = min($total, round($currentRemaining + $amount, 2));
        } else {
            $paid = min($total, round($currentPaid + $amount, 2));
            $remaining = max(0, round($currentRemaining - $amount, 2));
        }

        $invoice->paid_amount = $paid;
        $invoice->remaining_amount = $remaining;
        $invoice->status = 1;
        if (Schema::hasColumn($invoice->getTable(), 'payment_status')) {
            $invoice->payment_status = $invoice instanceof Sale
                ? app(\App\Services\SaleService::class)->paymentStatus($paid, $remaining)
                : app(\App\Services\BillService::class)->paymentStatus($paid, $remaining);
        } else {
            $invoice->approval_status = $this->invoiceApprovalStatus($invoice->approval_status, $paid, $remaining);
        }
        $invoice->save();

        return [
            'id' => $invoice->id,
            'amount' => $amount,
            'old' => $old,
            'new' => [
                'status' => (int) $invoice->status,
                'paid_amount' => (float) $invoice->paid_amount,
                'remaining_amount' => (float) $invoice->remaining_amount,
                'approval_status' => $invoice->approval_status,
            ],
        ];
    }

    private function invoiceApprovalStatus(?string $currentStatus, float $paid, float $remaining): string
    {
        if ($remaining <= 0) {
            return 'paid';
        }

        if ($paid > 0 || $currentStatus === 'paid') {
            return 'approved';
        }

        return $currentStatus ?: 'approved';
    }

    private function meta(Transaction $txn, array $transactionIds, array $extra = []): array
    {
        return array_merge([
            'unique_no' => $txn->unique_no,
            'company_id' => $txn->company_id,
            'transaction_ids' => $transactionIds,
        ], $extra);
    }
}
