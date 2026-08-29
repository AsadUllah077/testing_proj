<?php

namespace App\Imports;

use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\TaxRate;
use App\Models\Transaction;
use App\Services\SaleService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SaleImport
{
    protected $companyId;
    protected $userId;

    // Cache
    protected $periods;
    protected $customers; // key: lower(name) -> id
    protected $currencies; // key: code -> id
    protected $accounts; // key: lower(name) -> id
    protected $taxRates; // key: rate(float) -> id
    protected $taxRateNames; // key: rate(float) -> historical display name

    // Config
    protected $salesDiscountAccountId;
    protected $salesTaxPayableId;
    protected $accountsReceivableId;

    // Buffers
    protected $saleItemsBatch = [];
    protected $transactionsBatch = [];
    protected $salesForVersioning = [];

    // State
    protected $errors = [];
    protected $processedCount = 0;
    protected $skippedCount = 0;

    public function __construct()
    {
        $this->companyId = Auth::user()->company_id;
        $this->userId = Auth::id();
        set_time_limit(0);
    }

    public function fastImport($filepath)
    {
        $this->initializeCache();
        $this->errors = [];
        $this->processedCount = 0;
        $this->skippedCount = 0;

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

        // Normalize Header: trimmed, lowercase, spaces to underscores
        $headerMap = array_flip(array_map(function ($col) {
            $col = preg_replace('/^\xEF\xBB\xBF/', '', $col);
            return strtolower(str_replace(' ', '_', trim($col)));
        }, $header));

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
                if ($idxInvoice === null) {
                    break;
                }

                $invoiceNo = isset($row[$idxInvoice]) ? trim($row[$idxInvoice]) : null;
                if (!$invoiceNo) continue;

                // Check Group Change
                if ($currentInvoiceNo !== null && $invoiceNo !== $currentInvoiceNo) {
                    $this->processInvoiceGroup($currentInvoiceNo, $currentGroup, $headerMap);
                    $currentGroup = [];

                    // Flush DB if needed
                    if (count($this->saleItemsBatch) >= $batchSize) {
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

            $this->flushBatches(); // Remaining items and transactions

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
        $this->errors = [];
        $this->processedCount = 0;
        $this->skippedCount = 0;

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
            Log::error('Sale Import Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            DB::rollBack();

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

        if (Sale::where('company_id', $this->companyId)->where('invoice_no', $invoiceNo)->exists()) {
            $this->skippedCount++;
            $this->errors[] = "Invoice {$invoiceNo}: Invoice number already exists for this company.";
            return;
        }

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

        // 1. Customer
        $customerName = $val($firstRow, 'customer_name');
        if (!$customerName) {
            $this->skippedCount++;
            $this->errors[] = "Invoice {$invoiceNo}: Customer name is missing.";
            return;
        }

        $lowerName = strtolower($customerName);
        $customerId = $this->customers[$lowerName] ?? null;
        if (!$customerId) {
            $customerId = $this->createCustomer($firstRow, $val);
        }
        if (!$customerId) {
            $this->skippedCount++;
            $this->errors[] = "Invoice {$invoiceNo}: Could not create/find customer '{$customerName}'.";
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
        $payAccName = $val($firstRow, 'payment_account') ?: $val($firstRow, 'payment_account_name');
        $paymentAccountId = null;
        if ($payAccName) {
            $paymentAccountId = $this->accounts[strtolower($payAccName)] ?? null;
            if (!$paymentAccountId) {
                throw new \Exception("Invoice {$invoiceNo}: Chart of Account '{$payAccName}' not found.");
            }
            $paymentAccount = ChartOfAccount::find($paymentAccountId);
            if (!$paymentAccount || $paymentAccount->type !== 'Account' || $paymentAccount->sub_type !== 'BANK') {
                throw new \Exception("Invoice {$invoiceNo}: Payment account must be a bank/cash account.");
            }
        }
        $paidAmount = (float)($val($firstRow, 'paid_amount') ?? 0);
        $exchangeRate = (float)($val($firstRow, 'exchange_rate') ?? 1);
        if ($exchangeRate <= 0) {
            throw new \Exception("Invoice {$invoiceNo}: Exchange rate must be greater than zero.");
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
            if ($discPer < 0 || $discPer > 100) {
                throw new \Exception("Invoice {$invoiceNo}: Discount percentage must be between 0 and 100.");
            }

            $kg = (float)($val($row, 'kg') ?? 0);
            $gross = ($kg > 0 ? $kg : $qty) * $price;
            if ($gross <= 0) {
                throw new \Exception("Invoice {$invoiceNo}: Every line must have a positive quantity/weight and price.");
            }
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
        if ($remainingAmount < 0) {
            throw new \Exception("Invoice {$invoiceNo}: Paid amount cannot exceed the invoice total.");
        }
        if ($paidAmount > 0 && !$paymentAccountId) {
            throw new \Exception("Invoice {$invoiceNo}: A bank payment account is required when paid amount is greater than zero.");
        }
        app(SaleService::class)->assertAccountingConfiguration($this->companyId, $discountTotal, $taxTotal);

        // Generate unique sale number
        $saleUniqueNo = app(SaleService::class)->nextInvoiceUniqueNo($this->companyId, $date);
        $customer = Customer::find($customerId);
        $dueDate = Carbon::parse($date)->addDays((int) ($customer?->payment_terms_days ?? 30))->format('Y-m-d');
        $currentExposure = (float) Sale::where('customer_id', $customerId)->where('approval_status', 'approved')->sum('remaining_amount');
        $creditLimitExceeded = (float) ($customer?->credit_limit ?? 0) > 0
            && ($currentExposure + $grandTotal) > (float) $customer->credit_limit;

        // 6. Create Sale FIRST using create() for proper auto-increment handling
        $sale = Sale::create([
            'company_id' => $this->companyId,
            'accounting_period_id' => $period->id,
            'customer_id' => $customerId,
            'account_id' => $paymentAccountId,
            'currency_id' => $currencyId,
            'exchange_rate' => $exchangeRate,
            'unique_no' => $saleUniqueNo,
            'invoice_no' => $invoiceNo,
            'date' => $date,
            'due_date' => $dueDate,
            'ref_no' => $val($firstRow, 'ref_no'),
            'note' => $val($firstRow, 'note'),
            'sub_total' => $subTotal,
            'discount_total' => $discountTotal,
            'net_before_tax' => $totalBeforeTax,
            'tax' => $taxTotal,
            'total' => $grandTotal,
            'grand_total' => $grandTotal,
            'base_currency_total' => round($grandTotal * $exchangeRate, 4),
            'total_after_discount' => $grandTotal,
            'paid_amount' => $paidAmount,
            'remaining_amount' => $remainingAmount,
            'approval_status' => 'draft',
            'payment_status' => app(SaleService::class)->paymentStatus($paidAmount, $remainingAmount),
            'credit_limit_exceeded' => $creditLimitExceeded,
            'status' => 1,
            'created_by' => $this->userId,
            'updated_by' => $this->userId,
        ]);

        $saleId = $sale->id;

        // 7. Now create sale items with the actual sale_id
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

            // Account ID for Item
            $accName = $val($row, 'account_name');
            if (!$accName) {
                throw new \Exception("Invoice {$invoiceNo}: Item account name is missing.");
            }
            $accId = $this->accounts[strtolower($accName)] ?? null;
            if (!$accId) {
                throw new \Exception("Invoice {$invoiceNo}: Chart of Account '{$accName}' not found.");
            }
            $taxId = $this->taxRates[(string)$taxRateVal] ?? null;

            // Add Sale Item to batch
            $this->saleItemsBatch[] = [
                'company_id' => $this->companyId,
                'sale_id' => $saleId, // Use the ACTUAL sale ID
                'accounting_period_id' => $period->id,
                'unique_no' => $saleUniqueNo,
                'item' => $itemName,
                'description' => $itemDesc,
                'quantity' => $qty,
                'kg' => $kg,
                'unit_of_measure' => $kg > 0 ? 'kg' : 'unit',
                'price' => $price,
                'discount' => $discPer,
                'account_id' => $accId,
                'tax_id' => $taxId,
                'tax_name_snapshot' => $taxId ? ($this->taxRateNames[(string)$taxRateVal] ?? 'Imported Tax') : null,
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

            // Add Revenue Transaction
            if ($accId) {
                $this->transactionsBatch[] = [
                    'company_id' => $this->companyId,
                    'account_id' => $accId,
                    'reference_id' => $saleId,
                    'unique_no' => $saleUniqueNo,
                    'invoice_no' => $invoiceNo,
                    'description' => $itemDesc ?: $itemName ?: 'Sale Item',
                    'date' => $date,
                    'type' => 'Sale',
                    'credit' => round($gross * $exchangeRate, 4),
                    'debit' => 0,
                    'customer_id' => $customerId,
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
        // Discount
        if ($discountTotal > 0 && $this->salesDiscountAccountId) {
            $this->transactionsBatch[] = [
                'company_id' => $this->companyId,
                'account_id' => $this->salesDiscountAccountId,
                'reference_id' => $saleId,
                'unique_no' => $saleUniqueNo,
                'invoice_no' => $invoiceNo,
                'description' => 'Sales Discount',
                'date' => $date,
                'type' => 'Sale',
                'credit' => 0,
                'debit' => round($discountTotal * $exchangeRate, 4),
                'customer_id' => $customerId,
                'accounting_period_id' => $period->id,
                'status' => 0,
                'created_by' => $this->userId,
                'updated_by' => $this->userId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Tax
        if ($taxTotal > 0 && $this->salesTaxPayableId) {
            $this->transactionsBatch[] = [
                'company_id' => $this->companyId,
                'account_id' => $this->salesTaxPayableId,
                'reference_id' => $saleId,
                'unique_no' => $saleUniqueNo,
                'invoice_no' => $invoiceNo,
                'description' => 'Sales Tax',
                'date' => $date,
                'type' => 'Sale',
                'credit' => round($taxTotal * $exchangeRate, 4),
                'debit' => 0,
                'customer_id' => $customerId,
                'accounting_period_id' => $period->id,
                'status' => 0,
                'created_by' => $this->userId,
                'updated_by' => $this->userId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // AR
        $this->transactionsBatch[] = [
            'company_id' => $this->companyId,
            'account_id' => $this->accountsReceivableId,
            'reference_id' => $saleId,
            'unique_no' => $saleUniqueNo,
            'invoice_no' => $invoiceNo,
            'description' => 'Accounts Receivable',
            'date' => $date,
            'type' => 'Sale',
            'credit' => 0,
            'debit' => round($grandTotal * $exchangeRate, 4),
            'customer_id' => $customerId,
            'accounting_period_id' => $period->id,
            'status' => 0,
            'created_by' => $this->userId,
            'updated_by' => $this->userId,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        // Payment
        if ($paidAmount > 0 && $paymentAccountId) {
            $payUnique = 'PAY-' . $saleUniqueNo;

            // Dr Bank
            $this->transactionsBatch[] = [
                'company_id' => $this->companyId,
                'account_id' => $paymentAccountId,
                'reference_id' => $saleId,
                'unique_no' => $payUnique,
                'invoice_no' => $invoiceNo,
                'description' => 'Payment Received',
                'date' => $date,
                'type' => 'Sale',
                'credit' => 0,
                'debit' => round($paidAmount * $exchangeRate, 4),
                'customer_id' => $customerId,
                'accounting_period_id' => $period->id,
                'status' => 0,
                'created_by' => $this->userId,
                'updated_by' => $this->userId,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // Cr AR
            $this->transactionsBatch[] = [
                'company_id' => $this->companyId,
                'account_id' => $this->accountsReceivableId,
                'reference_id' => $saleId,
                'unique_no' => $payUnique,
                'invoice_no' => $invoiceNo,
                'description' => 'Payment Received',
                'date' => $date,
                'type' => 'Sale',
                'credit' => round($paidAmount * $exchangeRate, 4),
                'debit' => 0,
                'customer_id' => $customerId,
                'accounting_period_id' => $period->id,
                'status' => 0,
                'created_by' => $this->userId,
                'updated_by' => $this->userId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $this->salesForVersioning[] = $saleId;
        $this->processedCount++;
    }

    protected function flushBatches()
    {
        // Note: Sales are inserted individually, so we only flush items and transactions

        if (!empty($this->saleItemsBatch)) {
            SaleItem::insert($this->saleItemsBatch);
            $this->saleItemsBatch = [];
        }

        if (!empty($this->transactionsBatch)) {
            Transaction::insert($this->transactionsBatch);
            $this->transactionsBatch = [];
        }

        foreach ($this->salesForVersioning as $saleId) {
            app(SaleService::class)->captureVersion(Sale::with(['saleItems', 'customer'])->findOrFail($saleId), 'imported');
        }
        $this->salesForVersioning = [];
    }

    protected function createCustomer($row, $val)
    {
        $name = trim($val($row, 'customer_name'));
        if (!$name) {
            return null;
        }

        $email = $val($row, 'customer_email');

        // Generate fallback email if missing
        if (!$email) {
            $email = strtolower(str_replace(' ', '_', $name)) . '@example.com';
        }

        $existing = Customer::where('company_id', $this->companyId)
            ->where('email', $email)
            ->first();

        if ($existing) {
            $this->customers[strtolower($name)] = $existing->id;
            return $existing->id;
        }

        $customer = Customer::create([
            'company_id' => $this->companyId,
            'name' => $name,
            'email' => $email,
            'phone' => $val($row, 'customer_phone'),
            'address' => $val($row, 'customer_address'),
        ]);

        $id = $customer->id;

        $this->customers[strtolower($name)] = $id;
        return $id;
    }

    // protected function parseDate($dateStr)
    // {
    //     if (!$dateStr) return now()->format('Y-m-d');
    //     try {
    //         return Carbon::parse(str_replace('/', '-', $dateStr))->format('Y-m-d');
    //     } catch (\Exception $e) {
    //         return now()->format('Y-m-d');
    //     }
    // }

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
        if (!$this->customers) {
            $this->customers = Customer::where('company_id', $this->companyId)->pluck('id', 'name')->mapWithKeys(function ($item, $key) {
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
            $taxes = TaxRate::where('company_id', $this->companyId)->get(['id', 'name', 'tax_rate']);
            $this->taxRates = $taxes->mapWithKeys(fn ($tax) => [(string)(float)$tax->tax_rate => $tax->id])->toArray();
            $this->taxRateNames = $taxes->mapWithKeys(fn ($tax) => [(string)(float)$tax->tax_rate => $tax->name])->toArray();
        }

        $this->salesDiscountAccountId = ChartOfAccount::getSalesDiscountAccountId($this->companyId);
        $this->salesTaxPayableId = ChartOfAccount::getSalesTaxPayableId($this->companyId);
        $this->accountsReceivableId = ChartOfAccount::getAccountsReceivableId($this->companyId);
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
