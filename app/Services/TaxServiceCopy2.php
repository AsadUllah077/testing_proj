<?php

namespace App\Services;

use App\Models\TaxPeriod;
use App\Models\Transaction;
use App\Models\JournalEntry;
use App\Models\ChartOfAccount;
use App\Models\AccountingPeriod;
use Illuminate\Support\Facades\DB;
use Exception;

class TaxServiceCopy2
{
    /**
     * Calculate tax balances for a given date range.
     */
    public function calculateBalances($companyId, $startDate, $endDate)
    {
        $taxPayableAccountId = ChartOfAccount::getSalesTaxPayableId();
        $taxReceivableAccountId = ChartOfAccount::getPurchaseTaxAccountId();

        if (!$taxPayableAccountId || !$taxReceivableAccountId) {
            throw new Exception("Tax accounts not properly configured in Chart of Accounts.");
        }

        // Sales Tax Payable (Liability) - Grouped by Customer
        $payableBreakdown = Transaction::where('company_id', $companyId)
            ->where('account_id', $taxPayableAccountId)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('status', 1)
            ->where(function ($q) {
                $q->whereNull('type')
                    ->orWhereNotIn('type', ['Tax Settlement', 'Tax Payment', 'Tax Refund']);
            })
            ->select('customer_id', DB::raw('SUM(COALESCE(credit, 0)) - SUM(COALESCE(debit, 0)) as balance'))
            ->groupBy('customer_id')
            ->get();

        // Tax Refund Receivable (Asset) - Grouped by Vendor
        $receivableBreakdown = Transaction::where('company_id', $companyId)
            ->where('account_id', $taxReceivableAccountId)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('status', 1)
            ->where(function ($q) {
                $q->whereNull('type')
                    ->orWhereNotIn('type', ['Tax Settlement', 'Tax Payment', 'Tax Refund']);
            })
            ->select('vendor_id', DB::raw('SUM(COALESCE(debit, 0)) - SUM(COALESCE(credit, 0)) as balance'))
            ->groupBy('vendor_id')
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
        if ($taxPeriod->status !== 'open') {
            throw new Exception("Only open tax periods can be settled.");
        }

        return DB::transaction(function () use ($taxPeriod) {
            $balances = $this->calculateBalances($taxPeriod->company_id, $taxPeriod->start_date, $taxPeriod->end_date);

            $uniqueNo = 'TAX-SET-' . time();
            $authId = auth('web')->id() ?: 1;
            $now = now();

            // We need an accounting period for the settlement date (usually end of period)
            $accountingPeriod = AccountingPeriod::findPeriodForDate($taxPeriod->end_date);
            if (!$accountingPeriod) {
                throw new Exception("Accounting period not found for the end of tax period.");
            }

            $journalEntries = [];
            $transactions = [];

            $taxPayableAccountId = ChartOfAccount::getSalesTaxPayableId();
            $taxReceivableAccountId = ChartOfAccount::getPurchaseTaxAccountId();

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

            // 1. Journal Entries (Summary per Account)
            if ($balances['receivable'] > 0) {
                $journalEntries[] = array_merge($journalCommon, [
                    'account_id' => $taxReceivableAccountId,
                    'description' => "Tax Settlement: Offset Receivable",
                    'narration' => "Tax Settlement for period {$taxPeriod->name}",
                    'debit' => 0,
                    'credit' => $balances['receivable'],
                ]);

                $journalEntries[] = array_merge($journalCommon, [
                    'account_id' => $taxPayableAccountId,
                    'description' => "Tax Settlement: Offset Receivable",
                    'narration' => "Tax Settlement for period {$taxPeriod->name}",
                    'debit' => $balances['receivable'],
                    'credit' => 0,
                ]);
            }

            // 2. Transactions (Granular per Entity)
            foreach ($balances['receivable_breakdown'] as $row) {
                if ($row->balance <= 0) continue;

                // Credit Receivable
                $transactions[] = array_merge($transactionCommon, [
                    'account_id' => $taxReceivableAccountId,
                    'vendor_id' => $row->vendor_id,
                    'description' => "Tax Settlement: Offset Receivable",
                    'type' => 'Tax Settlement',
                    'debit' => 0,
                    'credit' => floatval($row->balance),
                ]);

                // Debit Payable
                $transactions[] = array_merge($transactionCommon, [
                    'account_id' => $taxPayableAccountId,
                    'vendor_id' => $row->vendor_id,
                    'description' => "Tax Settlement: Offset Receivable",
                    'type' => 'Tax Settlement',
                    'debit' => floatval($row->balance),
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
                'net_amount' => $balances['net'],
                'unique_no' => $uniqueNo,
            ]);

            return $taxPeriod;
        });
    }

    /**
     * Process Tax Payment or Refund.
     */
    public function recordPayment(TaxPeriod $taxPeriod, $bankAccountId, $paymentDate, $reference = null)
    {
        if ($taxPeriod->status !== 'settled') {
            throw new Exception("Period must be settled before processing payment.");
        }

        return DB::transaction(function () use ($taxPeriod, $bankAccountId, $paymentDate, $reference) {
            $authId = auth('web')->id() ?: 1;
            $now = now();
            $accountingPeriod = AccountingPeriod::findPeriodForDate($paymentDate);

            if (!$accountingPeriod) {
                throw new Exception("The payment date does not fall within any unlocked accounting period.");
            }

            $balances = $this->calculateBalances($taxPeriod->company_id, $taxPeriod->start_date, $taxPeriod->end_date);
            $uniqueNo = 'TAX-PAY-' . time();
            $taxPayableAccountId = ChartOfAccount::getSalesTaxPayableId();

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
                // Net Payable -> Pay to Government
                $journalEntries[] = array_merge($journalCommon, [
                    'account_id' => $taxPayableAccountId,
                    'description' => "Tax Payment for period {$taxPeriod->name}",
                    'narration' => $reference ?: "Tax Payment",
                    'debit' => abs($taxPeriod->net_amount),
                    'credit' => 0,
                ]);

                $journalEntries[] = array_merge($journalCommon, [
                    'account_id' => $bankAccountId,
                    'description' => "Tax Payment for period {$taxPeriod->name}",
                    'narration' => $reference ?: "Tax Payment",
                    'debit' => 0,
                    'credit' => abs($taxPeriod->net_amount),
                ]);

                // Granular Transactions for Payable Side
                foreach ($balances['payable_breakdown'] as $row) {
                    if ($row->balance <= 0) continue;

                    // Tax Account Entry
                    $transactions[] = array_merge($transactionCommon, [
                        'account_id' => $taxPayableAccountId,
                        'customer_id' => $row->customer_id,
                        'description' => "Tax Payment: Clear Payable",
                        'type' => 'Tax Payment',
                        'debit' => $row->balance,
                        'credit' => 0,
                    ]);

                    // Bank Account Entry (Paired for traceability)
                    $transactions[] = array_merge($transactionCommon, [
                        'account_id' => $bankAccountId,
                        'customer_id' => $row->customer_id,
                        'description' => "Tax Payment: Bank Payment",
                        'type' => 'Tax Payment',
                        'debit' => 0,
                        'credit' => $row->balance,
                    ]);
                }

                // Granular Transactions for Receivable Side (Intermediate offsets)
                foreach ($balances['receivable_breakdown'] as $row) {
                    if ($row->balance <= 0) continue;

                    // Tax Account Entry
                    $transactions[] = array_merge($transactionCommon, [
                        'account_id' => $taxPayableAccountId,
                        'vendor_id' => $row->vendor_id,
                        'description' => "Tax Payment: Offset Receivable",
                        'type' => 'Tax Payment',
                        'debit' => 0,
                        'credit' => $row->balance,
                    ]);

                    // Bank Account Entry (Paired for traceability)
                    // This effectively reverses the offset if we are paying the NET.
                    // Actually, if we pay the net, it means we subtract the receivable.
                    // So we credit Payable (Tax) and debit Bank (offsetting the credit to bank above).
                    $transactions[] = array_merge($transactionCommon, [
                        'account_id' => $bankAccountId,
                        'vendor_id' => $row->vendor_id,
                        'description' => "Tax Payment: Offset Adjustment",
                        'type' => 'Tax Payment',
                        'debit' => $row->balance,
                        'credit' => 0,
                    ]);
                }
            } else {
                // Net Receivable -> Refund from Government
                $journalEntries[] = array_merge($journalCommon, [
                    'account_id' => $bankAccountId,
                    'description' => "Tax Refund for period {$taxPeriod->name}",
                    'narration' => $reference ?: "Tax Refund",
                    'debit' => abs($taxPeriod->net_amount),
                    'credit' => 0,
                ]);

                $journalEntries[] = array_merge($journalCommon, [
                    'account_id' => $taxPayableAccountId,
                    'description' => "Tax Refund for period {$taxPeriod->name}",
                    'narration' => $reference ?: "Tax Refund",
                    'debit' => 0,
                    'credit' => abs($taxPeriod->net_amount),
                ]);

                // Granular Transactions for Receivable Side
                foreach ($balances['receivable_breakdown'] as $row) {
                    if ($row->balance <= 0) continue;

                    // Bank Account Entry
                    $transactions[] = array_merge($transactionCommon, [
                        'account_id' => $bankAccountId,
                        'vendor_id' => $row->vendor_id,
                        'description' => "Tax Refund: Bank Deposit",
                        'type' => 'Tax Refund',
                        'debit' => $row->balance,
                        'credit' => 0,
                    ]);

                    // Tax Account Entry
                    $transactions[] = array_merge($transactionCommon, [
                        'account_id' => $taxPayableAccountId,
                        'vendor_id' => $row->vendor_id,
                        'description' => "Tax Refund: Clear Receivable",
                        'type' => 'Tax Refund',
                        'debit' => 0,
                        'credit' => $row->balance,
                    ]);
                }

                // Granular Transactions for Payable Side (Intermediate offsets)
                foreach ($balances['payable_breakdown'] as $row) {
                    if ($row->balance <= 0) continue;

                    // Tax Account Entry
                    $transactions[] = array_merge($transactionCommon, [
                        'account_id' => $taxPayableAccountId,
                        'customer_id' => $row->customer_id,
                        'description' => "Tax Refund: Offset Payable",
                        'type' => 'Tax Refund',
                        'debit' => $row->balance,
                        'credit' => 0,
                    ]);

                    // Bank Account Entry (Paired for traceability)
                    $transactions[] = array_merge($transactionCommon, [
                        'account_id' => $bankAccountId,
                        'customer_id' => $row->customer_id,
                        'description' => "Tax Refund: Offset Adjustment",
                        'type' => 'Tax Refund',
                        'debit' => 0,
                        'credit' => $row->balance,
                    ]);
                }
            }

            if (!empty($journalEntries)) {
                DB::table('journal_entries')->insert($journalEntries);
            }

            if (!empty($transactions)) {
                DB::table('transactions')->insert($transactions);
            }

            $taxPeriod->update(['status' => 'paid', 'unique_no' => $uniqueNo]);

            return true;
        });
    }

