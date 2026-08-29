<?php

namespace App\Services;

use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\Sale;
use App\Models\SaleAttachments;
use App\Models\SaleItem;
use App\Models\TaxRate;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SaleService
{
    /**
     * Validate accounting period
     */
    public function validateAccountingPeriod(string $date): AccountingPeriod
    {
        $period = AccountingPeriod::findPeriodForDate($date);

        if (! $period) {
            throw new \Exception('The selected date does not fall within any unlocked accounting period.');
        }

        return $period;
    }

    /**
     * Calculate totals from rows
     */
   public function calculateTotals(array $rows): array
{
    $subTotal = 0;
    $discountTotal = 0;
    $taxTotal = 0;
    $total = 0;

    foreach ($rows as $row) {
        $quantity = floatval($row['quantity'] ?? 0);
        $kg = floatval($row['kg'] ?? 0);
        $price = floatval($row['price'] ?? 0);
        $discountPercentage = floatval($row['discount_percentage'] ?? 0);
        $taxId = $row['tax_id'] ?? null;

        // Calculate subtotal for the row
        $rowSubtotal = 0;
        if ($kg > 0) {
            $rowSubtotal = $price; // If using kg, price is per kg
        } else if ($quantity > 0) {
            $rowSubtotal = $quantity * $price;
        }

        $subTotal += $rowSubtotal;

        // Calculate discount amount
        $discountAmount = ($rowSubtotal * $discountPercentage) / 100;
        $discountTotal += $discountAmount;

        // Calculate amount after discount
        $amountAfterDiscount = $rowSubtotal - $discountAmount;

        // Calculate tax on discounted amount
        if ($taxId) {
            $tax = TaxRate::find($taxId);
            if ($tax) {
                $taxAmount = ($amountAfterDiscount * $tax->rate) / 100;
                $taxTotal += $taxAmount;
            }
        }

        // Calculate row total (after discount + tax)
        $rowTotal = $amountAfterDiscount + ($taxAmount ?? 0);
        $total += $rowTotal;
    }

    return [
        'sub_total' => $subTotal,
        'discount' => $discountTotal,
        'tax_total' => $taxTotal,
        'total' => $total,
    ];
}

    /**
     * Create sale record
     */
    public function createSaleRecord(
        array $validatedData,
        AccountingPeriod $period,
        array $totals,
        float $remainingAmount
    ): Sale {
        $nextID = (Sale::max('id') ?? 0) + 1;
        $uniqueNo = 'SO-'.str_pad($nextID, 5, '0', STR_PAD_LEFT);
        $authId = Auth::id();

        return Sale::create([
            'accounting_period_id' => $period->id,
            'customer_id' => $validatedData['customer_id'],
            'account_id' => $validatedData['payment_type'] ?? null,
            'currency_id' => $validatedData['currency_id'],
            'unique_no' => $uniqueNo,
            'invoice_no' => $validatedData['invoice_no'],
            'date' => $validatedData['date'],
            'ref_no' => $validatedData['ref_no'] ?? null,
            'note' => $validatedData['note'] ?? null,
            'sub_total' => $totals['sub_total'],
            'tax' => $totals['tax_total'],
            'total' => $totals['total'],
            'total_after_discount' => $totals['total_after_discount'],
            'paid_amount' => $validatedData['paid_amount'] ?? 0,
            'remaining_amount' => $remainingAmount,
            'created_by' => $authId,
            'updated_by' => $authId,
        ]);
    }

    /**
     * Handle file attachments
     */
    public function handleAttachments(Request $request, Sale $sale, AccountingPeriod $period): void
    {
        if (! $request->hasFile('attachment')) {
            return;
        }

        $authId = Auth::id();

        foreach ($request->file('attachment') as $file) {
            $original = $file->getClientOriginalName();
            $clean = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($original, PATHINFO_FILENAME));
            $timestamp = now()->format('Ymd_His');
            $newFile = "{$clean}_{$timestamp}.".$file->getClientOriginalExtension();

            $path = $file->storeAs('sale-attachments', $newFile, 'public');

            SaleAttachments::create([
                'sale_id' => $sale->id,
                'accounting_period_id' => $period->id,
                'attachment' => $path,
                'created_by' => $authId,
                'updated_by' => $authId,
            ]);
        }
    }

    /**
     * Create sale items and transactions
     */
    public function createSaleItemsAndTransactions(
        array $validatedData,
        Sale $sale,
        AccountingPeriod $period,
        array $totals,
        float $remainingAmount
    ): void {
        $companyId = loggedCompanyId()['company_id'];
        $authId = Auth::id();
        $now = now();

        $saleItems = [];
        $transactions = [];

        // Create sale items and revenue transactions
        foreach ($validatedData['rows'] as $row) {
            // $multiplier = $row['kg'] > 0 ? $row['kg'] : $row['quantity'];
            $itemTotal = $row['quantity'] ?? 1 * ($row['price'] ?? 0);
            $discount = $row['discount_percentage'] ??0;
        $discountAmount = ($itemTotal * $discount) / 100;

        $item_total = $itemTotal - $discountAmount;
            $saleItems[] = [
                'company_id' => $companyId,
                'accounting_period_id' => $period->id,
                'sale_id' => $sale->id,
                'unique_no' => $sale->unique_no,
                'item' => $row['item'] ?? null,
                'description' => $row['description'] ?? null,
                'hs_code' => $row['hs_code'] ?? null,
                'quantity' => $row['quantity'],
                'kg' => $row['kg'],
                'price' => $row['price'],
                'discount' => $row['discount_percentage'],
                'account_id' => $row['account_id'],
                'tax_id' => $row['tax_id'] ?? null,
                'total' => $item_total,
                'created_by' => $authId,
                'updated_by' => $authId,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // Revenue transaction
            $transactions[] = [
                'company_id' => $companyId,
                'accounting_period_id' => $period->id,
                'account_id' => $row['account_id'],
                'customer_id' => $validatedData['customer_id'],
                'reference_id' => $sale->id,
                'unique_no' => $sale->unique_no,
                'invoice_no' => $validatedData['invoice_no'],
                'description' => $row['description'] ?? $row['item'] ?? 'Sale Item',
                'date' => $validatedData['date'],
                'type' => 'Sale',
                'credit' => $item_total,
                'debit' => 0,
                'created_by' => $authId,
                'updated_by' => $authId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Payment transaction
        $paidAmount = $validatedData['paid_amount'] ?? 0;
        if ($paidAmount > 0 && ! empty($validatedData['payment_type'])) {
            $transactions[] = [
                'company_id' => $companyId,
                'accounting_period_id' => $period->id,
                'account_id' => $validatedData['payment_type'],
                'customer_id' => $validatedData['customer_id'],
                'reference_id' => $sale->id,
                'unique_no' => $sale->unique_no,
                'invoice_no' => $validatedData['invoice_no'],
                'description' => 'Payment for Sale '.$validatedData['invoice_no'],
                'date' => $validatedData['date'],
                'type' => 'Sale',
                'credit' => 0,
                'debit' => $paidAmount,
                'created_by' => $authId,
                'updated_by' => $authId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Remaining receivable
        if ($remainingAmount > 0) {
            $transactions[] = [
                'company_id' => $companyId,
                'accounting_period_id' => $period->id,
                'account_id' => ChartOfAccount::getAccountsReceivableId(),
                'customer_id' => $validatedData['customer_id'],
                'reference_id' => $sale->id,
                'unique_no' => $sale->unique_no,
                'invoice_no' => $validatedData['invoice_no'],
                'description' => 'Remaining Sale Amount '.$validatedData['invoice_no'],
                'date' => $validatedData['date'],
                'type' => 'Sale',
                'credit' => 0,
                'debit' => $remainingAmount,
                'created_by' => $authId,
                'updated_by' => $authId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        SaleItem::insert($saleItems);
        Transaction::insert($transactions);
    }

    public function calculateSubTotal(array $rows): float
    {
        $subTotal = 0;

        foreach ($rows as $row) {
            $quantity = floatval($row['quantity'] ?? 1);
            $kg = floatval($row['kg'] ?? 0);
            $price = floatval($row['price'] ?? 0);

            if ($kg > 0) {
                $subTotal += $price; // If using kg, price is per kg
            } elseif ($quantity > 0) {
                $subTotal += $quantity * $price;
            }
        }

        return $subTotal;
    }

    public function calculateTaxTotal(array $rows): float
    {
        $taxTotal = 0;

        foreach ($rows as $row) {
            $quantity = floatval($row['quantity'] ?? 1);
            $kg = floatval($row['kg'] ?? 0);
            $price = floatval($row['price'] ?? 0);
            $discountPercentage = floatval($row['discount_percentage'] ?? 0);
            $taxId = $row['tax_id'] ?? null;

            // Calculate subtotal for the row
            $rowSubtotal = 0;
            if ($kg > 0) {
                $rowSubtotal = $price; // If using kg, price is per kg
            } elseif ($quantity > 0) {
                $rowSubtotal = $quantity * $price;
            }

            // Calculate discount amount
            $discountAmount = ($rowSubtotal * $discountPercentage) / 100;
            $amountAfterDiscount = $rowSubtotal - $discountAmount;

            // Calculate tax on discounted amount
            if ($taxId) {
                $tax = TaxRate::find($taxId);
                if ($tax) {
                    $taxAmount = ($amountAfterDiscount * $tax->rate) / 100;
                    $taxTotal += $taxAmount;
                }
            }
        }

        return $taxTotal;
    }

    public function calculateDiscountTotal(array $rows): float
    {
        $discountTotal = 0;

        foreach ($rows as $row) {
            $quantity = floatval($row['quantity'] ?? 1);
            $kg = floatval($row['kg'] ?? 0);
            $price = floatval($row['price'] ?? 0);
            $discountPercentage = floatval($row['discount_percentage'] ?? 0);

            // Calculate subtotal for the row
            $rowSubtotal = 0;
            if ($kg > 0) {
                $rowSubtotal = $price; // If using kg, price is per kg
            } elseif ($quantity > 0) {
                $rowSubtotal = $quantity * $price;
            }

            // Calculate discount amount
            $discountAmount = ($rowSubtotal * $discountPercentage) / 100;
            $discountTotal += $discountAmount;
        }

        return $discountTotal;
    }

    public function updateSale(
        $sale,
        array $validatedData,
        AccountingPeriod $period,
        $subTotal,
        $taxTotal,
        $total,
        $total_before_discount,
        $paidAmount,
        $remainingAmount
    ) {

        dd($paidAmount);
        $sale->update([
            'accounting_period_id' => $period->id,
            'customer_id' => $validatedData['customer_id'],
            'account_id' => $validatedData['payment_type'],
            'currency_id' => $validatedData['currency_id'],
            'invoice_no' => $validatedData['invoice_no'],
            'date' => $validatedData['date'],
            'ref_no' => $validatedData['ref_no'],
            'note' => $validatedData['note'],
            'sub_total' => $subTotal,
            'tax' => $taxTotal,
            'total' => $total,
            'total_before_discount' => $total_before_discount,
            'paid_amount' => $paidAmount,
            'remaining_amount' => $remainingAmount,
            'updated_by' => Auth::id(),
        ]);
        return $paidAmount;
    }

    public function deleteSaleItemAndTransaction(Sale $sale)
    {
        SaleItem::where('sale_id', $sale->id)->delete();
        Transaction::where('unique_no', $sale->unique_no)->delete();
    }

    public function updateSaleItemAndTransaction(
        array $validatedData,
        Sale $sale,
        AccountingPeriod $period,
        $userId,
        $paidAmount,
        $remainingAmount
    ) {
        $saleItems = [];
        $transactions = [];
        $companyId = loggedCompanyId()['company_id'];
        $now = now();

        foreach ($validatedData['rows'] as $row) {
            // $itemTotal = ($row['quantity'] ?? 1) * ($row['price'] ?? 0);

            $itemTotal = $row['quantity'] ?? 1 * ($row['price'] ?? 0);
            $discount = $row['discount_percentage'] ??0;
        $discountAmount = ($itemTotal * $discount) / 100;
        $item_total = $itemTotal - $discountAmount;

            $saleItems[] = [
                'company_id' => $companyId,
                'accounting_period_id' => $period->id,
                'sale_id' => $sale->id,
                'unique_no' => $sale->unique_no,
                'item' => $row['item'],
                'description' => $row['description'],
                'hs_code' => $row['hs_code'],
                'quantity' => $row['quantity'],
                'kg' => $row['kg'],
                'price' => $row['price'],
                'discount' => $row['discount_percentage'],
                'account_id' => $row['account_id'],
                'tax_id' => $row['tax_id'],
                'total' => $item_total,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $transactions[] = [
                'company_id' => $companyId,
                'accounting_period_id' => $period->id,
                'account_id' => $row['account_id'],
                'customer_id' => $validatedData['customer_id'],
                'reference_id' => $sale->id,
                'unique_no' => $sale->unique_no,
                'invoice_no' => $validatedData['invoice_no'],
                'description' => $row['description'] ?: $row['item'] ?: 'Sale Item',
                'date' => $validatedData['date'],
                'type' => 'Sale',
                'credit' => $item_total,
                'debit' => 0,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Payment
        if ($paidAmount > 0) {
            $transactions[] = [
                'company_id' => $companyId,
                'accounting_period_id' => $period->id,
                'account_id' => $validatedData['payment_type'],
                'customer_id' => $validatedData['customer_id'],
                'reference_id' => $sale->id,
                'unique_no' => $sale->unique_no,
                'invoice_no' => $validatedData['invoice_no'],
                'description' => 'Payment for Sale '.$validatedData['invoice_no'],
                'date' => $validatedData['date'],
                'type' => 'Sale',
                'credit' => 0,
                'debit' => $paidAmount,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Receivable
        if ($remainingAmount > 0) {
            $transactions[] = [
                'company_id' => $companyId,
                'accounting_period_id' => $period->id,
                'account_id' => ChartOfAccount::getAccountsReceivableId(),
                'customer_id' => $validatedData['customer_id'],
                'reference_id' => $sale->id,
                'unique_no' => $sale->unique_no,
                'invoice_no' => $validatedData['invoice_no'],
                'description' => 'Remaining Sale Amount '.$validatedData['invoice_no'],
                'date' => $validatedData['date'],
                'type' => 'Sale',
                'credit' => 0,
                'debit' => $remainingAmount,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        SaleItem::insert($saleItems);
        Transaction::insert($transactions);
    }

    public function updateSaleAttachments($request, $sale, $period, $userId)
    {
        if ($request->hasFile('attachment')) {

            $existing = SaleAttachments::where('sale_id', $sale->id)->get();

            foreach ($existing as $att) {
                if (Storage::disk('public')->exists($att->attachment)) {
                    Storage::disk('public')->delete($att->attachment);
                }
                $att->delete();
            }

            foreach ($request->file('attachment') as $file) {
                $original = $file->getClientOriginalName();
                $clean = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($original, PATHINFO_FILENAME));
                $timestamp = now()->format('Ymd_His');
                $newFile = "{$clean}_{$timestamp}.".$file->getClientOriginalExtension();

                $path = $file->storeAs('sale-attachments', $newFile, 'public');

                SaleAttachments::create([
                    'sale_id' => $sale->id,
                    'accounting_period_id' => $period->id,
                    'attachment' => $path,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
            }
        }
    }
}
