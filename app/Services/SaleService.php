<?php

namespace App\Services;

use App\Mail\InvoiceMail;
use App\Jobs\SendSaleInvoiceEmail;
use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\CustomerPayment;
use App\Models\CustomerPaymentItem;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\SaleAttachments;
use App\Models\SaleItem;
use App\Models\SaleInvoiceDelivery;
use App\Models\SaleInvoiceVersion;
use App\Models\TaxRate;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class SaleService
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

    /**
     * Calculate totals from rows
     */
    public function calculateTotals(array $rows): array
    {
        $subTotal = 0;
        $discountTotal = 0;
        $taxTotal = 0;

        $totalBeforeTax = 0;   // after discount, before tax
        $grandTotal = 0;       // after discount + tax

        foreach ($rows as $row) {
            $quantity = (float) ($row['quantity'] ?? 0);
            $kg = (float) ($row['kg'] ?? 0);
            $price = (float) ($row['price'] ?? 0);
            $discountPercentage = (float) ($row['discount_percentage'] ?? 0);
            $taxId = $row['tax_id'] ?? null;

            // Row subtotal
            $rowSubtotal = 0;
            if ($kg > 0) {
                $rowSubtotal = $kg * $price;
            } elseif ($quantity > 0) {
                $rowSubtotal = $quantity * $price;
            }

            $subTotal += $rowSubtotal;

            // Discount
            $discountAmount = ($rowSubtotal * $discountPercentage) / 100;
            $discountTotal += $discountAmount;

            // Amount after discount (taxable base)
            $amountAfterDiscount = $rowSubtotal - $discountAmount;
            $totalBeforeTax += $amountAfterDiscount;

            // Tax (RESET per row)
            $taxAmount = 0;
            if ($taxId) {
                $tax = TaxRate::find($taxId);
                if ($tax) {
                    // ✅ Tax after discount
                    $taxAmount = ($amountAfterDiscount * (float) $tax->tax_rate) / 100;
                    $taxTotal += $taxAmount;
                }
            }

            // Row total
            $grandTotal += ($amountAfterDiscount + $taxAmount);
        }

        return [
            'sub_total' => round($subTotal, 2),
            'discount' => round($discountTotal, 2),
            'tax_total' => round($taxTotal, 2),
            'total' => round($totalBeforeTax, 2),               // after discount, before tax
            'total_after_discount' => round($grandTotal, 2),    // after discount + tax (final)
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
        $companyId = loggedCompanyId()['company_id'];
        $uniqueNo = $this->nextInvoiceUniqueNo($companyId, $validatedData['date']);
        $authId = Auth::id();
        $paidAmount = (float) ($validatedData['paid_amount'] ?? 0);
        $exchangeRate = (float) ($validatedData['exchange_rate'] ?? 1);
        $customer = Customer::findOrFail($validatedData['customer_id']);
        $currentExposure = (float) Sale::where('customer_id', $customer->id)
            ->where('approval_status', 'approved')
            ->whereNull('voided_at')
            ->selectRaw('COALESCE(SUM(remaining_amount * COALESCE(exchange_rate, 1)), 0) AS base_exposure')
            ->value('base_exposure');
        $invoiceBaseTotal = round($totals['total_after_discount'] * $exchangeRate, 4);
        $creditLimitExceeded = (float) ($customer->credit_limit ?? 0) > 0
            && ($currentExposure + $invoiceBaseTotal) > (float) $customer->credit_limit;
        $dueDate = $validatedData['due_date'] ?? \Carbon\Carbon::parse($validatedData['date'])
            ->addDays((int) ($customer->payment_terms_days ?? 30))
            ->toDateString();

        return Sale::create([
            'accounting_period_id' => $period->id,
            'customer_id' => $validatedData['customer_id'],
            'account_id' => $validatedData['payment_type'] ?? null,
            'currency_id' => $validatedData['currency_id'],
            'exchange_rate' => $exchangeRate,
            'unique_no' => $uniqueNo,
            'invoice_no' => $validatedData['invoice_no'],
            'date' => $validatedData['date'],
            'due_date' => $dueDate,
            'ref_no' => $validatedData['ref_no'] ?? null,
            'customer_po_no' => $validatedData['customer_po_no'] ?? null,
            'delivery_address' => $validatedData['delivery_address'] ?? null,
            'salesperson_id' => $validatedData['salesperson_id'] ?? null,
            'cost_centre' => $validatedData['cost_centre'] ?? null,
            'project' => $validatedData['project'] ?? null,
            'warehouse' => $validatedData['warehouse'] ?? null,
            'tax_invoice_reference' => $validatedData['tax_invoice_reference'] ?? null,
            'electronic_invoice_qr_payload' => $validatedData['electronic_invoice_qr_payload'] ?? null,
            'note' => $validatedData['note'] ?? null,
            'sub_total' => $totals['sub_total'],
            'discount_total' => $totals['discount'],
            'net_before_tax' => $totals['total'],
            'tax' => $totals['tax_total'],
            'total' => $totals['total_after_discount'],
            'grand_total' => $totals['total_after_discount'],
            'base_currency_total' => $invoiceBaseTotal,
            'total_after_discount' => $totals['total_after_discount'],
            'paid_amount' => $paidAmount,
            'remaining_amount' => $remainingAmount,
            'payment_status' => $this->paymentStatus($paidAmount, $remainingAmount),
            'email_on_approval' => ! empty($validatedData['send_email']),
            'approval_status' => 'draft',
            'credit_limit_exceeded' => $creditLimitExceeded,
            'status' => 1,
            'created_by' => $authId,
            'updated_by' => $authId,
        ]);
    }

    public function nextInvoiceUniqueNo(int $companyId, string $date): string
    {
        $year = (int) date('Y', strtotime($date));

        DB::table('sale_invoice_sequences')->upsert([
            ['company_id' => $companyId, 'year' => $year, 'next_number' => 1, 'created_at' => now(), 'updated_at' => now()],
        ], ['company_id', 'year'], ['updated_at']);

        $sequence = DB::table('sale_invoice_sequences')
            ->where('company_id', $companyId)
            ->where('year', $year)
            ->lockForUpdate()
            ->first();

        $number = (int) $sequence->next_number;
        DB::table('sale_invoice_sequences')->where('id', $sequence->id)->update([
            'next_number' => $number + 1,
            'updated_at' => now(),
        ]);

        return 'INV-' . $year . '-' . str_pad((string) $number, 6, '0', STR_PAD_LEFT);
    }

    public function paymentStatus(float $paidAmount, float $remainingAmount): string
    {
        if ($remainingAmount < 0) {
            return 'overpaid';
        }
        if ($remainingAmount <= 0) {
            return 'paid';
        }

        return $paidAmount > 0 ? 'partial' : 'unpaid';
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
            $newFile = "{$clean}_{$timestamp}." . $file->getClientOriginalExtension();

            $path = $file->storeAs('sale-attachments', $newFile, 'local');

            SaleAttachments::create([
                'sale_id' => $sale->id,
                'accounting_period_id' => $period->id,
                'attachment' => $path,
                'original_name' => $original,
                'storage_disk' => 'local',
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
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
        $exchangeRate = (float) ($validatedData['exchange_rate'] ?? 1);

        $this->assertAccountingConfiguration($companyId, $totals['discount'], $totals['tax_total']);

        $saleItems = [];
        $transactions = [];

        // Create sale items and revenue transactions
        // Create sale items and revenue transactions
        $totalTaxAmount = 0;
        $totalDiscountAmount = 0;
        foreach ($validatedData['rows'] as $row) {
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

            $saleItems[] = [
                'company_id' => $companyId,
                'accounting_period_id' => $period->id,
                'sale_id' => $sale->id,
                'unique_no' => $sale->unique_no,
                'item' => $row['item'] ?? null,
                'manual_item' => $row['manual_item'] ?? null,
                'description' => $row['description'] ?? null,
                'hs_code' => $row['hs_code'] ?? null,
                'quantity' => $row['quantity'],
                'kg' => $row['kg'],
                'unit_of_measure' => $row['unit_of_measure'] ?? (($rowKg > 0) ? 'kg' : 'unit'),
                'price' => $row['price'],
                'discount' => $row['discount_percentage'] ?? 0,
                'account_id' => $row['account_id'],
                'tax_id' => $row['tax_id'] ?? null,
                'tax_name_snapshot' => $tax->name ?? null,
                'tax_rate_snapshot' => $tax->tax_rate ?? 0,
                'line_subtotal' => $itemTotal,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'total' => $item_total,
                'created_by' => $authId,
                'updated_by' => $authId,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // Revenue transaction (Gross Amount)
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
                'credit' => round($itemTotal * $exchangeRate, 4),
                'debit' => 0,
                'status' => 0,
                'created_by' => $authId,
                'updated_by' => $authId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Discount transaction (If applicable)
        if ($totalDiscountAmount > 0) {
            $discountAccountId = ChartOfAccount::getSalesDiscountAccountId($companyId);
            if ($discountAccountId) {
                $transactions[] = [
                    'company_id' => $companyId,
                    'accounting_period_id' => $period->id,
                    'account_id' => $discountAccountId,
                    'customer_id' => $validatedData['customer_id'],
                    'reference_id' => $sale->id,
                    'unique_no' => $sale->unique_no,
                    'invoice_no' => $validatedData['invoice_no'],
                    'description' => 'Sales Discount for Sale ' . $validatedData['invoice_no'],
                    'date' => $validatedData['date'],
                    'type' => 'Sale',
                    'credit' => 0,
                    'debit' => round($totalDiscountAmount * $exchangeRate, 4),
                    'status' => 0,
                    'created_by' => $authId,
                    'updated_by' => $authId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // Tax transaction (If applicable)
        if ($totalTaxAmount > 0) {
            $taxAccountId = ChartOfAccount::getSalesTaxPayableId($companyId);
            if ($taxAccountId) {
                $transactions[] = [
                    'company_id' => $companyId,
                    'accounting_period_id' => $period->id,
                    'account_id' => $taxAccountId,
                    'customer_id' => $validatedData['customer_id'],
                    'reference_id' => $sale->id,
                    'unique_no' => $sale->unique_no,
                    'invoice_no' => $validatedData['invoice_no'],
                    'description' => 'Sales Tax for Sale ' . $validatedData['invoice_no'],
                    'date' => $validatedData['date'],
                    'type' => 'Sale',
                    'credit' => round($totalTaxAmount * $exchangeRate, 4),
                    'debit' => 0,
                    'status' => 0,
                    'created_by' => $authId,
                    'updated_by' => $authId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // Sale Journal - DR Accounts Receivable (Total)
        $totalSaleAmount = $totals['total_after_discount'];
        $transactions[] = [
            'company_id' => $companyId,
            'accounting_period_id' => $period->id,
            'account_id' => ChartOfAccount::getAccountsReceivableId($companyId),
            'customer_id' => $validatedData['customer_id'],
            'reference_id' => $sale->id,
            'unique_no' => $sale->unique_no,
            'invoice_no' => $validatedData['invoice_no'],
            'description' => 'Accounts Receivable for Sale ' . $validatedData['invoice_no'],
            'date' => $validatedData['date'],
            'type' => 'Sale',
            'credit' => 0,
            'debit' => round($totalSaleAmount * $exchangeRate, 4),
            'status' => 0,
            'created_by' => $authId,
            'updated_by' => $authId,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        // Payment Journal (Separate Journal Entry)
        $paidAmount = $validatedData['paid_amount'] ?? 0;
        if ($paidAmount > 0) {
            $paymentUniqueNo = 'PAY-' . $sale->unique_no;

            // DR Bank/Cash
            $transactions[] = [
                'company_id' => $companyId,
                'accounting_period_id' => $period->id,
                'account_id' => $validatedData['payment_type'],
                'customer_id' => $validatedData['customer_id'],
                'reference_id' => $sale->id,
                'unique_no' => $paymentUniqueNo,
                'invoice_no' => $validatedData['invoice_no'],
                'description' => 'Payment for Sale ' . $validatedData['invoice_no'],
                'date' => $validatedData['date'],
                'type' => 'Sale', // Or 'Payment' if you want
                'credit' => 0,
                'debit' => round($paidAmount * $exchangeRate, 4),
                'status' => 0,
                'created_by' => $authId,
                'updated_by' => $authId,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // CR Accounts Receivable
            $transactions[] = [
                'company_id' => $companyId,
                'accounting_period_id' => $period->id,
                'account_id' => ChartOfAccount::getAccountsReceivableId($companyId),
                'customer_id' => $validatedData['customer_id'],
                'reference_id' => $sale->id,
                'unique_no' => $paymentUniqueNo,
                'invoice_no' => $validatedData['invoice_no'],
                'description' => 'Payment received for Sale ' . $validatedData['invoice_no'],
                'date' => $validatedData['date'],
                'type' => 'Sale',
                'credit' => round($paidAmount * $exchangeRate, 4),
                'debit' => 0,
                'status' => 0,
                'created_by' => $authId,
                'updated_by' => $authId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        SaleItem::insert($saleItems);
        Transaction::insert($transactions);
    }

    public function assertAccountingConfiguration(int $companyId, float $discountTotal = 0, float $taxTotal = 0): void
    {
        if (! ChartOfAccount::getAccountsReceivableId($companyId)) {
            throw new \RuntimeException('Accounts Receivable is not configured for this company.');
        }
        if ($discountTotal > 0 && ! ChartOfAccount::getSalesDiscountAccountId($companyId)) {
            throw new \RuntimeException('Sales Discount account is not configured for this company.');
        }
        if ($taxTotal > 0 && ! ChartOfAccount::getSalesTaxPayableId($companyId)) {
            throw new \RuntimeException('Sales Tax Payable account is not configured for this company.');
        }
    }

    public function postApprovedSale(Sale $sale): void
    {
        $lines = Transaction::withoutGlobalScopes()
            ->where('company_id', $sale->company_id)
            ->where('reference_id', $sale->id)
            ->where('type', 'Sale')
            ->where('status', 0)
            ->whereNull('deleted_at')
            ->lockForUpdate()
            ->get();

        if ($lines->isEmpty()) {
            $alreadyPosted = Transaction::withoutGlobalScopes()
                ->where('company_id', $sale->company_id)
                ->where('reference_id', $sale->id)
                ->where('type', 'Sale')
                ->where('status', 1)
                ->whereNull('deleted_at')
                ->exists();

            if ($alreadyPosted) {
                return;
            }
            throw new \RuntimeException('This invoice has no journal lines to post.');
        }

        $debits = round((float) $lines->sum('debit'), 4);
        $credits = round((float) $lines->sum('credit'), 4);
        if (abs($debits - $credits) > 0.01) {
            throw new \RuntimeException("Invoice journal is not balanced (debits {$debits}, credits {$credits}).");
        }

        Transaction::withoutGlobalScopes()
            ->whereIn('id', $lines->pluck('id'))
            ->update([
                'status' => 1,
                'posted_by' => Auth::id(),
                'posted_at' => now(),
                'updated_by' => Auth::id(),
                'updated_at' => now(),
            ]);

        $this->ensureInitialPaymentAllocation($sale);
        $this->captureVersion($sale->fresh(), 'approved');

        if ($sale->email_on_approval) {
            SendSaleInvoiceEmail::dispatch($sale->id)->afterCommit();
        }
    }

    private function ensureInitialPaymentAllocation(Sale $sale): void
    {
        if ((float) $sale->paid_amount <= 0 || ! $sale->account_id || $sale->paymentAllocations()->exists()) {
            return;
        }

        $reference = 'CP-' . $sale->unique_no;
        $payment = CustomerPayment::firstOrCreate(
            ['company_id' => $sale->company_id, 'reference_no' => $reference],
            [
                'customer_id' => $sale->customer_id,
                'account_id' => $sale->account_id,
                'payment_date' => $sale->date,
                'note' => "Initial payment for Invoice #{$sale->invoice_no}",
                'total_amount' => $sale->paid_amount,
                'status' => 'confirmed',
                'created_by' => Auth::id(),
                'confirmed_by' => Auth::id(),
                'confirmed_at' => now(),
            ]
        );

        CustomerPaymentItem::firstOrCreate(
            ['customer_payment_id' => $payment->id, 'sale_id' => $sale->id],
            [
                'amount_to_apply' => $sale->paid_amount,
                'invoice_total' => $sale->grand_total ?: $sale->total_after_discount,
                'invoice_remaining' => $sale->grand_total ?: $sale->total_after_discount,
            ]
        );
    }

    public function captureVersion(Sale $sale, string $event): SaleInvoiceVersion
    {
        $sale->loadMissing(['saleItems', 'customer']);
        $version = ((int) $sale->versions()->max('version')) + 1;

        return SaleInvoiceVersion::create([
            'company_id' => $sale->company_id,
            'sale_id' => $sale->id,
            'version' => $version,
            'event' => $event,
            'snapshot' => [
                'invoice' => $sale->withoutRelations()->toArray(),
                'customer' => $sale->customer?->toArray(),
                'items' => $sale->saleItems->toArray(),
            ],
            'created_by' => Auth::id(),
        ]);
    }

    public function voidSale(Sale $sale, string $reason): void
    {
        if ($sale->approval_status !== 'approved') {
            throw new \LogicException('Only approved invoices can be voided.');
        }
        if ((float) $sale->paid_amount > 0 || $sale->paymentAllocations()->exists() || $sale->creditNoteAllocations()->exists()) {
            throw new \LogicException('Reverse payments and credit allocations before voiding this invoice.');
        }

        $posted = Transaction::withoutGlobalScopes()
            ->where('company_id', $sale->company_id)
            ->where('reference_id', $sale->id)
            ->where('type', 'Sale')
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->lockForUpdate()
            ->get();

        $now = now();
        foreach ($posted as $line) {
            Transaction::create([
                'company_id' => $line->company_id,
                'accounting_period_id' => $line->accounting_period_id,
                'account_id' => $line->account_id,
                'customer_id' => $line->customer_id,
                'reference_id' => $sale->id,
                'unique_no' => 'VOID-' . $sale->unique_no,
                'invoice_no' => $sale->invoice_no,
                'description' => 'Void reversal: ' . $reason,
                'date' => $now->toDateString(),
                'type' => 'Sale',
                'debit' => $line->credit,
                'credit' => $line->debit,
                'status' => 1,
                'reversal_of_transaction_id' => $line->id,
                'reversal_of_unique_no' => $line->unique_no,
                'correction_type' => 'void',
                'posted_by' => Auth::id(),
                'posted_at' => $now,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
        }

        $sale->update([
            'approval_status' => 'void',
            'payment_status' => 'void',
            'remaining_amount' => 0,
            'voided_at' => $now,
            'voided_by' => Auth::id(),
            'void_reason' => $reason,
            'updated_by' => Auth::id(),
        ]);
        $this->captureVersion($sale->fresh(), 'voided');
    }

    public function calculateSubTotal(array $rows): float
    {
        $subTotal = 0;

        foreach ($rows as $row) {
            $quantity = floatval($row['quantity'] ?? 1);
            $kg = floatval($row['kg'] ?? 0);
            $price = floatval($row['price'] ?? 0);

            if ($kg > 0) {
                $subTotal += $kg * $price; // Corrected: Multiply KG by price
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
                $rowSubtotal = $kg * $price; // Corrected: Multiply KG by price
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
                    $taxAmount = ($amountAfterDiscount * $tax->tax_rate) / 100;
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
                $rowSubtotal = $kg * $price; // Corrected: Multiply KG by price
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
        $total_after_discount,
        $paidAmount,
        $remainingAmount
    ) {
        $discountTotal = $this->calculateDiscountTotal($validatedData['rows']);
        $netBeforeTax = $subTotal - $discountTotal;
        $exchangeRate = (float) ($validatedData['exchange_rate'] ?? 1);
        $customer = Customer::findOrFail($validatedData['customer_id']);
        $exposure = (float) Sale::where('customer_id', $customer->id)
            ->whereKeyNot($sale->id)
            ->where('approval_status', 'approved')
            ->whereNull('voided_at')
            ->selectRaw('COALESCE(SUM(remaining_amount * COALESCE(exchange_rate, 1)), 0) AS base_exposure')
            ->value('base_exposure');
        $invoiceBaseTotal = round($total_after_discount * $exchangeRate, 4);
        $creditLimitExceeded = (float) ($customer->credit_limit ?? 0) > 0
            && ($exposure + $invoiceBaseTotal) > (float) $customer->credit_limit;
        $dueDate = $validatedData['due_date'] ?? \Carbon\Carbon::parse($validatedData['date'])
            ->addDays((int) ($customer->payment_terms_days ?? 30))
            ->toDateString();

        $sale->update([
            'accounting_period_id' => $period->id,
            'customer_id' => $validatedData['customer_id'],
            'account_id' => $validatedData['payment_type'] ?? null,
            'currency_id' => $validatedData['currency_id'],
            'exchange_rate' => $exchangeRate,
            'invoice_no' => $validatedData['invoice_no'],
            'date' => $validatedData['date'],
            'due_date' => $dueDate,
            'ref_no' => $validatedData['ref_no'] ?? null,
            'customer_po_no' => $validatedData['customer_po_no'] ?? null,
            'delivery_address' => $validatedData['delivery_address'] ?? null,
            'salesperson_id' => $validatedData['salesperson_id'] ?? null,
            'cost_centre' => $validatedData['cost_centre'] ?? null,
            'project' => $validatedData['project'] ?? null,
            'warehouse' => $validatedData['warehouse'] ?? null,
            'tax_invoice_reference' => $validatedData['tax_invoice_reference'] ?? null,
            'electronic_invoice_qr_payload' => $validatedData['electronic_invoice_qr_payload'] ?? null,
            'note' => $validatedData['note'] ?? null,
            'sub_total' => $subTotal,
            'discount_total' => $discountTotal,
            'net_before_tax' => $netBeforeTax,
            'tax' => $taxTotal,
            'total' => $total_after_discount,
            'grand_total' => $total_after_discount,
            'base_currency_total' => $invoiceBaseTotal,
            'total_after_discount' => $total_after_discount,
            'paid_amount' => $paidAmount,
            'remaining_amount' => $remainingAmount,
            'payment_status' => $this->paymentStatus((float) $paidAmount, (float) $remainingAmount),
            'email_on_approval' => ! empty($validatedData['send_email']),
            'credit_limit_exceeded' => $creditLimitExceeded,
            'updated_by' => Auth::id(),
        ]);
    }

    public function deleteSaleItemAndTransaction(Sale $sale)
    {
        SaleItem::where('sale_id', $sale->id)->delete();
        Transaction::withoutGlobalScopes()
            ->where('company_id', $sale->company_id)
            ->where('reference_id', $sale->id)
            ->where('type', 'Sale')
            ->where('status', 0)
            ->whereNull('deleted_at')
            ->delete();
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
        $exchangeRate = (float) ($validatedData['exchange_rate'] ?? 1);

        $this->assertAccountingConfiguration(
            $companyId,
            $this->calculateDiscountTotal($validatedData['rows']),
            $this->calculateTaxTotal($validatedData['rows'])
        );

        $totalTaxAmount = 0;
        $totalDiscountAmount = 0;
        foreach ($validatedData['rows'] as $row) {
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

            $saleItems[] = [
                'company_id' => $companyId,
                'accounting_period_id' => $period->id,
                'sale_id' => $sale->id,
                'unique_no' => $sale->unique_no,
                'item' => $row['item'] ?? null,
                'manual_item' => $row['manual_item'] ?? null,
                'description' => $row['description'] ?? null,
                'hs_code' => $row['hs_code'] ?? null,
                'quantity' => $row['quantity'],
                'kg' => $row['kg'],
                'unit_of_measure' => $row['unit_of_measure'] ?? (($rowKg > 0) ? 'kg' : 'unit'),
                'price' => $row['price'],
                'discount' => $row['discount_percentage'] ?? 0,
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
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // Revenue transaction (Gross)
            $transactions[] = [
                'company_id' => $companyId,
                'accounting_period_id' => $period->id,
                'account_id' => $row['account_id'],
                'customer_id' => $validatedData['customer_id'],
                'reference_id' => $sale->id,
                'unique_no' => $sale->unique_no,
                'invoice_no' => $validatedData['invoice_no'],
                'description' => ($row['description'] ?? null) ?: ($row['item'] ?? null) ?: ($row['manual_item'] ?? null) ?: 'Sale Item',
                'date' => $validatedData['date'],
                'type' => 'Sale',
                'credit' => round($itemTotal * $exchangeRate, 4),
                'debit' => 0,
                'status' => 0,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Discount transaction
        if ($totalDiscountAmount > 0) {
            $discountAccountId = ChartOfAccount::getSalesDiscountAccountId($companyId);
            if ($discountAccountId) {
                $transactions[] = [
                    'company_id' => $companyId,
                    'accounting_period_id' => $period->id,
                    'account_id' => $discountAccountId,
                    'customer_id' => $validatedData['customer_id'],
                    'reference_id' => $sale->id,
                    'unique_no' => $sale->unique_no,
                    'invoice_no' => $validatedData['invoice_no'],
                    'description' => 'Sales Discount for Sale ' . $validatedData['invoice_no'],
                    'date' => $validatedData['date'],
                    'type' => 'Sale',
                    'credit' => 0,
                    'debit' => round($totalDiscountAmount * $exchangeRate, 4),
                    'status' => 0,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // Tax transaction
        if ($totalTaxAmount > 0) {
            $taxAccountId = ChartOfAccount::getSalesTaxPayableId($companyId);
            if ($taxAccountId) {
                $transactions[] = [
                    'company_id' => $companyId,
                    'accounting_period_id' => $period->id,
                    'account_id' => $taxAccountId,
                    'customer_id' => $validatedData['customer_id'],
                    'reference_id' => $sale->id,
                    'unique_no' => $sale->unique_no,
                    'invoice_no' => $validatedData['invoice_no'],
                    'description' => 'Sales Tax for Sale ' . $validatedData['invoice_no'],
                    'date' => $validatedData['date'],
                    'type' => 'Sale',
                    'credit' => round($totalTaxAmount * $exchangeRate, 4),
                    'debit' => 0,
                    'status' => 0,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // Sale Journal - DR Accounts Receivable (Total)
        $totalSaleAmount = ($sale->total_after_discount);
        $transactions[] = [
            'company_id' => $companyId,
            'accounting_period_id' => $period->id,
            'account_id' => ChartOfAccount::getAccountsReceivableId($companyId),
            'customer_id' => $validatedData['customer_id'],
            'reference_id' => $sale->id,
            'unique_no' => $sale->unique_no,
            'invoice_no' => $validatedData['invoice_no'],
            'description' => 'Accounts Receivable for Sale ' . $validatedData['invoice_no'],
            'date' => $validatedData['date'],
            'type' => 'Sale',
            'credit' => 0,
            'debit' => round($totalSaleAmount * $exchangeRate, 4),
            'status' => 0,
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        // Payment
        if ($paidAmount > 0) {
            $paymentUniqueNo = 'PAY-' . $sale->unique_no;

            $transactions[] = [
                'company_id' => $companyId,
                'accounting_period_id' => $period->id,
                'account_id' => $validatedData['payment_type'],
                'customer_id' => $validatedData['customer_id'],
                'reference_id' => $sale->id,
                'unique_no' => $paymentUniqueNo,
                'invoice_no' => $validatedData['invoice_no'],
                'description' => 'Payment for Sale ' . $validatedData['invoice_no'],
                'date' => $validatedData['date'],
                'type' => 'Sale',
                'credit' => 0,
                'debit' => round($paidAmount * $exchangeRate, 4),
                'status' => 0,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $transactions[] = [
                'company_id' => $companyId,
                'accounting_period_id' => $period->id,
                'account_id' => ChartOfAccount::getAccountsReceivableId($companyId),
                'customer_id' => $validatedData['customer_id'],
                'reference_id' => $sale->id,
                'unique_no' => $paymentUniqueNo,
                'invoice_no' => $validatedData['invoice_no'],
                'description' => 'Payment received for Sale ' . $validatedData['invoice_no'],
                'date' => $validatedData['date'],
                'type' => 'Sale',
                'credit' => round($paidAmount * $exchangeRate, 4),
                'debit' => 0,
                'status' => 0,
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
                $disk = $att->storage_disk ?: 'public';
                if (Storage::disk($disk)->exists($att->attachment)) {
                    Storage::disk($disk)->delete($att->attachment);
                }
                $att->delete();
            }

            foreach ($request->file('attachment') as $file) {
                $original = $file->getClientOriginalName();
                $clean = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($original, PATHINFO_FILENAME));
                $timestamp = now()->format('Ymd_His');
                $newFile = "{$clean}_{$timestamp}." . $file->getClientOriginalExtension();

                $path = $file->storeAs('sale-attachments', $newFile, 'local');

                SaleAttachments::create([
                    'sale_id' => $sale->id,
                    'accounting_period_id' => $period->id,
                    'attachment' => $path,
                    'original_name' => $original,
                    'storage_disk' => 'local',
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
            }
        }
    }

    // ======================================
    // PDF GENERATION METHODS
    // ======================================

    /**
     * Generate invoice PDF
     */
    public function generateInvoicePdf(Sale $sale): string
    {
        try {
            // Load company data (if available)
            // Load with relationships
            $sale->load(['customer', 'saleItems', 'currency']);

            // Load company data
            $company = Company::find($sale->company_id);
            $invoiceTemplate = \App\Models\InvoiceTemplate::where('company_id', $sale->company_id)
                ->where('is_default', true)
                ->first() ?? new \App\Models\InvoiceTemplate();

            // Prepare data
            $data = [
                'sale' => $sale,
                'customer' => $sale->customer,
                'company' => $company,
                'items' => $sale->saleItems,
                'date' => $sale->date instanceof \Carbon\Carbon ? $sale->date->format('d-m-Y') : \Carbon\Carbon::parse($sale->date)->format('d-m-Y'),
                'subtotal' => $sale->sub_total,
                'discount' => $sale->discount_total,
                'tax' => $sale->tax,
                'total' => $sale->grand_total ?: $sale->total_after_discount,
                'paid_amount' => $sale->paid_amount,
                'remaining_amount' => $sale->remaining_amount,
                'amount_in_words' => $this->convertToWords($sale->grand_total ?: $sale->total_after_discount),
                'invoiceTemplate' => $invoiceTemplate,
                'title' => 'Sale Invoice',
            ];

            // Generate PDF
            $pdf = Pdf::loadView('pdf.invoice', $data);

            // Set paper size and orientation
            $pdf->setPaper('A4', 'portrait');

            // Return PDF content
            return $pdf->output();
        } catch (\Exception $e) {
            Log::error('PDF generation error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Convert number to words for invoice amount
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
        $hyphen = '-';
        $conjunction = ' and ';
        $separator = ', ';
        $negative = 'negative ';
        $decimal = ' point ';
        $dictionary = [
            0 => 'zero',
            1 => 'one',
            2 => 'two',
            3 => 'three',
            4 => 'four',
            5 => 'five',
            6 => 'six',
            7 => 'seven',
            8 => 'eight',
            9 => 'nine',
            10 => 'ten',
            11 => 'eleven',
            12 => 'twelve',
            13 => 'thirteen',
            14 => 'fourteen',
            15 => 'fifteen',
            16 => 'sixteen',
            17 => 'seventeen',
            18 => 'eighteen',
            19 => 'nineteen',
            20 => 'twenty',
            30 => 'thirty',
            40 => 'forty',
            50 => 'fifty',
            60 => 'sixty',
            70 => 'seventy',
            80 => 'eighty',
            90 => 'ninety',
            100 => 'hundred',
            1000 => 'thousand',
            1000000 => 'million',
            1000000000 => 'billion',
            1000000000000 => 'trillion',
            1000000000000000 => 'quadrillion',
            1000000000000000000 => 'quintillion',
        ];

        if (! is_numeric($number)) {
            return '';
        }

        if ($number < 0) {
            return $negative . $this->convertNumberToWords(abs($number));
        }

        $string = $fraction = null;

        if (strpos($number, '.') !== false) {
            [$number, $fraction] = explode('.', $number);
        }

        switch (true) {
            case $number < 21:
                $string = $dictionary[$number];
                break;
            case $number < 100:
                $tens = ((int) ($number / 10)) * 10;
                $units = $number % 10;
                $string = $dictionary[$tens];
                if ($units) {
                    $string .= $hyphen . $dictionary[$units];
                }
                break;
            case $number < 1000:
                $hundreds = $number / 100;
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

        if ($fraction !== null && is_numeric($fraction)) {
            $string .= $decimal;
            $words = [];
            foreach (str_split((string) $fraction) as $digit) {
                $words[] = $dictionary[$digit];
            }
            $string .= implode(' ', $words);
        }

        return $string;
    }

    /**
     * Send invoice email to customer
     */
    public function sendInvoiceEmail(Sale $sale): bool
    {
        $recipient = $sale->customer?->email;
        $delivery = SaleInvoiceDelivery::create([
            'company_id' => $sale->company_id,
            'sale_id' => $sale->id,
            'channel' => 'email',
            'recipient' => $recipient ?: 'missing',
            'status' => 'queued',
            'created_by' => Auth::id(),
        ]);

        try {
            // Check if customer has email
            if (! $sale->customer || ! $sale->customer->email) {
                $delivery->update(['status' => 'failed', 'failure_reason' => 'Customer has no email address.']);
                Log::warning('Customer does not have email address for sale: ' . $sale->invoice_no);

                return false;
            }

            // Generate PDF
            $pdfContent = $this->generateInvoicePdf($sale);

            // Send email
            Mail::to($sale->customer->email)
                ->send(new InvoiceMail($sale, $pdfContent));

            $delivery->update(['status' => 'sent', 'sent_at' => now(), 'failure_reason' => null]);

            Log::info('Invoice email sent successfully to: ' . $sale->customer->email);

            return true;
        } catch (\Exception $e) {
            $delivery->update(['status' => 'failed', 'failure_reason' => mb_substr($e->getMessage(), 0, 2000)]);
            Log::error('Failed to send invoice email: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return false;
        }
    }

    /**
     * Download invoice PDF
     */
    public function downloadInvoicePdf($saleId)
    {
        $sale = Sale::with(['customer', 'saleItems', 'currency'])->findOrFail($saleId);
        $pdfContent = $this->generateInvoicePdf($sale);

        $fileName = 'Invoice_' . $sale->invoice_no . '.pdf';

        return response()->streamDownload(function () use ($pdfContent) {
            echo $pdfContent;
        }, $fileName, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Preview invoice PDF in browser
     */
    public function previewInvoicePdf($saleId)
    {
        $sale = Sale::with(['customer', 'saleItems', 'currency'])->findOrFail($saleId);
        $pdfContent = $this->generateInvoicePdf($sale);

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Invoice_' . $sale->invoice_no . '.pdf"',
        ]);
    }

    /**
     * Get sale with all relationships for PDF
     */
    public function getSaleForPdf($saleId): Sale
    {
        return Sale::with([
            'customer',
            'saleItems',
            'currency',
            'accountingPeriod',
        ])->findOrFail($saleId);
    }

    /**
     * Resend invoice email
     */
    public function resendInvoiceEmail($saleId): bool
    {
        $sale = Sale::with('customer')->findOrFail($saleId);

        return $this->sendInvoiceEmail($sale);
    }
}