    /**
     * Process Partial Tax Payment or Refund.
     */
    public function recordPartialPayment(TaxPeriod $taxPeriod, $bankAccountId, $paymentDate, $amount, $reference = null)
    {
        if ($taxPeriod->status !== 'settled') {
            throw new Exception("Period must be settled before processing payment.");
        }

        return DB::transaction(function () use ($taxPeriod, $bankAccountId, $paymentDate, $amount, $reference) {
            $authId = auth('web')->id() ?: 1;
            $now = now();
            $accountingPeriod = AccountingPeriod::findPeriodForDate($paymentDate);

            if (!$accountingPeriod) {
                throw new Exception("The payment date does not fall within any unlocked accounting period.");
            }

            $uniqueNo = 'TAX-PAY-' . time() . '-' . rand(100, 999);
            $taxPayableAccountId = ChartOfAccount::getSalesTaxPayableId();

            if (!$taxPayableAccountId) {
                throw new Exception("Sales tax payable account not properly configured in Chart of Accounts.");
            }

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

            $balances = $this->calculateBalances($taxPeriod->company_id, $taxPeriod->start_date, $taxPeriod->end_date);
            $remainingAmount = $amount;

            if ($taxPeriod->net_amount > 0) {
                // Net Payable -> Pay to Government
                $journalEntries[] = array_merge($journalCommon, [
                    'account_id' => $taxPayableAccountId,
                    'description' => "Partial Tax Payment for period {$taxPeriod->name}",
                    'narration' => $reference ?: "Partial Tax Payment",
                    'debit' => $amount,
                    'credit' => 0,
                ]);

                $journalEntries[] = array_merge($journalCommon, [
                    'account_id' => $bankAccountId,
                    'description' => "Partial Tax Payment for period {$taxPeriod->name}",
                    'narration' => $reference ?: "Partial Tax Payment",
                    'debit' => 0,
                    'credit' => $amount,
                ]);

                // Distribute across customers (Payables)
                foreach ($balances['payable_breakdown'] as $row) {
                    if ($remainingAmount <= 0) break;
                    if ($row->balance <= 0) continue;

                    $allocation = min($remainingAmount, $row->balance);

                    $transactions[] = array_merge($transactionCommon, [
                        'account_id' => $taxPayableAccountId,
                        'customer_id' => $row->customer_id,
                        'description' => "Partial Tax Payment: Clear Payable",
                        'type' => 'Tax Payment',
                        'debit' => $allocation,
                        'credit' => 0,
                    ]);

                    $transactions[] = array_merge($transactionCommon, [
                        'account_id' => $bankAccountId,
                        'customer_id' => $row->customer_id,
                        'description' => "Partial Tax Payment: Bank Payment",
                        'type' => 'Tax Payment',
                        'debit' => 0,
                        'credit' => $allocation,
                    ]);

                    $remainingAmount -= $allocation;
                }

                // If there's still amount remaining (e.g. paying more than total customer balance? or mismatch), put remainder on null entity
                if ($remainingAmount > 0) {
                    $transactions[] = array_merge($transactionCommon, [
                        'account_id' => $taxPayableAccountId,
                        'description' => "Partial Tax Payment: Clear Payable (General)",
                        'type' => 'Tax Payment',
                        'debit' => $remainingAmount,
                        'credit' => 0,
                    ]);

                    $transactions[] = array_merge($transactionCommon, [
                        'account_id' => $bankAccountId,
                        'description' => "Partial Tax Payment: Bank Payment (General)",
                        'type' => 'Tax Payment',
                        'debit' => 0,
                        'credit' => $remainingAmount,
                    ]);
                }
            } else {
                // Net Receivable -> Refund from Government
                $journalEntries[] = array_merge($journalCommon, [
                    'account_id' => $bankAccountId,
                    'description' => "Partial Tax Refund for period {$taxPeriod->name}",
                    'narration' => $reference ?: "Partial Tax Refund",
                    'debit' => $amount,
                    'credit' => 0,
                ]);

                $journalEntries[] = array_merge($journalCommon, [
                    'account_id' => $taxPayableAccountId,
                    'description' => "Partial Tax Refund for period {$taxPeriod->name}",
                    'narration' => $reference ?: "Partial Tax Refund",
                    'debit' => 0,
                    'credit' => $amount,
                ]);

                // Distribute across vendors (Receivables)
                foreach ($balances['receivable_breakdown'] as $row) {
                    if ($remainingAmount <= 0) break;
                    if ($row->balance <= 0) continue;

                    $allocation = min($remainingAmount, $row->balance);

                    $transactions[] = array_merge($transactionCommon, [
                        'account_id' => $bankAccountId,
                        'vendor_id' => $row->vendor_id,
                        'description' => "Partial Tax Refund: Bank Deposit",
                        'type' => 'Tax Refund',
                        'debit' => $allocation,
                        'credit' => 0,
                    ]);

                    $transactions[] = array_merge($transactionCommon, [
                        'account_id' => $taxPayableAccountId,
                        'vendor_id' => $row->vendor_id,
                        'description' => "Partial Tax Refund: Clear Receivable",
                        'type' => 'Tax Refund',
                        'debit' => 0,
                        'credit' => $allocation,
                    ]);

                    $remainingAmount -= $allocation;
                }

                // Remainder handling
                if ($remainingAmount > 0) {
                    $transactions[] = array_merge($transactionCommon, [
                        'account_id' => $bankAccountId,
                        'description' => "Partial Tax Refund: Bank Deposit (General)",
                        'type' => 'Tax Refund',
                        'debit' => $remainingAmount,
                        'credit' => 0,
                    ]);

                    $transactions[] = array_merge($transactionCommon, [
                        'account_id' => $taxPayableAccountId,
                        'description' => "Partial Tax Refund: Clear Receivable (General)",
                        'type' => 'Tax Refund',
                        'debit' => 0,
                        'credit' => $remainingAmount,
                    ]);
                }
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
                'unique_no' => $uniqueNo,
            ]);

            return true;
        });
    }

    /**
     * Check if a date falls within a paid (locked) tax period.
     */
    public function isDateLocked($companyId, $date)
    {
        return TaxPeriod::where('company_id', $companyId)
            ->where('status', 'paid')
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->exists();
    }
}
