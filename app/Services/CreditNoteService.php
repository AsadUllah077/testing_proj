<?php

namespace App\Services;

use App\Models\AccountingPeriod;
use App\Models\CreditNote;
use App\Models\CreditNoteAllocation;
use App\Models\CreditNoteItem;
use App\Models\CreditNoteRefund;
// use App\Models\CreditNoteAuditLog; // (kept) not used here yet
use App\Models\Sale;
// use App\Models\SaleItem; // (kept) not used here
use App\Models\TaxRate;
use App\Models\Transaction;
use App\Models\ChartOfAccount;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreditNoteService
{
    /**
     * Validate accounting period
     */
    public function validateAccountingPeriod(string $date, ?int $companyId = null): AccountingPeriod
    {
        $companyId = $companyId ?? (loggedCompanyId()['company_id'] ?? null);
        $period = AccountingPeriod::findPeriodForDate($date, $companyId);

        if (! $period) {
            throw new \Exception('The selected date does not fall within any unlocked accounting period.');
        }

        return $period;
    }

    /**
     * Calculate totals for credit note
     *
     * IMPORTANT:
     * - Uses CENT-based rounding (row-level) to avoid 0.01 issues and to keep
     *   header totals aligned with line totals and journal totals.
     */
    public function calculateTotals(array $items): array
    {
        $subTotalCents = 0;
        $discountTotalCents = 0;
        $taxTotalCents = 0;

        $totalBeforeTaxCents = 0; // after discount, before tax
        $grandTotalCents = 0;     // after discount + tax

        foreach ($items as $item) {
            $quantity = (float) ($item['quantity'] ?? 0);
            $kg = (float) ($item['kg'] ?? 0);
            $price = (float) ($item['price'] ?? 0);

            $discountPercentage = (float) (
                $item['discount']
                ?? $item['discount_percentage']
                ?? 0
            );

            $taxId = $item['tax_id'] ?? null;

            $units = $kg > 0 ? $kg : $quantity;

            // Row subtotal (GROSS) in cents
            $rowSubtotalCents = (int) round($units * $price * 100);

            // Discount in cents
            $rowDiscountCents = (int) round(($rowSubtotalCents * $discountPercentage) / 100);

            // Net after discount in cents
            $rowNetCents = $rowSubtotalCents - $rowDiscountCents;

            // Tax in cents (computed on NET after discount)
            $rowTaxCents = 0;
            if ($taxId) {
                $tax = TaxRate::find($taxId);
                if ($tax) {
                    $rowTaxCents = (int) round(($rowNetCents * (float) $tax->tax_rate) / 100);
                }
            }

            // Accumulate
            $subTotalCents += $rowSubtotalCents;
            $discountTotalCents += $rowDiscountCents;
            $totalBeforeTaxCents += $rowNetCents;
            $taxTotalCents += $rowTaxCents;
            $grandTotalCents += ($rowNetCents + $rowTaxCents);
        }

        return [
            'sub_total' => round($subTotalCents / 100, 2),
            'discount' => round($discountTotalCents / 100, 2),
            'tax_total' => round($taxTotalCents / 100, 2),
            'total' => round($totalBeforeTaxCents / 100, 2),            // net after discount, before tax
            'total_after_discount' => round($grandTotalCents / 100, 2), // net after discount + tax (final)
        ];
    }

    /**
     * Split credit note usage into invoice allocation and cash refund.
     *
     * Linked sales credit notes are applied to the selected invoice first. Cash
     * refunds remain available only for standalone credit notes.
     */
    private function calculateCreditUsage(array $data, array $totals): array
    {
        $total = round((float) $totals['total_after_discount'], 2);
        $cashRefundAmount = round((float) ($data['paid_amount'] ?? 0), 2);
        $allocatedAmount = 0.0;
        $saleUniqueNo = null;

        if (!empty($data['sale_id'])) {
            $companyId = (int) (Auth::user()?->company_id ?? loggedCompanyId()['company_id']);
            $sale = Sale::whereKey($data['sale_id'])
                ->where('company_id', $companyId)
                ->where('customer_id', $data['customer_id'])
                ->lockForUpdate()
                ->firstOrFail();
            $saleUniqueNo = $sale->unique_no;
            $cashRefundAmount = 0.0;
            $allocatedAmount = min($total, round((float) $sale->remaining_amount, 2));
        }

        if ($cashRefundAmount > $total) {
            throw new \Exception('Paid amount cannot be greater than the total amount.');
        }

        $remainingAmount = max(0, round($total - $allocatedAmount - $cashRefundAmount, 2));

        return [
            'sale_unique_no' => $saleUniqueNo,
            'cash_refund_amount' => $cashRefundAmount,
            'allocated_amount' => $allocatedAmount,
            'remaining_amount' => $remainingAmount,
        ];
    }

    /**
     * Create a new credit note
     */
    public function createCreditNote(array $data, $request): CreditNote
    {
        // $request is kept for signature compatibility (not used in service layer currently).
        return DB::transaction(function () use ($data, $request) {

            $companyId = Auth::user()->company_id;
            $period = $this->validateAccountingPeriod($data['date'], $companyId);
            $totals = $this->calculateTotals($data['items']);
            $usage = $this->calculateCreditUsage($data, $totals);

            if ($usage['cash_refund_amount'] > $totals['total_after_discount']) {
                throw new \Exception('Paid amount cannot be greater than the total amount.');
            }

            $uniqueNo = CreditNote::generateUniqueNo();
            $authId = Auth::id();
            $creditNote = CreditNote::create([
                'company_id' => $companyId,
                'accounting_period_id' => $period->id,
                'customer_id' => $data['customer_id'],
                'account_id' => $data['payment_type'] ?? null,
                'currency_id' => $data['currency_id'],
                'sale_id' => $data['sale_id'] ?? null,
                'sale_unique_no' => $usage['sale_unique_no'],
                'unique_no' => $uniqueNo,
                'invoice_no' => $data['invoice_no'] ?? null,
                'date' => $data['date'],
                'ref_no' => $data['ref_no'] ?? null,
                'sub_total' => $totals['sub_total'],
                'tax' => $totals['tax_total'],
                'discount' => $totals['discount'],
                'total' => $totals['total'], // net after discount, before tax (kept as your column)
                'total_after_discount' => $totals['total_after_discount'], // final total (incl tax)
                'paid_amount' => $usage['cash_refund_amount'],
                'allocated_amount' => $usage['allocated_amount'],
                'remaining_amount' => $usage['remaining_amount'],
                'status' => 1,
                'created_by' => $authId,
                'updated_by' => $authId,
            ]);

            $this->createItemsAndTransactions($creditNote, $data, $period, $totals);
            $this->syncWithLinkedSale($creditNote);

            return $creditNote;
        });
    }

    /**
     * Update an existing credit note
     */
    public function updateCreditNote(CreditNote $creditNote, array $data, $request): CreditNote
    {
        // $request is kept for signature compatibility (not used in service layer currently).
        return DB::transaction(function () use ($creditNote, $data, $request) {

            $period = $this->validateAccountingPeriod($data['date'], $creditNote->company_id);
            $totals = $this->calculateTotals($data['items']);
            $authId = Auth::id();

            // Revert old linked sale impact BEFORE updating the sale_id
            $this->revertSaleImpact($creditNote);
            $usage = $this->calculateCreditUsage($data, $totals);

            if ($usage['cash_refund_amount'] > $totals['total_after_discount']) {
                throw new \Exception('Paid amount cannot be greater than the total amount.');
            }

            $creditNote->update([
                'accounting_period_id' => $period->id,
                'customer_id' => $data['customer_id'],
                'account_id' => $data['payment_type'] ?? null,
                'currency_id' => $data['currency_id'],
                'sale_id' => $data['sale_id'] ?? null,
                'sale_unique_no' => $usage['sale_unique_no'],
                'invoice_no' => $data['invoice_no'] ?? null,
                'date' => $data['date'],
                'ref_no' => $data['ref_no'] ?? null,
                'sub_total' => $totals['sub_total'],
                'tax' => $totals['tax_total'],
                'discount' => $totals['discount'],
                'total' => $totals['total'], // net after discount, before tax (kept)
                'total_after_discount' => $totals['total_after_discount'],
                'paid_amount' => $usage['cash_refund_amount'],
                'allocated_amount' => $usage['allocated_amount'],
                'remaining_amount' => $usage['remaining_amount'],
                'updated_by' => $authId,
            ]);

            // Clear old items and ALL transactions tied to this credit note
            $creditNote->creditNoteItems()->delete();

            // IMPORTANT FIX:
            // Previously you deleted only unique_no == CRN..., but REF-... journal remained.
            Transaction::where('reference_id', $creditNote->id)
                ->where('type', 'Credit Note')
                ->delete();

            $this->createItemsAndTransactions($creditNote, $data, $period, $totals);
            $this->syncWithLinkedSale($creditNote);

            return $creditNote;
        });
    }

    /**
     * Create credit note items and their accounting transactions.
     *
     * ACCOUNTING (Correct):
     * - DR Revenue (GROSS)
     * - DR Sales Tax Payable (reverse output tax)
     * - CR Sales Discounts (reverse the contra-revenue)
     * - CR Accounts Receivable (reduce customer receivable / create customer credit)
     *
     * Refund (if paid_amount > 0):
     * - DR Accounts Receivable
     * - CR Bank/Cash
     */
    private function createItemsAndTransactions(CreditNote $creditNote, array $data, $period, array $totals): void
    {
        $companyId = $creditNote->company_id ?? loggedCompanyId()['company_id'];
        $userId = Auth::id();
        $now = now();

        $creditNoteItems = [];
        $transactions = [];

        if (empty($data['items']) || !is_array($data['items'])) {
            throw new \Exception('No items provided for credit note.');
        }

        // Get account IDs
        $discountAccountId = ChartOfAccount::getSalesDiscountAccountId($companyId);
        $taxAccountId = ChartOfAccount::getSalesTaxPayableId($companyId);
        $arAccountId = ChartOfAccount::getAccountsReceivableId($companyId);

        if (!$arAccountId) {
            throw new \Exception('Accounts Receivable account not found.');
        }

        // Config validation for accounting correctness
        if ($totals['discount'] > 0 && !$discountAccountId) {
            throw new \Exception('Sales Discounts account not found (required because discount is used).');
        }
        if ($totals['tax_total'] > 0 && !$taxAccountId) {
            throw new \Exception('Sales Tax Payable account not found (required because tax is used).');
        }

        // Accumulators (cent-based)
        $revenueByAccountCents = []; // revenue_account_id => gross cents
        $computedSubTotalCents = 0;
        $computedDiscountCents = 0;
        $computedTaxCents = 0;
        $computedGrandCents = 0;

        // 1) Process items and create credit note items
        foreach ($data['items'] as $index => $item) {
            if (!isset($item['account_id'])) {
                throw new \Exception("Account ID is missing for item at index {$index}");
            }

            $qty = (float)($item['quantity'] ?? 0);
            $kg = (float)($item['kg'] ?? 0);
            $price = (float)($item['price'] ?? 0);

            $discountPercentage = (float)($item['discount'] ?? $item['discount_percentage'] ?? 0);

            $units = $kg > 0 ? $kg : $qty;

            $rowSubtotalCents = (int) round($units * $price * 100);
            $rowDiscountCents = (int) round(($rowSubtotalCents * $discountPercentage) / 100);
            $rowNetCents = $rowSubtotalCents - $rowDiscountCents;

            $rowTaxCents = 0;
            if (!empty($item['tax_id'])) {
                $taxRate = TaxRate::find($item['tax_id']);
                if ($taxRate) {
                    $rowTaxCents = (int) round(($rowNetCents * (float)$taxRate->tax_rate) / 100);
                }
            }

            $rowTotalCents = $rowNetCents + $rowTaxCents;

            // Revenue reversal should be GROSS (subtotal), grouped by revenue account
            $revenueAccountId = $item['account_id'];
            $revenueByAccountCents[$revenueAccountId] = ($revenueByAccountCents[$revenueAccountId] ?? 0) + $rowSubtotalCents;

            // Accumulate for validation
            $computedSubTotalCents += $rowSubtotalCents;
            $computedDiscountCents += $rowDiscountCents;
            $computedTaxCents += $rowTaxCents;
            $computedGrandCents += $rowTotalCents;

            // Store item
            $creditNoteItems[] = [
                'company_id' => $companyId,
                'accounting_period_id' => $period->id,
                'credit_note_id' => $creditNote->id,
                'unique_no' => $creditNote->unique_no,
                'item' => $item['item'] ?? 'Item ' . ($index + 1),
                'description' => $item['description'] ?? null,
                'hs_code' => $item['hs_code'] ?? null,
                'quantity' => $item['quantity'] ?? 0,
                'kg' => $item['kg'] ?? 0,
                'price' => $item['price'] ?? 0,
                'discount' => $discountPercentage,
                'account_id' => $item['account_id'],
                'tax_id' => $item['tax_id'] ?? null,
                'total' => round($rowTotalCents / 100, 2),
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Cross-check header totals vs lines (prevents silent rounding mismatches)
        $expectedSubTotalCents = (int) round($totals['sub_total'] * 100);
        $expectedDiscountCents = (int) round($totals['discount'] * 100);
        $expectedTaxCents = (int) round($totals['tax_total'] * 100);
        $expectedGrandCents = (int) round($totals['total_after_discount'] * 100);

        if (
            $computedSubTotalCents !== $expectedSubTotalCents ||
            $computedDiscountCents !== $expectedDiscountCents ||
            $computedTaxCents !== $expectedTaxCents ||
            $computedGrandCents !== $expectedGrandCents
        ) {
            throw new \Exception('Header totals do not match sum of line totals. Please re-check rounding and item data.');
        }

        // 2) CREDIT NOTE JOURNAL (CRN...) - Main credit note entries

        // (A) Debit Revenue accounts (GROSS)
        foreach ($revenueByAccountCents as $revAccountId => $grossCents) {
            if ((int)$grossCents === 0) continue;

            $transactions[] = [
                'company_id' => $companyId,
                'accounting_period_id' => $period->id,
                'account_id' => $revAccountId,
                'customer_id' => $creditNote->customer_id,
                'payable_type' => 'customer',
                'payable_id' => $creditNote->customer_id,
                'reference_id' => $creditNote->id,
                'unique_no' => $creditNote->unique_no,
                'invoice_no' => $creditNote->invoice_no,
                'date' => $creditNote->date,
                'type' => 'Credit Note',
                'description' => "Credit Note Balance: {$creditNote->unique_no}",
                'debit' => round($grossCents / 100, 2),
                'credit' => 0,
                'status' => 1,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // (B) Debit Sales Tax Payable (reverse tax liability)
        if ($expectedTaxCents > 0) {
            $transactions[] = [
                'company_id' => $companyId,
                'accounting_period_id' => $period->id,
                'account_id' => $taxAccountId,
                'customer_id' => $creditNote->customer_id,
                'payable_type' => 'customer',
                'payable_id' => $creditNote->customer_id,
                'reference_id' => $creditNote->id,
                'unique_no' => $creditNote->unique_no,
                'invoice_no' => $creditNote->invoice_no,
                'date' => $creditNote->date,
                'type' => 'Credit Note',
                'description' => "Credit Note Balance: {$creditNote->unique_no}",
                'debit' => round($expectedTaxCents / 100, 2),
                'credit' => 0,
                'status' => 1,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // (C) Credit Sales Discounts (reverse discount)
        if ($expectedDiscountCents > 0) {
            $transactions[] = [
                'company_id' => $companyId,
                'accounting_period_id' => $period->id,
                'account_id' => $discountAccountId,
                'customer_id' => $creditNote->customer_id,
                'payable_type' => 'customer',
                'payable_id' => $creditNote->customer_id,
                'reference_id' => $creditNote->id,
                'unique_no' => $creditNote->unique_no,
                'invoice_no' => $creditNote->invoice_no,
                'date' => $creditNote->date,
                'type' => 'Credit Note',
                'description' => "Credit Note Balance: {$creditNote->unique_no}",
                'debit' => 0,
                'credit' => round($expectedDiscountCents / 100, 2),
                'status' => 1,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // (D) Credit Accounts Receivable (customer credit / reduce AR)
        $transactions[] = [
            'company_id' => $companyId,
            'accounting_period_id' => $period->id,
            'account_id' => $arAccountId,
            'customer_id' => $creditNote->customer_id,
            'payable_type' => 'customer',
            'payable_id' => $creditNote->customer_id,
            'reference_id' => $creditNote->id,
            'unique_no' => $creditNote->unique_no,
            'invoice_no' => $creditNote->invoice_no,
            'description' => "Credit Note Balance: {$creditNote->unique_no}",
            'date' => $creditNote->date,
            'type' => 'Credit Note',
            'debit' => 0,
            'credit' => round($expectedGrandCents / 100, 2),
            'status' => 1,
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        // 3) REFUND JOURNAL (REF-xxxxxx) - Only if paid_amount > 0
        if ($creditNote->paid_amount > 0) {
            if (!$creditNote->account_id) {
                // IMPORTANT FIX:
                // Without bank/cash account, you would create an unbalanced refund journal.
                throw new \Exception('Payment account (bank/cash) is required when paid amount is greater than zero.');
            }

            $bankAccountIsValid = ChartOfAccount::forCompany($companyId)
                ->whereKey($creditNote->account_id)
                ->where('type', 'Account')
                ->where('sub_type', 'BANK')
                ->exists();

            if (!$bankAccountIsValid) {
                throw new \Exception('A valid company bank/cash account is required for the customer refund.');
            }

            $refund = CreditNoteRefund::create([
                'company_id' => $companyId,
                'credit_note_id' => $creditNote->id,
                'account_id' => $creditNote->account_id,
                'accounting_period_id' => $period->id,
                'refund_no' => 'PENDING-' . bin2hex(random_bytes(8)),
                'refund_date' => $creditNote->date,
                'amount' => $creditNote->paid_amount,
                'description' => "Customer refund for credit note {$creditNote->unique_no}",
                'created_by' => $userId,
            ]);
            $refundUniqueNo = 'CRF' . now()->format('Ym') . str_pad((string) $refund->id, 6, '0', STR_PAD_LEFT);
            $refund->update(['refund_no' => $refundUniqueNo]);
            $paidCents = (int) round($creditNote->paid_amount * 100);

            // Debit Accounts Receivable (reduce customer credit / settle)
            $transactions[] = [
                'company_id' => $companyId,
                'accounting_period_id' => $period->id,
                'account_id' => $arAccountId,
                'customer_id' => $creditNote->customer_id,
                'payable_type' => 'customer',
                'payable_id' => $creditNote->customer_id,
                'reference_id' => $creditNote->id,
                'unique_no' => $refundUniqueNo,
                'invoice_no' => $creditNote->invoice_no,
                'description' => "Payment for Credit Note {$creditNote->unique_no}",
                'date' => $creditNote->date,
                'type' => 'CreditNoteRefund',
                'debit' => round($paidCents / 100, 2),
                'credit' => 0,
                'status' => 1,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // Credit Bank/Cash Account (money going out)
            $transactions[] = [
                'company_id' => $companyId,
                'accounting_period_id' => $period->id,
                'account_id' => $creditNote->account_id,
                'customer_id' => $creditNote->customer_id,
                'payable_type' => 'customer',
                'payable_id' => $creditNote->customer_id,
                'reference_id' => $creditNote->id,
                'unique_no' => $refundUniqueNo,
                'invoice_no' => $creditNote->invoice_no,
                'description' => "Payment for Credit Note {$creditNote->unique_no}",
                'date' => $creditNote->date,
                'type' => 'CreditNoteRefund',
                'debit' => 0,
                'credit' => round($paidCents / 100, 2),
                'status' => 1,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Validate journals are balanced per unique_no (CRN... and REF-...)
        $this->assertJournalsBalanced($transactions);

        // Insert all records
        if (!empty($creditNoteItems)) {
            CreditNoteItem::insert($creditNoteItems);
        }

        if (!empty($transactions)) {
            Transaction::insert($transactions);
        }
    }

    /**
     * Throws if any journal (grouped by unique_no) is not balanced.
     */
    private function assertJournalsBalanced(array $transactions): void
    {
        $groups = [];
        foreach ($transactions as $t) {
            $u = $t['unique_no'] ?? 'UNKNOWN';
            $groups[$u][] = $t;
        }

        foreach ($groups as $uniqueNo => $rows) {
            $debit = 0.0;
            $credit = 0.0;

            foreach ($rows as $r) {
                $debit += (float)($r['debit'] ?? 0);
                $credit += (float)($r['credit'] ?? 0);
            }

            $debit = round($debit, 2);
            $credit = round($credit, 2);

            if ($debit !== $credit) {
                Log::error("Unbalanced journal {$uniqueNo}", [
                    'debit' => $debit,
                    'credit' => $credit,
                    'diff' => round($credit - $debit, 2),
                ]);
                throw new \Exception("Unbalanced journal entries for {$uniqueNo}.");
            }
        }
    }

    public function generateRefundUniqueNo()
    {
        $latestRefund = Transaction::where('unique_no', 'like', 'REF-%')->orderBy('id', 'desc')->first();

        if ($latestRefund) {
            $number = intval(substr($latestRefund->unique_no, 4)) + 1;
        } else {
            $number = 1;
        }

        return 'REF-' . str_pad($number, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate sub total (kept; not used by create/update flow above)
     */
    public function calculateSubTotal(array $rows): float
    {
        $subTotal = 0;

        foreach ($rows as $row) {
            $quantity = floatval($row['quantity'] ?? 1);
            $kg = floatval($row['kg'] ?? 0);
            $price = floatval($row['price'] ?? 0);

            if ($kg > 0) {
                $subTotal += $kg * $price;
            } elseif ($quantity > 0) {
                $subTotal += $quantity * $price;
            }
        }

        return $subTotal;
    }

    /**
     * Calculate tax total (kept; not used by create/update flow above)
     */
    public function calculateTaxTotal(array $rows): float
    {
        $taxTotal = 0;

        foreach ($rows as $row) {
            $quantity = floatval($row['quantity'] ?? 1);
            $kg = floatval($row['kg'] ?? 0);
            $price = floatval($row['price'] ?? 0);
            $discountPercentage = floatval($row['discount_percentage'] ?? 0);
            $taxId = $row['tax_id'] ?? null;

            $rowSubtotal = 0;
            if ($kg > 0) {
                $rowSubtotal = $kg * $price;
            } elseif ($quantity > 0) {
                $rowSubtotal = $quantity * $price;
            }

            $discountAmount = ($rowSubtotal * $discountPercentage) / 100;
            $amountAfterDiscount = $rowSubtotal - $discountAmount;

            if ($taxId) {
                $tax = TaxRate::find($taxId);
                if ($tax) {
                    $taxAmount = ($amountAfterDiscount * $tax->tax_rate) / 100;
                    $taxTotal += $taxAmount;
                }
            }
        }

        return $taxTotal;
    }

    /**
     * Calculate discount total (kept; not used by create/update flow above)
     */
    public function calculateDiscountTotal(array $rows): float
    {
        $discountTotal = 0;

        foreach ($rows as $row) {
            $quantity = floatval($row['quantity'] ?? 1);
            $kg = floatval($row['kg'] ?? 0);
            $price = floatval($row['price'] ?? 0);
            $discountPercentage = floatval($row['discount_percentage'] ?? 0);

            $rowSubtotal = 0;
            if ($kg > 0) {
                $rowSubtotal = $kg * $price;
            } elseif ($quantity > 0) {
                $rowSubtotal = $quantity * $price;
            }

            $discountAmount = ($rowSubtotal * $discountPercentage) / 100;
            $discountTotal += $discountAmount;
        }

        return $discountTotal;
    }

    /**
     * Sync with linked sale
     */
    private function syncWithLinkedSale(CreditNote $creditNote): void
    {
        if ($creditNote->sale_id && (float) $creditNote->allocated_amount > 0) {
            $sale = Sale::find($creditNote->sale_id);
            if ($sale) {
                // IMPORTANT:
                // Sale remaining_amount normally represents the customer's receivable (incl tax).
                // So we reduce it only by the amount actually allocated to this invoice.
                CreditNoteAllocation::create([
                    'company_id' => $creditNote->company_id,
                    'credit_note_id' => $creditNote->id,
                    'sale_id' => $sale->id,
                    'allocation_date' => $creditNote->date,
                    'amount' => $creditNote->allocated_amount,
                    'created_by' => Auth::id(),
                ]);

                $sale->remaining_amount -= $creditNote->allocated_amount;

                if ($sale->remaining_amount < 0) {
                    $sale->remaining_amount = 0;
                }

                $sale->credit_note_status = 1;
                $sale->payment_status = app(SaleService::class)->paymentStatus(
                    (float) $sale->paid_amount,
                    (float) $sale->remaining_amount
                );
                $sale->save();
            }
        }
    }

    /**
     * Revert linked sale impact
     */
    private function revertSaleImpact(CreditNote $creditNote): void
    {
        $allocations = $creditNote->allocations()->get();
        if ($allocations->isNotEmpty()) {
            foreach ($allocations as $allocation) {
                $sale = Sale::find($allocation->sale_id);
                if ($sale) {
                    $sale->remaining_amount = round((float) $sale->remaining_amount + (float) $allocation->amount, 2);
                    $sale->payment_status = app(SaleService::class)->paymentStatus(
                        (float) $sale->paid_amount,
                        (float) $sale->remaining_amount
                    );
                    $sale->save();
                }
            }

            $creditNote->allocations()->delete();
            $this->refreshCreditNoteStatuses($allocations->pluck('sale_id')->all(), $creditNote->id);
            return;
        }

        if ($creditNote->sale_id) {
            $sale = Sale::find($creditNote->sale_id);
            if ($sale) {
                $sale->remaining_amount += $creditNote->allocated_amount;
                $sale->payment_status = app(SaleService::class)->paymentStatus(
                    (float) $sale->paid_amount,
                    (float) $sale->remaining_amount
                );

                $otherCount = CreditNote::where('sale_id', $creditNote->sale_id)
                    ->where('id', '!=', $creditNote->id)
                    ->count();

                if ($otherCount === 0) {
                    $sale->credit_note_status = 0;
                }

                $sale->save();
            }
        }
    }

    private function refreshCreditNoteStatuses(array $saleIds, int $excludingCreditNoteId): void
    {
        foreach (array_unique($saleIds) as $saleId) {
            $hasOtherAllocations = CreditNoteAllocation::where('sale_id', $saleId)
                ->where('credit_note_id', '!=', $excludingCreditNoteId)
                ->exists();
            Sale::whereKey($saleId)->update(['credit_note_status' => $hasOtherAllocations ? 1 : 0]);
        }
    }

    /**
     * Delete credit note and its impacts
     */
    public function deleteCreditNote(CreditNote $creditNote): void
    {
        DB::transaction(function () use ($creditNote) {

            $this->revertSaleImpact($creditNote);

            $creditNote->creditNoteItems()->delete();
            Transaction::where('reference_id', $creditNote->id)
                ->where('type', 'Credit Note')
                ->delete();

            // IMPORTANT FIX: removed duplicate revertSaleImpact()

            $creditNote->delete();
        });
    }
}
