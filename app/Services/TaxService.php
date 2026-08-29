<?php

namespace App\Services;

use App\Models\TaxPeriod;
use App\Models\Transaction;
use App\Models\JournalEntry;
use App\Models\ChartOfAccount;
use App\Models\AccountingPeriod;
use Illuminate\Support\Facades\DB;
use Exception;

class TaxService
{
    /**
     * Calculate tax balances for a given date range.
     */
    public function calculateBalances($companyId, $startDate, $endDate)
    {
        $taxPayableAccountIds = ChartOfAccount::getSalesTaxPayableIds($companyId);
        $taxReceivableAccountIds = ChartOfAccount::getPurchaseTaxAccountIds($companyId);
        
        if ($taxPayableAccountIds->isEmpty() || $taxReceivableAccountIds->isEmpty()) {
            throw new Exception("Tax accounts not properly configured in Chart of Accounts.");
        }

        // Sales Tax Payable (Liability) - Grouped by Customer
        $payableBreakdown = Transaction::where('company_id', $companyId)
            ->whereIn('account_id', $taxPayableAccountIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('status', 1)
            ->where(function ($q) {
                $q->whereNull('type')
                    ->orWhereNotIn('type', ['Tax Settlement', 'Tax Payment', 'Tax Refund']);
            })
            ->select('account_id', 'customer_id', DB::raw('SUM(COALESCE(credit, 0)) - SUM(COALESCE(debit, 0)) as balance'))
            ->groupBy('account_id', 'customer_id')
            ->get();

        // Tax Refund Receivable (Asset) - Grouped by Vendor
        $receivableBreakdown = Transaction::where('company_id', $companyId)
            ->whereIn('account_id', $taxReceivableAccountIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('status', 1)
            ->where(function ($q) {
                $q->whereNull('type')
                    ->orWhereNotIn('type', ['Tax Settlement', 'Tax Payment', 'Tax Refund']);
            })
            ->select('account_id', 'vendor_id', DB::raw('SUM(COALESCE(debit, 0)) - SUM(COALESCE(credit, 0)) as balance'))
            ->groupBy('account_id', 'vendor_id')
            ->get();

        $totalPayable = $payableBreakdown->sum('balance');
        $totalReceivable = $receivableBreakdown->sum('balance');

        // Get carried forward amount from previous period
        $carriedForwardAmount = 0;
        $taxPeriod = TaxPeriod::where('company_id', $companyId)
            ->where('start_date', '<=', $startDate)
            ->where('end_date', '>=', $endDate)
            ->first();
        if ($taxPeriod) {
            $carriedForwardAmount = $taxPeriod->carried_forward_amount ?? 0;
        }

        return [
            'payable' => floatval($totalPayable),
            'receivable' => floatval($totalReceivable),
            'carried_forward' => floatval($carriedForwardAmount),
            'net' => floatval($totalPayable - $totalReceivable - $carriedForwardAmount),
            'payable_breakdown' => $payableBreakdown,
            'receivable_breakdown' => $receivableBreakdown
        ];
    }

    /**
     * Generate Tax Settlement Journal.
     */
    public function settlePeriod(TaxPeriod $taxPeriod)
    {
        return DB::transaction(function () use ($taxPeriod) {
            $taxPeriod = TaxPeriod::withoutGlobalScopes()->whereKey($taxPeriod->id)->lockForUpdate()->firstOrFail();
            if ($taxPeriod->status !== 'open') throw new Exception('Only open tax periods can be settled.');
            if ($taxPeriod->approval_status !== 'approved') throw new Exception('A tax period must be approved before settlement.');
            $balances = $this->calculateBalances($taxPeriod->company_id, $taxPeriod->start_date, $taxPeriod->end_date);

            $uniqueNo = 'TAX-SET-' . $taxPeriod->id . '-' . strtoupper(bin2hex(random_bytes(5)));
            $authId = auth('web')->id() ?: 1;
            $now = now();

            // We need an accounting period for the settlement date (usually end of period)
            $accountingPeriod = AccountingPeriod::findPeriodForDate($taxPeriod->end_date, $taxPeriod->company_id);
            if (!$accountingPeriod) {
                throw new Exception("Accounting period not found for the end of tax period.");
            }

            $journalEntries = [];
            $transactions = [];

            $taxPayableAccountIds = ChartOfAccount::getSalesTaxPayableIds($taxPeriod->company_id);
            $taxReceivableAccountIds = ChartOfAccount::getPurchaseTaxAccountIds($taxPeriod->company_id);
            $taxPayableLiabilityId = ChartOfAccount::getTaxPayableLiabilityId($taxPeriod->company_id);
            $taxRecoverableAssetId = ChartOfAccount::getTaxRecoverableAssetId($taxPeriod->company_id);

            if ($taxPayableAccountIds->isEmpty() || $taxReceivableAccountIds->isEmpty() || !$taxPayableLiabilityId || !$taxRecoverableAssetId) {
                throw new Exception("Tax accounts not properly configured in Chart of Accounts.");
            }
            if ($taxPayableAccountIds->contains($taxPayableLiabilityId) || $taxReceivableAccountIds->contains($taxRecoverableAssetId)) throw new Exception('Settlement clearing accounts must be separate from the source tax accounts.');

            // Common data for both tables
            $baseData = [
                'company_id' => $taxPeriod->company_id,
                'accounting_period_id' => $accountingPeriod->id,
                'date' => $taxPeriod->end_date,
                'unique_no' => $uniqueNo,
                'created_by' => $authId,
                'updated_by' => $authId,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // Transaction-specific keys
            $transactionCommon = array_merge($baseData, [
                'vendor_id' => null,
                'customer_id' => null,
                'description' => null,
                'type' => null,
                'reference_id' => $taxPeriod->id,
                'debit' => 0,
                'credit' => 0,
                'status' => 1,
            ]);

            // Journal-specific keys
            $journalCommon = array_merge($baseData, [
                'description' => null,
                'narration' => null,
                'debit' => 0,
                'credit' => 0,
            ]);

            foreach ($balances['payable_breakdown']->groupBy('account_id') as $accountId => $rows) {
                $amount = round((float) $rows->sum('balance'), 4); if ($amount == 0.0) continue;
                $journalEntries[] = array_merge($journalCommon, ['account_id' => $accountId, 'description' => 'Tax Settlement: Clear Output Tax', 'narration' => "Tax Settlement for period {$taxPeriod->name}", 'debit' => max($amount, 0), 'credit' => max(-$amount, 0)]);
            }
            foreach ($balances['receivable_breakdown']->groupBy('account_id') as $accountId => $rows) {
                $amount = round((float) $rows->sum('balance'), 4); if ($amount == 0.0) continue;
                $journalEntries[] = array_merge($journalCommon, ['account_id' => $accountId, 'description' => 'Tax Settlement: Clear Input Tax', 'narration' => "Tax Settlement for period {$taxPeriod->name}", 'debit' => max(-$amount, 0), 'credit' => max($amount, 0)]);
            }

            $carriedForwardAmount = round((float) ($balances['carried_forward'] ?? 0), 2);
            $netAmount = round((float) $balances['net'], 2);

            if ($carriedForwardAmount > 0) {
                $journalEntries[] = array_merge($journalCommon, [
                    'account_id' => $taxRecoverableAssetId,
                    'description' => "Tax Settlement: Apply Carried Forward Credit",
                    'narration' => "Tax Settlement for period {$taxPeriod->name}",
                    'debit' => 0,
                    'credit' => $carriedForwardAmount,
                ]);
            } elseif ($carriedForwardAmount < 0) {
                $journalEntries[] = array_merge($journalCommon, [
                    'account_id' => $taxPayableLiabilityId,
                    'description' => "Tax Settlement: Apply Carried Forward Payable",
                    'narration' => "Tax Settlement for period {$taxPeriod->name}",
                    'debit' => abs($carriedForwardAmount),
                    'credit' => 0,
                ]);
            }

            // Handle the final net after current-period tax and carried-forward amount.
            if ($netAmount > 0) {
                // Net Payable -> Credit Tax Payable Liability
                $journalEntries[] = array_merge($journalCommon, [
                    'account_id' => $taxPayableLiabilityId,
                    'description' => "Tax Settlement: Record Tax Payable Liability",
                    'narration' => "Tax Settlement for period {$taxPeriod->name}",
                    'debit' => 0,
                    'credit' => $netAmount,
                ]);
            } elseif ($netAmount < 0) {
                // Refund Due -> Debit Tax Recoverable Asset
                $journalEntries[] = array_merge($journalCommon, [
                    'account_id' => $taxRecoverableAssetId,
                    'description' => "Tax Settlement: Record Tax Recoverable Asset",
                    'narration' => "Tax Settlement for period {$taxPeriod->name}",
                    'debit' => abs($netAmount),
                    'credit' => 0,
                ]);
            }

            // Now handle granular transactions (per customer/vendor)
            // For Sales Tax Payable: debit positive credit balances, credit negative debit balances.
            foreach ($balances['payable_breakdown'] as $row) {
                if ((float) $row->balance === 0.0) continue;
                $transactions[] = array_merge($transactionCommon, [
                    'account_id' => $row->account_id,
                    'customer_id' => $row->customer_id,
                    'description' => $row->balance > 0
                        ? "Tax Settlement: Clear Payable"
                        : "Tax Settlement: Clear Payable Debit Balance",
                    'type' => 'Tax Settlement',
                    'debit' => $row->balance > 0 ? $row->balance : 0,
                    'credit' => $row->balance < 0 ? abs($row->balance) : 0,
                ]);
            }

            // For Purchase Tax Input: credit positive debit balances, debit negative credit balances.
            foreach ($balances['receivable_breakdown'] as $row) {
                if ((float) $row->balance === 0.0) continue;
                $transactions[] = array_merge($transactionCommon, [
                    'account_id' => $row->account_id,
                    'vendor_id' => $row->vendor_id,
                    'description' => $row->balance > 0
                        ? "Tax Settlement: Clear Receivable"
                        : "Tax Settlement: Clear Receivable Credit Balance",
                    'type' => 'Tax Settlement',
                    'debit' => $row->balance < 0 ? abs($row->balance) : 0,
                    'credit' => $row->balance > 0 ? $row->balance : 0,
                ]);
            }

            if ($carriedForwardAmount > 0) {
                $transactions[] = array_merge($transactionCommon, [
                    'account_id' => $taxRecoverableAssetId,
                    'description' => "Tax Settlement: Apply Carried Forward Credit",
                    'type' => 'Tax Settlement',
                    'debit' => 0,
                    'credit' => $carriedForwardAmount,
                ]);
            } elseif ($carriedForwardAmount < 0) {
                $transactions[] = array_merge($transactionCommon, [
                    'account_id' => $taxPayableLiabilityId,
                    'description' => "Tax Settlement: Apply Carried Forward Payable",
                    'type' => 'Tax Settlement',
                    'debit' => abs($carriedForwardAmount),
                    'credit' => 0,
                ]);
            }

            // Add granular transactions for the new accounts (Tax Payable Liability / Tax Recoverable Asset)
            if ($netAmount > 0) {
                $transactions[] = array_merge($transactionCommon, [
                    'account_id' => $taxPayableLiabilityId,
                    'description' => "Tax Settlement: Record Tax Payable",
                    'type' => 'Tax Settlement',
                    'debit' => 0,
                    'credit' => $netAmount,
                ]);
            } elseif ($netAmount < 0) {
                $transactions[] = array_merge($transactionCommon, [
                    'account_id' => $taxRecoverableAssetId,
                    'description' => "Tax Settlement: Record Tax Recoverable",
                    'type' => 'Tax Settlement',
                    'debit' => abs($netAmount),
                    'credit' => 0,
                ]);
            }

            if (!empty($journalEntries)) {
                DB::table('journal_entries')->insert($journalEntries);
            }

            if (!empty($transactions)) {
                DB::table('transactions')->insert($transactions);
            }

            $taxPeriod->update([
                'status' => 'settled',
                'total_payable' => $balances['payable'],
                'total_receivable' => $balances['receivable'],
                'net_amount' => $netAmount,
                'unique_no' => $uniqueNo,
                'settlement_reference' => $uniqueNo,
                'locked_at' => $now,
                'locked_by' => $authId,
            ]);
            \App\Models\TaxSettlementBatch::create([
                'company_id' => $taxPeriod->company_id,
                'tax_period_id' => $taxPeriod->id,
                'reference' => $uniqueNo,
                'settlement_date' => $taxPeriod->end_date,
                'output_tax' => $balances['payable'],
                'input_tax' => $balances['receivable'],
                'carried_forward_amount' => $carriedForwardAmount,
                'net_amount' => $netAmount,
                'snapshot' => [
                    'totals' => ['output_tax' => (float) $balances['payable'], 'input_tax' => (float) $balances['receivable'], 'carried_forward' => (float) $carriedForwardAmount, 'net_amount' => (float) $netAmount],
                    'output_breakdown' => $balances['payable_breakdown']->map(fn ($row) => ['account_id' => (int) $row->account_id, 'customer_id' => $row->customer_id, 'balance' => (float) $row->balance])->values()->all(),
                    'input_breakdown' => $balances['receivable_breakdown']->map(fn ($row) => ['account_id' => (int) $row->account_id, 'vendor_id' => $row->vendor_id, 'balance' => (float) $row->balance])->values()->all(),
                    'source_tax_accounts' => ['output' => $taxPayableAccountIds->values()->all(), 'input' => $taxReceivableAccountIds->values()->all()],
                ],
                'created_by' => $authId,
            ]);

            return $taxPeriod;
        });
    }

    /**
     * Process Tax Payment or Refund.
     * Only posts the NET amount (what actually moves to/from the bank).
     * The settlement step already handled the receivable/payable offset.
     */
    public function recordPayment(TaxPeriod $taxPeriod, $bankAccountId, $paymentDate, $reference = null)
    {
        $remaining = round(abs((float) $taxPeriod->net_amount) - (float) $taxPeriod->paid_amount, 4);
        return $this->recordPartialPayment($taxPeriod, $bankAccountId, $paymentDate, $remaining, $reference);

        if ($taxPeriod->status !== 'settled') {
            throw new Exception("Period must be settled before processing payment.");
        }

        $bankAccount = ChartOfAccount::forCompany($taxPeriod->company_id)->find($bankAccountId);

        if (!$bankAccount) {
            throw new Exception("Please select a valid bank or cash account for this company.");
        }

        return DB::transaction(function () use ($taxPeriod, $bankAccount, $paymentDate, $reference) {
            $authId = auth('web')->id() ?: 1;
            $now = now();
            $accountingPeriod = AccountingPeriod::findPeriodForDate($paymentDate, $taxPeriod->company_id);

            if (!$accountingPeriod) {
                throw new Exception("The payment date does not fall within any unlocked accounting period.");
            }

            // Use stored values from settlement — no re-query needed
            $netAmount = abs($taxPeriod->net_amount);
            $uniqueNo = 'TAX-PAY-' . now()->format('YmdHis') . '-' . $taxPeriod->id;
            $taxPayableLiabilityId = ChartOfAccount::getTaxPayableLiabilityId($taxPeriod->company_id);
            $taxRecoverableAssetId = ChartOfAccount::getTaxRecoverableAssetId($taxPeriod->company_id);

            $journalEntries = [];
            $transactions = [];

            $baseData = [
                'company_id' => $taxPeriod->company_id,
                'accounting_period_id' => $accountingPeriod->id,
                'date' => $paymentDate,
                'unique_no' => $uniqueNo,
                'created_by' => $authId,
                'updated_by' => $authId,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $transactionCommon = array_merge($baseData, [
                'vendor_id' => null,
                'customer_id' => null,
                'reference_id' => $taxPeriod->id,
                'description' => null,
                'type' => null,
                'debit' => 0,
                'credit' => 0,
                'status' => 1,
            ]);

            $journalCommon = array_merge($baseData, [
                'description' => null,
                'narration' => null,
                'debit' => 0,
                'credit' => 0,
            ]);

            if ($taxPeriod->net_amount > 0) {
                // Net Payable -> Pay Tax Payable Liability to Government
                // Journal: DR Tax Payable Liability, CR Bank
                $journalEntries[] = array_merge($journalCommon, [
                    'account_id' => $taxPayableLiabilityId,
                    'description' => "Tax Payment for period {$taxPeriod->name}",
                    'narration' => $reference ?: "Tax Payment",
                    'debit' => $netAmount,
                    'credit' => 0,
                ]);

                $journalEntries[] = array_merge($journalCommon, [
                    'account_id' => $bankAccount->id,
                    'description' => "Tax Payment for period {$taxPeriod->name}",
                    'narration' => $reference ?: "Tax Payment",
                    'debit' => 0,
                    'credit' => $netAmount,
                ]);

                $transactions[] = array_merge($transactionCommon, [
                    'account_id' => $taxPayableLiabilityId,
                    'description' => "Tax Payment: Clear Tax Payable",
                    'type' => 'Tax Payment',
                    'debit' => $netAmount,
                    'credit' => 0,
                ]);

                $transactions[] = array_merge($transactionCommon, [
                    'account_id' => $bankAccount->id,
                    'description' => "Tax Payment: Bank Payment",
                    'type' => 'Tax Payment',
                    'debit' => 0,
                    'credit' => $netAmount,
                ]);
            } elseif ($taxPeriod->net_amount < 0) {
                // Refund Due -> Receive from Government
                // Journal: DR Bank, CR Tax Recoverable Asset
                $journalEntries[] = array_merge($journalCommon, [
                    'account_id' => $bankAccount->id,
                    'description' => "Tax Refund for period {$taxPeriod->name}",
                    'narration' => $reference ?: "Tax Refund",
                    'debit' => $netAmount,
                    'credit' => 0,
                ]);

                $journalEntries[] = array_merge($journalCommon, [
                    'account_id' => $taxRecoverableAssetId,
                    'description' => "Tax Refund for period {$taxPeriod->name}",
                    'narration' => $reference ?: "Tax Refund",
                    'debit' => 0,
                    'credit' => $netAmount,
                ]);

                $transactions[] = array_merge($transactionCommon, [
                    'account_id' => $bankAccount->id,
                    'description' => "Tax Refund: Bank Deposit",
                    'type' => 'Tax Refund',
                    'debit' => $netAmount,
                    'credit' => 0,
                ]);

                $transactions[] = array_merge($transactionCommon, [
                    'account_id' => $taxRecoverableAssetId,
                    'description' => "Tax Refund: Clear Tax Recoverable",
                    'type' => 'Tax Refund',
                    'debit' => 0,
                    'credit' => $netAmount,
                ]);
            }

            if (!empty($journalEntries)) {
                DB::table('journal_entries')->insert($journalEntries);
            }

            if (!empty($transactions)) {
                DB::table('transactions')->insert($transactions);
            }

            $taxPeriod->update([
                'status' => 'paid',
                'paid_amount' => $netAmount,
                'unique_no' => $uniqueNo,
            ]);

            return true;
        });
    }

    /**
     * Process Partial Tax Payment or Refund.
     */
    public function recordPartialPayment(TaxPeriod $taxPeriod, $bankAccountId, $paymentDate, $amount, $reference = null)
    {
        return DB::transaction(function () use ($taxPeriod, $bankAccountId, $paymentDate, $amount, $reference) {
            $taxPeriod = TaxPeriod::withoutGlobalScopes()->whereKey($taxPeriod->id)->lockForUpdate()->firstOrFail();
            if ($taxPeriod->status !== 'settled') throw new Exception('Period must be settled before processing payment.');
            if ($paymentDate < $taxPeriod->end_date->format('Y-m-d')) throw new Exception('Payment/refund date cannot precede the tax-period end date. Record a prepayment separately.');
            $remaining = round(abs((float) $taxPeriod->net_amount) - (float) $taxPeriod->paid_amount, 4);
            if ((float) $amount <= 0 || (float) $amount > $remaining + 0.0001) throw new Exception('Payment amount cannot exceed the remaining tax balance.');
            $bankAccount = ChartOfAccount::forCompany($taxPeriod->company_id)->where('type', 'Account')->where('sub_type', 'BANK')->find($bankAccountId);
            if (!$bankAccount) throw new Exception('Please select a valid bank account for this company.');
            $authId = auth('web')->id() ?: 1;
            $now = now();
            $accountingPeriod = AccountingPeriod::findPeriodForDate($paymentDate, $taxPeriod->company_id);

            if (!$accountingPeriod) {
                throw new Exception("The payment date does not fall within any unlocked accounting period.");
            }

            $uniqueNo = 'TAX-PAY-' . $taxPeriod->id . '-' . strtoupper(bin2hex(random_bytes(5)));
            $taxPayableLiabilityId = ChartOfAccount::getTaxPayableLiabilityId($taxPeriod->company_id);
            $taxRecoverableAssetId = ChartOfAccount::getTaxRecoverableAssetId($taxPeriod->company_id);

            $journalEntries = [];
            $transactions = [];

            $baseData = [
                'company_id' => $taxPeriod->company_id,
                'accounting_period_id' => $accountingPeriod->id,
                'date' => $paymentDate,
                'unique_no' => $uniqueNo,
                'created_by' => $authId,
                'updated_by' => $authId,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $transactionCommon = array_merge($baseData, [
                'vendor_id' => null,
                'customer_id' => null,
                'reference_id' => $taxPeriod->id,
                'description' => null,
                'type' => null,
                'debit' => 0,
                'credit' => 0,
                'status' => 1,
            ]);

            $journalCommon = array_merge($baseData, [
                'description' => null,
                'narration' => null,
                'debit' => 0,
                'credit' => 0,
            ]);

            if ($taxPeriod->net_amount > 0) {
                // Net Payable -> Partial Payment to Government
                $journalEntries[] = array_merge($journalCommon, [
                    'account_id' => $taxPayableLiabilityId,
                    'description' => "Partial Tax Payment for period {$taxPeriod->name}",
                    'narration' => $reference ?: "Partial Tax Payment",
                    'debit' => $amount,
                    'credit' => 0,
                ]);

                $journalEntries[] = array_merge($journalCommon, [
                    'account_id' => $bankAccount->id,
                    'description' => "Partial Tax Payment for period {$taxPeriod->name}",
                    'narration' => $reference ?: "Partial Tax Payment",
                    'debit' => 0,
                    'credit' => $amount,
                ]);

                $transactions[] = array_merge($transactionCommon, [
                    'account_id' => $taxPayableLiabilityId,
                    'description' => "Partial Tax Payment: Clear Tax Payable",
                    'type' => 'Tax Payment',
                    'debit' => $amount,
                    'credit' => 0,
                ]);

                $transactions[] = array_merge($transactionCommon, [
                    'account_id' => $bankAccount->id,
                    'description' => "Partial Tax Payment: Bank Payment",
                    'type' => 'Tax Payment',
                    'debit' => 0,
                    'credit' => $amount,
                ]);
            } else {
                // Refund Due -> Partial Refund from Government
                $journalEntries[] = array_merge($journalCommon, [
                    'account_id' => $bankAccount->id,
                    'description' => "Partial Tax Refund for period {$taxPeriod->name}",
                    'narration' => $reference ?: "Partial Tax Refund",
                    'debit' => $amount,
                    'credit' => 0,
                ]);

                $journalEntries[] = array_merge($journalCommon, [
                    'account_id' => $taxRecoverableAssetId,
                    'description' => "Partial Tax Refund for period {$taxPeriod->name}",
                    'narration' => $reference ?: "Partial Tax Refund",
                    'debit' => 0,
                    'credit' => $amount,
                ]);

                $transactions[] = array_merge($transactionCommon, [
                    'account_id' => $bankAccount->id,
                    'description' => "Partial Tax Refund: Bank Deposit",
                    'type' => 'Tax Refund',
                    'debit' => $amount,
                    'credit' => 0,
                ]);

                $transactions[] = array_merge($transactionCommon, [
                    'account_id' => $taxRecoverableAssetId,
                    'description' => "Partial Tax Refund: Clear Tax Recoverable",
                    'type' => 'Tax Refund',
                    'debit' => 0,
                    'credit' => $amount,
                ]);
            }

            if (!empty($journalEntries)) {
                DB::table('journal_entries')->insert($journalEntries);
            }

            if (!empty($transactions)) {
                DB::table('transactions')->insert($transactions);
            }

            // Update paid amount
            $currentPaidAmount = $taxPeriod->paid_amount ?? 0;
            $newPaidAmount = $currentPaidAmount + $amount;
            $status = $newPaidAmount >= abs($taxPeriod->net_amount) ? 'paid' : 'settled';

            // Update the record
            $taxPeriod->update([
                'paid_amount' => $newPaidAmount,
                'status' => $status,
            ]);

            $paymentBatch = \App\Models\TaxPaymentBatch::create([
                'company_id' => $taxPeriod->company_id,
                'tax_period_id' => $taxPeriod->id,
                'bank_account_id' => $bankAccount->id,
                'reference' => $uniqueNo,
                'direction' => $taxPeriod->net_amount >= 0 ? 'payment' : 'refund',
                'payment_date' => $paymentDate,
                'amount' => $amount,
                'external_reference' => $reference,
                'created_by' => $authId,
            ]);

            return $paymentBatch;
        });
    }

    /**
     * Check if a date falls within a paid (locked) tax period.
     */
    public function isDateLocked($companyId, $date)
    {
        return TaxPeriod::where('company_id', $companyId)
            ->whereIn('status', ['settled', 'partial_paid', 'paid'])
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->exists();
    }

    public function reverseSettlement(TaxPeriod $taxPeriod, string $reason): void
    {
        DB::transaction(function () use ($taxPeriod, $reason) {
            $taxPeriod = TaxPeriod::withoutGlobalScopes()->whereKey($taxPeriod->id)->lockForUpdate()->firstOrFail();
            if ($taxPeriod->status !== 'settled' || (float) $taxPeriod->paid_amount > 0) throw new Exception('Only unpaid settled periods can be reversed. Reverse payments first.');
            $batch = \App\Models\TaxSettlementBatch::where('tax_period_id', $taxPeriod->id)->where('status', 'posted')->lockForUpdate()->firstOrFail();
            $originals = Transaction::withoutGlobalScopes()->where('company_id', $taxPeriod->company_id)->where('reference_id', (string) $taxPeriod->id)->where('unique_no', $batch->reference)->where('type', 'Tax Settlement')->where('status', 1)->get();
            if ($originals->isEmpty()) throw new Exception('Settlement journal lines were not found.');
            $reversalDate = now()->toDateString();
            $accountingPeriod = AccountingPeriod::forCompany($taxPeriod->company_id)
                ->where('start_date', '<=', $reversalDate)
                ->where('end_date', '>=', $reversalDate)
                ->where('is_active', true)
                ->where('is_locked', false)
                ->first();
            if (!$accountingPeriod) throw new Exception('Settlement reversals require an active, unlocked accounting period for today.');

            $reference = 'TAX-REV-' . $taxPeriod->id . '-' . strtoupper(bin2hex(random_bytes(5)));
            foreach ($originals as $line) {
                Transaction::create(['company_id' => $line->company_id, 'accounting_period_id' => $accountingPeriod->id, 'account_id' => $line->account_id, 'reference_id' => (string) $taxPeriod->id, 'unique_no' => $reference, 'date' => $reversalDate, 'type' => 'Tax Settlement Reversal', 'description' => 'Tax settlement reversal: ' . $reason, 'debit' => $line->credit, 'credit' => $line->debit, 'status' => 1, 'posted_by' => auth('web')->id(), 'posted_at' => now(), 'created_by' => auth('web')->id(), 'updated_by' => auth('web')->id()]);
                JournalEntry::create(['company_id' => $line->company_id, 'accounting_period_id' => $accountingPeriod->id, 'account_id' => $line->account_id, 'unique_no' => $reference, 'date' => $reversalDate, 'narration' => 'Tax settlement reversal', 'description' => $reason, 'debit' => $line->credit, 'credit' => $line->debit, 'created_by' => auth('web')->id(), 'updated_by' => auth('web')->id()]);
            }
            $batch->update(['status' => 'reversed', 'reversal_reason' => $reason, 'reversed_by' => auth('web')->id(), 'reversed_at' => now()]);
            $taxPeriod->update(['status' => 'open', 'approval_status' => 'rejected', 'approval_note' => 'Settlement reversed: ' . $reason, 'locked_at' => null, 'locked_by' => null]);
        });
    }
}
