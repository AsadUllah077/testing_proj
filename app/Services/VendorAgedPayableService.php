<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\PayItem;
use App\Models\Transaction;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Request as RequestFacade;

/**
 * Vendor Aged Payable (AP Aging) — global accounting-standard report.
 *
 * Scope: TRADE VENDORS ONLY.
 *   - Accounts: Liability accounts containing "Payable" in the name,
 *               EXCLUDING statutory accounts (Sales/Purchase Tax) and
 *               payroll-related PayItem accounts (Wages, EPF, SOCSO, PAYE, etc.).
 *   - Entities: only records whose polymorphic payable_type resolves to Vendor.
 *   - Aging:    invoice-level, bucketed by age_days = asOf - MIN(transaction_date)
 *               per unique_no (invoice/bill number).
 *
 * This matches how QuickBooks / Xero / SAP / NetSuite present AP Aging:
 * trade creditors only, separate from payroll and tax-authority liabilities.
 */
class VendorAgedPayableService
{
    public function resolveBuckets(string $period): array
    {
        return match ($period) {
            '4x1' => [
                [[0, 30], [31, 60], [61, 90], [91, 9999]],
                ['0-30 Days', '31-60 Days', '61-90 Days', '90+ Days'],
            ],
            '5x15' => [
                [[0, 15], [16, 30], [31, 45], [46, 60], [61, 9999]],
                ['0-15', '16-30', '31-45', '46-60', '60+'],
            ],
            '6x1' => [
                [[0, 30], [31, 60], [61, 90], [91, 120], [121, 150], [151, 9999]],
                ['0-30', '31-60', '61-90', '91-120', '121-150', '150+'],
            ],
            default => [
                [[0, 30], [31, 60], [61, 90], [91, 9999]],
                ['0-30 Days', '31-60 Days', '61-90 Days', '90+ Days'],
            ],
        };
    }

