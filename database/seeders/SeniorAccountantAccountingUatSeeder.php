<?php

namespace Database\Seeders;

use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\TaxPeriod;
use App\Services\TaxService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeniorAccountantAccountingUatSeeder extends Seeder
{
    private const PREFIX = 'CODX-UAT-20260801';

    private int $companyId;
    private int $userId;
    private int $periodId;
    private int $currencyId;
    private int $customerId;
    private int $vendorId;
    private int $bankId;
    private int $cashId;
    private int $arId;
    private int $apId;
    private int $salesId;
    private int $salesDiscountId;
    private int $purchaseDiscountId;
    private int $salesTaxId;
    private int $purchaseTaxId;
    private int $officeExpenseId;
    private int $rentId;
    private int $wagesId;
    private int $wagesPayableId;
    private int $bankFeesId;
    private int $interestIncomeId;
    private int $fixedAssetId;
    private int $depreciationExpenseId;
    private int $openingEquityId;
    private int $shareCapitalId;

    public function run(): void
    {
        $this->resolveContext();

        DB::transaction(function () {
            $this->openingBalanceConversion();
            $saleOne = $this->sale('SALE-001', '2026-09-02', 100000, 0, 17000, 117000);
            $saleTwo = $this->sale('SALE-002', '2026-09-03', 200000, 20000, 30600, 210600);
            $this->customerReceipt('RCPT-FULL', $saleOne, '2026-09-04', 117000);
            $this->customerReceipt('RCPT-PART', $saleTwo, '2026-09-05', 100000);
            $this->creditNote($saleTwo, '2026-09-06', 50000, 8500, 58500);

            $billOne = $this->bill('BILL-001', '2026-09-07', 30000, 0, 1500, 31500);
            $billTwo = $this->bill('BILL-002', '2026-09-08', 150000, 15000, 6750, 141750);
            $this->vendorPayment('VPAY-FULL', $billOne, '2026-09-09', 31500);
            $this->vendorPayment('VPAY-PART', $billTwo, '2026-09-10', 70000);
            $this->debitNote($billTwo, '2026-09-11', 20000, 1000, 21000);

            $this->bankSpendReceiveTransfer();
            $this->manualJournals();
            $this->recurringTemplates();
            $this->budgetAndFixedAsset();
            $this->bankStatementLines();
        });

        $this->taxSettlementAndPayment();

        $this->command?->info('Senior accountant UAT entries are ready. Prefix: ' . self::PREFIX);
    }

    private function resolveContext(): void
    {
        $company = Company::query()->orderBy('id')->first();
        if (! $company) {
            throw new \RuntimeException('No company found.');
        }

        $this->companyId = (int) $company->id;
        $this->userId = (int) (DB::table('users')->where('company_id', $this->companyId)->value('id') ?? DB::table('users')->value('id') ?? 1);
        $this->currencyId = (int) (DB::table('currencies')->where('code', 'PKR')->value('id') ?? DB::table('currencies')->value('id') ?? 1);
        $this->customerId = (int) DB::table('customers')->where('company_id', $this->companyId)->orderBy('id')->value('id');
        $this->vendorId = (int) DB::table('vendors')->where('company_id', $this->companyId)->orderBy('id')->value('id');

        if (! $this->customerId || ! $this->vendorId) {
            throw new \RuntimeException('Customer and vendor master data are required before seeding accounting UAT entries.');
        }

        $period = AccountingPeriod::query()
            ->where('company_id', $this->companyId)
            ->where('start_date', '<=', '2026-09-30')
            ->where('end_date', '>=', '2026-09-01')
            ->where('is_locked', false)
            ->first();

        if (! $period) {
            $period = AccountingPeriod::create([
                'company_id' => $this->companyId,
                'name' => 'FY - 2026-2027 UAT',
                'start_date' => '2026-07-01',
                'end_date' => '2027-06-30',
                'is_active' => true,
                'is_locked' => false,
                'created_by' => $this->userId,
                'updated_by' => $this->userId,
            ]);
        }

        $this->periodId = (int) $period->id;
        $this->bankId = $this->account('93', 'BANK');
        $this->cashId = $this->account('190', 'Cash');
        $this->arId = $this->account('610', 'ACCOUNTS_RECEIVABLE');
        $this->apId = $this->account('800', 'ACCOUNTS_PAYABLE');
        $this->salesId = $this->account('200', 'Sales');
        $this->salesDiscountId = $this->account('413', 'SALES_DISCOUNT');
        $this->purchaseDiscountId = $this->account('316', 'PURCHASE_DISCOUNT');
        $this->salesTaxId = ChartOfAccount::getSalesTaxPayableId($this->companyId) ?: $this->account('803', 'SALES_TAX');
        $this->purchaseTaxId = ChartOfAccount::getPurchaseTaxAccountId($this->companyId) ?: $this->account('152', 'PURCHASE_TAX');
        $this->officeExpenseId = $this->account('453', 'Office Expenses');
        $this->rentId = $this->account('469', 'Rent');
        $this->wagesId = $this->account('477', 'Wages');
        $this->wagesPayableId = $this->account('802', 'Wages Payable');
        $this->bankFeesId = $this->account('408', 'Bank Fees');
        $this->interestIncomeId = $this->account('270', 'Interest Income');
        $this->fixedAssetId = $this->account('1020', 'Fixed Assets');
        $this->depreciationExpenseId = $this->account('416', 'Depreciation');
        $this->openingEquityId = $this->account('399', 'Opening Balance Equity');
        $this->shareCapitalId = $this->account('3000', 'Share Capital');
    }

    private function account(string $number, string $fallbackName): int
    {
        $id = ChartOfAccount::withoutGlobalScopes()
            ->where('company_id', $this->companyId)
            ->where('account_number', $number)
            ->value('id');

        $id ??= ChartOfAccount::withoutGlobalScopes()
            ->where('company_id', $this->companyId)
            ->where(function ($query) use ($fallbackName) {
                $query->where('name', 'like', "%{$fallbackName}%")
                    ->orWhere('system_account_type', $fallbackName)
                    ->orWhere('account_type', $fallbackName);
            })
            ->orderBy('id')
            ->value('id');

        if (! $id) {
            throw new \RuntimeException("Required chart account missing: {$number} / {$fallbackName}");
        }

        return (int) $id;
    }

    private function openingBalanceConversion(): void
    {
        if (! Schema::hasTable('opening_balance_batches')) {
            return;
        }

        $reference = self::PREFIX . '-OB';
        $batch = DB::table('opening_balance_batches')
            ->where('company_id', $this->companyId)
            ->where('reference', $reference)
            ->first();

        if (! $batch) {
            $batchId = $this->insert('opening_balance_batches', [
                'company_id' => $this->companyId,
                'reference' => $reference,
                'source_system' => 'Senior Accountant UAT',
                'conversion_date' => '2026-07-01',
                'closing_date' => '2026-06-30',
                'fiscal_year' => '2026-2027',
                'status' => 'Posted',
                'notes' => 'UAT conversion/opening balance batch entered by senior accountant review.',
                'total_debit' => 1750000,
                'total_credit' => 1750000,
                'posted_at' => now(),
                'posted_by' => $this->userId,
                'created_by' => $this->userId,
                'updated_by' => $this->userId,
            ]);

            foreach ([
                [$this->bankId, 1200000, 0, 'Opening bank balance'],
                [$this->arId, 300000, 0, 'Opening receivables'],
                [$this->fixedAssetId, 250000, 0, 'Opening fixed assets'],
                [$this->apId, 0, 150000, 'Opening payables'],
                [$this->shareCapitalId, 0, 1000000, 'Opening share capital'],
                [$this->openingEquityId, 0, 600000, 'Opening retained conversion equity'],
            ] as $line) {
                $this->insert('opening_balance_lines', [
                    'opening_balance_batch_id' => $batchId,
                    'company_id' => $this->companyId,
                    'account_id' => $line[0],
                    'debit' => $line[1],
                    'credit' => $line[2],
                    'description' => $line[3],
                    'source_reference' => $reference,
                ]);
            }
        }

        $this->post(self::PREFIX . '-OB-JRN', '2026-07-01', 'Opening Balance Conversion', [
            [$this->bankId, 1200000, 0, 'Opening bank balance'],
            [$this->arId, 300000, 0, 'Opening receivables'],
            [$this->fixedAssetId, 250000, 0, 'Opening fixed assets'],
            [$this->apId, 0, 150000, 'Opening payables'],
            [$this->shareCapitalId, 0, 1000000, 'Opening share capital'],
            [$this->openingEquityId, 0, 600000, 'Opening retained conversion equity'],
        ]);
    }

    private function sale(string $suffix, string $date, float $gross, float $discount, float $tax, float $total): int
    {
        $unique = self::PREFIX . '-' . $suffix;
        $sale = DB::table('sales')->where('company_id', $this->companyId)->where('unique_no', $unique)->first();

        if (! $sale) {
            $saleId = $this->insert('sales', [
                'company_id' => $this->companyId,
                'accounting_period_id' => $this->periodId,
                'customer_id' => $this->customerId,
                'account_id' => $this->salesId,
                'currency_id' => $this->currencyId,
                'unique_no' => $unique,
                'invoice_no' => $unique,
                'date' => $date,
                'ref_no' => $unique,
                'note' => 'UAT customer invoice with senior accountant expected posting.',
                'sub_total' => $gross,
                'discount' => $discount,
                'tax' => $tax,
                'total_after_discount' => $gross - $discount,
                'total' => $total,
                'paid_amount' => 0,
                'remaining_amount' => $total,
                'status' => 1,
                'created_by' => $this->userId,
                'updated_by' => $this->userId,
            ]);
            $this->insert('sale_items', [
                'company_id' => $this->companyId,
                'accounting_period_id' => $this->periodId,
                'sale_id' => $saleId,
                'unique_no' => $unique,
                'manual_item' => 'Senior accountant UAT service revenue',
                'description' => 'Consulting services invoice',
                'quantity' => 1,
                'price' => $gross,
                'account_id' => $this->salesId,
                'tax_id' => DB::table('tax_rates')->where('company_id', $this->companyId)->where('name', 'like', '%17%')->value('id'),
                'total' => $gross,
                'created_by' => $this->userId,
                'updated_by' => $this->userId,
            ]);
        } else {
            $saleId = (int) $sale->id;
        }

        $lines = [[$this->arId, $total, 0, 'Invoice receivable']];
        if ($discount > 0) {
            $lines[] = [$this->salesDiscountId, $discount, 0, 'Sales discount'];
        }
        $lines[] = [$this->salesId, 0, $gross, 'Sales revenue'];
        $lines[] = [$this->salesTaxId, 0, $tax, 'Output tax'];
        $this->post($unique, $date, 'Sale', $lines, $saleId, $this->customerId, null, $unique);

        return $saleId;
    }

    private function customerReceipt(string $suffix, int $saleId, string $date, float $amount): void
    {
        $unique = self::PREFIX . '-' . $suffix;
        $payment = DB::table('customer_payments')->where('company_id', $this->companyId)->where('reference_no', $unique)->first();
        if (! $payment) {
            $paymentId = $this->insert('customer_payments', [
                'company_id' => $this->companyId,
                'customer_id' => $this->customerId,
                'account_id' => $this->bankId,
                'reference_no' => $unique,
                'payment_date' => $date,
                'total_amount' => $amount,
                'note' => 'UAT customer receipt allocated against invoice.',
                'created_by' => $this->userId,
            ]);
            $sale = DB::table('sales')->find($saleId);
            $this->insert('customer_payment_items', [
                'customer_payment_id' => $paymentId,
                'sale_id' => $saleId,
                'amount_to_apply' => $amount,
                'invoice_total' => $sale->total,
                'invoice_remaining' => max(0, $sale->total - $amount),
            ]);
        }

        $this->post($unique, $date, 'Customer Payment', [
            [$this->bankId, $amount, 0, 'Bank receipt from customer'],
            [$this->arId, 0, $amount, 'Clear customer receivable'],
        ], $saleId, $this->customerId);
    }

    private function creditNote(int $saleId, string $date, float $gross, float $tax, float $total): void
    {
        $unique = self::PREFIX . '-CRN-001';
        $note = DB::table('credit_notes')->where('company_id', $this->companyId)->where('unique_no', $unique)->first();
        if (! $note) {
            $noteId = $this->insert('credit_notes', [
                'company_id' => $this->companyId,
                'accounting_period_id' => $this->periodId,
                'customer_id' => $this->customerId,
                'account_id' => $this->salesId,
                'currency_id' => $this->currencyId,
                'sale_id' => $saleId,
                'sale_unique_no' => self::PREFIX . '-SALE-002',
                'unique_no' => $unique,
                'invoice_no' => $unique,
                'ref_no' => $unique,
                'date' => $date,
                'sub_total' => $gross,
                'discount' => 0,
                'tax' => $tax,
                'total_after_discount' => $total,
                'total' => $gross,
                'paid_amount' => 0,
                'allocated_amount' => 0,
                'remaining_amount' => $total,
                'status' => 1,
                'created_by' => $this->userId,
                'updated_by' => $this->userId,
            ]);
            $this->insert('credit_note_items', [
                'company_id' => $this->companyId,
                'accounting_period_id' => $this->periodId,
                'credit_note_id' => $noteId,
                'account_id' => $this->salesId,
                'tax_id' => DB::table('tax_rates')->where('company_id', $this->companyId)->where('name', 'like', '%17%')->value('id'),
                'unique_no' => $unique,
                'manual_item' => 'UAT sales return',
                'description' => 'Credit for returned service scope',
                'quantity' => 1,
                'price' => $gross,
                'total' => $total,
                'created_by' => $this->userId,
                'updated_by' => $this->userId,
            ]);
        }

        $this->post($unique, $date, 'Credit Note', [
            [$this->salesId, $gross, 0, 'Reverse sales revenue'],
            [$this->salesTaxId, $tax, 0, 'Reverse output tax'],
            [$this->arId, 0, $total, 'Reduce customer receivable'],
        ], $saleId, $this->customerId);
    }

    private function bill(string $suffix, string $date, float $gross, float $discount, float $tax, float $total): int
    {
        $unique = self::PREFIX . '-' . $suffix;
        $bill = DB::table('bills')->where('company_id', $this->companyId)->where('unique_no', $unique)->first();
        if (! $bill) {
            $billId = $this->insert('bills', [
                'company_id' => $this->companyId,
                'accounting_period_id' => $this->periodId,
                'vendor_id' => $this->vendorId,
                'account_id' => $this->officeExpenseId,
                'currency_id' => $this->currencyId,
                'unique_no' => $unique,
                'invoice_no' => $unique,
                'date' => $date,
                'ref_no' => $unique,
                'note' => 'UAT vendor bill with expected purchase tax posting.',
                'sub_total' => $gross,
                'discount' => $discount,
                'tax' => $tax,
                'total_after_discount' => $gross - $discount,
                'total' => $total,
                'paid_amount' => 0,
                'remaining_amount' => $total,
                'status' => 1,
                'created_by' => $this->userId,
                'updated_by' => $this->userId,
            ]);
            $this->insert('bill_items', [
                'company_id' => $this->companyId,
                'accounting_period_id' => $this->periodId,
                'bill_id' => $billId,
                'unique_no' => $unique,
                'manual_item' => 'Senior accountant UAT vendor cost',
                'description' => 'Office operating cost bill',
                'quantity' => 1,
                'price' => $gross,
                'account_id' => $this->officeExpenseId,
                'tax_id' => DB::table('tax_rates')->where('company_id', $this->companyId)->where('name', 'like', '%5%')->value('id'),
                'total' => $gross,
                'created_by' => $this->userId,
                'updated_by' => $this->userId,
            ]);
        } else {
            $billId = (int) $bill->id;
        }

        $lines = [[$this->officeExpenseId, $gross, 0, 'Expense before supplier discount']];
        $lines[] = [$this->purchaseTaxId, $tax, 0, 'Input tax'];
        if ($discount > 0) {
            $lines[] = [$this->purchaseDiscountId, 0, $discount, 'Purchase discount'];
        }
        $lines[] = [$this->apId, 0, $total, 'Vendor payable'];
        $this->post($unique, $date, 'Bill', $lines, $billId, null, $this->vendorId, $unique);

        return $billId;
    }

    private function vendorPayment(string $suffix, int $billId, string $date, float $amount): void
    {
        $unique = self::PREFIX . '-' . $suffix;
        $run = DB::table('vendor_payment_runs')->where('company_id', $this->companyId)->where('run_reference', $unique)->first();
        if (! $run) {
            $runId = $this->insert('vendor_payment_runs', [
                'company_id' => $this->companyId,
                'run_reference' => $unique,
                'payment_date' => $date,
                'status' => 'processed',
                'total_amount' => $amount,
                'notes' => 'UAT vendor payment run allocated against bill.',
                'created_by' => $this->userId,
                'confirmed_by' => $this->userId,
                'confirmed_at' => now(),
            ]);
            $bill = DB::table('bills')->find($billId);
            $this->insert('vendor_payment_run_items', [
                'vendor_payment_run_id' => $runId,
                'bill_id' => $billId,
                'vendor_id' => $this->vendorId,
                'amount_to_pay' => $amount,
                'bill_total' => $bill->total,
                'bill_remaining' => max(0, $bill->total - $amount),
            ]);
        }

        $this->post($unique, $date, 'Vendor Payment', [
            [$this->apId, $amount, 0, 'Clear vendor payable'],
            [$this->bankId, 0, $amount, 'Bank payment to vendor'],
        ], $billId, null, $this->vendorId);
    }

    private function debitNote(int $billId, string $date, float $gross, float $tax, float $total): void
    {
        $unique = self::PREFIX . '-DRN-001';
        $note = DB::table('debit_notes')->where('company_id', $this->companyId)->where('unique_no', $unique)->first();
        if (! $note) {
            $noteId = $this->insert('debit_notes', [
                'company_id' => $this->companyId,
                'accounting_period_id' => $this->periodId,
                'vendor_id' => $this->vendorId,
                'account_id' => $this->officeExpenseId,
                'bill_id' => $billId,
                'unique_no' => $unique,
                'ref_no' => $unique,
                'bill_unique_no' => self::PREFIX . '-BILL-002',
                'date' => $date,
                'note' => 'UAT debit note for supplier return/allowance.',
                'sub_total' => $gross,
                'tax' => $tax,
                'discount' => 0,
                'total' => $total,
                'allocated_amount' => 0,
                'refunded_amount' => 0,
                'remaining_amount' => $total,
                'status' => 1,
                'created_by' => $this->userId,
                'updated_by' => $this->userId,
            ]);
            $this->insert('debit_note_items', [
                'debit_note_id' => $noteId,
                'account_id' => $this->officeExpenseId,
                'tax_id' => DB::table('tax_rates')->where('company_id', $this->companyId)->where('name', 'like', '%5%')->value('id'),
                'description' => 'Supplier allowance against bill',
                'quantity' => 1,
                'price' => $gross,
                'discount_percentage' => 0,
                'sub_total' => $gross,
                'tax_amount' => $tax,
                'total' => $total,
            ]);
        }

        $this->post($unique, $date, 'Debit Note', [
            [$this->apId, $total, 0, 'Reduce vendor payable'],
            [$this->officeExpenseId, 0, $gross, 'Reverse purchase expense'],
            [$this->purchaseTaxId, 0, $tax, 'Reverse input tax'],
        ], $billId, null, $this->vendorId);
    }

    private function bankSpendReceiveTransfer(): void
    {
        $this->post(self::PREFIX . '-BANK-SPEND', '2026-09-12', 'Spend Money', [
            [$this->bankFeesId, 2500, 0, 'Bank service charges'],
            [$this->bankId, 0, 2500, 'Bank withdrawal'],
        ]);

        $this->post(self::PREFIX . '-BANK-RECEIVE', '2026-09-13', 'Receive Money', [
            [$this->bankId, 12000, 0, 'Bank interest received'],
            [$this->interestIncomeId, 0, 12000, 'Interest income'],
        ]);

        $this->post(self::PREFIX . '-BANK-TRANSFER', '2026-09-14', 'Bank Transfer', [
            [$this->cashId, 50000, 0, 'Cash funded from bank'],
            [$this->bankId, 0, 50000, 'Bank withdrawal to cash'],
        ]);
    }

    private function manualJournals(): void
    {
        $this->post(self::PREFIX . '-JRN-SALARY-ACCRUAL', '2026-09-25', 'Manual Journal', [
            [$this->wagesId, 80000, 0, 'Accrue monthly payroll cost'],
            [$this->wagesPayableId, 0, 80000, 'Salary payable accrual'],
        ]);

        $this->post(self::PREFIX . '-JRN-SALARY-PAY', '2026-09-28', 'Manual Journal', [
            [$this->wagesPayableId, 80000, 0, 'Clear salary payable'],
            [$this->bankId, 0, 80000, 'Salary paid from bank'],
        ]);
    }

    private function recurringTemplates(): void
    {
        $this->recurring('recurring_invoices', 'recurring_invoice_items', 'recurring_invoice_id', [
            'company_id' => $this->companyId,
            'customer_id' => $this->customerId,
            'account_id' => $this->salesId,
            'accounting_period_id' => $this->periodId,
            'title' => self::PREFIX . ' Monthly retainer invoice',
            'description' => 'UAT recurring sales invoice with line discount and GST.',
            'frequency' => 'monthly',
            'interval' => 1,
            'start_date' => '2026-10-01',
            'next_run_date' => '2026-10-01',
            'status' => 'active',
            'sub_total' => 90000,
            'discount' => 9000,
            'tax' => 13770,
            'total' => 94770,
            'payment_terms_days' => 30,
            'note' => 'Generate monthly for service retainer.',
            'invoices_generated' => 0,
            'max_occurrences' => 12,
            'created_by' => $this->userId,
            'updated_by' => $this->userId,
        ], [
            'account_id' => $this->salesId,
            'tax_id' => DB::table('tax_rates')->where('company_id', $this->companyId)->where('name', 'like', '%17%')->value('id'),
            'description' => 'Monthly accounting advisory retainer',
            'quantity' => 1,
            'price' => 90000,
            'discount_percentage' => 10,
            'sub_total' => 90000,
            'tax_amount' => 13770,
            'total' => 94770,
        ]);

        $this->recurring('recurring_bills', 'recurring_bill_items', 'recurring_bill_id', [
            'company_id' => $this->companyId,
            'vendor_id' => $this->vendorId,
            'account_id' => $this->rentId,
            'accounting_period_id' => $this->periodId,
            'title' => self::PREFIX . ' Monthly office rent bill',
            'description' => 'UAT recurring vendor bill with line discount and GST.',
            'frequency' => 'monthly',
            'interval' => 1,
            'start_date' => '2026-10-01',
            'next_run_date' => '2026-10-01',
            'status' => 'active',
            'sub_total' => 120000,
            'discount' => 6000,
            'tax' => 5700,
            'total' => 119700,
            'payment_terms_days' => 5,
            'note' => 'Generate monthly rent and building services bill.',
            'bills_generated' => 0,
            'max_occurrences' => 12,
            'created_by' => $this->userId,
            'updated_by' => $this->userId,
        ], [
            'account_id' => $this->rentId,
            'tax_id' => DB::table('tax_rates')->where('company_id', $this->companyId)->where('name', 'like', '%5%')->value('id'),
            'description' => 'Monthly office rent and service charges',
            'quantity' => 1,
            'price' => 120000,
            'discount_percentage' => 5,
            'sub_total' => 120000,
            'tax_amount' => 5700,
            'total' => 119700,
        ]);
    }

    private function recurring(string $table, string $itemTable, string $fk, array $document, array $item): void
    {
        $row = DB::table($table)->where('company_id', $this->companyId)->where('title', $document['title'])->first();
        if ($row) {
            return;
        }

        $id = $this->insert($table, $document);
        $this->insert($itemTable, array_merge($item, [$fk => $id]));
    }

    private function budgetAndFixedAsset(): void
    {
        if (Schema::hasTable('budgets')) {
            foreach ([
                [$this->salesId, 1000000, 'Monthly sales target budget'],
                [$this->officeExpenseId, 250000, 'Office operating expense budget'],
                [$this->wagesId, 500000, 'Payroll/wages budget'],
            ] as $budget) {
                DB::table('budgets')->updateOrInsert(
                    ['company_id' => $this->companyId, 'accounting_period_id' => $this->periodId, 'account_id' => $budget[0]],
                    $this->filter('budgets', [
                        'amount' => $budget[1],
                        'notes' => self::PREFIX . ' - ' . $budget[2],
                        'created_by' => $this->userId,
                        'updated_by' => $this->userId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                );
            }
        }

        if (Schema::hasTable('fixed_assets') && ! DB::table('fixed_assets')->where('asset_number', self::PREFIX . '-FA-001')->exists()) {
            $this->insert('fixed_assets', [
                'company_id' => $this->companyId,
                'accounting_period_id' => $this->periodId,
                'asset_account_id' => $this->fixedAssetId,
                'depreciation_expense_account_id' => $this->depreciationExpenseId,
                'accumulated_depreciation_account_id' => $this->openingEquityId,
                'asset_number' => self::PREFIX . '-FA-001',
                'name' => 'UAT Laptop Fleet',
                'description' => 'Five laptops capitalised for accounting UAT.',
                'category' => 'Equipment',
                'purchase_date' => '2026-09-15',
                'in_service_date' => '2026-09-16',
                'purchase_cost' => 450000,
                'residual_value' => 50000,
                'useful_life_months' => 36,
                'depreciation_method' => 'straight_line',
                'current_book_value' => 450000,
                'accumulated_depreciation' => 0,
                'status' => 'active',
                'notes' => self::PREFIX . ' fixed asset test.',
                'created_by' => $this->userId,
                'updated_by' => $this->userId,
            ]);
        }
    }

    private function bankStatementLines(): void
    {
        if (! Schema::hasTable('bank_statement_lines')) {
            return;
        }

        foreach ([
            ['2026-09-04', 117000, 'Customer receipt matched', self::PREFIX . '-RCPT-FULL', 'Reconciled'],
            ['2026-09-09', -31500, 'Vendor payment matched', self::PREFIX . '-VPAY-FULL', 'Reconciled'],
            ['2026-09-12', -2500, 'Bank charge imported from statement', self::PREFIX . '-BANK-SPEND', 'Unreconciled'],
        ] as $line) {
            if (DB::table('bank_statement_lines')->where('company_id', $this->companyId)->where('reference', $line[3])->exists()) {
                continue;
            }
            $this->insert('bank_statement_lines', [
                'company_id' => $this->companyId,
                'bank_account_id' => $this->bankId,
                'date' => $line[0],
                'amount' => $line[1],
                'payee' => 'Senior Accountant UAT',
                'description' => $line[2],
                'reference' => $line[3],
                'status' => $line[4],
            ]);
        }
    }

    private function taxSettlementAndPayment(): void
    {
        $period = TaxPeriod::withoutGlobalScopes()
            ->where('company_id', $this->companyId)
            ->where('name', self::PREFIX . ' GST September 2026')
            ->first();

        if (! $period) {
            $period = TaxPeriod::create([
                'company_id' => $this->companyId,
                'name' => self::PREFIX . ' GST September 2026',
                'type' => 'monthly',
                'start_date' => '2026-09-01',
                'end_date' => '2026-09-30',
                'status' => 'open',
                'carried_forward_amount' => 0,
                'created_by' => $this->userId,
                'updated_by' => $this->userId,
            ]);
        }

        $service = app(TaxService::class);
        if ($period->status === 'open') {
            $period = $service->settlePeriod($period->fresh());
        }

        if ($period->fresh()->status === 'settled') {
            $service->recordPayment($period->fresh(), $this->bankId, '2026-09-30', self::PREFIX . ' GST payment');
        }
    }

    private function post(string $uniqueNo, string $date, string $type, array $lines, ?int $referenceId = null, ?int $customerId = null, ?int $vendorId = null, ?string $invoiceNo = null): void
    {
        if (DB::table('transactions')->where('company_id', $this->companyId)->where('unique_no', $uniqueNo)->exists()
            || DB::table('journal_entries')->where('company_id', $this->companyId)->where('unique_no', $uniqueNo)->exists()) {
            return;
        }

        foreach ($lines as $line) {
            [$accountId, $debit, $credit, $description] = $line;
            $base = [
                'company_id' => $this->companyId,
                'accounting_period_id' => $this->periodId,
                'account_id' => $accountId,
                'unique_no' => $uniqueNo,
                'date' => $date,
                'description' => $description,
                'debit' => $debit,
                'credit' => $credit,
                'created_by' => $this->userId,
                'updated_by' => $this->userId,
            ];

            $this->insert('journal_entries', array_merge($base, [
                'narration' => self::PREFIX . ' ' . $type,
            ]));

            $this->insert('transactions', array_merge($base, [
                'customer_id' => $customerId,
                'vendor_id' => $vendorId,
                'reference_id' => $referenceId,
                'invoice_no' => $invoiceNo,
                'type' => $type,
                'status' => 1,
            ]));
        }
    }

    private function insert(string $table, array $data): int
    {
        $data = $this->filter($table, array_merge($data, [
            'created_at' => $data['created_at'] ?? now(),
            'updated_at' => $data['updated_at'] ?? now(),
        ]));

        return (int) DB::table($table)->insertGetId($data);
    }

    private function filter(string $table, array $data): array
    {
        $columns = array_flip(Schema::getColumnListing($table));

        return array_intersect_key($data, $columns);
    }
}
