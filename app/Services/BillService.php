<?php

namespace App\Services;

use App\Jobs\SendBillInvoiceEmail;
use App\Models\AccountingPeriod;
use App\Models\Bill;
use App\Models\BillAttachment;
use App\Models\BillItem;
use App\Models\BillInvoiceDelivery;
use App\Models\BillInvoiceVersion;
use App\Models\ChartOfAccount;
use App\Models\TaxRate;
use App\Models\Transaction;
use App\Models\Vendor;
use App\Models\VendorPaymentRun;
use App\Models\VendorPaymentRunItem;
use App\Models\Company;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Mail\VendorBillMail;

class BillService
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

        if ($companyId && \App\Models\TaxPeriod::isDateLocked($companyId, $date)) {
            throw new \Exception('The selected date falls within a locked Tax Period (Paid).');
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
                return $kg * $price; // Corrected: Multiply KG by price
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
                $rowSubtotal = $kg * $price; // Corrected: Multiply KG by price
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

            $quantity = (float) ($row['quantity'] ?? 0);
            $kg = (float) ($row['kg'] ?? 0);
            $price = (float) ($row['price'] ?? 0);
            $discountPercentage = (float) ($row['discount_percentage'] ?? 0);
            $taxId = $row['tax_id'] ?? null;

            // 1️⃣ Row subtotal
            $rowSubtotal = 0;
            if ($kg > 0) {
                $rowSubtotal = $kg * $price;
            } elseif ($quantity > 0) {
                $rowSubtotal = $quantity * $price;
            }

            // 2️⃣ Discount
            $discountAmount = ($rowSubtotal * $discountPercentage) / 100;

            // 3️⃣ Amount after discount
            $amountAfterDiscount = $rowSubtotal - $discountAmount;

            // 4️⃣ Tax AFTER discount
            if ($taxId) {
                $taxRate = TaxRate::find($taxId)?->tax_rate ?? 0;
                return ($amountAfterDiscount * $taxRate) / 100;
            }

            return 0;
        });
    }

    /**
     * Calculate totals from rows (similar to SaleService)
     */
    public function calculateTotals(array $rows): array
    {
        $subTotal = 0;
        $discountTotal = 0;
        $taxTotal = 0;

        $netTotal = 0;     // after discount, before tax
        $grandTotal = 0;  // after discount + tax

        foreach ($rows as $row) {
            $quantity = (float) ($row['quantity'] ?? 0);
            $kg = (float) ($row['kg'] ?? 0);
            $price = (float) ($row['price'] ?? 0);
            $discountPercentage = (float) ($row['discount_percentage'] ?? 0);
            $taxId = $row['tax_id'] ?? null;

            // 1️⃣ Row subtotal
            $rowSubtotal = 0;
            if ($kg > 0) {
                $rowSubtotal = $kg * $price;
            } elseif ($quantity > 0) {
                $rowSubtotal = $quantity * $price;
            }

            $subTotal += $rowSubtotal;

            // 2️⃣ Discount
            $discountAmount = ($rowSubtotal * $discountPercentage) / 100;
            $discountTotal += $discountAmount;

            // 3️⃣ Amount after discount
            $amountAfterDiscount = $rowSubtotal - $discountAmount;
            $netTotal += $amountAfterDiscount;

            // 4️⃣ Tax (RESET per row)
            $taxAmount = 0;
            if ($taxId) {
                $tax = TaxRate::find($taxId);
                if ($tax) {
                    // ✅ Tax AFTER discount
                    $taxAmount = ($amountAfterDiscount * $tax->tax_rate) / 100;
                    $taxTotal += $taxAmount;
                }
            }

            // 5️⃣ Row total
            $grandTotal += ($amountAfterDiscount + $taxAmount);
        }

        return [
            'sub_total' => round($subTotal, 2),
            'discount' => round($discountTotal, 2),
            'tax_total' => round($taxTotal, 2),
            'total' => round($netTotal, 2),            // after discount, before tax
            'total_after_discount' => round($grandTotal, 2), // final payable
        ];
    }


    /**
     * Create bill record (similar to SaleService)
     */
    public function createBillRecord(
        array $validatedData,
        AccountingPeriod $period,
        array $totals,
        float $remainingAmount
    ): Bill {
        $companyId = (int) loggedCompanyId()['company_id'];
        $uniqueNo = $this->nextBillUniqueNo($companyId, $validatedData['date']);
        $authId = Auth::id();
        $paidAmount = (float) ($validatedData['paid_amount'] ?? 0);
        $withholding = (float) ($validatedData['withholding_tax'] ?? 0);
        $exchangeRate = (float) ($validatedData['exchange_rate'] ?? 1);
        $vendor = Vendor::findOrFail($validatedData['vendor_id']);
        $dueDate = $validatedData['due_date'] ?? \Carbon\Carbon::parse($validatedData['date'])
            ->addDays((int) ($vendor->payment_terms_days ?? 30))->toDateString();
        $payable = round($totals['total_after_discount'] - $withholding, 2);

        return Bill::create([
            'company_id' => $companyId,
            'accounting_period_id' => $period->id,
            'vendor_id' => $validatedData['vendor_id'],
            'account_id' => $validatedData['payment_type'] ?? null,
            'currency_id' => $validatedData['currency_id'],
            'exchange_rate' => $exchangeRate,
            'unique_no' => $uniqueNo,
            'invoice_no' => $validatedData['invoice_no'],
            'date' => $validatedData['date'],
            'vendor_invoice_date' => $validatedData['vendor_invoice_date'] ?? $validatedData['date'],
            'posting_date' => $validatedData['posting_date'] ?? $validatedData['date'],
            'due_date' => $dueDate,
            'ref_no' => $validatedData['ref_no'] ?? null,
            'purchase_order_no' => $validatedData['purchase_order_no'] ?? null,
            'goods_receipt_no' => $validatedData['goods_receipt_no'] ?? null,
            'project' => $validatedData['project'] ?? null,
            'cost_centre' => $validatedData['cost_centre'] ?? null,
            'department' => $validatedData['department'] ?? null,
            'warehouse' => $validatedData['warehouse'] ?? null,
            'vendor_tax_invoice_reference' => $validatedData['vendor_tax_invoice_reference'] ?? null,
            'note' => $validatedData['note'] ?? null,
            'sub_total' => $totals['sub_total'],
            'discount_total' => $totals['discount'],
            'net_before_tax' => $totals['total'],
            'tax' => $totals['tax_total'],
            'grand_total' => $totals['total_after_discount'],
            'withholding_tax' => $withholding,
            'withholding_account_id' => $validatedData['withholding_account_id'] ?? null,
            'total' => $payable,
            'total_after_discount' => $payable,
            'base_currency_total' => round($payable * $exchangeRate, 4),
            'paid_amount' => $paidAmount,
            'remaining_amount' => $remainingAmount,
            'approval_status' => 'draft',
            'payment_status' => $this->paymentStatus($paidAmount, $remainingAmount),
            'email_on_approval' => ! empty($validatedData['send_email']),
            'status' => 1,
            'created_by' => $authId,
            'updated_by' => $authId,
        ]);
    }


    public function createBill(
        array $validatedData,
        AccountingPeriod $period,
        string $uniqueNo,
        float $subTotal,
        float $taxTotal,
        float $total,
        float $total_after_discount,
        float $paidAmount,
        float $remainingAmount
    ) {
        $user_id = Auth::id();
        $totals = [
            'sub_total' => $subTotal,
            'discount' => max(0, $subTotal - ($total_after_discount - $taxTotal)),
            'tax_total' => $taxTotal,
            'total' => $total_after_discount - $taxTotal,
            'total_after_discount' => $total_after_discount,
        ];

        return $this->createBillRecord($validatedData, $period, $totals, $remainingAmount);
    }

    public function nextBillUniqueNo(int $companyId, string $date): string
    {
        $year = (int) date('Y', strtotime($date));
        DB::table('bill_invoice_sequences')->upsert([
            ['company_id' => $companyId, 'year' => $year, 'next_number' => 1, 'created_at' => now(), 'updated_at' => now()],
        ], ['company_id', 'year'], ['updated_at']);

        $sequence = DB::table('bill_invoice_sequences')
            ->where('company_id', $companyId)->where('year', $year)->lockForUpdate()->first();
        $number = (int) $sequence->next_number;
        DB::table('bill_invoice_sequences')->where('id', $sequence->id)->update([
            'next_number' => $number + 1,
            'updated_at' => now(),
        ]);

        return 'BILL-' . $year . '-' . str_pad((string) $number, 6, '0', STR_PAD_LEFT);
    }

    public function paymentStatus(float $paidAmount, float $remainingAmount): string
    {
        if ($remainingAmount < 0) return 'overpaid';
        if ($remainingAmount <= 0) return 'paid';
        return $paidAmount > 0 ? 'partial' : 'unpaid';
    }

    public function assertAccountingConfiguration(int $companyId, float $discount = 0, float $tax = 0, float $withholding = 0, ?int $withholdingAccountId = null): void
    {
        if (! ChartOfAccount::getAccountsPayableId($companyId)) {
            throw new \RuntimeException('Accounts Payable is not configured for this company.');
        }
        if ($discount > 0 && ! ChartOfAccount::getPurchaseDiscountAccountId($companyId)) {
            throw new \RuntimeException('Purchase Discount is not configured for this company.');
        }
        if ($tax > 0 && ! ChartOfAccount::getPurchaseTaxAccountId($companyId)) {
            throw new \RuntimeException('Purchase Tax is not configured for this company.');
        }
        if ($withholding > 0 && ! $withholdingAccountId) {
            throw new \RuntimeException('A withholding tax payable account is required.');
        }
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
                $newFileName = $fileNameWithoutSpaces . '_' . $timestamp . '.' . $extension;

                // Store file with new name
                $attachmentPath = $file->storeAs('bill-attachments', $newFileName, 'local');

                BillAttachment::create([
                    'company_id' => Auth::user()->company_id,
                    'bill_id' => $bill->id,
                    'accounting_period_id' => $period->id,
                    'attachment' => $attachmentPath,
                    'original_name' => $originalName,
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'storage_disk' => 'local',
                    'checksum' => hash_file('sha256', $file->getRealPath()),
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
        $exchangeRate = (float) ($request->exchange_rate ?? 1);

        $billItems = [];
        $transactions = [];

        // 1. Bill Journal - Records the expense and creates the payable
        $totalTaxAmount = 0;
        $totalDiscountAmount = 0;
        foreach ($request->rows as $row) {
            $rowQty = floatval($row['quantity'] ?? 0);
            $rowKg = floatval($row['kg'] ?? 0);
            $rowPrice = floatval($row['price'] ?? 0);
            $itemTotal = ($rowKg > 0) ? ($rowKg * $rowPrice) : ($rowQty * $rowPrice);
            $discount = $row['discount_percentage'] ?? 0;
            $discountAmount = ($itemTotal * $discount) / 100;
            $netAmount = $itemTotal - $discountAmount;

            $taxId = $row['tax_id'] ?? null;
            $tax = null;
            $taxAmount = 0;
            if ($taxId) {
                $tax = TaxRate::find($taxId);
                if ($tax) {
                    $taxAmount = ($netAmount * $tax->tax_rate) / 100;
                }
            }
            $totalTaxAmount += $taxAmount;
            $totalDiscountAmount += $discountAmount;
            $item_total = $netAmount + $taxAmount;

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
                'kg' => $row['kg'] ?? 0,
                'unit_of_measure' => $row['unit_of_measure'] ?? ($rowKg > 0 ? 'kg' : 'unit'),
                'price' => $row['price'],
                'discount' => $row['discount_percentage'],
                'account_id' => $row['account_id'],
                'tax_id' => $row['tax_id'] ?? null,
                'tax_name_snapshot' => $tax->name ?? null,
                'tax_rate_snapshot' => $tax->tax_rate ?? 0,
                'line_subtotal' => $itemTotal,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'total' => $item_total,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Debit Expense/Asset (Gross Amount)
            $transactions[] = [
                'company_id' => $companyId,
                'accounting_period_id' => $period->id,
                'account_id' => $row['account_id'],
                'vendor_id' => $request->vendor_id,
                'payable_type' => 'vendor',
                'payable_id'   => $request->vendor_id,
                'reference_id' => $bill->id,
                'unique_no' => $uniqueNo,
                'invoice_no' => $invoiceNo,
                'description' => $row['description'] ?: $row['item'] ?: 'Bill Item',
                'date' => $date,
                'type' => 'Bill',
                'debit' => round($itemTotal * $exchangeRate, 4),
                'credit' => 0,
                'status' => 0,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $withholding = (float) ($request->withholding_tax ?? 0);
        $withholdingAccountId = $request->withholding_account_id ?: null;
        $this->assertAccountingConfiguration($companyId, $totalDiscountAmount, $totalTaxAmount, $withholding, $withholdingAccountId);

        // Discount transaction (If applicable)
        if ($totalDiscountAmount > 0) {
            $discountAccountId = ChartOfAccount::getPurchaseDiscountAccountId($companyId);
            if ($discountAccountId) {
                $transactions[] = [
                    'company_id' => $companyId,
                    'accounting_period_id' => $period->id,
                    'account_id' => $discountAccountId,
                    'vendor_id' => $request->vendor_id,
                    'payable_type' => 'vendor',
                    'payable_id'   => $request->vendor_id,
                    'reference_id' => $bill->id,
                    'unique_no' => $uniqueNo,
                    'invoice_no' => $invoiceNo,
                    'description' => 'Purchase Discount for Bill ' . $invoiceNo,
                    'date' => $date,
                    'type' => 'Bill',
                    'debit' => 0,
                    'credit' => round($totalDiscountAmount * $exchangeRate, 4),
                    'status' => 0,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Tax transaction (If applicable)
        if ($totalTaxAmount > 0) {
            $taxAccountId = ChartOfAccount::getPurchaseTaxAccountId($companyId);
            if ($taxAccountId) {
                $transactions[] = [
                    'company_id' => $companyId,
                    'accounting_period_id' => $period->id,
                    'account_id' => $taxAccountId,
                    'vendor_id' => $request->vendor_id,
                    'payable_type' => 'vendor',
                    'payable_id'   => $request->vendor_id,
                    'reference_id' => $bill->id,
                    'unique_no' => $uniqueNo,
                    'invoice_no' => $invoiceNo,
                    'description' => 'Purchase Tax for Bill ' . $invoiceNo,
                    'date' => $date,
                    'type' => 'Bill',
                    'debit' => round($totalTaxAmount * $exchangeRate, 4),
                    'credit' => 0,
                    'status' => 0,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if ($withholding > 0) {
            $transactions[] = [
                'company_id' => $companyId,
                'accounting_period_id' => $period->id,
                'account_id' => $withholdingAccountId,
                'vendor_id' => $request->vendor_id,
                'payable_type' => 'vendor',
                'payable_id' => $request->vendor_id,
                'reference_id' => $bill->id,
                'unique_no' => $uniqueNo,
                'invoice_no' => $invoiceNo,
                'description' => 'Withholding Tax for Bill ' . $invoiceNo,
                'date' => $date,
                'type' => 'Bill',
                'debit' => 0,
                'credit' => round($withholding * $exchangeRate, 4),
                'status' => 0,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Credit Accounts Payable (Total Bill Amount)
        $transactions[] = [
            'company_id' => $companyId,
            'accounting_period_id' => $period->id,
            'account_id' => ChartOfAccount::getAccountsPayableId($companyId),
            'vendor_id' => $request->vendor_id,
            'payable_type' => 'vendor',
            'payable_id'   => $request->vendor_id,
            'reference_id' => $bill->id,
            'unique_no' => $uniqueNo,
            'invoice_no' => $invoiceNo,
            'description' => "Accounts Payable for Bill $invoiceNo",
            'date' => $date,
            'type' => 'Bill',
            'debit' => 0,
            'credit' => round((float) $bill->total_after_discount * $exchangeRate, 4),
            'status' => 0,
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // 2. Payment Journal - Records the money out and clears the payable (if paid)
        if ($paidAmount > 0) {
            $paymentUniqueNo = 'PAY-' . $bill->unique_no;

            // Debit Accounts Payable
            $transactions[] = [
                'company_id' => $companyId,
                'accounting_period_id' => $period->id,
                'account_id' => ChartOfAccount::getAccountsPayableId($companyId),
                'vendor_id' => $request->vendor_id,
                'payable_type' => 'vendor',
                'payable_id'   => $request->vendor_id,
                'reference_id' => $bill->id,
                'unique_no' => $paymentUniqueNo,
                'invoice_no' => $invoiceNo,
                'description' => "Payment for Bill $invoiceNo",
                'date' => $date,
                'type' => 'Bill',
                'debit' => round($paidAmount * $exchangeRate, 4),
                'credit' => 0,
                'status' => 0,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Credit Payment Account (Bank/Cash)
            $transactions[] = [
                'company_id' => $companyId,
                'accounting_period_id' => $period->id,
                'account_id' => $request->payment_type,
                'vendor_id' => $request->vendor_id,
                'payable_type' => 'vendor',
                'payable_id'   => $request->vendor_id,
                'reference_id' => $bill->id,
                'unique_no' => $paymentUniqueNo,
                'invoice_no' => $invoiceNo,
                'description' => "Payment for Bill $invoiceNo",
                'date' => $date,
                'type' => 'Bill',
                'debit' => 0,
                'credit' => round($paidAmount * $exchangeRate, 4),
                'status' => 0,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        BillItem::insert($billItems);
        Transaction::insert($transactions);
    }

    public function generatePaymentUniqueNo()
    {
        $latestPayment = Transaction::where('unique_no', 'like', 'PAY-%')->orderBy('id', 'desc')->first();

        if ($latestPayment) {
            // Extract the numeric part and increment it
            $number = intval(substr($latestPayment->unique_no, 4)) + 1;
        } else {
            $number = 1;
        }

        return 'PAY-' . str_pad($number, 6, '0', STR_PAD_LEFT);
    }

    public function generateUniqueNo()
    {
        return $this->nextBillUniqueNo((int) loggedCompanyId()['company_id'], now()->toDateString());
    }

    public function updateBill(
        AccountingPeriod $period,
        Bill $bill,
        $request,
        $subTotal,
        $taxTotal,
        $total,
        $total_after_discount,
        $paidAmount,
        $remainingAmount
    ) {
        $discountTotal = $this->calculateDiscountTotal($request->rows ?? []);
        $withholding = (float) ($request->withholding_tax ?? 0);
        $grandTotal = $total_after_discount + $withholding;
        $exchangeRate = (float) ($request->exchange_rate ?? 1);
        $bill->update([
            'accounting_period_id' => $period->id,
            'vendor_id' => $request->vendor_id,
            'account_id' => $request->payment_type,
            'currency_id' => $request->currency_id,
            'exchange_rate' => $exchangeRate,
            'invoice_no' => $request->invoice_no,
            'date' => $request->date,
            'vendor_invoice_date' => $request->vendor_invoice_date ?? $request->date,
            'posting_date' => $request->posting_date ?? $request->date,
            'due_date' => $request->due_date,
            'ref_no' => $request->ref_no,
            'purchase_order_no' => $request->purchase_order_no,
            'goods_receipt_no' => $request->goods_receipt_no,
            'project' => $request->project,
            'cost_centre' => $request->cost_centre,
            'department' => $request->department,
            'warehouse' => $request->warehouse,
            'vendor_tax_invoice_reference' => $request->vendor_tax_invoice_reference,
            'note' => $request->note,
            'sub_total' => $subTotal,
            'discount_total' => $discountTotal,
            'net_before_tax' => $subTotal - $discountTotal,
            'tax' => $taxTotal,
            'grand_total' => $grandTotal,
            'withholding_tax' => $withholding,
            'withholding_account_id' => $request->withholding_account_id,
            'total' => $total_after_discount,
            'total_after_discount' => $total_after_discount,
            'base_currency_total' => round($total_after_discount * $exchangeRate, 4),
            'paid_amount' => $paidAmount,
            'remaining_amount' => $remainingAmount,
            'payment_status' => $this->paymentStatus((float) $paidAmount, (float) $remainingAmount),
            'email_on_approval' => (bool) $request->send_email,
            'updated_by' => Auth::id(),
        ]);
    }

    public function updateHandleAttachment($request, $bill, $period)
    {
        if ($request->hasFile('attachment')) {
            // Upload new attachments
            foreach ($request->file('attachment') as $file) {
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();

                // Remove spaces and special characters from file name
                $fileNameWithoutSpaces = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));

                // Add timestamp to make it unique
                $timestamp = now()->format('Ymd_His');
                $newFileName = $fileNameWithoutSpaces . '_' . $timestamp . '.' . $extension;

                // Store file with new name
                $attachmentPath = $file->storeAs('bill-attachments', $newFileName, 'local');

                BillAttachment::create([
                    'company_id' => Auth::user()->company_id,
                    'bill_id' => $bill->id,
                    'accounting_period_id' => $period->id,
                    'attachment' => $attachmentPath,
                    'original_name' => $originalName,
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'storage_disk' => 'local',
                    'checksum' => hash_file('sha256', $file->getRealPath()),
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
            }
        }
    }

    public function deleteBillItemAndTransaction(Bill $bill)
    {
        BillItem::where('bill_id', $bill->id)->delete();
        Transaction::withoutGlobalScopes()
            ->where('company_id', $bill->company_id)
            ->where('reference_id', (string) $bill->id)
            ->whereIn('type', ['Bill', 'BillPayment'])
            ->where('status', 0)
            ->delete();
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

        $totalTaxAmount = 0;
        $totalDiscountAmount = 0;
        foreach ($request->rows as $row) {
            $itemTotal = ($row['quantity'] ?? 1) * ($row['price'] ?? 0);
            $discount = $row['discount_percentage'] ?? 0;
            $discountAmount = ($itemTotal * $discount) / 100;
            $netAmount = $itemTotal - $discountAmount;

            $taxId = $row['tax_id'] ?? null;
            $taxAmount = 0;
            if ($taxId) {
                $tax = TaxRate::find($taxId);
                if ($tax) {
                    $taxAmount = ($netAmount * $tax->tax_rate) / 100;
                }
            }
            $totalTaxAmount += $taxAmount;
            $totalDiscountAmount += $discountAmount;
            $item_total = $netAmount + $taxAmount;

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
                'total' => $item_total,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Debit Expense/Asset (Gross Amount)
            $transactions[] = [
                'company_id' => $companyId,
                'accounting_period_id' => $period->id,
                'account_id' => $row['account_id'],
                'vendor_id' => $request->vendor_id,
                'payable_type' => 'vendor',
                'payable_id'   => $request->vendor_id,
                'reference_id' => $bill->id,
                'unique_no' => $uniqueNo,
                'invoice_no' => $invoiceNo,
                'description' => $row['description'] ?: $row['item'] ?: 'Bill Item',
                'date' => $date,
                'type' => 'Bill',
                'debit' => $itemTotal,
                'credit' => 0,
                'status' => 1,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Discount transaction
        if ($totalDiscountAmount > 0) {
            $discountAccountId = ChartOfAccount::getPurchaseDiscountAccountId($companyId);
            if ($discountAccountId) {
                $transactions[] = [
                    'company_id' => $companyId,
                    'accounting_period_id' => $period->id,
                    'account_id' => $discountAccountId,
                    'vendor_id' => $request->vendor_id,
                    'payable_type' => 'vendor',
                    'payable_id'   => $request->vendor_id,
                    'reference_id' => $bill->id,
                    'unique_no' => $uniqueNo,
                    'invoice_no' => $invoiceNo,
                    'description' => 'Purchase Discount for Bill ' . $invoiceNo,
                    'date' => $date,
                    'type' => 'Bill',
                    'debit' => 0,
                    'credit' => $totalDiscountAmount,
                    'status' => 1,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Tax transaction
        if ($totalTaxAmount > 0) {
            $taxAccountId = ChartOfAccount::getPurchaseTaxAccountId($companyId);
            if ($taxAccountId) {
                $transactions[] = [
                    'company_id' => $companyId,
                    'accounting_period_id' => $period->id,
                    'account_id' => $taxAccountId,
                    'vendor_id' => $request->vendor_id,
                    'payable_type' => 'vendor',
                    'payable_id'   => $request->vendor_id,
                    'reference_id' => $bill->id,
                    'unique_no' => $uniqueNo,
                    'invoice_no' => $invoiceNo,
                    'description' => 'Purchase Tax for Bill ' . $invoiceNo,
                    'date' => $date,
                    'type' => 'Bill',
                    'debit' => $totalTaxAmount,
                    'credit' => 0,
                    'status' => 1,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Credit Accounts Payable (Total Bill Amount)
        $transactions[] = [
            'company_id' => $companyId,
            'accounting_period_id' => $period->id,
            'account_id' => ChartOfAccount::getAccountsPayableId($companyId),
            'vendor_id' => $request->vendor_id,
            'payable_type' => 'vendor',
            'payable_id'   => $request->vendor_id,
            'reference_id' => $bill->id,
            'unique_no' => $uniqueNo,
            'invoice_no' => $invoiceNo,
            'description' => "Accounts Payable for Bill $invoiceNo",
            'date' => $date,
            'type' => 'Bill',
            'debit' => 0,
            'credit' => $bill->total_after_discount,
            'status' => 1,
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // 2. Payment Journal - Records the money out and clears the payable (if paid)
        if ($paidAmount > 0) {
            $paymentUniqueNo = $this->generatePaymentUniqueNo();

            // Debit Accounts Payable
            $transactions[] = [
                'company_id' => $companyId,
                'accounting_period_id' => $period->id,
                'account_id' => ChartOfAccount::getAccountsPayableId($companyId),
                'vendor_id' => $request->vendor_id,
                'payable_type' => 'vendor',
                'payable_id'   => $request->vendor_id,
                'reference_id' => $bill->id,
                'unique_no' => $paymentUniqueNo,
                'invoice_no' => $invoiceNo,
                'description' => "Payment for Bill $invoiceNo",
                'date' => $date,
                'type' => 'Bill',
                'debit' => $paidAmount,
                'credit' => 0,
                'status' => 1,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Credit Payment Account (Bank/Cash)
            $transactions[] = [
                'company_id' => $companyId,
                'accounting_period_id' => $period->id,
                'account_id' => $request->payment_type,
                'vendor_id' => $request->vendor_id,
                'payable_type' => 'vendor',
                'payable_id'   => $request->vendor_id,
                'reference_id' => $bill->id,
                'unique_no' => $paymentUniqueNo,
                'invoice_no' => $invoiceNo,
                'description' => "Payment for Bill $invoiceNo",
                'date' => $date,
                'type' => 'Bill',
                'debit' => 0,
                'credit' => $paidAmount,
                'status' => 1,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        BillItem::insert($billItems);
        Transaction::insert($transactions);
    }

    public function postApprovedBill(Bill $bill): void
    {
        $lines = Transaction::withoutGlobalScopes()
            ->where('company_id', $bill->company_id)
            ->where('reference_id', (string) $bill->id)
            ->where('type', 'Bill')
            ->where('status', 0)
            ->whereNull('deleted_at')
            ->lockForUpdate()
            ->get();

        if ($lines->isEmpty()) {
            $alreadyPosted = Transaction::withoutGlobalScopes()
                ->where('company_id', $bill->company_id)
                ->where('reference_id', (string) $bill->id)
                ->where('type', 'Bill')
                ->where('status', 1)
                ->whereNull('deleted_at')
                ->exists();
            if ($alreadyPosted) return;
            throw new \RuntimeException('This bill has no journal lines to post.');
        }

        $debits = round((float) $lines->sum('debit'), 4);
        $credits = round((float) $lines->sum('credit'), 4);
        if (abs($debits - $credits) > 0.01) {
            throw new \RuntimeException("Bill journal is not balanced (debits {$debits}, credits {$credits}).");
        }

        Transaction::withoutGlobalScopes()->whereIn('id', $lines->pluck('id'))->update([
            'status' => 1,
            'posted_by' => Auth::id(),
            'posted_at' => now(),
            'updated_by' => Auth::id(),
            'updated_at' => now(),
        ]);

        $this->ensureInitialPaymentRun($bill);
        $this->captureVersion($bill->fresh(), 'approved');
        if ($bill->email_on_approval) {
            SendBillInvoiceEmail::dispatch($bill->id)->afterCommit();
        }
    }

    private function ensureInitialPaymentRun(Bill $bill): void
    {
        if ((float) $bill->paid_amount <= 0 || ! $bill->account_id || $bill->paymentAllocations()->exists()) return;

        $reference = 'INIT-' . $bill->company_id . '-' . $bill->unique_no;
        $run = VendorPaymentRun::firstOrCreate(
            ['run_reference' => $reference],
            [
                'company_id' => $bill->company_id,
                'account_id' => $bill->account_id,
                'payment_date' => $bill->date,
                'status' => 'processed',
                'total_amount' => $bill->paid_amount,
                'notes' => "Initial payment for Bill #{$bill->invoice_no}",
                'created_by' => Auth::id(),
                'confirmed_by' => Auth::id(),
                'confirmed_at' => now(),
            ]
        );

        VendorPaymentRunItem::firstOrCreate(
            ['vendor_payment_run_id' => $run->id, 'bill_id' => $bill->id],
            [
                'vendor_id' => $bill->vendor_id,
                'amount_to_pay' => $bill->paid_amount,
                'bill_total' => $bill->total_after_discount,
                'bill_remaining' => $bill->remaining_amount,
            ]
        );
    }

    public function captureVersion(Bill $bill, string $event): BillInvoiceVersion
    {
        $bill->loadMissing(['billItems', 'vendor', 'currency']);
        $version = (int) BillInvoiceVersion::withoutGlobalScopes()
            ->where('bill_id', $bill->id)->max('version') + 1;

        return BillInvoiceVersion::create([
            'company_id' => $bill->company_id,
            'bill_id' => $bill->id,
            'version' => $version,
            'event' => $event,
            'snapshot' => [
                'bill' => $bill->only([
                    'unique_no', 'invoice_no', 'date', 'vendor_invoice_date', 'posting_date', 'due_date',
                    'ref_no', 'purchase_order_no', 'goods_receipt_no', 'project', 'cost_centre', 'department',
                    'warehouse', 'vendor_tax_invoice_reference', 'currency_id', 'exchange_rate', 'sub_total',
                    'discount_total', 'net_before_tax', 'tax', 'grand_total', 'withholding_tax',
                    'total_after_discount', 'paid_amount', 'remaining_amount', 'approval_status', 'payment_status', 'note',
                ]),
                'vendor' => $bill->vendor?->only(['id', 'name', 'email', 'phone', 'address']),
                'currency' => $bill->currency?->only(['id', 'name', 'code', 'symbol']),
                'items' => $bill->billItems->map->only([
                    'item', 'manual_item', 'description', 'hs_code', 'quantity', 'kg', 'unit_of_measure',
                    'price', 'discount', 'tax_name_snapshot', 'tax_rate_snapshot', 'line_subtotal',
                    'discount_amount', 'tax_amount', 'total', 'account_id',
                ])->values()->all(),
            ],
            'created_by' => Auth::id(),
        ]);
    }

    public function voidBill(Bill $bill, string $reason): void
    {
        if ($bill->approval_status !== 'approved') {
            throw new \RuntimeException('Only approved bills can be voided.');
        }
        if ((float) $bill->paid_amount > 0 || $bill->paymentAllocations()->exists() || $bill->debitNoteAllocations()->exists()) {
            throw new \RuntimeException('A bill with payments or debit-note allocations cannot be voided. Reverse those settlements first.');
        }

        $posted = Transaction::withoutGlobalScopes()
            ->where('company_id', $bill->company_id)
            ->where('reference_id', (string) $bill->id)
            ->where('type', 'Bill')->where('status', 1)->whereNull('deleted_at')->lockForUpdate()->get();
        if ($posted->isEmpty()) throw new \RuntimeException('No posted bill journal was found to reverse.');

        $now = now();
        $reversalReference = 'VOID-' . $bill->unique_no;
        foreach ($posted as $line) {
            Transaction::create([
                'company_id' => $bill->company_id,
                'accounting_period_id' => $bill->accounting_period_id,
                'account_id' => $line->account_id,
                'vendor_id' => $bill->vendor_id,
                'payable_type' => 'vendor',
                'payable_id' => $bill->vendor_id,
                'reference_id' => $bill->id,
                'unique_no' => $reversalReference,
                'invoice_no' => $bill->invoice_no,
                'description' => 'Void reversal: ' . $line->description,
                'date' => $bill->posting_date ?? $bill->date,
                'type' => 'BillVoid',
                'debit' => $line->credit,
                'credit' => $line->debit,
                'status' => 1,
                'posted_by' => Auth::id(),
                'posted_at' => $now,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
        }

        $bill->update([
            'approval_status' => 'void',
            'payment_status' => 'unpaid',
            'remaining_amount' => 0,
            'void_reason' => $reason,
            'voided_by' => Auth::id(),
            'voided_at' => $now,
            'updated_by' => Auth::id(),
        ]);
        $this->captureVersion($bill->fresh(), 'voided');
    }

    // ======================================
    // PDF GENERATION METHODS
    // ======================================

    /**
     * Generate bill PDF
     */
    public function generateBillPdf(Bill $bill): string
    {
        try {
            // Load with relationships
            $bill->load(['vendor', 'billItems', 'currency']);

            // Load company data
            $company = Company::find($bill->company_id);

            // Prepare data for PDF
            $data = [
                'bill' => $bill,
                'vendor' => $bill->vendor,
                'company' => $company,
                'items' => $bill->billItems,
                'date' => $bill->date instanceof \Carbon\Carbon ? $bill->date->format('d-m-Y') : \Carbon\Carbon::parse($bill->date)->format('d-m-Y'),
                'subtotal' => $bill->sub_total,
                'discount' => $bill->discount_total,
                'tax' => $bill->tax,
                'total' => $bill->total_after_discount,
                'paid_amount' => $bill->paid_amount,
                'remaining_amount' => $bill->remaining_amount,
                'amount_in_words' => $this->convertToWords($bill->total_after_discount),
                'invoiceTemplate' => \App\Models\InvoiceTemplate::where('company_id', $bill->company_id)->where('is_default', true)->first() ?? new \App\Models\InvoiceTemplate(),
            ];

            // Generate PDF
            $pdf = Pdf::loadView('pdf.bill', $data);

            // Set paper size and orientation
            $pdf->setPaper('A4', 'portrait');

            // Return PDF content
            return $pdf->output();
        } catch (\Exception $e) {
            Log::error('Bill PDF generation error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Convert number to words for bill amount
     */
    public function convertToWords($number): string
    {
        $number = floatval($number);
        $whole = floor($number);
        $fraction = round(($number - $whole) * 100);

        $words = $this->convertNumberToWords($whole);

        if ($fraction > 0) {
            $words .= ' AND ' . $this->convertNumberToWords($fraction) . ' CENTS';
        }

        return strtoupper($words . ' ONLY');
    }

    /**
     * Convert number to words helper
     */
    private function convertNumberToWords($number): string
    {
        $hyphen      = '-';
        $conjunction = ' and ';
        $separator   = ', ';
        $negative    = 'negative ';
        $decimal     = ' point ';
        $dictionary  = array(
            0                   => 'zero',
            1                   => 'one',
            2                   => 'two',
            3                   => 'three',
            4                   => 'four',
            5                   => 'five',
            6                   => 'six',
            7                   => 'seven',
            8                   => 'eight',
            9                   => 'nine',
            10                  => 'ten',
            11                  => 'eleven',
            12                  => 'twelve',
            13                  => 'thirteen',
            14                  => 'fourteen',
            15                  => 'fifteen',
            16                  => 'sixteen',
            17                  => 'seventeen',
            18                  => 'eighteen',
            19                  => 'nineteen',
            20                  => 'twenty',
            30                  => 'thirty',
            40                  => 'forty',
            50                  => 'fifty',
            60                  => 'sixty',
            70                  => 'seventy',
            80                  => 'eighty',
            90                  => 'ninety',
            100                 => 'hundred',
            1000                => 'thousand',
            1000000             => 'million',
            1000000000          => 'billion',
            1000000000000       => 'trillion',
            1000000000000000    => 'quadrillion',
            1000000000000000000 => 'quintillion'
        );

        if (!is_numeric($number)) {
            return '';
        }

        if ($number < 0) {
            return $negative . $this->convertNumberToWords(abs($number));
        }

        $string = $fraction = null;

        if (strpos($number, '.') !== false) {
            list($number, $fraction) = explode('.', $number);
        }

        switch (true) {
            case $number < 21:
                $string = $dictionary[$number];
                break;
            case $number < 100:
                $tens   = ((int) ($number / 10)) * 10;
                $units  = $number % 10;
                $string = $dictionary[$tens];
                if ($units) {
                    $string .= $hyphen . $dictionary[$units];
                }
                break;
            case $number < 1000:
                $hundreds  = $number / 100;
                $remainder = $number % 100;
                $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
                if ($remainder) {
                    $string .= $conjunction . $this->convertNumberToWords($remainder);
                }
                break;
            default:
                $baseUnit = pow(1000, floor(log($number, 1000)));
                $numBaseUnits = (int) ($number / $baseUnit);
                $remainder = $number % $baseUnit;
                $string = $this->convertNumberToWords($numBaseUnits) . ' ' . $dictionary[$baseUnit];
                if ($remainder) {
                    $string .= $remainder < 100 ? $conjunction : $separator;
                    $string .= $this->convertNumberToWords($remainder);
                }
                break;
        }

        if (null !== $fraction && is_numeric($fraction)) {
            $string .= $decimal;
            $words = array();
            foreach (str_split((string) $fraction) as $digit) {
                $words[] = $dictionary[$digit];
            }
            $string .= implode(' ', $words);
        }

        return $string;
    }

    /**
     * Send bill email to vendor
     */
    public function sendBillEmail(Bill $bill): bool
    {
        $recipient = $bill->vendor?->email;
        $delivery = BillInvoiceDelivery::create([
            'company_id' => $bill->company_id,
            'bill_id' => $bill->id,
            'channel' => 'email',
            'recipient' => $recipient ?: 'missing',
            'status' => 'queued',
            'created_by' => Auth::id(),
        ]);

        try {
            if ($bill->approval_status !== 'approved') {
                throw new \RuntimeException('Only approved bills can be emailed.');
            }
            // Check if vendor has email
            if (!$recipient) throw new \RuntimeException('Vendor does not have an email address.');

            // Generate PDF
            $pdfContent = $this->generateBillPdf($bill);

            // Send email
            Mail::to($recipient)
                ->send(new VendorBillMail($bill, $pdfContent));

            // Update bill record
            // $bill->update([
            //     'email_sent' => true,
            //     'email_sent_at' => now(),
            // ]);

            $delivery->update(['status' => 'sent', 'sent_at' => now()]);
            Log::info('Bill email sent successfully to: ' . $recipient);

            return true;
        } catch (\Exception $e) {
            $delivery->update(['status' => 'failed', 'failure_reason' => $e->getMessage()]);
            Log::error('Failed to send bill email: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Download bill PDF
     */
    public function downloadBillPdf($billId)
    {
        $bill = Bill::with(['vendor', 'items', 'currency'])->findOrFail($billId);
        $pdfContent = $this->generateBillPdf($bill);

        $fileName = 'Bill_' . $bill->invoice_no . '.pdf';

        return response()->streamDownload(function () use ($pdfContent) {
            echo $pdfContent;
        }, $fileName, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Preview bill PDF in browser
     */
    public function previewBillPdf($billId)
    {
        $bill = Bill::with(['vendor', 'items', 'currency'])->findOrFail($billId);
        $pdfContent = $this->generateBillPdf($bill);

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Bill_' . $bill->invoice_no . '.pdf"',
        ]);
    }

    /**
     * Resend bill email
     */
    public function resendBillEmail($billId): bool
    {
        $bill = Bill::with('vendor')->findOrFail($billId);
        return $this->sendBillEmail($bill);
    }
}
