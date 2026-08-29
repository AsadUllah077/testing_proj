<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\Vendor;
use App\Models\TaxRate;
use App\Models\RecurringInvoice;
use App\Models\RecurringInvoiceItem;
use App\Models\RecurringBill;
use App\Models\RecurringBillItem;
use App\Models\FixedAsset;
use App\Models\AccountingPeriod;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AccountingTestDataSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Authenticate ───────────────────────────────────────────
        $user = Auth::user();

        if (!$user) {
            $user = User::where('username', 'shayan.ahmad')->first()
                ?? User::whereNotNull('company_id')->first();

            if (!$user) {
                $this->command->error('No user with company found. Seeder aborted.');
                return;
            }

            Auth::login($user);
        }

        $companyId = $user->company_id;
        $userId    = $user->id;
        $now       = now();

        // ─── Ensure Accounting Period ───────────────────────────────
        $period = AccountingPeriod::where('company_id', $companyId)
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->first();

        if (!$period) {
            $period = AccountingPeriod::create([
                'company_id' => $companyId,
                'name'       => $now->format('F Y'),
                'start_date' => $now->copy()->startOfMonth(),
                'end_date'   => $now->copy()->endOfMonth(),
                'is_active'  => true,
                'is_locked'  => false,
            ]);
            $this->command->info("Created accounting period: {$period->name}");
        }

        // ─── Resolve Accounts (by account_number) ──────────────────
        // chart_of_accounts is global (no company_id column)
        $accounts = ChartOfAccount::pluck('id', 'account_number')->toArray();

        if (empty($accounts)) {
            $this->command->error('No Chart of Accounts found. Run ChartOfAccountSeeder first.');
            return;
        }

        // Helper to get account ID by number
        $acct = fn(string $num) => $accounts[$num] ?? null;

        // ─── Ensure Tax Rates Exist ─────────────────────────────────
        $gst17 = TaxRate::firstOrCreate(
            ['name' => 'GST 17%', 'company_id' => $companyId],
            ['tax_rate' => 17.00, 'code' => 'GST17', 'tax_type' => 'percentage', 'created_at' => $now, 'updated_at' => $now]
        );

        $gst5 = TaxRate::firstOrCreate(
            ['name' => 'GST 5%', 'company_id' => $companyId],
            ['tax_rate' => 5.00, 'code' => 'GST5', 'tax_type' => 'percentage', 'created_at' => $now, 'updated_at' => $now]
        );

        $this->command->info("Tax rates ready: GST 17% (#{$gst17->id}), GST 5% (#{$gst5->id})");

        // ─── Ensure Sample Customers ────────────────────────────────
        $customers = $this->ensureCustomers($companyId, $userId, $now);

        // ─── Ensure Sample Vendors ──────────────────────────────────
        $vendors = $this->ensureVendors($companyId, $userId, $now);

        // ════════════════════════════════════════════════════════════
        //  SECTION 1: RECURRING INVOICES (5 entries)
        // ════════════════════════════════════════════════════════════
        $this->command->info('');
        $this->command->info('━━━ Seeding Recurring Invoices ━━━');

        $recurringInvoices = [
            [
                'title'              => 'Monthly SaaS Subscription - TechCorp',
                'description'        => 'Enterprise software license subscription billed monthly',
                'customer_id'        => $customers[0]->id,
                'account_id'         => $acct('410'), // Sales Revenue
                'frequency'          => 'monthly',
                'interval'           => 1,
                'start_date'         => Carbon::now()->startOfMonth(),
                'end_date'           => Carbon::now()->addYear()->endOfMonth(),
                'max_occurrences'    => 12,
                'payment_terms_days' => 30,
                'note'               => 'Auto-renewing annual contract. Invoice on 1st of each month.',
                'items'              => [
                    ['account_id' => $acct('410'), 'tax_id' => $gst17->id, 'description' => 'Enterprise SaaS License (50 users)',      'quantity' => 1, 'price' => 75000.00, 'discount_percentage' => 0],
                    ['account_id' => $acct('420'), 'tax_id' => $gst17->id, 'description' => 'Premium Support Package',                  'quantity' => 1, 'price' => 15000.00, 'discount_percentage' => 0],
                ],
            ],
            [
                'title'              => 'Quarterly Consulting Retainer - GreenEnergy Ltd',
                'description'        => 'Quarterly IT consulting retainer agreement',
                'customer_id'        => $customers[1]->id,
                'account_id'         => $acct('421'), // Consulting Income
                'frequency'          => 'quarterly',
                'interval'           => 1,
                'start_date'         => Carbon::now()->startOfQuarter(),
                'end_date'           => null,
                'max_occurrences'    => null,
                'payment_terms_days' => 15,
                'note'               => 'Ongoing consulting retainer. 40 hours/quarter guaranteed.',
                'items'              => [
                    ['account_id' => $acct('421'), 'tax_id' => $gst17->id, 'description' => 'IT Consulting Retainer (40 hrs @ 5,000/hr)', 'quantity' => 40, 'price' => 5000.00, 'discount_percentage' => 5],
                ],
            ],
            [
                'title'              => 'Weekly Server Monitoring - DataSafe Inc',
                'description'        => 'Weekly infrastructure monitoring and reporting service',
                'customer_id'        => $customers[2]->id,
                'account_id'         => $acct('422'), // Maintenance Service Income
                'frequency'          => 'weekly',
                'interval'           => 1,
                'start_date'         => Carbon::now()->startOfWeek(),
                'end_date'           => Carbon::now()->addMonths(6),
                'max_occurrences'    => 26,
                'payment_terms_days' => 7,
                'note'               => '6-month monitoring contract. Weekly health reports included.',
                'items'              => [
                    ['account_id' => $acct('422'), 'tax_id' => $gst5->id, 'description' => 'Server Monitoring (10 servers)',  'quantity' => 10, 'price' => 2500.00, 'discount_percentage' => 0],
                    ['account_id' => $acct('422'), 'tax_id' => $gst5->id, 'description' => 'Weekly Health Report Generation', 'quantity' => 1,  'price' => 5000.00, 'discount_percentage' => 0],
                ],
            ],
            [
                'title'              => 'Yearly Domain & Hosting Bundle - BlueOcean Traders',
                'description'        => 'Annual web hosting and domain renewal package',
                'customer_id'        => $customers[3]->id,
                'account_id'         => $acct('420'), // Service Income
                'frequency'          => 'yearly',
                'interval'           => 1,
                'start_date'         => Carbon::now()->startOfYear(),
                'end_date'           => null,
                'max_occurrences'    => 5,
                'payment_terms_days' => 30,
                'note'               => '5-year hosting contract with fixed pricing guarantee.',
                'items'              => [
                    ['account_id' => $acct('420'), 'tax_id' => $gst17->id, 'description' => 'Web Hosting - Business Plan (Annual)',   'quantity' => 1, 'price' => 48000.00, 'discount_percentage' => 10],
                    ['account_id' => $acct('420'), 'tax_id' => null,       'description' => 'Domain Renewal (.com + .pk)',            'quantity' => 2, 'price' => 3500.00,  'discount_percentage' => 0],
                    ['account_id' => $acct('420'), 'tax_id' => $gst17->id, 'description' => 'SSL Certificate (Wildcard)',             'quantity' => 1, 'price' => 12000.00, 'discount_percentage' => 0],
                ],
            ],
            [
                'title'              => 'Bi-Monthly Maintenance - PakSteel Manufacturing',
                'description'        => 'Equipment maintenance service every 2 months',
                'customer_id'        => $customers[4]->id,
                'account_id'         => $acct('422'), // Maintenance Service Income
                'frequency'          => 'monthly',
                'interval'           => 2,
                'start_date'         => Carbon::now()->startOfMonth(),
                'end_date'           => Carbon::now()->addYear(),
                'max_occurrences'    => 6,
                'payment_terms_days' => 45,
                'note'               => 'Bi-monthly preventive maintenance. Includes parts and labor.',
                'items'              => [
                    ['account_id' => $acct('422'), 'tax_id' => $gst17->id, 'description' => 'Preventive Maintenance Visit',           'quantity' => 1, 'price' => 35000.00, 'discount_percentage' => 0],
                    ['account_id' => $acct('422'), 'tax_id' => $gst17->id, 'description' => 'Replacement Parts (estimated)',          'quantity' => 1, 'price' => 15000.00, 'discount_percentage' => 0],
                    ['account_id' => $acct('422'), 'tax_id' => null,       'description' => 'Travel & Transportation',                'quantity' => 1, 'price' => 5000.00,  'discount_percentage' => 0],
                ],
            ],
        ];

        foreach ($recurringInvoices as $data) {
            $this->createRecurringInvoice($data, $companyId, $userId, $period->id, $now);
        }

        $this->command->info("Created " . count($recurringInvoices) . " recurring invoices");

        // ════════════════════════════════════════════════════════════
        //  SECTION 2: RECURRING BILLS (5 entries)
        // ════════════════════════════════════════════════════════════
        $this->command->info('');
        $this->command->info('━━━ Seeding Recurring Bills ━━━');

        $recurringBills = [
            [
                'title'              => 'Monthly Office Rent - Tower 42',
                'description'        => 'Office space rental at Blue Area, Islamabad',
                'vendor_id'          => $vendors[0]->id,
                'account_id'         => $acct('610'), // Rent Expense
                'frequency'          => 'monthly',
                'interval'           => 1,
                'start_date'         => Carbon::now()->startOfMonth(),
                'end_date'           => null,
                'max_occurrences'    => null,
                'payment_terms_days' => 5,
                'note'               => 'Lease agreement #LA-2025-042. Due by 5th of each month.',
                'items'              => [
                    ['account_id' => $acct('610'), 'tax_id' => null,       'description' => 'Office Rent - Floor 4, Suite 402 (2,500 sqft)',  'quantity' => 1, 'price' => 180000.00, 'discount_percentage' => 0],
                    ['account_id' => $acct('620'), 'tax_id' => $gst17->id, 'description' => 'Common Area Maintenance (CAM) Charges',           'quantity' => 1, 'price' => 25000.00,  'discount_percentage' => 0],
                ],
            ],
            [
                'title'              => 'Monthly Internet & Phone - PTCL Business',
                'description'        => 'Business broadband and phone lines',
                'vendor_id'          => $vendors[1]->id,
                'account_id'         => $acct('630'), // Telephone & Internet
                'frequency'          => 'monthly',
                'interval'           => 1,
                'start_date'         => Carbon::now()->startOfMonth(),
                'end_date'           => null,
                'max_occurrences'    => null,
                'payment_terms_days' => 15,
                'note'               => 'Account #BIZ-ISB-78432. 100 Mbps dedicated fiber.',
                'items'              => [
                    ['account_id' => $acct('630'), 'tax_id' => $gst17->id, 'description' => 'Business Broadband 100 Mbps (Dedicated)', 'quantity' => 1, 'price' => 18000.00, 'discount_percentage' => 0],
                    ['account_id' => $acct('630'), 'tax_id' => $gst17->id, 'description' => 'Phone Lines (5 extensions)',              'quantity' => 5, 'price' => 1500.00,  'discount_percentage' => 0],
                ],
            ],
            [
                'title'              => 'Quarterly Insurance Premium - State Life',
                'description'        => 'Comprehensive office and equipment insurance',
                'vendor_id'          => $vendors[2]->id,
                'account_id'         => $acct('142'), // Prepaid Insurance
                'frequency'          => 'quarterly',
                'interval'           => 1,
                'start_date'         => Carbon::now()->startOfQuarter(),
                'end_date'           => Carbon::now()->addYears(2),
                'max_occurrences'    => 8,
                'payment_terms_days' => 30,
                'note'               => 'Policy #SL-COM-2025-1892. Covers fire, theft, equipment damage.',
                'items'              => [
                    ['account_id' => $acct('142'), 'tax_id' => null, 'description' => 'Property & Equipment Insurance (Quarterly)',     'quantity' => 1, 'price' => 45000.00, 'discount_percentage' => 0],
                    ['account_id' => $acct('142'), 'tax_id' => null, 'description' => 'Business Liability Insurance (Quarterly)',       'quantity' => 1, 'price' => 22000.00, 'discount_percentage' => 0],
                ],
            ],
            [
                'title'              => 'Weekly Office Cleaning - CleanPro Services',
                'description'        => 'Professional office cleaning service every week',
                'vendor_id'          => $vendors[3]->id,
                'account_id'         => $acct('750'), // Miscellaneous Expense
                'frequency'          => 'weekly',
                'interval'           => 1,
                'start_date'         => Carbon::now()->startOfWeek(),
                'end_date'           => Carbon::now()->addMonths(6),
                'max_occurrences'    => 26,
                'payment_terms_days' => 7,
                'note'               => 'Mon-Wed-Fri deep cleaning. Contract renewable.',
                'items'              => [
                    ['account_id' => $acct('750'), 'tax_id' => $gst5->id, 'description' => 'Office Deep Cleaning (3 visits/week)', 'quantity' => 3, 'price' => 3500.00, 'discount_percentage' => 0],
                    ['account_id' => $acct('640'), 'tax_id' => null,      'description' => 'Cleaning Supplies & Materials',         'quantity' => 1, 'price' => 2000.00, 'discount_percentage' => 0],
                ],
            ],
            [
                'title'              => 'Yearly Software Licenses - Microsoft 365',
                'description'        => 'Annual Microsoft 365 Business Premium licenses',
                'vendor_id'          => $vendors[4]->id,
                'account_id'         => $acct('143'), // Prepaid Subscriptions
                'frequency'          => 'yearly',
                'interval'           => 1,
                'start_date'         => Carbon::now()->startOfYear(),
                'end_date'           => null,
                'max_occurrences'    => 3,
                'payment_terms_days' => 30,
                'note'               => 'Tenant: company.onmicrosoft.com. 50 user licenses.',
                'items'              => [
                    ['account_id' => $acct('143'), 'tax_id' => $gst17->id, 'description' => 'Microsoft 365 Business Premium (50 licenses)', 'quantity' => 50, 'price' => 2400.00, 'discount_percentage' => 10],
                    ['account_id' => $acct('143'), 'tax_id' => $gst17->id, 'description' => 'Azure AD P1 Add-on (50 licenses)',              'quantity' => 50, 'price' => 800.00,  'discount_percentage' => 10],
                ],
            ],
        ];

        foreach ($recurringBills as $data) {
            $this->createRecurringBill($data, $companyId, $userId, $period->id, $now);
        }

        $this->command->info("Created " . count($recurringBills) . " recurring bills");

        // ════════════════════════════════════════════════════════════
        //  SECTION 3: FIXED ASSETS (8 entries)
        // ════════════════════════════════════════════════════════════
        $this->command->info('');
        $this->command->info('━━━ Seeding Fixed Assets ━━━');

        $fixedAssets = [
            // ── Equipment ──
            [
                'name'                                => 'Dell PowerEdge R750 Server',
                'description'                         => 'Production database server with 128GB RAM, 4TB NVMe RAID',
                'category'                            => 'Equipment',
                'purchase_date'                       => Carbon::now()->subMonths(6)->format('Y-m-d'),
                'in_service_date'                     => Carbon::now()->subMonths(6)->addDays(5)->format('Y-m-d'),
                'purchase_cost'                       => 850000.00,
                'residual_value'                      => 85000.00,
                'useful_life_months'                  => 60,
                'depreciation_method'                 => 'straight_line',
                'asset_account_id'                    => $acct('167'), // Computer Hardware
                'depreciation_expense_account_id'     => $acct('710'), // Depreciation Expense
                'accumulated_depreciation_account_id' => $acct('172'), // Accum Depr - Equipment
                'notes'                               => 'Serial: SVR-2025-R750-001. Warranty until Dec 2028. Located in Server Room A.',
            ],
            [
                'name'                                => 'HP LaserJet Enterprise M611dn Printers (x3)',
                'description'                         => '3 enterprise printers for floors 3, 4, and 5',
                'category'                            => 'Equipment',
                'purchase_date'                       => Carbon::now()->subMonths(4)->format('Y-m-d'),
                'in_service_date'                     => Carbon::now()->subMonths(4)->format('Y-m-d'),
                'purchase_cost'                       => 225000.00,
                'residual_value'                      => 15000.00,
                'useful_life_months'                  => 48,
                'depreciation_method'                 => 'straight_line',
                'asset_account_id'                    => $acct('164'), // Office Equipment
                'depreciation_expense_account_id'     => $acct('710'),
                'accumulated_depreciation_account_id' => $acct('172'),
                'notes'                               => 'Serials: PRT-001, PRT-002, PRT-003. Maintenance contract with HP.',
            ],

            // ── Vehicles ──
            [
                'name'                                => 'Toyota Hilux Revo 2025 (Delivery)',
                'description'                         => 'Delivery vehicle for client site visits and equipment transport',
                'category'                            => 'Vehicle',
                'purchase_date'                       => Carbon::now()->subMonths(3)->format('Y-m-d'),
                'in_service_date'                     => Carbon::now()->subMonths(3)->addDays(10)->format('Y-m-d'),
                'purchase_cost'                       => 6500000.00,
                'residual_value'                      => 2000000.00,
                'useful_life_months'                  => 60,
                'depreciation_method'                 => 'declining_balance',
                'asset_account_id'                    => $acct('165'), // Vehicles
                'depreciation_expense_account_id'     => $acct('710'),
                'accumulated_depreciation_account_id' => $acct('173'), // Accum Depr - Vehicles
                'notes'                               => 'Registration: ISB-2025-1234. Driver: Kamran Ali. Insurance Policy #VI-2025-789.',
            ],
            [
                'name'                                => 'Honda Civic 2024 (Executive)',
                'description'                         => 'Executive vehicle for management use',
                'category'                            => 'Vehicle',
                'purchase_date'                       => Carbon::now()->subMonths(8)->format('Y-m-d'),
                'in_service_date'                     => Carbon::now()->subMonths(8)->format('Y-m-d'),
                'purchase_cost'                       => 5200000.00,
                'residual_value'                      => 2500000.00,
                'useful_life_months'                  => 60,
                'depreciation_method'                 => 'declining_balance',
                'asset_account_id'                    => $acct('165'),
                'depreciation_expense_account_id'     => $acct('710'),
                'accumulated_depreciation_account_id' => $acct('173'),
                'notes'                               => 'Registration: ISB-2024-5678. Assigned to CEO. Full insurance coverage.',
            ],

            // ── Furniture ──
            [
                'name'                                => 'Office Workstations (20 units)',
                'description'                         => 'Ergonomic standing desks with chairs for open-plan office',
                'category'                            => 'Furniture',
                'purchase_date'                       => Carbon::now()->subMonths(10)->format('Y-m-d'),
                'in_service_date'                     => Carbon::now()->subMonths(10)->addDays(3)->format('Y-m-d'),
                'purchase_cost'                       => 1200000.00,
                'residual_value'                      => 120000.00,
                'useful_life_months'                  => 84,
                'depreciation_method'                 => 'straight_line',
                'asset_account_id'                    => $acct('166'), // Furniture and Fixtures
                'depreciation_expense_account_id'     => $acct('710'),
                'accumulated_depreciation_account_id' => $acct('172'),
                'notes'                               => 'Supplier: Office Solutions Pvt Ltd. 3-year warranty on hydraulic mechanisms.',
            ],

            // ── Building ──
            [
                'name'                                => 'Warehouse - I-9 Industrial Area',
                'description'                         => '5,000 sqft warehouse facility for inventory storage',
                'category'                            => 'Building',
                'purchase_date'                       => Carbon::now()->subYears(2)->format('Y-m-d'),
                'in_service_date'                     => Carbon::now()->subYears(2)->addMonths(1)->format('Y-m-d'),
                'purchase_cost'                       => 25000000.00,
                'residual_value'                      => 10000000.00,
                'useful_life_months'                  => 240,
                'depreciation_method'                 => 'straight_line',
                'asset_account_id'                    => $acct('162'), // Buildings
                'depreciation_expense_account_id'     => $acct('710'),
                'accumulated_depreciation_account_id' => $acct('171'), // Accum Depr - Buildings
                'notes'                               => 'Property deed #WH-I9-2023. Annual property tax: PKR 150,000. Security system installed.',
            ],

            // ── Intangible ──
            [
                'name'                                => 'SAP Business One License',
                'description'                         => 'ERP software perpetual license for 25 named users',
                'category'                            => 'Intangible',
                'purchase_date'                       => Carbon::now()->subMonths(12)->format('Y-m-d'),
                'in_service_date'                     => Carbon::now()->subMonths(11)->format('Y-m-d'),
                'purchase_cost'                       => 3500000.00,
                'residual_value'                      => 0.00,
                'useful_life_months'                  => 60,
                'depreciation_method'                 => 'straight_line',
                'asset_account_id'                    => $acct('181'), // Software Licenses
                'depreciation_expense_account_id'     => $acct('720'), // Amortization Expense
                'accumulated_depreciation_account_id' => $acct('172'),
                'notes'                               => 'License Key: SAP-B1-ENT-2025-XXX. Annual maintenance: PKR 700,000 (separate bill).',
            ],

            // ── Land ──
            [
                'name'                                => 'Plot - DHA Phase 5, Islamabad',
                'description'                         => '1 Kanal commercial plot for future office expansion',
                'category'                            => 'Land',
                'purchase_date'                       => Carbon::now()->subYears(1)->format('Y-m-d'),
                'in_service_date'                     => Carbon::now()->subYears(1)->format('Y-m-d'),
                'purchase_cost'                       => 45000000.00,
                'residual_value'                      => 45000000.00, // Land doesn't depreciate
                'useful_life_months'                  => 600,         // Very long life
                'depreciation_method'                 => 'straight_line',
                'asset_account_id'                    => $acct('161'), // Land
                'depreciation_expense_account_id'     => $acct('710'),
                'accumulated_depreciation_account_id' => $acct('172'),
                'notes'                               => 'Plot #DHA-5-C-42. Registry deed filed. No depreciation (residual = cost).',
            ],
        ];

        foreach ($fixedAssets as $data) {
            $this->createFixedAsset($data, $companyId, $userId, $period->id, $now);
        }

        $this->command->info("Created " . count($fixedAssets) . " fixed assets");

        // ─── Summary ────────────────────────────────────────────────
        $this->command->info('');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('  Accounting test data seeded!');
        $this->command->info("  Recurring Invoices: " . count($recurringInvoices));
        $this->command->info("  Recurring Bills:    " . count($recurringBills));
        $this->command->info("  Fixed Assets:       " . count($fixedAssets));
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    }

    // ════════════════════════════════════════════════════════════════
    //  HELPER: Create Sample Customers
    // ════════════════════════════════════════════════════════════════
    private function ensureCustomers(int $companyId, int $userId, $now): array
    {
        $customersData = [
            ['name' => 'TechCorp Solutions Pvt Ltd',     'email' => 'billing@techcorp.pk',      'phone' => '+92-51-2345678',  'address' => '12-B, Blue Area, Islamabad'],
            ['name' => 'GreenEnergy Pakistan Ltd',       'email' => 'accounts@greenenergy.pk',  'phone' => '+92-42-8765432',  'address' => '45 Gulberg III, Lahore'],
            ['name' => 'DataSafe Hosting Inc',           'email' => 'finance@datasafe.pk',      'phone' => '+92-21-3456789',  'address' => '78 Clifton Block 9, Karachi'],
            ['name' => 'BlueOcean Traders International', 'email' => 'ap@blueocean-traders.pk', 'phone' => '+92-51-9876543',  'address' => 'Plot 5, I-8 Markaz, Islamabad'],
            ['name' => 'PakSteel Manufacturing Co',      'email' => 'procurement@paksteel.pk',  'phone' => '+92-55-1234567',  'address' => 'Industrial Area, GT Road, Gujranwala'],
        ];

        $customers = [];
        foreach ($customersData as $c) {
            $customers[] = Customer::firstOrCreate(
                ['email' => $c['email'], 'company_id' => $companyId],
                array_merge($c, ['company_id' => $companyId, 'created_at' => $now, 'updated_at' => $now])
            );
        }

        $this->command->info("Customers ready: " . count($customers) . " records");
        return $customers;
    }

    // ════════════════════════════════════════════════════════════════
    //  HELPER: Create Sample Vendors
    // ════════════════════════════════════════════════════════════════
    private function ensureVendors(int $companyId, int $userId, $now): array
    {
        $vendorsData = [
            ['name' => 'Tower 42 Property Management',   'email' => 'leasing@tower42.pk',       'phone' => '+92-51-1111111',  'address' => 'Tower 42, Blue Area, Islamabad'],
            ['name' => 'PTCL Business Solutions',         'email' => 'corporate@ptcl.net.pk',    'phone' => '+92-51-2222222',  'address' => 'PTCL HQ, G-8/4, Islamabad'],
            ['name' => 'State Life Insurance Corp',       'email' => 'claims@statelife.gov.pk',  'phone' => '+92-51-3333333',  'address' => 'State Life Building, Jinnah Avenue'],
            ['name' => 'CleanPro Facility Services',      'email' => 'ops@cleanpro.pk',          'phone' => '+92-51-4444444',  'address' => 'Office 12, F-10 Markaz, Islamabad'],
            ['name' => 'SoftZone IT Distributors',        'email' => 'sales@softzone.pk',        'phone' => '+92-51-5555555',  'address' => '22-A, Jinnah Super Market, F-7'],
        ];

        $vendors = [];
        foreach ($vendorsData as $v) {
            $vendors[] = Vendor::firstOrCreate(
                ['email' => $v['email'], 'company_id' => $companyId],
                array_merge($v, ['company_id' => $companyId, 'created_at' => $now, 'updated_at' => $now])
            );
        }

        $this->command->info("Vendors ready: " . count($vendors) . " records");
        return $vendors;
    }

    // ════════════════════════════════════════════════════════════════
    //  HELPER: Create Recurring Invoice with Items
    // ════════════════════════════════════════════════════════════════
    private function createRecurringInvoice(array $data, int $companyId, int $userId, int $periodId, $now): void
    {
        DB::transaction(function () use ($data, $companyId, $userId, $periodId, $now) {
            $items = $data['items'];
            unset($data['items']);

            // Calculate totals
            $subTotal = 0;
            $taxTotal = 0;
            $discountTotal = 0;

            foreach ($items as &$item) {
                $rowSub = $item['quantity'] * $item['price'];
                $rowDiscount = $rowSub * ($item['discount_percentage'] / 100);
                $afterDiscount = $rowSub - $rowDiscount;
                $taxRate = $item['tax_id'] ? TaxRate::find($item['tax_id'])->tax_rate : 0;
                $rowTax = $afterDiscount * ($taxRate / 100);

                $item['sub_total'] = round($rowSub, 2);
                $item['tax_amount'] = round($rowTax, 2);
                $item['total'] = round($afterDiscount + $rowTax, 2);

                $subTotal += $rowSub;
                $taxTotal += $rowTax;
                $discountTotal += $rowDiscount;
            }

            $invoice = RecurringInvoice::create(array_merge($data, [
                'company_id'           => $companyId,
                'accounting_period_id' => $periodId,
                'next_run_date'        => $data['start_date'],
                'status'               => 'active',
                'sub_total'            => round($subTotal, 2),
                'tax'                  => round($taxTotal, 2),
                'discount'             => round($discountTotal, 2),
                'total'                => round($subTotal - $discountTotal + $taxTotal, 2),
                'invoices_generated'   => 0,
                'created_by'           => $userId,
                'updated_by'           => $userId,
            ]));

            foreach ($items as $item) {
                RecurringInvoiceItem::create(array_merge($item, [
                    'recurring_invoice_id' => $invoice->id,
                ]));
            }

            $this->command->line("  + {$invoice->title} ({$invoice->frequency}, PKR " . number_format($invoice->total) . ")");
        });
    }

    // ════════════════════════════════════════════════════════════════
    //  HELPER: Create Recurring Bill with Items
    // ════════════════════════════════════════════════════════════════
    private function createRecurringBill(array $data, int $companyId, int $userId, int $periodId, $now): void
    {
        DB::transaction(function () use ($data, $companyId, $userId, $periodId, $now) {
            $items = $data['items'];
            unset($data['items']);

            $subTotal = 0;
            $taxTotal = 0;
            $discountTotal = 0;

            foreach ($items as &$item) {
                $rowSub = $item['quantity'] * $item['price'];
                $rowDiscount = $rowSub * ($item['discount_percentage'] / 100);
                $afterDiscount = $rowSub - $rowDiscount;
                $taxRate = $item['tax_id'] ? TaxRate::find($item['tax_id'])->tax_rate : 0;
                $rowTax = $afterDiscount * ($taxRate / 100);

                $item['sub_total'] = round($rowSub, 2);
                $item['tax_amount'] = round($rowTax, 2);
                $item['total'] = round($afterDiscount + $rowTax, 2);

                $subTotal += $rowSub;
                $taxTotal += $rowTax;
                $discountTotal += $rowDiscount;
            }

            $bill = RecurringBill::create(array_merge($data, [
                'company_id'           => $companyId,
                'accounting_period_id' => $periodId,
                'next_run_date'        => $data['start_date'],
                'status'               => 'active',
                'sub_total'            => round($subTotal, 2),
                'tax'                  => round($taxTotal, 2),
                'discount'             => round($discountTotal, 2),
                'total'                => round($subTotal - $discountTotal + $taxTotal, 2),
                'bills_generated'      => 0,
                'created_by'           => $userId,
                'updated_by'           => $userId,
            ]));

            foreach ($items as $item) {
                RecurringBillItem::create(array_merge($item, [
                    'recurring_bill_id' => $bill->id,
                ]));
            }

            $this->command->line("  + {$bill->title} ({$bill->frequency}, PKR " . number_format($bill->total) . ")");
        });
    }

    // ════════════════════════════════════════════════════════════════
    //  HELPER: Create Fixed Asset
    // ════════════════════════════════════════════════════════════════
    private function createFixedAsset(array $data, int $companyId, int $userId, int $periodId, $now): void
    {
        $asset = FixedAsset::create(array_merge($data, [
            'company_id'                => $companyId,
            'accounting_period_id'      => $periodId,
            'asset_number'              => FixedAsset::generateAssetNumber(),
            'current_book_value'        => $data['purchase_cost'],
            'accumulated_depreciation'  => 0,
            'status'                    => 'active',
            'created_by'                => $userId,
            'updated_by'                => $userId,
        ]));

        // Calculate monthly depreciation for display
        if ($data['depreciation_method'] === 'straight_line') {
            $monthly = ($data['purchase_cost'] - $data['residual_value']) / $data['useful_life_months'];
        } else {
            $annualRate = 2 / ($data['useful_life_months'] / 12);
            $monthly = $data['purchase_cost'] * $annualRate / 12;
        }

        $this->command->line("  + {$asset->asset_number} | {$asset->name} | PKR " . number_format($data['purchase_cost']) . " | ~PKR " . number_format($monthly) . "/mo depr.");
    }
}
