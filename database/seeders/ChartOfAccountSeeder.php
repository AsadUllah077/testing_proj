<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use Illuminate\Database\Seeder;

class ChartOfAccountSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $idMap = [];
        $insertBatch = [];

        // Predefined accounts structure
        $accounts = $this->getAccounts();

        // Sort to ensure parent accounts appear before children
        usort($accounts, fn($a, $b) => strcmp($a[0], $b[0]));

        foreach ($accounts as $acc) {
            [$number, $name, $type, $parentNumber] = $acc;

            $insertBatch[] = [
                'account_number' => $number,
                'name' => $name,
                'type' => $type,
                'parent_id' => $parentNumber ? null : null, // filled after insert
                'category' => $this->detectCategory($number),
                'initial_balance' => 0,
                'opening_date' => $now,
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Insert all accounts at once
        ChartOfAccount::insert($insertBatch);

        // Fetch newly inserted accounts with ID mapping
        $accountsDB = ChartOfAccount::pluck('id', 'account_number')->toArray();

        // Update parent_id column correctly
        foreach ($accounts as $acc) {
            [$number,,, $parentNumber] = $acc;

            if ($parentNumber !== null) {
                ChartOfAccount::where('account_number', $number)
                    ->update(['parent_id' => $accountsDB[$parentNumber]]);
            }
        }

        $this->command->info('🔥 Chart of Accounts seeded successfully using bulk insert!');
    }

    /**
     * Account Category Detector
     */
    private function detectCategory($number): string
    {
        return match (true) {
            $number < 200 => 'Assets',
            $number < 300 => 'Liabilities',
            $number < 400 => 'Equity',
            $number < 500 => 'Revenue',
            $number < 600 => 'COGS',
            default       => 'Expenses',
        };
    }

    /**
     * Account List — Moved into separate function for readability
     */
    private function getAccounts(): array
    {
        return [
            // ===================== ASSETS (100-199) =====================
            ['100', 'Assets', 'Group', null],

            ['110', 'Cash and Cash Equivalents', 'Group', '100'],
            ['111', 'Cash on Hand', 'Account', '110'],
            ['112', 'Main Bank Account', 'Account', '110'],
            ['113', 'Savings Bank Account', 'Account', '110'],
            ['114', 'Foreign Currency Bank Account', 'Account', '110'],
            ['115', 'Petty Cash', 'Account', '110'],

            ['120', 'Accounts Receivable', 'Account', '100'],
            ['121', 'Allowance for Doubtful Accounts', 'Account', '100'],
            ['125', 'Notes Receivable', 'Account', '100'],

            ['130', 'Inventory', 'Group', '100'],
            ['131', 'Raw Materials Inventory', 'Account', '130'],
            ['132', 'Work In Progress Inventory', 'Account', '130'],
            ['133', 'Finished Goods Inventory', 'Account', '130'],
            ['134', 'Merchandise Inventory', 'Account', '130'],

            ['140', 'Prepayments', 'Group', '100'],
            ['141', 'Prepaid Rent', 'Account', '140'],
            ['142', 'Prepaid Insurance', 'Account', '140'],
            ['143', 'Prepaid Subscriptions', 'Account', '140'],
            ['144', 'Prepaid Taxes', 'Account', '140'],

            ['150', 'Other Current Assets', 'Group', '100'],
            ['151', 'Employee Advances', 'Account', '150'],
            ['152', 'Tax Refund Receivable', 'Account', '150'],
            ['153', 'Undeposited Funds', 'Account', '150'],
            ['154', 'VAT Input Tax', 'Account', '150'],
            ['155', 'Security Deposits', 'Account', '150'],
            ['156', 'Accrued Revenue', 'Account', '150'],

            ['160', 'Fixed Assets', 'Group', '100'],
            ['161', 'Land', 'Account', '160'],
            ['162', 'Buildings', 'Account', '160'],
            ['163', 'Leasehold Improvements', 'Account', '160'],
            ['164', 'Office Equipment', 'Account', '160'],
            ['165', 'Vehicles', 'Account', '160'],
            ['166', 'Furniture and Fixtures', 'Account', '160'],
            ['167', 'Computer Hardware', 'Account', '160'],
            ['168', 'Machinery and Equipment', 'Account', '160'],
            ['169', 'Printers and Scanners', 'Account', '160'],

            ['170', 'Accumulated Depreciation', 'Group', '100'],
            ['171', 'Accumulated Depreciation - Buildings', 'Account', '170'],
            ['172', 'Accumulated Depreciation - Equipment', 'Account', '170'],
            ['173', 'Accumulated Depreciation - Vehicles', 'Account', '170'],
            ['174', 'Accumulated Depreciation - Furniture', 'Account', '170'],
            ['175', 'Accumulated Depreciation - Computer Hardware', 'Account', '170'],
            ['176', 'Accumulated Depreciation - Machinery', 'Account', '170'],

            ['180', 'Intangible Assets', 'Group', '100'],
            ['181', 'Software Licenses', 'Account', '180'],
            ['182', 'Patents', 'Account', '180'],
            ['183', 'Trademarks', 'Account', '180'],
            ['184', 'Goodwill', 'Account', '180'],
            ['185', 'Accumulated Amortization', 'Account', '180'],

            ['190', 'Other Non-Current Assets', 'Group', '100'],
            ['191', 'Long Term Investments', 'Account', '190'],
            ['192', 'Long Term Deposits', 'Account', '190'],

            // ===================== LIABILITIES (200-299) =====================
            ['200', 'Liabilities', 'Group', null],

            ['210', 'Accounts Payable', 'Account', '200'],
            ['215', 'Notes Payable', 'Account', '200'],

            ['220', 'Credit Cards', 'Group', '200'],
            ['221', 'Visa Credit Card', 'Account', '220'],
            ['222', 'Mastercard Credit Card', 'Account', '220'],

            ['230', 'Accrued Liabilities', 'Group', '200'],
            ['231', 'Accrued Rent', 'Account', '230'],
            ['232', 'Accrued Utilities', 'Account', '230'],
            ['233', 'Accrued Salaries', 'Account', '230'],
            ['234', 'Accrued Interest', 'Account', '230'],
            ['235', 'Accrued Expenses - Other', 'Account', '230'],

            ['240', 'Tax Liabilities', 'Group', '200'],
            ['241', 'Sales Tax Payable', 'Account', '240'],
            ['242', 'Income Tax Payable', 'Account', '240'],
            ['243', 'Payroll Tax Payable', 'Account', '240'],
            ['244', 'Pension Fund Payable', 'Account', '240'],
            ['245', 'VAT Output Tax', 'Account', '240'],
            ['246', 'Withholding Tax Payable', 'Account', '240'],

            ['250', 'Loans and Borrowings', 'Group', '200'],
            ['251', 'Short Term Loan', 'Account', '250'],
            ['252', 'Long Term Loan', 'Account', '250'],
            ['253', 'Director Loan Payable', 'Account', '250'],
            ['254', 'Current Portion of Long Term Debt', 'Account', '250'],
            ['255', 'Bank Overdraft', 'Account', '250'],

            ['260', 'Other Liabilities', 'Group', '200'],
            ['261', 'Customer Deposits', 'Account', '260'],
            ['262', 'Unearned Revenue', 'Account', '260'],
            ['263', 'Dividends Payable', 'Account', '260'],
            ['264', 'Employee Benefits Payable', 'Account', '260'],

            // ===================== EQUITY (300-399) =====================
            ['300', 'Equity', 'Group', null],
            ['310', "Owner's Equity", 'Account', '300'],
            ['320', 'Retained Earnings', 'Account', '300'],
            ['330', 'Owner Drawings', 'Account', '300'],
            ['340', 'Share Capital', 'Account', '300'],
            ['350', 'Additional Paid-In Capital', 'Account', '300'],
            ['360', 'Treasury Stock', 'Account', '300'],
            ['370', 'Reserves', 'Account', '300'],
            ['380', 'Other Comprehensive Income', 'Account', '300'],
            ['399', 'Opening Balance Equity', 'Account', '300'],

            // ===================== REVENUE (400-499) =====================
            ['400', 'Revenue', 'Group', null],
            ['410', 'Sales Revenue', 'Account', '400'],
            ['411', 'Online Sales Revenue', 'Account', '400'],
            ['412', 'Wholesale Sales Revenue', 'Account', '400'],
            ['413', 'Sales Returns and Allowances', 'Account', '400'],
            ['414', 'Sales Discounts', 'Account', '400'],

            ['420', 'Service Income', 'Account', '400'],
            ['421', 'Consulting Income', 'Account', '400'],
            ['422', 'Maintenance Service Income', 'Account', '400'],
            ['423', 'Commission Income', 'Account', '400'],

            ['430', 'Other Income', 'Group', '400'],
            ['431', 'Interest Income', 'Account', '430'],
            ['432', 'Foreign Exchange Gain', 'Account', '430'],
            ['433', 'Late Fee Income', 'Account', '430'],
            ['434', 'Gain on Disposal of Assets', 'Account', '430'],
            ['435', 'Rental Income', 'Account', '430'],
            ['436', 'Dividend Income', 'Account', '430'],
            ['437', 'Miscellaneous Income', 'Account', '430'],

            // ===================== COGS (500-599) =====================
            ['500', 'Cost of Goods Sold', 'Group', null],
            ['510', 'Purchases', 'Account', '500'],
            ['511', 'Direct Labor', 'Account', '500'],
            ['512', 'Manufacturing Supplies', 'Account', '500'],
            ['513', 'Freight and Delivery (COGS)', 'Account', '500'],
            ['514', 'Purchase Returns and Allowances', 'Account', '500'],
            ['515', 'Purchase Discounts', 'Account', '500'],
            ['516', 'Factory Overhead', 'Account', '500'],
            ['517', 'Packaging Costs', 'Account', '500'],
            ['518', 'Import Duties', 'Account', '500'],
            ['519', 'Warehousing Cost (COGS)', 'Account', '500'],
            ['520', 'Subcontractor Costs', 'Account', '500'],
            ['521', 'Customs and Clearing Charges', 'Account', '500'],

            // ===================== EXPENSES (600-999) =====================
            ['600', 'Expenses', 'Group', null],
            ['610', 'Rent Expense', 'Account', '600'],
            ['611', 'Lease Expense', 'Account', '600'],
            ['620', 'Utilities Expense', 'Account', '600'],
            ['621', 'Electricity Expense', 'Account', '600'],
            ['622', 'Water Expense', 'Account', '600'],
            ['623', 'Gas Expense', 'Account', '600'],
            ['630', 'Telephone and Internet', 'Account', '600'],
            ['640', 'Office Supplies', 'Account', '600'],
            ['641', 'Printing and Stationery', 'Account', '600'],
            ['650', 'Postage and Courier', 'Account', '600'],

            ['660', 'Employee Expenses', 'Group', '600'],
            ['661', 'Salaries and Wages', 'Account', '660'],
            ['662', 'Bonuses', 'Account', '660'],
            ['663', 'Employee Benefits', 'Account', '660'],
            ['664', 'Payroll Taxes', 'Account', '660'],
            ['665', 'Staff Training and Development', 'Account', '660'],
            ['666', 'Staff Welfare Expense', 'Account', '660'],
            ['667', 'Recruitment Expense', 'Account', '660'],
            ['668', 'Medical Expense', 'Account', '660'],

            ['670', 'Marketing and Advertising', 'Group', '600'],
            ['671', 'Social Media Advertising', 'Account', '670'],
            ['672', 'Google Ads', 'Account', '670'],
            ['673', 'Print Advertising', 'Account', '670'],
            ['674', 'Promotional Materials', 'Account', '670'],
            ['675', 'Website and SEO Expense', 'Account', '670'],

            ['680', 'Travel and Entertainment', 'Group', '600'],
            ['681', 'Meals and Entertainment', 'Account', '680'],
            ['682', 'Travel Expenses', 'Account', '680'],
            ['683', 'Hotel Accommodation', 'Account', '680'],
            ['684', 'Client Entertainment Expense', 'Account', '680'],

            ['690', 'Vehicle Expenses', 'Group', '600'],
            ['691', 'Fuel Expense', 'Account', '690'],
            ['692', 'Vehicle Repairs and Maintenance', 'Account', '690'],
            ['693', 'Vehicle Insurance', 'Account', '690'],
            ['694', 'Vehicle Registration and Licensing', 'Account', '690'],

            ['700', 'Professional Fees', 'Group', '600'],
            ['701', 'Legal Fees', 'Account', '700'],
            ['702', 'Audit Fees', 'Account', '700'],
            ['703', 'Consulting Fees', 'Account', '700'],
            ['704', 'Accounting Fees', 'Account', '700'],

            ['710', 'Depreciation Expense', 'Account', '600'],
            ['720', 'Amortization Expense', 'Account', '600'],
            ['730', 'Bank Fees and Charges', 'Account', '600'],
            ['740', 'Bad Debts Expense', 'Account', '600'],
            ['750', 'Miscellaneous Expense', 'Account', '600'],
            ['760', 'Office Expenses', 'Account', '600'],
            ['770', 'Insurance Expense', 'Account', '600'],

            ['780', 'Repairs and Maintenance', 'Group', '600'],
            ['781', 'Building Maintenance Expense', 'Account', '780'],
            ['782', 'IT Maintenance Expense', 'Account', '780'],
            ['783', 'Equipment Maintenance Expense', 'Account', '780'],
            ['784', 'Plumbing Expense', 'Account', '780'],
            ['785', 'Electrical Maintenance Expense', 'Account', '780'],

            ['790', 'Interest Expense', 'Account', '600'],
            ['800', 'Foreign Exchange Loss', 'Account', '600'],
            ['810', 'Income Tax Expense', 'Account', '600'],
            ['820', 'Penalties and Fines', 'Account', '600'],
            ['830', 'Donations and Contributions', 'Account', '600'],
            ['840', 'Research and Development', 'Account', '600'],
            ['850', 'Loss on Disposal of Assets', 'Account', '600'],
            ['860', 'Waste Disposal Expense', 'Account', '600'],
            ['870', 'Security Expense', 'Account', '600'],
            ['880', 'Cleaning Expense', 'Account', '600'],
            ['890', 'Tea and Refreshment', 'Account', '600'],
            ['900', 'Freight and Delivery Expense', 'Account', '600'],
            ['910', 'Loading and Unloading Expense', 'Account', '600'],
            ['920', 'Packing Material Expense', 'Account', '600'],
            ['930', 'CSR Expense', 'Account', '600'],
            ['940', 'Subscription Expense', 'Account', '600'],
            ['950', 'Software Expense', 'Account', '600'],
        ];
    }
}
