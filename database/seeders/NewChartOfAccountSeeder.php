<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NewChartOfAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();
        $insertBatch = [];

        Schema::disableForeignKeyConstraints();
        ChartOfAccount::truncate();
        Schema::enableForeignKeyConstraints();

        $accounts = $this->getAccounts();

        foreach ($accounts as $acc) {
            [$number, $name, $type, $taxCode, $description] = $acc;

            $insertBatch[] = [
                'account_number' => $number,
                'name' => $name,
                'type' => 'Account',
                'parent_id' => null,
                'category' => $this->mapCategory($type),
                'system_account_type' => $this->mapSystemAccountType($number),
                'sub_type' => $this->mapSubType($type),
                'tax_code' => $taxCode,
                'description' => $description,
                'initial_balance' => 0,
                'opening_date' => $now,
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Chunk insert to avoid overly large queries if the list grows
        foreach (array_chunk($insertBatch, 50) as $chunk) {
            ChartOfAccount::insert($chunk);
        }

        $this->command->info('🔥 Imported Custom Chart of Accounts successfully seeded!');
    }

    /**
     * Map image Type to system Category
     */
    private function mapCategory($type): string
    {
        return match ($type) {
            'Bank', 'Current Asset', 'Fixed Asset', 'Non-current Asset', 'Accounts Receivable', 'Inventory' => 'Assets',
            'Accounts Payable', 'Current Liability', 'Non-current Liability', 'Wages Payable', 'Unpaid Expense Claims', 'Sales Tax' => 'Liabilities',
            'Equity', 'Retained Earnings' => 'Equity',
            'Revenue' => 'Revenue',
            'Direct Costs' => 'COGS',
            'Expense', 'Rounding', 'Tracking Transfers' => 'Expenses',
            default => 'Expenses',
        };
    }

    /**
     * Xero-style sub_type — classifies accounts for reporting.
     */
    private function mapSubType($type): ?string
    {
        return match ($type) {
            'Bank' => 'BANK',
            'Current Asset' => 'CURRENT_ASSET',
            'Accounts Receivable' => 'RECEIVABLE',
            'Inventory' => 'INVENTORY',
            'Fixed Asset' => 'FIXED_ASSET',
            'Non-current Asset' => 'NON_CURRENT_ASSET',
            'Accounts Payable' => 'CURRENT_LIABILITY',
            'Current Liability' => 'CURRENT_LIABILITY',
            'Wages Payable' => 'CURRENT_LIABILITY',
            'Unpaid Expense Claims' => 'CURRENT_LIABILITY',
            'Sales Tax' => 'CURRENT_LIABILITY',
            'Non-current Liability' => 'NON_CURRENT_LIABILITY',
            'Equity' => 'EQUITY',
            'Retained Earnings' => 'EQUITY',
            'Revenue' => 'REVENUE',
            'Direct Costs' => 'DIRECT_COST',
            'Expense' => 'OPERATING_EXPENSE',
            'Rounding' => 'OPERATING_EXPENSE',
            'Tracking Transfers' => 'OPERATING_EXPENSE',
            default => null,
        };
    }

    /**
     * Xero-style system_account_type — tags critical accounts by their ROLE.
     * Only ~10 accounts get tagged. Keyed by account_number for lookup.
     */
    private function mapSystemAccountType(string $accountNumber): ?string
    {
        return match ($accountNumber) {
            '610' => 'ACCOUNTS_RECEIVABLE',
            '800' => 'ACCOUNTS_PAYABLE',
            '803' => 'SALES_TAX',
            '152' => 'PURCHASE_TAX',
            '413' => 'SALES_DISCOUNT',
            '316' => 'PURCHASE_DISCOUNT',
            '802' => 'WAGES_PAYABLE',
            '625' => 'EMPLOYEE_ADVANCES',
            '399' => 'OPENING_BALANCE_EQUITY',
            '950' => 'RETAINED_EARNINGS',
            default => null,
        };
    }

    private function getAccounts(): array
    {
        return [
            ['090', 'Business Bank Account', 'Bank', 'Tax Exempt (0%)', null],
            ['091', 'Business Account', 'Bank', 'Tax Exempt (0%)', null],
            ['191', 'Business A/c', 'Bank', 'Tax Exempt (0%)', null],
            ['092', 'Bank of Maldives Business account', 'Bank', 'Tax Exempt (0%)', null],
            ['093', 'Habib metropolitan Business account', 'Bank', 'Tax Exempt (0%)', null],
            ['200', 'Sales', 'Revenue', 'Tax on Sales (0%)', 'Income from any normal business activity'],
            ['222', 'Container clearance', 'Direct Costs', 'Tax on Purchases (0%)', null],
            ['227', 'Sales under Habib L.C/new', 'Revenue', 'Tax on Sales (0%)', null],
            ['260', 'Other Revenue', 'Revenue', 'Tax on Sales (0%)', 'Any other income that does not relate to normal business activities and is not recurring'],
            ['270', 'Interest Income', 'Revenue', 'Tax Exempt (0%)', 'Interest income'],
            ['290', 'Suspense revenue', 'Revenue', 'Tax on Sales (0%)', null],
            ['291', 'Revenue from Rent', 'Revenue', 'Tax on Sales (0%)', null],
            ['292', 'Revenue from Royalties', 'Revenue', 'Tax on Sales (0%)', null],
            ['294', 'Rent revenue from new factory', 'Revenue', 'Tax on Sales (0%)', null],
            ['295', 'Received from Sale Solution for Purchases', 'Revenue', 'Tax on Sales (0%)', null],
            ['296', 'M/H Container 1/2 purchases', 'Direct Costs', 'Tax on Purchases (0%)', null],
            ['297', 'M/H Container 3 clearance cost', 'Direct Costs', 'Tax on Purchases (0%)', null],
            ['298', 'M/H Container other costs', 'Direct Costs', 'Tax on Purchases (0%)', null],
            ['299', 'M/H Container 1/2 clearance cost', 'Direct Costs', 'Tax on Purchases (0%)', null],
            ['300', 'PURCHASES', 'Direct Costs', 'Tax on Purchases (0%)', 'Cost of goods sold by the business'],
            ['301', 'Assignment Freight', 'Direct Costs', 'Tax on Purchases (0%)', null],
            ['310', 'Inventory cost', 'Direct Costs', 'Tax on Purchases (0%)', null],
            ['311', 'Return purchases', 'Direct Costs', 'Tax on Purchases (0%)', null],
            ['312', 'M/H Container 4 costs', 'Direct Costs', 'Tax on Purchases (0%)', null],
            ['313', 'Container 4 Purchases', 'Direct Costs', 'Tax on Purchases (0%)', null],
            ['314', 'Container 4 clearance costs', 'Direct Costs', 'Tax on Purchases (0%)', null],
            ['400', 'Advertising', 'Expense', 'Tax on Purchases (0%)', 'Expenses incurred for advertisements which promote the business'],
            ['404', 'Bank revaluations', 'Expense', 'Tax on Purchases (0%)', null],
            ['408', 'Bank Fees', 'Expense', 'Tax on Purchases (0%)', 'Fees charged by your bank for transactions related to your bank account(s).'],
            ['409', 'Cleaning', 'Expense', 'Tax on Purchases (0%)', 'Expenses incurred for cleaning business property.'],
            ['412', 'Consulting & Accounting', 'Expense', 'Tax on Purchases (0%)', 'Expenses related to paying consultants'],
            ['416', 'Depreciation', 'Expense', 'Tax Exempt (0%)', "The amount of the asset's cost (based on the useful life) that was consumed during the period."],
            ['420', 'ENTERTAINMENT/MEETINGS', 'Expense', 'Tax on Purchases (0%)', 'Expenses paid by company for the business but are not deductible for income tax purposes.'],
            ['425', 'Freight & Courier', 'Expense', 'Tax on Purchases (0%)', 'Expenses incurred on sending freight items.'],
            ['428', 'Dealers commission', 'Expense', 'Tax on Purchases (0%)', null],
            ['429', 'General Expenses', 'Expense', 'Tax on Purchases (0%)', 'General expenses related to the running of the business.'],
            ['430', 'Sample expense', 'Expense', 'Tax on Purchases (0%)', null],
            ['433', 'Insurance', 'Expense', 'Tax on Purchases (0%)', 'Expenses incurred for insuring the business assets.'],
            ['437', 'Interest Expense', 'Expense', 'Tax Exempt (0%)', 'Any interest expenses paid by your for business, business loan accounts or credit card accounts.'],
            ['441', 'Legal expenses', 'Expense', 'Tax on Purchases (0%)', 'Expenses incurred on any legal matters'],
            ['445', 'Light, Power, Heating', 'Expense', 'Tax on Purchases (0%)', 'Expenses incurred for lighting, power and heating in the business'],
            ['449', 'Motor Vehicle Expenses', 'Expense', 'Tax on Purchases (0%)', 'Expenses incurred on the running of company motor vehicles'],
            ['453', 'Office Expenses', 'Expense', 'Tax on Purchases (0%)', null],
            ['461', 'Printing & Stationery', 'Expense', 'Tax on Purchases (0%)', 'General expenses related to the running of the business offices.'],
            ['469', 'Rent', 'Expense', 'Tax on Purchases (0%)', 'Expenses incurred on stationary and small printing and stationery'],
            ['473', 'Repairs and Maintenance', 'Expense', 'Tax on Purchases (0%)', 'The expense on lease or renting a space'],
            ['474', 'STAFF TEA/WATER/MEALS', 'Expense', 'Tax on Purchases (0%)', 'Expenses incurred on any repairs or maintenance of assets that will bring the asset back to its original condition.'],
            ['477', 'Wages and Salaries', 'Expense', 'Tax Exempt (0%)', 'Payments to employees in exchange for their resources'],
            ['478', 'Superannuation', 'Expense', 'Tax Exempt (0%)', 'Superannuation contributions'],
            ['479', 'Telephone', 'Expense', 'Tax on Purchases (0%)', null],
            ['480', 'Subscriptions', 'Expense', 'Tax on Purchases (0%)', 'E.g. Magazines, professional bodies'],
            ['485', 'Telephone & Internet', 'Expense', 'Tax on Purchases (0%)', 'Expenditure incurred for any business related phone calls, phone lines or internet connections'],
            ['489', 'Travel - National', 'Expense', 'Tax on Purchases (0%)', 'Expenses incurred from domestic travel which has a business purpose.'],
            ['493', 'Travel - International', 'Expense', 'Tax on Purchases (0%)', 'Expenses incurred from international travel which has a business purpose.'],
            ['497', 'Security expense', 'Expense', 'Tax on Purchases (0%)', null],
            ['498', 'Income Tax Expense', 'Expense', 'Tax on Purchases (0%)', 'A percentage of total earnings paid to the government.'],
            ['500', 'Office renovation', 'Expense', 'Tax on Purchases (0%)', null],
            ['507', 'Kitchen/hardware supplies', 'Expense', 'Tax on Purchases (0%)', null],
            ['508', 'Snacks food expense', 'Expense', 'Tax on Purchases (0%)', null],
            ['509', 'Pest kill', 'Expense', 'Tax on Purchases (0%)', null],
            ['510', 'Electric work', 'Expense', 'Tax on Purchases (0%)', null],
            ['511', 'Staff uniform', 'Expense', 'Tax on Purchases (0%)', null],
            ['512', 'Office supplies', 'Expense', 'Tax on Purchases (0%)', null],
            ['513', 'Wages', 'Expense', 'Tax on Purchases (0%)', null],
            ['514', 'Accommodation', 'Expense', 'Tax on Purchases (0%)', null],
            ['515', 'Food For Staff', 'Expense', 'Tax on Purchases (0%)', null],
            ['516', 'Other supplies', 'Expense', 'Tax on Purchases (0%)', null],
            ['517', 'Visa For Staff', 'Expense', 'Tax on Purchases (0%)', null],
            ['518', 'Suspense', 'Expense', 'Tax on Purchases (0%)', 'An account that allows an unknown transaction to be entered, so the accounts can still be worked on in balance and the entry can be fixed in a later time.'],
            ['519', 'Fuel Expense', 'Expense', 'Tax on Purchases (0%)', null],
            ['520', 'Medical expenses', 'Expense', 'Tax on Purchases (0%)', null],
            ['521', 'Bike parts/repair', 'Expense', 'Tax on Purchases (0%)', null],
            ['522', 'License fee', 'Expense', 'Tax on Purchases (0%)', null],
            ['523', 'Car repairs/parts', 'Expense', 'Tax on Purchases (0%)', null],
            ['524', 'Vehicle repair', 'Expense', 'Tax on Purchases (0%)', null],
            ['525', 'Repair expenses', 'Expense', 'Tax on Purchases (0%)', null],
            ['526', 'Return on sales', 'Expense', 'Tax on Purchases (0%)', null],
            ['527', 'M/Fuel Expense', 'Expense', 'Tax on Purchases (0%)', null],
            ['528', 'Computer Supplies', 'Expense', 'Tax on Purchases (0%)', null],
            ['529', 'Vehicle Fuel/Oil/Car Wash/service fee', 'Expense', 'Tax on Purchases (0%)', null],
            ['530', 'Contractor Work', 'Expense', 'Tax on Purchases (0%)', null],
            ['610', 'Accounts Receivable', 'Accounts Receivable', 'Tax Exempt (0%)', 'Outstanding invoices the business has issued out to the client yet, has not yet received as of balance date.'],
            ['611', 'Accounts Receivable 1 unadjusted', 'Current Asset', 'Tax Exempt (0%)', null],
            ['612', 'Loan receivable from student', 'Current Asset', 'Tax Exempt (0%)', 'Abdurrehman loan received by student'],
            ['613', 'Loan taken by Hasan unadj', 'Current Asset', 'Tax Exempt (0%)', null],
            ['614', 'Loan taken from Qazi Muhammad unad', 'Current Asset', 'Tax Exempt (0%)', null],
            ['615', 'Loan to jamal upd', 'Current Asset', 'Tax Exempt (0%)', null],
            ['616', 'Loan to Sohaib unadj import', 'Current Asset', 'Tax Exempt (0%)', null],
            ['617', 'Loan given to Rabie', 'Current Asset', 'Tax Exempt (0%)', null],
            ['618', 'Loan receivable from Qazi Yaseen', 'Current Asset', 'Tax Exempt (0%)', null],
            ['620', 'Prepayments', 'Current Asset', 'Tax Exempt (0%)', 'An expenditure that has been paid for in advance.'],
            ['625', 'Employee Advances', 'Current Asset', 'Tax Exempt (0%)', 'Salary advances issued to employees and recovered through payroll.'],
            ['621', 'Loan to Faraz', 'Current Asset', 'Tax Exempt (0%)', null],
            ['622', 'Loan to Amin urahman', 'Current Asset', 'Tax Exempt (0%)', null],
            ['630', 'Inventory', 'Inventory', 'Tax Exempt (0%)', 'Values of unsold items for resale.'],
            ['631', 'Loan to Noman', 'Current Asset', 'Tax Exempt (0%)', null],
            ['632', 'Loan to Aminullah V/ass', 'Current Asset', 'Tax Exempt (0%)', null],
            ['633', 'Loan to Junaid', 'Current Asset', 'Tax Exempt (0%)', null],
            ['634', 'Loan to Noman.', 'Current Asset', 'Tax Exempt (0%)', null],
            ['635', 'Loan to Usman', 'Current Asset', 'Tax Exempt (0%)', null],
            ['636', 'Loan to M..', 'Current Asset', 'Tax Exempt (0%)', null],
            ['637', 'Contract Charges', 'Expense', 'Tax on Purchases (0%)', null],
            ['638', 'Loan To Sheri Zada', 'Current Asset', 'Tax Exempt (0%)', null],
            ['639', 'CUTTING OF WOOD FOR WATER', 'Current Asset', 'Tax Exempt (0%)', 'WOOD CUTTING FOR WOOD'],
            ['640', 'M/h Permit Fees', 'Expense', 'Tax on Purchases (0%)', null],
            ['641', 'Lease Tax', 'Expense', 'Tax on Purchases (0%)', null],
            ['642', 'Allowance and stamp', 'Expense', 'Tax on Purchases (0%)', null],
            ['643', 'M. Personal', 'Expense', 'Tax on Purchases (0%)', null],
            ['644', 'CONTAINER 1 COST', 'Expense', 'Tax on Purchases (0%)', 'ALL THE EXPENSE DONE ON CONTAINER 1'],
            ['645', 'Petty Cash exp', 'Expense', 'Tax on Purchases (0%)', null],
            ['646', 'TRAFFIC CHALLAN', 'Expense', 'Tax on Purchases (0%)', null],
            ['647', 'ENVIRONMENTAL PROTECTION CHARGES', 'Expense', 'Tax on Purchases (0%)', 'SAFETY ITEMS EXPENSE FOR EMPLOYEES'],
            ['648', 'SECURITY CARDS CHG', 'Expense', 'Tax on Purchases (0%)', null],
            ['649', 'VEHICLE MAINTENNENCE', 'Expense', 'Tax on Purchases (0%)', null],
            ['650', 'MONTHLY WASTE DISPOSAL CHARGES', 'Expense', 'Tax on Purchases (0%)', null],
            ['651', 'COMMERCE/CHAMBER REG.', 'Expense', 'Tax on Purchases (0%)', null],
            ['652', 'STATIONARY EXPENSE', 'Expense', 'Tax on Purchases (0%)', null],
            ['653', 'Credit Card Expense', 'Expense', 'Tax on Purchases (0%)', null],
            ['654', 'M/H M.Rizwan personal', 'Expense', 'Tax on Purchases (0%)', null],
            ['655', 'Fund Expenses', 'Expense', 'Tax on Purchases (0%)', null],
            ['670', 'Factory Supplies', 'Non-current Asset', 'Tax Exempt (0%)', null],
            ['710', 'Office Equipment', 'Fixed Asset', 'Tax on Purchases (0%)', 'Office equipment that is owned and controlled by the business'],
            ['711', 'Less Accumulated Depreciation on Office Equipment', 'Fixed Asset', 'Tax Exempt (0%)', 'The total amount of office equipment cost that has been consumed to the present based on the useful life)'],
            ['720', 'Computer Equipment', 'Fixed Asset', 'Tax on Purchases (0%)', 'Computer equipment that is owned and controlled by the business'],
            ['721', 'Less Accumulated Depreciation on Computer Equipment', 'Fixed Asset', 'Tax Exempt (0%)', 'The total amount of computer equipment cost that has been consumed to the present based on the useful life)'],
            ['722', 'Office property purchase', 'Fixed Asset', 'Tax on Purchases (0%)', 'Old building for office'],
            ['723', 'Furniture', 'Fixed Asset', 'Tax on Purchases (0%)', null],
            ['724', 'New factory equipment', 'Fixed Asset', 'Tax on Purchases (0%)', null],
            ['730', 'Building Improvements', 'Fixed Asset', 'Tax on Purchases (0%)', null],
            ['731', 'Camera security', 'Fixed Asset', 'Tax on Purchases (0%)', null],
            ['732', 'Television', 'Fixed Asset', 'Tax on Purchases (0%)', null],
            ['733', 'Room', 'Fixed Asset', 'Tax on Purchases (0%)', null],
            ['734', 'Air Condition for Office', 'Fixed Asset', 'Tax on Purchases (0%)', null],
            ['750', 'Builders Project', 'Fixed Asset', 'Tax Exempt (0%)', 'Money sent and rec. from ABC A/C'],
            ['751', 'machine and equipment', 'Fixed Asset', 'Tax on Purchases (0%)', null],
            ['752', 'fixture and fittings', 'Fixed Asset', 'Tax on Purchases (0%)', null],
            ['753', 'fork lift purchase exp', 'Fixed Asset', 'Tax on Purchases (0%)', null],
            ['754', 'LMJ EQUIPMENT', 'Fixed Asset', 'Tax on Purchases (0%)', null],
            ['800', 'Accounts Payable', 'Accounts Payable', 'Tax Exempt (0%)', 'Outstanding invoices the business has received from suppliers yet, has not yet paid as of balance date'],
            ['801', 'Unpaid Expense Claims', 'Unpaid Expense Claims', 'Tax Exempt (0%)', 'Expense claims typically made by employees of the business who are still outstanding.'],
            ['802', 'Wages Payable', 'Wages Payable', 'Tax Exempt (0%)', 'Xero automatically updates this account for payroll entries created using Payrun and will show the overall amount to be paid to the employee for the payrun. This account enables you to maintain separate accounts for employee Wages Payable amounts and Accounts Payable amounts.'],
            ['803', 'Sales Tax', 'Sales Tax', 'Tax Exempt (0%)', 'The balance in this account represents Sales Tax owing as of the end of the tax period. At the end of the tax period, a bank transaction should be used to code against in order to reconcile the end of period payment or your tax authority that will appear on the bank statement. Xero has hard-coded this account to be the only one used with...'],
            ['805', 'Payables to Director', 'Current Liability', 'Tax Exempt (0%)', null],
            ['820', 'Employee Tax Payable', 'Current Liability', 'Tax Exempt (0%)', 'The amount of tax that has been deducted from wages or salaries paid to employees and is due to be paid.'],
            ['825', 'Superannuation Payable', 'Current Liability', 'Tax Exempt (0%)', 'The amount of superannuation that is due to be paid'],
            ['830', 'Income Tax Payable', 'Current Liability', 'Tax Exempt (0%)', 'The amount of income tax that is due to be paid, also resides withholding tax paid or interest received.'],
            ['835', 'Loan Receivable from Rafi Ullah', 'Current Asset', 'Tax Exempt (0%)', null],
            ['840', 'Historical Adjustment', 'Current Liability', 'Tax Exempt (0%)', 'For year ended adjustments'],
            ['845', 'Suspense /Cheque/cash', 'Current Liability', 'Tax Exempt (0%)', null],
            ['850', 'Rounding', 'Rounding', 'Tax Exempt (0%)', 'An adjustment entry to allow for rounding'],
            ['851', 'Tracking Transfers', 'Tracking Transfers', 'Tax Exempt (0%)', 'Transfers between tracking categories.'],
            ['860', 'M. Drawings', 'Current Liability', 'Tax Exempt (0%)', 'Withdrawals by the owners'],
            ['861', 'Owner A Funds Introduced', 'Current Liability', 'Tax Exempt (0%)', 'Funds contributed by the owners'],
            ['865', 'Owner drawing Jamila', 'Current Liability', 'Tax Exempt (0%)', null],
            ['900', 'Loan', 'Non-current Liability', 'Tax Exempt (0%)', 'Money that has been borrowed from a creditor.'],
            ['901', 'Loan from Lal M. Jan', 'Current Liability', 'Tax Exempt (0%)', null],
            ['902', 'Sale by Payable', 'Current Liability', 'Tax Exempt (0%)', null],
            ['903', 'T .Salaries payable', 'Current Liability', 'Tax Exempt (0%)', null],
            ['904', 'Loan from Jamal .Advances', 'Current Liability', 'Tax Exempt (0%)', null],
            ['950', 'Retained Earnings', 'Retained Earnings', 'Tax Exempt (0%)', 'Earned value'],
            ['960', 'Owner A Share Capital', 'Equity', 'Tax Exempt (0%)', 'The value of shares purchased by the shareholders.'],
            ['980', 'M. Capital Contribution for property', 'Equity', 'Tax Exempt (0%)', null],
            ['981', 'Sale Solution Capital contribution for salaries', 'Equity', 'Tax Exempt (0%)', null],
            ['982', 'Sale Solution Capital contri for Property', 'Equity', 'Tax Exempt (0%)', null],
            ['983', 'M. capital contribution for listed expenses', 'Equity', 'Tax Exempt (0%)', null],
            ['984', 'Capital contribution by Sale Solution', 'Equity', 'Tax Exempt (0%)', null],
            ['985', 'Distribution to Sale Solution for salaries', 'Equity', 'Tax Exempt (0%)', null],

            // === Missing system accounts (required for ERP core functions) ===
            ['152', 'Purchase Tax Input', 'Current Asset', 'Tax Exempt (0%)', 'Tax paid on purchases that can be claimed back from the tax authority.'],
            ['413', 'Sales Discount', 'Revenue', 'Tax Exempt (0%)', 'Discounts given to customers on sales invoices.'],
            ['316', 'Purchase Discount', 'Direct Costs', 'Tax Exempt (0%)', 'Discounts received from suppliers on purchase invoices.'],
            ['399', 'Opening Balance Equity', 'Equity', 'Tax Exempt (0%)', 'Used to balance opening entries when setting up initial account balances.'],
        ];
    }
}
