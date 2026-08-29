<?php

namespace App\Imports;

use App\Models\ChartOfAccount;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;

class ChartOfAccountImport implements ToCollection, WithHeadingRow
{
    protected $errors = [];
    protected ?int $companyId;

    public function __construct(?int $companyId = null)
    {
        $this->companyId = $companyId ?? (loggedCompanyId()['company_id'] ?? auth('web')->user()?->company_id);
    }

    public function collection(Collection $rows)
    {
        if ($this->hasCompanyColumn() && ! $this->companyId) {
            $this->errors[] = 'Company is required before importing chart of accounts.';
            return;
        }

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +1 for 0-index, +1 for header row

            // Support both standard Xero and legacy column naming formats
            $code = $row['code'] ?? $row['account_number'] ?? $row['account_code'] ?? null;
            $name = $row['name'] ?? $row['account_name'] ?? null;
            $type = $row['type'] ?? $row['account_type'] ?? 'Expense';
            $description = $row['description'] ?? $row['desc'] ?? null;
            $taxRate = $row['tax_rate'] ?? $row['tax_code'] ?? $row['tax_code_name'] ?? $row['taxrate'] ?? null;
            $initialBalance = 0;

            if (empty($code) || empty($name)) {
                $this->errors[] = "Row {$rowNumber}: Code and Name are required.";
                continue;
            }

            // Find parent if specified
            $parentId = null;
            $parentName = $row['parent_account_name'] ?? $row['parent'] ?? null;
            if (!empty($parentName)) {
                $parent = ChartOfAccount::withoutGlobalScopes()
                    ->where('name', $parentName)
                    ->when($this->hasCompanyColumn(), fn ($query) => $query->where('company_id', $this->companyId))
                    ->first();
                if ($parent) {
                    $parentId = $parent->id;
                } else {
                    $this->errors[] = "Row {$rowNumber}: Parent account '{$parentName}' not found.";
                    continue;
                }
            }

            // Skip only when this account code already exists for the import company.
            $existing = ChartOfAccount::withoutGlobalScopes()
                ->where('account_number', $code)
                ->when(
                    $this->hasCompanyColumn(),
                    fn ($query) => $query->where('company_id', $this->companyId)
                )
                ->first();
            if ($existing) {
                continue;
            }

            // Normalize the Xero Type string to the exact standard names used in the NewChartOfAccountSeeder matching logic
            $normalizedType = $this->normalizeXeroType($type);
            $category = $this->mapCategory($normalizedType);
            $subType = $this->mapSubType($normalizedType);
            $systemAccountType = $this->mapSystemAccountType((string) $code);

            try {
                $payload = [
                    'account_number'      => $code,
                    'name'                => $name,
                    'type'                => 'Account',
                    'category'            => $category,
                    'system_account_type' => $systemAccountType,
                    'sub_type'            => $subType,
                    'description'         => $description,
                    'tax_code'            => $taxRate ?? ($category === 'Expenses' || $category === 'COGS' ? 'Tax on Purchases (0%)' : 'Tax Exempt (0%)'),
                    'parent_id'           => $parentId,
                    'initial_balance'     => 0,
                    'opening_date'        => now(),
                ];

                if ($this->hasCompanyColumn()) {
                    $payload['company_id'] = $this->companyId;
                }

                $account = ChartOfAccount::create($payload);
            } catch (\Exception $e) {
                Log::error("Import Error at Row {$rowNumber}: " . $e->getMessage());
                $this->errors[] = "Row {$rowNumber}: " . $e->getMessage();
            }
        }
    }

    /**
     * Normalize variations of Xero types to the exact strings matching the Seeder
     */
    private function normalizeXeroType(string $type): string
    {
        $typeClean = strtolower(str_replace([' ', '_', '-'], '', $type));

        return match ($typeClean) {
            'bank' => 'Bank',
            'fixedasset', 'fixedassets', 'fixed' => 'Fixed Asset',
            'noncurrentasset', 'noncurrentassets', 'noncurrent' => 'Non-current Asset',
            'accountsreceivable', 'receivable' => 'Accounts Receivable',
            'inventory' => 'Inventory',
            'currentasset', 'currentassets', 'asset', 'assets' => 'Current Asset',
            'accountspayable', 'payable' => 'Accounts Payable',
            'currentliability', 'currentliabilities', 'liability', 'liabilities' => 'Current Liability',
            'noncurrentliability', 'noncurrentliabilities' => 'Non-current Liability',
            'wagespayable' => 'Wages Payable',
            'unpaidexpenseclaims' => 'Unpaid Expense Claims',
            'salestax' => 'Sales Tax',
            'equity' => 'Equity',
            'retainedearnings' => 'Retained Earnings',
            'revenue', 'revenues', 'income', 'sales' => 'Revenue',
            'directcosts', 'directcost', 'cogs', 'costofgoodsold' => 'Direct Costs',
            'rounding' => 'Rounding',
            'trackingtransfers' => 'Tracking Transfers',
            'expense', 'expenses', 'operatingexpense', 'operatingexpenses' => 'Expense',
            default => 'Expense',
        };
    }

    /**
     * Map type to category like NewChartOfAccountSeeder
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
     * Map type to sub_type like NewChartOfAccountSeeder
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
     * Map accountNumber to system_account_type like NewChartOfAccountSeeder
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
            '399' => 'OPENING_BALANCE_EQUITY',
            '950' => 'RETAINED_EARNINGS',
            default => null,
        };
    }

    private function postOpeningBalanceEntry(ChartOfAccount $account, float $amount): void
    {
        $obeId = ChartOfAccount::getOpeningBalanceEquityId();
        if (! $obeId) {
            Log::warning("Opening Balance Equity account not found — skipping opening balance entry for account {$account->id}");
            return;
        }

        $ref = 'OBE-' . str_pad($account->id, 5, '0', STR_PAD_LEFT);

        if (DB::table('transactions')->where('unique_no', $ref)->exists()) {
            return; // Already posted
        }

        $period = DB::table('accounting_periods')
            ->where('is_active', true)
            ->orderBy('start_date')
            ->first();

        $date     = $period ? $period->start_date : now()->toDateString();
        $periodId = $period?->id;
        $companyId = auth('web')->user()?->company_id;

        $isDebitNormal = in_array($account->category, ['Assets', 'Expenses', 'COGS']);

        DB::table('transactions')->insert([
            [
                'unique_no'            => $ref,
                'date'                 => $date,
                'account_id'           => $account->id,
                'debit'                => $isDebitNormal ? $amount : 0,
                'credit'               => $isDebitNormal ? 0 : $amount,
                'description'          => 'Opening balance — ' . $account->name,
                'accounting_period_id' => $periodId,
                'type'                 => 'OpeningBalance',
                'company_id'           => $companyId,
                'created_by'           => auth('web')->id(),
                'updated_by'           => auth('web')->id(),
                'created_at'           => now(),
                'updated_at'           => now(),
            ],
            [
                'unique_no'            => $ref,
                'date'                 => $date,
                'account_id'           => $obeId,
                'debit'                => $isDebitNormal ? 0 : $amount,
                'credit'               => $isDebitNormal ? $amount : 0,
                'description'          => 'Opening balance — ' . $account->name,
                'accounting_period_id' => $periodId,
                'type'                 => 'OpeningBalance',
                'company_id'           => $companyId,
                'created_by'           => auth('web')->id(),
                'updated_by'           => auth('web')->id(),
                'created_at'           => now(),
                'updated_at'           => now(),
            ],
        ]);
    }

    public function getErrors()
    {
        return $this->errors;
    }

    private function hasCompanyColumn(): bool
    {
        return Schema::hasColumn('chart_of_accounts', 'company_id');
    }
}
