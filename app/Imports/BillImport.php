<?php

namespace App\Imports;

use App\Models\AccountingPeriod;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\ChartOfAccount;
use App\Models\Currency;
use App\Models\TaxRate;
use App\Models\Transaction;
use App\Models\Vendor;
use App\Services\BillService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BillImport
{
    protected $companyId;
    protected $userId;

    // Cache
    protected $periods;
    protected $vendors; // key: lower(name) -> id
    protected $currencies; // key: code -> id
    protected $accounts; // key: lower(name) -> id
    protected $taxRates; // key: rate(float) -> id

    // Config
    protected $purchaseDiscountAccountId;
    protected $purchaseTaxAccountId;
    protected $accountsPayableId;

    // Buffers
    protected $billItemsBatch = [];
    protected $transactionsBatch = [];
    protected $billsForVersioning = [];

    // State
    protected $payCounter = 0;
    protected $processedCount = 0;
    protected $skippedCount = 0;
    protected $errors = [];

    public function __construct()
    {
        $this->companyId = Auth::user()->company_id;
        $this->userId = Auth::id();
        set_time_limit(0);
    }

    public function fastImport($filepath)
    {
        $this->initializeCache();

        $handle = fopen($filepath, 'r');
        if (!$handle) {
            throw new \Exception("Could not open file: $filepath");
        }

        // Header
        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return;
        }

        // Normalize Header
        $headerMap = array_flip(array_map(function ($col) {
            $col = preg_replace('/^\xEF\xBB\xBF/', '', $col);
            return strtolower(str_replace(' ', '_', trim($col)));
        }, $header));

        // Start payment counter
        $this->payCounter = Transaction::where('unique_no', 'like', 'PAY-%')->count();

        $currentInvoiceNo = null;
        $currentGroup = [];
        $batchSize = 2000;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                // Skip empty rows
                if (empty($row) || (count($row) === 1 && $row[0] === null)) continue;

                // Extract Invoice No
                $idxInvoice = $headerMap['invoice_no'] ?? null;
                if ($idxInvoice === null) break;

                $invoiceNo = isset($row[$idxInvoice]) ? trim($row[$idxInvoice]) : null;
                if (!$invoiceNo) continue;

                // Check Group Change
                if ($currentInvoiceNo !== null && $invoiceNo !== $currentInvoiceNo) {
                    $this->processInvoiceGroup($currentInvoiceNo, $currentGroup, $headerMap);
                    $currentGroup = [];

                    // Flush DB if needed
                    if (count($this->billItemsBatch) >= $batchSize) {
                        $this->flushBatches();
                    }
                }

                $currentInvoiceNo = $invoiceNo;
                $currentGroup[] = $row;
            }

            // Process Last Group
            if (!empty($currentGroup)) {
                $this->processInvoiceGroup($currentInvoiceNo, $currentGroup, $headerMap);
            }

            $this->flushBatches(); // Remaining

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);
            throw $e;
        }

        fclose($handle);
    }

    public function importChunk(array $rows)
    {
        $this->initializeCache();


        // Start payment counter
        $this->payCounter = Transaction::where('unique_no', 'like', 'PAY-%')->count();

        $currentInvoiceNo = null;
        $currentGroup = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                // Row is associative array with normalized keys from frontend
                $invoiceNo = trim($row['invoice_no'] ?? '');
                if (!$invoiceNo) continue;

                if ($currentInvoiceNo !== null && $invoiceNo !== $currentInvoiceNo) {
                    $this->processInvoiceGroup($currentInvoiceNo, $currentGroup, null);
                    $currentGroup = [];
                }

                $currentInvoiceNo = $invoiceNo;
                $currentGroup[] = $row;
            }

            if (!empty($currentGroup)) {
                $this->processInvoiceGroup($currentInvoiceNo, $currentGroup, null);
            }

            $this->flushBatches();

            DB::commit();
            return [
                'success' => true,
                'processed' => $this->processedCount,
                'skipped' => $this->skippedCount,
                'errors' => $this->errors,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Bill Import Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'processed' => $this->processedCount,
                'skipped' => $this->skippedCount,
                'errors' => $this->errors,
            ];
        }
    }

    protected function processInvoiceGroup($invoiceNo, $group, $headerMap)
    {
        if (empty($group)) return;

        // Accessor helper
        $firstRow = $group[0];
        $val = function ($row, $key) use ($headerMap) {
            if ($headerMap === null) {
                // Associative array input
                return isset($row[$key]) ? trim($row[$key]) : null;
            }
            $idx = $headerMap[$key] ?? -1;
            return isset($row[$idx]) ? trim($row[$idx]) : null;
        };

        // 1. Vendor
        $vendorName = $val($firstRow, 'vendor_name');
        if (!$vendorName) {
            $this->skippedCount++;
            $this->errors[] = "Invoice {$invoiceNo}: Vendor name is missing.";
            return;
        }

        $lowerName = strtolower($vendorName);
        $vendorId = $this->vendors[$lowerName] ?? null;
        if (!$vendorId) {
            $vendorId = $this->createVendor($firstRow, $val);
        }
        if (Bill::where('company_id', $this->companyId)->where('vendor_id', $vendorId)->where('invoice_no', $invoiceNo)->exists()) {
            $this->skippedCount++;
            $this->errors[] = "Invoice {$invoiceNo}: This vendor invoice already exists.";
            return;
        }
        if (!$vendorId) {
            $this->skippedCount++;
            $this->errors[] = "Invoice {$invoiceNo}: Could not create/find vendor '{$vendorName}'.";
            return;
        }

        // 2. Date & Period
        $dateStr = $val($firstRow, 'date');
        $date = $this->parseDate($dateStr);
        $period = $this->findPeriod($date);

        if (!$period) {
            $this->skippedCount++;
            $this->errors[] = "Invoice {$invoiceNo}: No active accounting period found for date '{$dateStr}'.";
            return;
        }

        // 3. Currency
        $currencyCode = $val($firstRow, 'currency_code');
        $currencyId = $this->currencies[$currencyCode] ?? ($this->currencies['USD'] ?? 1);

        // 4. Payment Account
        $payAccName = $val($firstRow, 'payment_account') ?? $val($firstRow, 'payment_account_name');
        $paymentAccountId = null;
        if ($payAccName) {
            $paymentAccountId = $this->accounts[strtolower($payAccName)] ?? null;
            if (!$paymentAccountId) {
                throw new \Exception("Invoice {$invoiceNo}: Chart of Account '{$payAccName}' not found.");
            }
        }
        $paidAmount = (float) ($val($firstRow, 'paid_amount') ?? 0);
        $exchangeRate = (float) ($val($firstRow, 'exchange_rate') ?? 1);
        if ($exchangeRate <= 0) throw new \Exception("Invoice {$invoiceNo}: Exchange rate must be greater than zero.");
        if ($paidAmount > 0 && ! $paymentAccountId) throw new \Exception("Invoice {$invoiceNo}: A bank payment account is required for the initial payment.");
        if ($paymentAccountId) {
            $paymentAccount = ChartOfAccount::find($paymentAccountId);
            if (! $paymentAccount || $paymentAccount->type !== 'Account' || $paymentAccount->sub_type !== 'BANK') throw new \Exception("Invoice {$invoiceNo}: Payment account must be a bank/cash account.");
        }

        // 5. Calculate Totals & Items
        $subTotal = 0;
        $discountTotal = 0;
        $taxTotal = 0;
        $grandTotal = 0;
        $now = now();

        // First, calculate all totals
        foreach ($group as $row) {
            $qty = (float)($val($row, 'quantity') ?? 1);
            $price = (float)($val($row, 'price') ?? 0);
            $taxRateVal = (float)($val($row, 'tax_rate') ?? 0);
            $discPer = (float)($val($row, 'discount_percentage') ?? 0);
            $kg = (float) ($val($row, 'kg') ?? 0);
            if ($discPer < 0 || $discPer > 100) throw new \Exception("Invoice {$invoiceNo}: Discount must be between 0 and 100 percent.");

            $gross = ($kg > 0 ? $kg : $qty) * $price;
            if ($gross <= 0) throw new \Exception("Invoice {$invoiceNo}: Every line requires a positive quantity/weight and price.");
            $discAmount = 0;
            if ($discPer > 0) {
                $discAmount = $gross * ($discPer / 100);
            }

            $net = $gross - $discAmount;
            $taxAmount = $net * ($taxRateVal / 100);
            $totalLine = $net + $taxAmount;

            $subTotal += $gross;
            $discountTotal += $discAmount;
            $taxTotal += $taxAmount;
            $grandTotal += $totalLine;
        }

        $totalBeforeTax = $subTotal - $discountTotal;
        $remainingAmount = $grandTotal - $paidAmount;
        if ($remainingAmount < 0) throw new \Exception("Invoice {$invoiceNo}: Paid amount cannot exceed the bill total.");
        app(BillService::class)->assertAccountingConfiguration($this->companyId, $discountTotal, $taxTotal);

        // Generate unique bill number
        $billUniqueNo = app(BillService::class)->nextBillUniqueNo($this->companyId, $date);
        $vendor = Vendor::find($vendorId);
        $dueDate = Carbon::parse($date)->addDays((int) ($vendor?->payment_terms_days ?? 30))->toDateString();

        // 6. Create Bill FIRST using create() for proper auto-increment handling
        $bill = Bill::create([
            'company_id' => $this->companyId,
            'accounting_period_id' => $period->id,
            'vendor_id' => $vendorId,
            'account_id' => $paymentAccountId,
            'currency_id' => $currencyId,
            'exchange_rate' => $exchangeRate,
            'unique_no' => $billUniqueNo,
            'invoice_no' => $invoiceNo,
            'date' => $date,
            'vendor_invoice_date' => $date,
            'posting_date' => $date,
            'due_date' => $dueDate,
            'ref_no' => $val($firstRow, 'ref_no'),
            'note' => $val($firstRow, 'note'),
            'sub_total' => $subTotal,
            'discount_total' => $discountTotal,
            'net_before_tax' => $totalBeforeTax,
            'tax' => $taxTotal,
            'grand_total' => $grandTotal,
            'base_currency_total' => round($grandTotal * $exchangeRate, 4),
            'total' => $grandTotal,
            'total_after_discount' => $grandTotal,
            'paid_amount' => $paidAmount,
            'remaining_amount' => $remainingAmount,
            'approval_status' => 'draft',
            'payment_status' => app(BillService::class)->paymentStatus($paidAmount, $remainingAmount),
            'status' => 1,
            'created_by' => $this->userId,
            'updated_by' => $this->userId,
        ]);

        $billId = $bill->id;

        // 7. Now create bill items with the actual bill_id
        foreach ($group as $row) {
            $itemDesc = $val($row, 'description');
            $itemName = $val($row, 'item');
            $qty = (float)($val($row, 'quantity') ?? 1);
            $kg = (float)($val($row, 'kg') ?? 0);
            $price = (float)($val($row, 'price') ?? 0);
            $taxRateVal = (float)($val($row, 'tax_rate') ?? 0);
            $discPer = (float)($val($row, 'discount_percentage') ?? 0);

            $gross = ($kg > 0 ? $kg : $qty) * $price;
            $discAmount = 0;
            if ($discPer > 0) {
                $discAmount = $gross * ($discPer / 100);
            }

            $net = $gross - $discAmount;
            $taxAmount = $net * ($taxRateVal / 100);
            $totalLine = $net + $taxAmount;

            // Account ID for Item (Expense Account)
            $accName = $val($row, 'account_name');
            if (!$accName) {
                throw new \Exception("Invoice {$invoiceNo}: Item account name is missing.");
            }
            $accId = $this->accounts[strtolower($accName)] ?? null;
            if (!$accId) {
                throw new \Exception("Invoice {$invoiceNo}: Chart of Account '{$accName}' not found.");
            }
            $taxId = $this->taxRates[(string)$taxRateVal] ?? null;
            $tax = $taxId ? TaxRate::find($taxId) : null;

            // Add Bill Item to batch
            $this->billItemsBatch[] = [
                'company_id' => $this->companyId,
                'bill_id' => $billId, // Use the ACTUAL bill ID
                'accounting_period_id' => $period->id,
                'unique_no' => $billUniqueNo,
                'item' => $itemName,
                'description' => $itemDesc,
                'quantity' => $qty,
                'kg' => $kg,
                'unit_of_measure' => $kg > 0 ? 'kg' : 'unit',
                'price' => $price,
                'discount' => $discPer,
                'account_id' => $accId,
                'tax_id' => $taxId,
                'tax_name_snapshot' => $tax?->name,
                'tax_rate_snapshot' => $taxRateVal,
                'line_subtotal' => $gross,
                'discount_amount' => $discAmount,
                'tax_amount' => $taxAmount,
                'total' => $totalLine,
                'created_by' => $this->userId,
                'updated_by' => $this->userId,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // Add Expense Transaction (Debit Expense)
            if ($accId) {
                $this->transactionsBatch[] = [
                    'company_id' => $this->companyId,
                    'account_id' => $accId,
                    'reference_id' => $billId,
                    'unique_no' => $billUniqueNo,
                    'invoice_no' => $invoiceNo,
                    'description' => $itemDesc ?: $itemName ?: 'Bill Item',
                    'date' => $date,
                    'type' => 'Bill',
                    'credit' => 0,
                    'debit' => round($gross * $exchangeRate, 4),
                    'vendor_id' => $vendorId,
                    'payable_id' => $vendorId,
                    'payable_type' => 'vendor',
                    'accounting_period_id' => $period->id,
                    'status' => 0,
                    'created_by' => $this->userId,
                    'updated_by' => $this->userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // 8. Create summary transactions
        // Discount (Credit)
        if ($discountTotal > 0 && $this->purchaseDiscountAccountId) {
            $this->transactionsBatch[] = [
                'company_id' => $this->companyId,
                'account_id' => $this->purchaseDiscountAccountId,
                'reference_id' => $billId,
                'unique_no' => $billUniqueNo,
                'invoice_no' => $invoiceNo,
                'description' => 'Purchase Discount',
                'date' => $date,
                'type' => 'Bill',
                'credit' => round($discountTotal * $exchangeRate, 4),
                'debit' => 0,
                'vendor_id' => $vendorId,
                'payable_id' => $vendorId,
                'payable_type' => 'vendor',
                'accounting_period_id' => $period->id,
                'status' => 0,
                'created_by' => $this->userId,
                'updated_by' => $this->userId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Tax (Debit Input Tax)
        if ($taxTotal > 0 && $this->purchaseTaxAccountId) {
            $this->transactionsBatch[] = [
                'company_id' => $this->companyId,
                'account_id' => $this->purchaseTaxAccountId,
                'reference_id' => $billId,
                'unique_no' => $billUniqueNo,
                'invoice_no' => $invoiceNo,
                'description' => 'Input Tax',
                'date' => $date,
                'type' => 'Bill',
                'credit' => 0,
                'debit' => round($taxTotal * $exchangeRate, 4),
                'vendor_id' => $vendorId,
                'payable_id' => $vendorId,
                'payable_type' => 'vendor',
                'accounting_period_id' => $period->id,
                'status' => 0,
                'created_by' => $this->userId,
                'updated_by' => $this->userId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // AP (Credit)
        $this->transactionsBatch[] = [
            'company_id' => $this->companyId,
            'account_id' => $this->accountsPayableId,
            'reference_id' => $billId,
            'unique_no' => $billUniqueNo,
            'invoice_no' => $invoiceNo,
            'description' => 'Accounts Payable',
            'date' => $date,
            'type' => 'Bill',
            'credit' => round($grandTotal * $exchangeRate, 4),
            'debit' => 0,
            'vendor_id' => $vendorId,
            'payable_id' => $vendorId,
            'payable_type' => 'vendor',
            'accounting_period_id' => $period->id,
            'status' => 0,
            'created_by' => $this->userId,
            'updated_by' => $this->userId,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        // Payment
        if ($paidAmount > 0 && $paymentAccountId) {
            $payUnique = 'PAY-' . $billUniqueNo;

            // Cr Bank
            $this->transactionsBatch[] = [
                'company_id' => $this->companyId,
                'account_id' => $paymentAccountId,
                'reference_id' => $billId,
                'unique_no' => $payUnique,
                'invoice_no' => $invoiceNo,
                'description' => 'Payment Sent',
                'date' => $date,
                'type' => 'Bill',
                'credit' => round($paidAmount * $exchangeRate, 4),
                'debit' => 0,
                'vendor_id' => $vendorId,
                'payable_id' => $vendorId,
                'payable_type' => 'vendor',
                'accounting_period_id' => $period->id,
                'status' => 0,
                'created_by' => $this->userId,
                'updated_by' => $this->userId,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // Dr AP
            $this->transactionsBatch[] = [
                'company_id' => $this->companyId,
                'account_id' => $this->accountsPayableId,
                'reference_id' => $billId,
                'unique_no' => $payUnique,
                'invoice_no' => $invoiceNo,
                'description' => 'Payment Sent',
                'date' => $date,
                'type' => 'Bill',
                'credit' => 0,
                'debit' => round($paidAmount * $exchangeRate, 4),
                'vendor_id' => $vendorId,
                'payable_id' => $vendorId,
                'payable_type' => 'vendor',
                'accounting_period_id' => $period->id,
                'status' => 0,
                'created_by' => $this->userId,
                'updated_by' => $this->userId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $this->billsForVersioning[] = $billId;
        $this->processedCount++;
    }

    protected function flushBatches()
    {
        // Note: Bills are inserted individually, so we only flush items and transactions

        if (!empty($this->billItemsBatch)) {
            // Insert bill items one by one to handle auto-increment properly
            foreach ($this->billItemsBatch as $item) {
                BillItem::create($item);
            }
            $this->billItemsBatch = [];
        }

        if (!empty($this->transactionsBatch)) {
            // Insert transactions one by one to handle auto-increment properly
            foreach ($this->transactionsBatch as $transaction) {
                Transaction::create($transaction);
            }
            $this->transactionsBatch = [];
        }

        foreach (array_unique($this->billsForVersioning) as $billId) {
            $bill = Bill::with('billItems')->find($billId);
            if ($bill && ! $bill->versions()->exists()) app(BillService::class)->captureVersion($bill, 'imported');
        }
        $this->billsForVersioning = [];
    }

    protected function createVendor($row, $val)
    {
        $name = trim($val($row, 'vendor_name'));
        if (!$name) {
            return null;
        }

        $email = $val($row, 'vendor_email');

        // Generate fallback email if missing
        if (!$email) {
            $email = strtolower(str_replace(' ', '_', $name)) . '@example.com';
        }

        $existing = Vendor::where('company_id', $this->companyId)
            ->where('email', $email)
            ->first();

        if ($existing) {
            $this->vendors[strtolower($name)] = $existing->id;
            return $existing->id;
        }

        $vendor = Vendor::create([
            'company_id' => $this->companyId,
            'name' => $name,
            'email' => $email,
            'phone' => $val($row, 'vendor_phone'),
            'address' => $val($row, 'vendor_address'),
        ]);

        $id = $vendor->id;

        $this->vendors[strtolower($name)] = $id;
        return $id;
    }

    protected function parseDate($dateStr)
    {
        if (empty($dateStr)) {
            return now()->format('Y-m-d');
        }

        try {
            $dateStr = trim($dateStr);

            // Excel numeric date
            if (is_numeric($dateStr) && (float)$dateStr > 1000) {
                return Carbon::createFromTimestamp(
                    ((float)$dateStr - 25569) * 86400
                )->format('Y-m-d');
            }

            // Common formats to try explicitly first
            $formats = [
                'j/n/Y',   // 4/4/2026
                'j-n-Y',   // 4-4-2026
                'j.n.Y',   // 4.4.2026
                'd/m/Y',   // 04/04/2026
                'd-m-Y',   // 04-04-2026
                'd.m.Y',   // 04.04.2026
                'Y-m-d',   // 2026-04-04 (ISO)
                'm/d/Y',
                'm-d-Y',
                'm.d.Y',
                'd/m/y',
                'm/d/y',
                'd-m-y',
                'm-d-y',
                'd.m.y',
                'm.d.y',
                'j/n/y',
                'j-n-y',
                'd-M-Y',
                'd-M-y',
                'd/M/Y',
                'd/M/y',
                'd.M.Y',
                'd.M.y',
                'j F, Y',
                'd F, Y',
            ];

            foreach ($formats as $format) {
                try {
                    $date = Carbon::createFromFormat($format, $dateStr);
                    if ($date && $date->format($format) === $dateStr) {
                        return $date->format('Y-m-d');
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            // Fallback to general parsing
            return Carbon::parse($dateStr)->format('Y-m-d');
        } catch (\Exception $e) {
            return now()->format('Y-m-d');
        }
    }

    protected function initializeCache()
    {
        if (!$this->periods) {
            $this->periods = AccountingPeriod::where('company_id', $this->companyId)->active()->get();
        }
        if (!$this->vendors) {
            $this->vendors = Vendor::where('company_id', $this->companyId)->pluck('id', 'name')->mapWithKeys(function ($item, $key) {
                return [strtolower($key) => $item];
            })->toArray();
        }
        if (!$this->currencies) {
            $this->currencies = Currency::pluck('id', 'code')->toArray();
        }
        if (!$this->accounts) {
            $accountsList = ChartOfAccount::forCompany($this->companyId)->get();
            $this->accounts = [];
            foreach ($accountsList as $acc) {
                $this->accounts[strtolower(trim($acc->name))] = $acc->id;
                if ($acc->account_number) {
                    $this->accounts[strtolower(trim($acc->account_number))] = $acc->id;
                }
            }
        }
        if (!$this->taxRates) {
            $this->taxRates = TaxRate::where('company_id', $this->companyId)->pluck('id', 'tax_rate')->mapWithKeys(function ($item, $key) {
                return [(string)(float)$key => $item];
            })->toArray();
        }

        $this->purchaseDiscountAccountId = ChartOfAccount::getPurchaseDiscountAccountId($this->companyId);
        $this->purchaseTaxAccountId = ChartOfAccount::getPurchaseTaxAccountId($this->companyId);
        $this->accountsPayableId = ChartOfAccount::getAccountsPayableId($this->companyId);
    }

    protected function findPeriod($dateStr)
    {
        $date = Carbon::parse($dateStr)->format('Y-m-d');

        foreach ($this->periods as $period) {
            if ($period->company_id != $this->companyId) {
                continue;
            }

            $startDate = Carbon::parse($period->start_date)->format('Y-m-d');
            $endDate = Carbon::parse($period->end_date)->format('Y-m-d');

            if ($date >= $startDate && $date <= $endDate) {
                /** @var \App\Models\AccountingPeriod $period */
                if ($period->is_locked && !$period->allowedUsers()->where('user_id', $this->userId)->exists()) {
                    return null;
                }
                return $period;
            }
        }
        return null;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getProcessedCount()
    {
        return $this->processedCount;
    }

    public function getSkippedCount()
    {
        return $this->skippedCount;
    }
}
