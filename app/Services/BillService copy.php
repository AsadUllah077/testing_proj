<?php

namespace App\Services;

use App\Models\AccountingPeriod;
use App\Models\Bill;
use App\Models\BillAttachment;
use App\Models\BillItem;
use App\Models\ChartOfAccount;
use App\Models\TaxRate;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BillService
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

    public function calculateSubTotal($rows)
    {
        return collect($rows)->sum(function ($row) {
            $quantity = $row['quantity'] ?? 1;
            $price = $row['price'] ?? 0;

            // Calculate subtotal based on either quantity or kg
            $kg = $row['kg'] ?? 0;
            if ($kg > 0) {
                return $price; // If using kg, price is per kg
            } else {
                return $quantity * $price;
            }
        });
    }

    public function calculateDiscountTotal($rows)
    {
        return collect($rows)->sum(function ($row) {
            $quantity = $row['quantity'] ?? 1;
            $price = $row['price'] ?? 0;
            $discountPercentage = $row['discount_percentage'] ?? 0;

            // Calculate row subtotal
            $kg = $row['kg'] ?? 0;
            $rowSubtotal = 0;
            if ($kg > 0) {
                $rowSubtotal = $price; // If using kg, price is per kg
            } else {
                $rowSubtotal = $quantity * $price;
            }

            // Calculate discount amount
            return ($rowSubtotal * $discountPercentage) / 100;
        });
    }

    public function calculateTaxTotal($rows)
    {
        return collect($rows)->sum(function ($row) {
            $quantity = $row['quantity'] ?? 1;
            $price = $row['price'] ?? 0;
            $discountPercentage = $row['discount_percentage'] ?? 0;
            $taxId = $row['tax_id'] ?? null;

            // Calculate row subtotal
            $kg = $row['kg'] ?? 0;
            $rowSubtotal = 0;
            if ($kg > 0) {
                $rowSubtotal = $price; // If using kg, price is per kg
            } else {
                $rowSubtotal = $quantity * $price;
            }

            // Calculate discount amount
            $discountAmount = ($rowSubtotal * $discountPercentage) / 100;

            // Calculate amount after discount
            $amountAfterDiscount = $rowSubtotal - $discountAmount;

            // Get tax rate
            $taxRate = 0;
            if ($taxId) {
                $taxRate = TaxRate::find($taxId)?->rate ?? 0;
            }

            // Calculate tax on discounted amount
            return $amountAfterDiscount * ($taxRate / 100);
        });
    }

    public function createBill(
        array $validatedData,
        AccountingPeriod $period,
        string $uniqueNo,
        float $subTotal,
        float $taxTotal,
        float $total,
        float $paidAmount,
        float $remainingAmount
    ) {
        $user_id = Auth::id();

        return Bill::create([
            'company_id' => Auth::user()->company_id,
            'accounting_period_id' => $period->id,
            'vendor_id' => $validatedData['vendor_id'],
            'account_id' => $validatedData['payment_type'],
            'currency_id' => $validatedData['currency_id'],
            'unique_no' => $uniqueNo,
            'invoice_no' => $validatedData['invoice_no'],
            'date' => $validatedData['date'],
            'ref_no' => $validatedData['ref_no'],
            'note' => $validatedData['note'],
            'sub_total' => $subTotal,
            'tax' => $taxTotal,
            'total' => $total,
            'paid_amount' => $paidAmount,
            'remaining_amount' => $remainingAmount,
            'status' => 0,
            'created_by' => $user_id,
            'updated_by' => $user_id,
        ]);
    }

    public function handleAttachment($request, AccountingPeriod $period, Bill $bill)
    {
        if ($request->hasFile('attachment')) {
            foreach ($request->file('attachment') as $file) {
                // Get original file name and extension
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();

                // Remove spaces and special characters from file name
                $fileNameWithoutSpaces = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));

                // Add timestamp to make it unique
                $timestamp = now()->format('Ymd_His');
                $newFileName = $fileNameWithoutSpaces.'_'.$timestamp.'.'.$extension;

                // Store file with new name
                $attachmentPath = $file->storeAs('bill-attachments', $newFileName, 'public');

                BillAttachment::create([
                    'company_id' => Auth::user()->company_id,
                    'bill_id' => $bill->id,
                    'accounting_period_id' => $period->id,
                    'attachment' => $attachmentPath,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
            }
        }
    }

    public function createBillItemsAndTransactions(
        $request,
        Bill $bill,
        AccountingPeriod $period,
        string $uniqueNo,
        float $paidAmount,
        float $remainingAmount
    ): void {

        $companyId = Auth::user()->company_id;
        $userId = Auth::id();
        $invoiceNo = $request->invoice_no;
        $date = $request->date;

        $billItems = [];
        $transactions = [];

        foreach ($request->rows as $row) {

            $itemTotal = ($row['quantity'] ?? 1) * ($row['price'] ?? 0);

            // Prepare BillItem
            $billItems[] = [
                'company_id' => $companyId,
                'accounting_period_id' => $period->id,
                'bill_id' => $bill->id,
                'unique_no' => $uniqueNo,
                'item' => $row['item'],
                'manual_item' => $row['manual_item'] ?? null,
                'description' => $row['description'],
                'hs_code' => $row['hs_code'],
                'quantity' => $row['quantity'],
                'kg' => $row['kg'],
                'price' => $row['price'],
                'discount' => $row['discount_percentage'],
                'account_id' => $row['account_id'],
                'tax_id' => $row['tax_id'],
                'total' => $itemTotal,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Prepare Transaction for each bill item
            $transactions[] = [
                'company_id' => $companyId,
                'accounting_period_id' => $period->id,
                'account_id' => $row['account_id'],
                'vendor_id' => $request->vendor_id,
                'reference_id' => $bill->id,
                'unique_no' => $uniqueNo,
                'invoice_no' => $invoiceNo,
                'description' => $row['description'] ?: $row['item'] ?: 'Bill Item',
                'date' => $date,
                'type' => 'Bill',
                'debit' => $itemTotal,
                'credit' => 0,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Payment transaction (if paid)
        if ($paidAmount > 0) {
            $transactions[] = [
                'company_id' => $companyId,
                'accounting_period_id' => $period->id,
                'account_id' => $request->payment_type,
                'vendor_id' => $request->vendor_id,
                'reference_id' => $bill->id,
                'unique_no' => $uniqueNo,
                'invoice_no' => $invoiceNo,
                'description' => "Payment for Bill $invoiceNo",
                'date' => $date,
                'type' => 'Bill',
                'debit' => 0,
                'credit' => $paidAmount,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Remaining amount transaction
        if ($remainingAmount > 0) {
            $transactions[] = [
                'company_id' => $companyId,
                'accounting_period_id' => $period->id,
                'account_id' => ChartOfAccount::getAccountsPayableId(),
                'vendor_id' => $request->vendor_id,
                'reference_id' => $bill->id,
                'unique_no' => $uniqueNo,
                'invoice_no' => $invoiceNo,
                'description' => "Remaining Bill Amount $invoiceNo",
                'date' => $date,
                'type' => 'Bill',
                'debit' => 0,
                'credit' => $remainingAmount,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        BillItem::insert($billItems);
        Transaction::insert($transactions);
    }

    public function generateUniqueNo()
    {
        $latestBill = Bill::latest('id')->first();
        $nextNo = $latestBill ? $latestBill->id + 1 : 1;

        return 'BILL-'.str_pad($nextNo, 6, '0', STR_PAD_LEFT);
    }

    public function updateBill(
        AccountingPeriod $period,
        Bill $bill,
        $request,
        $subTotal,
        $taxTotal,
        $total,
        $paidAmount,
        $remainingAmount
    ) {
        $bill->update([
            'accounting_period_id' => $period->id,
            'vendor_id' => $request->vendor_id,
            'account_id' => $request->payment_type,
            'currency_id' => $request->currency_id,
            'invoice_no' => $request->invoice_no,
            'date' => $request->date,
            'ref_no' => $request->ref_no,
            'note' => $request->note,
            'sub_total' => $subTotal,
            'tax' => $taxTotal,
            'total' => $total,
            'paid_amount' => $paidAmount,
            'remaining_amount' => $remainingAmount,
            'updated_by' => Auth::id(),
        ]);
    }

    public function updateHandleAttachment($request, $bill, $period)
    {
        if ($request->hasFile('attachment')) {
            // Delete existing attachments
            $existingAttachments = BillAttachment::where('bill_id', $bill->id)->get();

            foreach ($existingAttachments as $attachment) {
                // Delete file from storage
                if (Storage::disk('public')->exists($attachment->attachment)) {
                    Storage::disk('public')->delete($attachment->attachment);
                }
                // Delete record from database
                $attachment->delete();
            }

            // Upload new attachments
            foreach ($request->file('attachment') as $file) {
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();

                // Remove spaces and special characters from file name
                $fileNameWithoutSpaces = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));

                // Add timestamp to make it unique
                $timestamp = now()->format('Ymd_His');
                $newFileName = $fileNameWithoutSpaces.'_'.$timestamp.'.'.$extension;

                // Store file with new name
                $attachmentPath = $file->storeAs('bill-attachments', $newFileName, 'public');

                BillAttachment::create([
                    'company_id' => Auth::user()->company_id,
                    'bill_id' => $bill->id,
                    'accounting_period_id' => $period->id,
                    'attachment' => $attachmentPath,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
            }
        }
    }

    public function deleteBillItemAndTransaction(Bill $bill)
    {
        BillItem::where('bill_id', $bill->id)->delete();
        Transaction::where('unique_no', $bill->unique_no)->delete();
    }

    public function updateBillItemAndTransaction($request, $bill, $period, $paidAmount, $remainingAmount)
    {
        $companyId = Auth::user()->company_id;
        $userId = Auth::id();
        $invoiceNo = $request->invoice_no;
        $uniqueNo = $bill->unique_no;
        $date = $request->date;

        $billItems = [];
        $transactions = [];

        foreach ($request->rows as $row) {

            $itemTotal = ($row['quantity'] ?? 1) * ($row['price'] ?? 0);

            // -------------------------------
            // Prepare BillItem (NO CREATE)
            // -------------------------------
            $billItems[] = [
                'company_id' => $companyId,
                'accounting_period_id' => $period->id,
                'bill_id' => $bill->id,
                'unique_no' => $uniqueNo,
                'item' => $row['item'],
                'manual_item' => $row['manual_item'] ?? null,
                'description' => $row['description'],
                'hs_code' => $row['hs_code'],
                'quantity' => $row['quantity'],
                'kg' => $row['kg'],
                'price' => $row['price'],
                'discount' => $row['discount_percentage'],
                'account_id' => $row['account_id'],
                'tax_id' => $row['tax_id'],
                'total' => $itemTotal,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // -------------------------------
            // Prepare Transaction for this Bill Item
            // -------------------------------
            $transactions[] = [
                'company_id' => $companyId,
                'accounting_period_id' => $period->id,
                'account_id' => $row['account_id'],
                'vendor_id' => $request->vendor_id,
                'reference_id' => $bill->id,
                'unique_no' => $uniqueNo,
                'invoice_no' => $invoiceNo,
                'description' => $row['description'] ?: $row['item'] ?: 'Bill Item',
                'date' => $date,
                'type' => 'Bill',
                'debit' => $itemTotal,
                'credit' => 0,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        if ($paidAmount > 0) {
            $transactions[] = [
                'company_id' => $companyId,
                'accounting_period_id' => $period->id,
                'account_id' => $request->payment_type,
                'vendor_id' => $request->vendor_id,
                'reference_id' => $bill->id,
                'unique_no' => $uniqueNo,
                'invoice_no' => $invoiceNo,
                'description' => "Payment for Bill $invoiceNo",
                'date' => $date,
                'type' => 'Bill',
                'debit' => 0,
                'credit' => $paidAmount,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // --------------------------------
        // Remaining balance transaction
        // --------------------------------
        if ($remainingAmount > 0) {
            $transactions[] = [
                'company_id' => $companyId,
                'accounting_period_id' => $period->id,
                'account_id' => ChartOfAccount::getAccountsPayableId(),
                'vendor_id' => $request->vendor_id,
                'reference_id' => $bill->id,
                'unique_no' => $uniqueNo,
                'invoice_no' => $invoiceNo,
                'description' => "Remaining Bill Amount $invoiceNo",
                'date' => $date,
                'type' => 'Bill',
                'debit' => 0,
                'credit' => $remainingAmount,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        BillItem::insert($billItems);
        Log::info('Bill Items bulk inserted');
        Transaction::insert($transactions);
        Log::info('Transaction bulk inserted');
    }
}
