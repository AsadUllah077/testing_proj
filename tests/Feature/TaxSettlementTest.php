<?php

namespace Tests\Feature;

use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Vendor;
use App\Models\TaxPeriod;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TaxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxSettlementTest extends TestCase
{
    use RefreshDatabase;

    protected $taxService;
    protected $company;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->taxService = new TaxService();

        $country = \Illuminate\Support\Facades\DB::table('countries')->insertGetId([
            'name' => 'Test Country',
            'code' => 'TC',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->company = Company::create([
            'name' => 'Test Company',
            'email' => 'test@example.com',
            'country_id' => $country,
        ]);

        $this->user = User::create([
            'company_id' => $this->company->id,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test_user_' . time() . '@example.com',
            'phone_number' => '123456789' . time(),
            'username' => 'testuser' . time(),
            'profile_pic' => 'default.png',
            'designation' => 'Admin',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($this->user);

        // Mock loggedCompanyId helper if necessary, or just ensure it works.
        // In the erp, it usually relies on session or a specific logic.
        // I will mock it using a closure if it's a helper.
        // Assuming loggedCompanyId() is a global helper that returns ['company_id' => ...].

        // Seed basic accounts
        ChartOfAccount::create([
            'company_id' => $this->company->id,
            'name' => 'Sales Tax Payable',
            'account_number' => '241',
            'category' => 'Liabilities',
            'type' => 'Account',
            'system_account_type' => 'SALES_TAX',
        ]);

        ChartOfAccount::create([
            'company_id' => $this->company->id,
            'name' => 'Tax Refund Receivable',
            'account_number' => '152',
            'category' => 'Assets',
            'type' => 'Account',
            'system_account_type' => 'PURCHASE_TAX',
        ]);

        ChartOfAccount::create([
            'company_id' => $this->company->id,
            'name' => 'Tax Payable',
            'account_number' => '242',
            'category' => 'Liabilities',
            'type' => 'Account',
            'system_account_type' => 'SALES_TAX',
        ]);

        ChartOfAccount::create([
            'company_id' => $this->company->id,
            'name' => 'Tax Recoverable Asset',
            'account_number' => '153',
            'category' => 'Assets',
            'type' => 'Account',
            'system_account_type' => 'PURCHASE_TAX',
        ]);

        ChartOfAccount::create([
            'company_id' => $this->company->id,
            'name' => 'Bank Account',
            'account_number' => '120',
            'category' => 'Assets',
            'type' => 'Account',
        ]);

        // Create accounting periods
        AccountingPeriod::create([
            'company_id' => $this->company->id,
            'name' => 'Jan 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'status' => 'unlocked',
        ]);

        AccountingPeriod::create([
            'company_id' => $this->company->id,
            'name' => 'Feb 2026',
            'start_date' => '2026-02-01',
            'end_date' => '2026-02-28',
            'status' => 'unlocked',
        ]);
    }

    public function test_tax_balance_calculation()
    {
        $payableId = ChartOfAccount::getSalesTaxPayableId();
        $receivableId = ChartOfAccount::getPurchaseTaxAccountId();
        $taxPayableLiabilityId = ChartOfAccount::getTaxPayableLiabilityId();

        // Verify account lookups return different accounts!
        $this->assertNotEquals($payableId, $taxPayableLiabilityId);
        $this->assertNotNull($payableId);
        $this->assertNotNull($taxPayableLiabilityId);

        // Simulate Sales Tax (Credit Payable)
        Transaction::create([
            'company_id' => $this->company->id,
            'account_id' => $payableId,
            'date' => '2026-01-10',
            'credit' => 100,
            'debit' => 0,
            'status' => 1,
            'unique_no' => 'INV-001',
        ]);

        // Simulate Purchase Tax (Debit Receivable)
        Transaction::create([
            'company_id' => $this->company->id,
            'account_id' => $receivableId,
            'date' => '2026-01-15',
            'credit' => 0,
            'debit' => 40,
            'status' => 1,
            'unique_no' => 'BILL-001',
        ]);

        $balances = $this->taxService->calculateBalances($this->company->id, '2026-01-01', '2026-01-31');

        $this->assertEquals(100, $balances['payable']);
        $this->assertEquals(40, $balances['receivable']);
        $this->assertEquals(60, $balances['net']);
    }

    public function test_period_settlement_generates_journal()
    {
        $payableId = ChartOfAccount::getSalesTaxPayableId();
        $receivableId = ChartOfAccount::getPurchaseTaxAccountId();
        $taxPayableLiabilityId = ChartOfAccount::getTaxPayableLiabilityId();

        Transaction::create([
            'company_id' => $this->company->id,
            'account_id' => $payableId,
            'date' => '2026-01-10',
            'credit' => 100,
            'debit' => 0,
            'status' => 1,
            'unique_no' => 'INV-002',
        ]);

        Transaction::create([
            'company_id' => $this->company->id,
            'account_id' => $receivableId,
            'date' => '2026-01-15',
            'credit' => 0,
            'debit' => 40,
            'status' => 1,
            'unique_no' => 'BILL-002',
        ]);

        $taxPeriod = TaxPeriod::create([
            'company_id' => $this->company->id,
            'name' => 'Jan 26 Test',
            'type' => 'monthly',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'status' => 'open',
            'approval_status' => 'approved',
        ]);

        $this->taxService->settlePeriod($taxPeriod);

        $this->assertEquals('settled', $taxPeriod->fresh()->status);
        $this->assertEquals(60, $taxPeriod->fresh()->net_amount);

        // Verify Journal Entries
        // Should have:
        // DR Sales Tax Payable 100
        // CR Purchase Tax Input 40
        // CR Tax Payable Liability 60
        $this->assertDatabaseHas('journal_entries', [
            'account_id' => $payableId,
            'debit' => 100,
            'credit' => 0,
        ]);

        $this->assertDatabaseHas('journal_entries', [
            'account_id' => $receivableId,
            'debit' => 0,
            'credit' => 40,
        ]);

        $this->assertDatabaseHas('journal_entries', [
            'account_id' => $taxPayableLiabilityId,
            'debit' => 0,
            'credit' => 60,
        ]);
    }

    public function test_period_settlement_refund_case()
    {
        $payableId = ChartOfAccount::getSalesTaxPayableId();
        $receivableId = ChartOfAccount::getPurchaseTaxAccountId();
        $taxRecoverableAssetId = ChartOfAccount::getTaxRecoverableAssetId();

        Transaction::create([
            'company_id' => $this->company->id,
            'account_id' => $payableId,
            'date' => '2026-01-10',
            'credit' => 50,
            'debit' => 0,
            'status' => 1,
            'unique_no' => 'INV-003',
        ]);

        Transaction::create([
            'company_id' => $this->company->id,
            'account_id' => $receivableId,
            'date' => '2026-01-15',
            'credit' => 0,
            'debit' => 80,
            'status' => 1,
            'unique_no' => 'BILL-003',
        ]);

        $taxPeriod = TaxPeriod::create([
            'company_id' => $this->company->id,
            'name' => 'Jan 26 Refund Test',
            'type' => 'monthly',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'status' => 'open',
            'approval_status' => 'approved',
        ]);

        $this->taxService->settlePeriod($taxPeriod);

        $this->assertEquals('settled', $taxPeriod->fresh()->status);
        $this->assertEquals(-30, $taxPeriod->fresh()->net_amount);

        // Verify Journal Entries
        // Should have:
        // DR Sales Tax Payable 50
        // DR Tax Recoverable Asset 30
        // CR Purchase Tax Input 80
        $this->assertDatabaseHas('journal_entries', [
            'account_id' => $payableId,
            'debit' => 50,
            'credit' => 0,
        ]);

        $this->assertDatabaseHas('journal_entries', [
            'account_id' => $taxRecoverableAssetId,
            'debit' => 30,
            'credit' => 0,
        ]);

        $this->assertDatabaseHas('journal_entries', [
            'account_id' => $receivableId,
            'debit' => 0,
            'credit' => 80,
        ]);
    }

    public function test_period_settlement_zero_balance()
    {
        $payableId = ChartOfAccount::getSalesTaxPayableId();
        $receivableId = ChartOfAccount::getPurchaseTaxAccountId();

        Transaction::create([
            'company_id' => $this->company->id,
            'account_id' => $payableId,
            'date' => '2026-01-10',
            'credit' => 60,
            'debit' => 0,
            'status' => 1,
            'unique_no' => 'INV-004',
        ]);

        Transaction::create([
            'company_id' => $this->company->id,
            'account_id' => $receivableId,
            'date' => '2026-01-15',
            'credit' => 0,
            'debit' => 60,
            'status' => 1,
            'unique_no' => 'BILL-004',
        ]);

        $taxPeriod = TaxPeriod::create([
            'company_id' => $this->company->id,
            'name' => 'Jan 26 Zero Test',
            'type' => 'monthly',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'status' => 'open',
            'approval_status' => 'approved',
        ]);

        $this->taxService->settlePeriod($taxPeriod);

        $this->assertEquals('settled', $taxPeriod->fresh()->status);
        $this->assertEquals(0, $taxPeriod->fresh()->net_amount);

        // Verify Journal Entries
        // Should have:
        // DR Sales Tax Payable 60
        // CR Purchase Tax Input 60
        $this->assertDatabaseHas('journal_entries', [
            'account_id' => $payableId,
            'debit' => 60,
            'credit' => 0,
        ]);

        $this->assertDatabaseHas('journal_entries', [
            'account_id' => $receivableId,
            'debit' => 0,
            'credit' => 60,
        ]);
    }

    public function test_accounts_are_cleared_after_settlement()
    {
        $payableId = ChartOfAccount::getSalesTaxPayableId();
        $receivableId = ChartOfAccount::getPurchaseTaxAccountId();
        $taxPayableLiabilityId = ChartOfAccount::getTaxPayableLiabilityId();

        // Verify accounts are different!
        $this->assertNotEquals($payableId, $taxPayableLiabilityId);

        $salesTaxAmount = 180;
        $purchaseTaxAmount = 50;
        $netTaxPayable = $salesTaxAmount - $purchaseTaxAmount;

        // Create sales tax transaction (credit)
        Transaction::create([
            'company_id' => $this->company->id,
            'account_id' => $payableId,
            'date' => '2026-01-10',
            'credit' => $salesTaxAmount,
            'debit' => 0,
            'status' => 1,
            'unique_no' => 'INV-005',
        ]);

        // Create purchase tax transaction (debit)
        Transaction::create([
            'company_id' => $this->company->id,
            'account_id' => $receivableId,
            'date' => '2026-01-15',
            'credit' => 0,
            'debit' => $purchaseTaxAmount,
            'status' => 1,
            'unique_no' => 'BILL-005',
        ]);

        $taxPeriod = TaxPeriod::create([
            'company_id' => $this->company->id,
            'name' => 'Jan 26 Clear Test',
            'type' => 'monthly',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'status' => 'open',
            'approval_status' => 'approved',
        ]);

        $this->taxService->settlePeriod($taxPeriod);

        // Check journal entries!
        $this->assertDatabaseHas('journal_entries', [
            'account_id' => $payableId,
            'debit' => $salesTaxAmount,
            'credit' => 0,
        ]);

        $this->assertDatabaseHas('journal_entries', [
            'account_id' => $receivableId,
            'debit' => 0,
            'credit' => $purchaseTaxAmount,
        ]);

        $this->assertDatabaseHas('journal_entries', [
            'account_id' => $taxPayableLiabilityId,
            'debit' => 0,
            'credit' => $netTaxPayable,
        ]);

        // Now check actual account balances!
        $salesTaxAccount = ChartOfAccount::findOrFail($payableId);
        $purchaseTaxAccount = ChartOfAccount::findOrFail($receivableId);
        $taxPayableAccount = ChartOfAccount::findOrFail($taxPayableLiabilityId);

        // Get all transactions for each account!
        $salesTaxTransactions = Transaction::where('account_id', $payableId)->where('status', 1)->get();
        $purchaseTaxTransactions = Transaction::where('account_id', $receivableId)->where('status', 1)->get();
        $taxPayableTransactions = Transaction::where('account_id', $taxPayableLiabilityId)->where('status', 1)->get();

        // Calculate balances manually!
        // For liability: balance = sum(credits) - sum(debits)
        $salesTaxBalance = $salesTaxTransactions->sum('credit') - $salesTaxTransactions->sum('debit');
        $purchaseTaxBalance = $purchaseTaxTransactions->sum('debit') - $purchaseTaxTransactions->sum('credit');
        $taxPayableBalance = $taxPayableTransactions->sum('credit') - $taxPayableTransactions->sum('debit');

        $this->assertEquals(0, $salesTaxBalance);
        $this->assertEquals(0, $purchaseTaxBalance);
        $this->assertEquals($netTaxPayable, $taxPayableBalance);
    }

    public function test_granular_settlement_and_payment()
    {
        $payableId = ChartOfAccount::getSalesTaxPayableId();
        $receivableId = ChartOfAccount::getPurchaseTaxAccountId();
        $taxPayableLiabilityId = ChartOfAccount::getTaxPayableLiabilityId($this->company->id);
        $bankAccountId = ChartOfAccount::where('name', 'Bank Account')->first()->id;

        $customerA = Customer::create(['company_id' => $this->company->id, 'name' => 'Customer A']);
        $customerB = Customer::create(['company_id' => $this->company->id, 'name' => 'Customer B']);
        $vendorX = Vendor::create(['company_id' => $this->company->id, 'name' => 'Vendor X']);
        $vendorY = Vendor::create(['company_id' => $this->company->id, 'name' => 'Vendor Y']);

        // Sales Tax (Credit)
        Transaction::create(['company_id' => $this->company->id, 'account_id' => $payableId, 'customer_id' => $customerA->id, 'date' => '2026-01-10', 'credit' => 60, 'status' => 1, 'unique_no' => 'INV-A']);
        Transaction::create(['company_id' => $this->company->id, 'account_id' => $payableId, 'customer_id' => $customerB->id, 'date' => '2026-01-11', 'credit' => 40, 'status' => 1, 'unique_no' => 'INV-B']);

        // Purchase Tax (Debit)
        Transaction::create(['company_id' => $this->company->id, 'account_id' => $receivableId, 'vendor_id' => $vendorX->id, 'date' => '2026-01-15', 'debit' => 25, 'status' => 1, 'unique_no' => 'BILL-X']);
        Transaction::create(['company_id' => $this->company->id, 'account_id' => $receivableId, 'vendor_id' => $vendorY->id, 'date' => '2026-01-16', 'debit' => 15, 'status' => 1, 'unique_no' => 'BILL-Y']);

        $taxPeriod = TaxPeriod::create([
            'company_id' => $this->company->id,
            'name' => 'Jan 26 Granular',
            'type' => 'monthly',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'status' => 'open',
            'approval_status' => 'approved',
        ]);

        // 1. Settle
        $this->taxService->settlePeriod($taxPeriod);

        // Verify Settlement Transactions for Receivable Offset
        // Vendor X: DR Payable 25, CR Receivable 25
        $this->assertDatabaseHas('transactions', ['account_id' => $receivableId, 'vendor_id' => $vendorX->id, 'credit' => 25, 'type' => 'Tax Settlement']);
        $this->assertDatabaseHas('transactions', ['account_id' => $payableId, 'vendor_id' => $vendorX->id, 'debit' => 25, 'type' => 'Tax Settlement']);

        // Vendor Y: DR Payable 15, CR Receivable 15
        $this->assertDatabaseHas('transactions', ['account_id' => $receivableId, 'vendor_id' => $vendorY->id, 'credit' => 15, 'type' => 'Tax Settlement']);
        $this->assertDatabaseHas('transactions', ['account_id' => $payableId, 'vendor_id' => $vendorY->id, 'debit' => 15, 'type' => 'Tax Settlement']);

        // 2. Pay (Net = 100 - 40 = 60)
        $this->taxService->recordPayment($taxPeriod->fresh(), $bankAccountId, '2026-02-05');

        // The bank payment clears the single net tax-payable clearing account.
        $this->assertDatabaseHas('transactions', ['account_id' => $taxPayableLiabilityId, 'debit' => 60, 'credit' => 0, 'type' => 'Tax Payment']);
        $this->assertDatabaseHas('transactions', ['account_id' => $bankAccountId, 'debit' => 0, 'credit' => 60, 'type' => 'Tax Payment']);
        $this->assertDatabaseHas('tax_payment_batches', ['tax_period_id' => $taxPeriod->id, 'direction' => 'payment', 'amount' => 60]);
    }

    public function test_locking_mechanism_prevents_edits()
    {
        $taxPeriod = TaxPeriod::create([
            'company_id' => $this->company->id,
            'name' => 'Jan 26 Test',
            'type' => 'monthly',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'status' => 'paid', // Locked
        ]);

        $this->assertTrue(TaxPeriod::isDateLocked($this->company->id, '2026-01-15'));
        $this->assertFalse(TaxPeriod::isDateLocked($this->company->id, '2026-02-01'));
    }
}