    public function getAgedData(
        string $asOf,
        string $period = '4x1',
        ?string $fromDate = null,
        int $perPage = 20,
        string $groupBy = 'unique_no'
    ): array {
        [$bucketRanges, $labels] = $this->resolveBuckets($period);
        $companyId = loggedCompanyId()['company_id'];

        // --- Build the TRADE-PAYABLE account whitelist -------------------------
        // Get all accounts marked as PAYABLE or having name matches.
        $payableAccounts = ChartOfAccount::forCompany($companyId)
            ->where(function ($query) {
                $query->where('sub_type', 'PAYABLE')
                    ->orWhere(function ($q) {
                        $q->where('category', 'Liabilities')
                            ->where('name', 'LIKE', '%Payable%');
                    });
            })
            ->pluck('id')
            ->toArray();

        // Exclude statutory accounts: sales/purchase tax + all PayItem (payroll) accounts.
        //
        // NOTE: We deliberately do NOT call ChartOfAccount::getWagesPayableAccount() here.
        // That helper falls back to matching "%Accounts Payable%" when no dedicated
        // "Wages Payable" account exists, which would incorrectly exclude the main
        // trade AP account. If a real Wages Payable account exists, it should already
        // be linked via PayItem::account_id and captured below.
        $statutoryAccounts = array_unique(array_filter(array_merge(
            array_filter([
                ChartOfAccount::getSalesTaxPayableId($companyId),
                ChartOfAccount::getPurchaseTaxAccountId($companyId),
            ]),
            PayItem::where('company_id', $companyId)->pluck('account_id')->toArray()
        )));

        $accounts = array_values(array_diff($payableAccounts, $statutoryAccounts));

        if (empty($accounts)) {
            return $this->emptyResult($asOf, $fromDate, $period, $groupBy, $labels, $bucketRanges, $perPage);
        }

        // --- Query: aggregate per (account, vendor, invoice) --------------------
        $vendorMorphAliases = $this->vendorMorphAliases();

        $rows = Transaction::query()
            ->selectRaw("
                account_id,
                payable_type,
                payable_id,
                unique_no,
                MIN(date) as invoice_date,
                SUM(debit) as total_debit,
                SUM(credit) as total_credit,
                (?::date - MIN(date))::int as age_days
            ", [$asOf])
            ->whereIn('account_id', $accounts)
            ->where('company_id', $companyId)
            ->whereIn('payable_type', $vendorMorphAliases)
            ->whereNotNull('payable_id')
            ->whereDate('date', '<=', $asOf)
            ->when($fromDate, fn($q) => $q->whereDate('date', '>=', $fromDate))
            ->groupBy('account_id', 'payable_type', 'payable_id', 'unique_no')
            ->get();

        // --- Batch-load vendor names --------------------------------------------
        $vendorIds = $rows->pluck('payable_id')->unique()->filter()->all();
        $vendors = $vendorIds
            ? Vendor::where('company_id', $companyId)->whereIn('id', $vendorIds)->pluck('name', 'id')
            : collect();

        $accountIds = $rows->pluck('account_id')->unique()->filter()->all();
        $accountNames = ChartOfAccount::forCompany($companyId)->whereIn('id', $accountIds)->pluck('name', 'id');

        // --- Aggregate per vendor, filter zero balances -------------------------
        $reportData = [];
        $grandTotals = array_fill(0, count($bucketRanges), 0);
        $grandTotal = 0;

        foreach ($rows as $row) {
            $balance = $row->total_credit - $row->total_debit; // liability = credit - debit
            if ((float) $balance == 0.0) {
                continue;
            }

            $vendorId = (int) $row->payable_id;
            $entityKey = 'vendor:' . $vendorId;
            $bucketIndex = $this->bucketIndex((int) $row->age_days, $bucketRanges);
            $bucketVals = array_fill(0, count($bucketRanges), 0);
            $bucketVals[$bucketIndex] = $balance;

            if (! isset($reportData[$entityKey])) {
                $reportData[$entityKey] = [
                    'entity_type' => 'Vendor',
                    'name' => $vendors[$vendorId] ?? 'Unknown Vendor',
                    'vendor' => $vendors[$vendorId] ?? 'Unknown Vendor',
                    'rows' => [],
                    'totals' => array_fill(0, count($bucketRanges), 0),
                    'total' => 0,
                ];
            }

            $reportData[$entityKey]['rows'][] = [
                'invoice_no' => $row->unique_no,
                'invoice_date' => $row->invoice_date,
                'buckets' => $bucketVals,
                'total' => $balance,
                'account_name' => $accountNames[$row->account_id] ?? null,
            ];

            foreach ($bucketVals as $i => $val) {
                $reportData[$entityKey]['totals'][$i] += $val;
                $grandTotals[$i] += $val;
            }
            $reportData[$entityKey]['total'] += $balance;
            $grandTotal += $balance;
        }

        // Sort vendors alphabetically
        uasort($reportData, fn($a, $b) => strcasecmp($a['name'], $b['name']));
        $entities = array_values($reportData);

        // --- Paginate AFTER aggregation (one page = N vendors, not N rows) ------
        $page = Paginator::resolveCurrentPage('page');
        $total = count($entities);
        $slice = array_slice($entities, ($page - 1) * $perPage, $perPage);

        $paginator = new LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            [
                'path' => RequestFacade::url(),
                'query' => RequestFacade::query(),
            ]
        );

        return [
            'reportData' => $paginator,
            'grandTotals' => $grandTotals,
            'grandTotal' => $grandTotal,
            'labels' => $labels,
            'as_of_date' => $asOf,
            'from_date' => $fromDate,
            'period' => $period,
            'groupBy' => $groupBy,
        ];
    }

    private function emptyResult(
        string $asOf,
        ?string $fromDate,
        string $period,
        string $groupBy,
        array $labels,
        array $bucketRanges,
        int $perPage
    ): array {
        $paginator = new LengthAwarePaginator([], 0, $perPage, 1, [
            'path' => RequestFacade::url(),
            'query' => RequestFacade::query(),
        ]);

        return [
            'reportData' => $paginator,
            'grandTotals' => array_fill(0, count($bucketRanges), 0),
            'grandTotal' => 0,
            'labels' => $labels,
            'as_of_date' => $asOf,
            'from_date' => $fromDate,
            'period' => $period,
            'groupBy' => $groupBy,
        ];
    }

    /**
     * Every alias Laravel might store for the Vendor morph (FQCN, morph-map key, etc.).
     */
    private function vendorMorphAliases(): array
    {
        $aliases = [Vendor::class];
        foreach (Relation::morphMap() as $alias => $class) {
            if ($class === Vendor::class) {
                $aliases[] = $alias;
            }
        }

        return array_unique($aliases);
    }

    private function bucketIndex(int $age, array $ranges): int
    {
        foreach ($ranges as $i => [$from, $to]) {
            if ($age >= $from && $age <= $to) {
                return $i;
            }
        }

        return count($ranges) - 1;
    }
}
