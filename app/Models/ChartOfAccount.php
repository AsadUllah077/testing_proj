<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChartOfAccount extends Model
{
    use BelongsToCompany, HasFactory, LogsActivity, SoftDeletes, Sortable;

    protected $fillable = [
        'account_number',
        'company_id',
        'acc_no',
        'name',
        'parent_id',
        'category',
        'system_account_type',
        'sub_type',
        'type',
        'tax_code',
        'description',
        'initial_balance',
        'opening_date',
        'is_system',
    ];

    public $defaultSort = 'account_number';

    public $sortable = ['id', 'name', 'account_number', 'category', 'type', 'initial_balance', 'opening_date', 'tax_code'];

    public function parent()
    {
        return $this->belongsTo(ChartOfAccount::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ChartOfAccount::class, 'parent_id');
    }

    public function childrenRecursive()
    {
        return $this->children()->with('childrenRecursive');
    }

    public function chartOfAccountLogs()
    {
        return $this->hasMany(ChartOfAccountLog::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'account_id');
    }

    public function bankStatementLines()
    {
        return $this->hasMany(BankStatementLine::class, 'bank_account_id');
    }

    public function scopeForCompany($query, ?int $companyId = null)
    {
        if (! self::hasCompanyColumn()) {
            return $query;
        }

        $companyId ??= self::currentCompanyId();

        return $companyId
            ? $query->withoutGlobalScope('company')->where('company_id', $companyId)
            : $query;
    }

    private static function companyQuery(?int $companyId = null)
    {
        $companyId ??= self::currentCompanyId();

        return self::query()->forCompany($companyId);
    }

    private static function hasCompanyColumn(): bool
    {
        static $hasCompanyColumn = null;

        if ($hasCompanyColumn === null) {
            $hasCompanyColumn = \Illuminate\Support\Facades\Schema::hasColumn('chart_of_accounts', 'company_id');
        }

        return $hasCompanyColumn;
    }

    private static function currentCompanyId(): ?int
    {
        try {
            return loggedCompanyId()['company_id'] ?? auth('web')->user()?->company_id;
        } catch (\Throwable) {
            return null;
        }
    }

    // =========================================================================
    // Xero-style system account lookups — resolve by ROLE, not number or name.
    // The system_account_type column tags ~10 critical accounts.
    // =========================================================================

    public static function getAccountsReceivableId(?int $companyId = null)
    {
        return self::companyQuery($companyId)->where('system_account_type', 'ACCOUNTS_RECEIVABLE')->value('id');
    }

    public static function getAccountsPayableId(?int $companyId = null)
    {
        return self::companyQuery($companyId)->where('system_account_type', 'ACCOUNTS_PAYABLE')->value('id');
    }

    public static function getSalesTaxPayableId(?int $companyId = null)
    {
        return self::getSalesTaxPayableIds($companyId)->first();
    }

    public static function getPurchaseTaxAccountId(?int $companyId = null)
    {
        return self::getPurchaseTaxAccountIds($companyId)->first();
    }

    public static function getSalesTaxPayableIds(?int $companyId = null)
    {
        return self::taxAccountIds(
            'SALES_TAX',
            ['803', '241', '245'],
            ['sales tax', 'output tax', 'vat output', 'gst output'],
            $companyId
        );
    }

    public static function getPurchaseTaxAccountIds(?int $companyId = null)
    {
        return self::taxAccountIds(
            'PURCHASE_TAX',
            ['152', '154'],
            ['purchase tax', 'input tax', 'vat input', 'gst input', 'tax refund receivable'],
            $companyId
        );
    }

    public static function getTaxPayableLiabilityId(?int $companyId = null)
    {
        $salesTaxPayableIds = self::getSalesTaxPayableIds($companyId);

        // First, look for exact name matches (priority 1)
        $exactMatches = self::companyQuery($companyId)
            ->where(function ($query) {
                $query->whereRaw('LOWER(name) = ?', ['tax payable'])
                    ->orWhereRaw('LOWER(name) = ?', ['tax payable liability'])
                    ->orWhereRaw('LOWER(name) = ?', ['vat payable'])
                    ->orWhereRaw('LOWER(name) = ?', ['gst payable']);
            })
            ->where('category', 'Liabilities')
            ->whereNotIn('id', $salesTaxPayableIds)
            ->orderBy('account_number', 'desc')
            ->get();

        if ($exactMatches->isNotEmpty()) {
            return $exactMatches->first()->id;
        }

        // Next, look for partial matches (priority 2)
        $partialMatches = self::companyQuery($companyId)
            ->where(function ($query) {
                $query->where('system_account_type', 'SALES_TAX')
                    ->orWhereIn('account_number', ['242', '246'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%sales tax payable%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%vat payable%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%gst payable%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%output tax payable%']);
            })
            ->where('category', 'Liabilities')
            ->whereNotIn('id', $salesTaxPayableIds)
            ->orderBy('account_number', 'desc')
            ->get();

        // Include child accounts too
        $partialMatchIds = $partialMatches->pluck('id')->toArray();
        $allAccounts = self::companyQuery($companyId)
            ->where(function ($query) use ($partialMatchIds) {
                $query->whereIn('id', $partialMatchIds)
                    ->orWhereIn('parent_id', $partialMatchIds);
            })
            ->where('category', 'Liabilities')
            ->whereNotIn('id', $salesTaxPayableIds)
            ->orderBy('account_number', 'desc')
            ->get();

        if ($allAccounts->isNotEmpty()) {
            return $allAccounts->first()->id;
        }

        // If no separate account found, throw exception instead of using sales tax account
        throw new \Exception('No separate Tax Payable liability account found. Please configure a Tax Payable, VAT Payable, or GST Payable account.');
    }

    public static function getTaxRecoverableAssetId(?int $companyId = null)
    {
        $purchaseTaxAccountIds = self::getPurchaseTaxAccountIds($companyId);

        // First, look for exact name matches (priority 1)
        $exactMatches = self::companyQuery($companyId)
            ->where(function ($query) {
                $query->whereRaw('LOWER(name) = ?', ['tax recoverable'])
                    ->orWhereRaw('LOWER(name) = ?', ['tax recoverable asset'])
                    ->orWhereRaw('LOWER(name) = ?', ['tax refund due'])
                    ->orWhereRaw('LOWER(name) = ?', ['vat refund due'])
                    ->orWhereRaw('LOWER(name) = ?', ['gst refund due']);
            })
            ->where('category', 'Assets')
            ->whereNotIn('id', $purchaseTaxAccountIds)
            ->orderBy('account_number', 'desc')
            ->get();

        if ($exactMatches->isNotEmpty()) {
            return $exactMatches->first()->id;
        }

        // Next, look for partial matches (priority 2)
        $partialMatches = self::companyQuery($companyId)
            ->where(function ($query) {
                $query->where('system_account_type', 'PURCHASE_TAX')
                    ->orWhereIn('account_number', ['153', '155'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%tax recoverable%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%vat refund due%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%gst refund due%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%input tax recoverable%']);
            })
            ->where('category', 'Assets')
            ->whereNotIn('id', $purchaseTaxAccountIds)
            ->orderBy('account_number', 'desc')
            ->get();

        // Include child accounts too
        $partialMatchIds = $partialMatches->pluck('id')->toArray();
        $allAccounts = self::companyQuery($companyId)
            ->where(function ($query) use ($partialMatchIds) {
                $query->whereIn('id', $partialMatchIds)
                    ->orWhereIn('parent_id', $partialMatchIds);
            })
            ->where('category', 'Assets')
            ->whereNotIn('id', $purchaseTaxAccountIds)
            ->orderBy('account_number', 'desc')
            ->get();

        if ($allAccounts->isNotEmpty()) {
            return $allAccounts->first()->id;
        }

        // If no separate account found, throw exception instead of using purchase tax account
        throw new \Exception('No separate Tax Recoverable asset account found. Please configure a Tax Recoverable, Tax Refund Due, VAT Refund Due, or GST Refund Due account.');
    }

    private static function taxAccountIds(string $systemAccountType, array $accountNumbers, array $namePatterns, ?int $companyId = null)
    {
        $accounts = self::companyQuery($companyId)
            ->where(function ($query) use ($systemAccountType, $accountNumbers, $namePatterns) {
                $query->where('system_account_type', $systemAccountType)
                    ->orWhereIn('account_number', $accountNumbers);

                foreach ($namePatterns as $pattern) {
                    $query->orWhereRaw('LOWER(name) LIKE ?', ['%' . strtolower($pattern) . '%']);
                }
            })
            ->orderBy('account_number', 'asc')
            ->get(['id', 'parent_id', 'account_number']);

        if ($accounts->isEmpty()) {
            return collect();
        }

        $ids = $accounts->pluck('id');
        $childIds = self::companyQuery($companyId)->whereIn('parent_id', $ids)->pluck('id');

        return $ids->merge($childIds)->unique()->values();
    }

    public static function getSalesDiscountAccountId(?int $companyId = null)
    {
        return self::companyQuery($companyId)->where('system_account_type', 'SALES_DISCOUNT')->value('id');
    }

    public static function getPurchaseDiscountAccountId(?int $companyId = null)
    {
        return self::companyQuery($companyId)->where('system_account_type', 'PURCHASE_DISCOUNT')->value('id');
    }

    public static function getWagesPayableAccount(?int $companyId = null)
    {
        return self::companyQuery($companyId)->where('system_account_type', 'WAGES_PAYABLE')->first();
    }

    public static function getEmployeeAdvancesAccount(?int $companyId = null)
    {
        return self::companyQuery($companyId)
            ->where(function ($query) {
                $query->where('system_account_type', 'EMPLOYEE_ADVANCES')
                    ->orWhereRaw('LOWER(name) = ?', ['employee advances'])
                    ->orWhereRaw('LOWER(name) = ?', ['salary advances'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%employee%advance%']);
            })
            ->orderByRaw("CASE WHEN system_account_type = 'EMPLOYEE_ADVANCES' THEN 0 ELSE 1 END")
            ->first();
    }

    public static function getOpeningBalanceEquityId(?int $companyId = null)
    {
        return self::companyQuery($companyId)
            ->where('system_account_type', 'OPENING_BALANCE_EQUITY')
            ->value('id');
    }

    public static function getRetainedEarningsId(?int $companyId = null)
    {
        return self::companyQuery($companyId)->where('system_account_type', 'RETAINED_EARNINGS')->value('id');
    }

    // =========================================================================
    // Sub-type scopes — for reporting (Balance Sheet, Cash Flow, Dashboard)
    // =========================================================================

    public function scopeSubType($query, string|array $subType)
    {
        return $query->whereIn('sub_type', (array) $subType);
    }

    public function scopeSystemAccount($query, string $type)
    {
        return $query->where('system_account_type', $type);
    }

    /**
     * Check if this account is a bank/cash account.
     */
    public function isBankAccount(): bool
    {
        return $this->sub_type === 'BANK';
    }

    /**
     * Check if this account is a non-current (fixed) asset.
     */
    public function isNonCurrentAsset(): bool
    {
        return in_array($this->sub_type, ['FIXED_ASSET', 'NON_CURRENT_ASSET']);
    }

    /**
     * Check if this account is a non-current liability.
     */
    public function isNonCurrentLiability(): bool
    {
        return $this->sub_type === 'NON_CURRENT_LIABILITY';
    }

    public function bills()
    {
        return $this->hasMany(Bill::class);
    }

    public function scopeAssets($query)
    {
        return $query->where('category', 'Assets');
    }

    public function scopeLiabilities($query)
    {
        return $query->where('category', 'Liabilities');
    }

    public function scopeEquity($query)
    {
        return $query->where('category', 'Equity');
    }

    public function scopeRevenue($query)
    {
        return $query->where('category', 'Revenue');
    }

    public function scopeExpenses($query)
    {
        return $query->where('category', 'Expenses');
    }

    public function scopeCOGS($query)
    {
        return $query->where('category', 'COGS');
    }

    public function scopeGroups($query)
    {
        return $query->where('type', 'Group');
    }

    public function scopeAccounts($query)
    {
        return $query->where('type', 'Account');
    }

    // Helper Methods
    public function getBalanceAttribute()
    {
        return $this->calculateBalance();
    }

    public function calculateBalance($asOfDate = null)
    {
        $query = $this->transactions()->where('status', 1);

        if ($asOfDate) {
            $query->where('date', '<=', $asOfDate);
        }

        $transactions = $query->get();
        $balance = 0;

        foreach ($transactions as $transaction) {
            if (in_array($this->category, ['Assets', 'Expenses', 'COGS'])) {
                $balance += ($transaction->debit ?? 0) - ($transaction->credit ?? 0);
            } else {
                $balance += ($transaction->credit ?? 0) - ($transaction->debit ?? 0);
            }
        }

        return $balance;
    }

    public function getFullNameAttribute()
    {
        if ($this->parent) {
            return $this->parent->name . ' - ' . $this->name;
        }
        return $this->name;
    }

    /**
     * Recursively get all child account IDs
     */
    public function getAllChildrenIds(): array
    {
        $ids = [];

        foreach ($this->children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $child->getAllChildrenIds());
        }

        return $ids;
    }
}
