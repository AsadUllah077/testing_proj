<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Request as RequestFacade;

/**
 * Customer Aged Receivable (AR Aging) — global accounting-standard report.
 *
 * Scope: TRADE CUSTOMERS ONLY.
 *   - Accounts: Asset accounts containing "Receivable" in the name,
 *               EXCLUDING any statutory/tax accounts.
 *   - Entities: customer_id-driven only; rows without a customer are ignored.
 *   - Aging:    invoice-level, bucketed by age_days = asOf - MIN(transaction_date)
 *               per unique_no (invoice number).
 *
 * This matches how QuickBooks / Xero / SAP / NetSuite present AR Aging:
 * trade debtors only, separate from tax-authority receivables.
 */
class CustomerAgedReceivableService
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

        // --- Build the TRADE-RECEIVABLE account whitelist -----------------------
        $receivableAccounts = ChartOfAccount::forCompany($companyId)
            ->where(function ($query) {
                $query->where('sub_type', 'RECEIVABLE')
                    ->orWhere(function ($q) {
                        $q->where('category', 'Assets')
                            ->where('name', 'LIKE', '%Receivable%');
                    });
            })
            ->pluck('id')
            ->toArray();

        // Exclude tax-related accounts defensively (rare on the asset side, but safe).
        $taxAccounts = array_filter([
            ChartOfAccount::getSalesTaxPayableId($companyId),
            ChartOfAccount::getPurchaseTaxAccountId($companyId),
        ]);

        $accounts = array_values(array_diff($receivableAccounts, $taxAccounts));

        if (empty($accounts)) {
            return $this->emptyResult($asOf, $fromDate, $period, $groupBy, $labels, $bucketRanges, $perPage);
        }

        // --- Query: aggregate per (account, customer, invoice) ------------------
        $rows = Transaction::query()
            ->selectRaw("
                account_id,
                customer_id,
                unique_no,
                MIN(date) as invoice_date,
                SUM(debit) as total_debit,
                SUM(credit) as total_credit,
                (?::date - MIN(date))::int as age_days
            ", [$asOf])
            ->whereIn('account_id', $accounts)
            ->where('company_id', $companyId)
            ->whereNotNull('customer_id')
            ->whereDate('date', '<=', $asOf)
            ->when($fromDate, fn($q) => $q->whereDate('date', '>=', $fromDate))
            ->groupBy('account_id', 'customer_id', 'unique_no')
            ->get();

        // --- Batch-load names ---------------------------------------------------
        $customerIds = $rows->pluck('customer_id')->unique()->filter()->all();
        $customers = $customerIds
            ? Customer::where('company_id', $companyId)->whereIn('id', $customerIds)->pluck('name', 'id')
            : collect();

        $accountIds = $rows->pluck('account_id')->unique()->filter()->all();
        $accountNames = ChartOfAccount::forCompany($companyId)->whereIn('id', $accountIds)->pluck('name', 'id');

        // --- Aggregate per customer --------------------------------------------
        $reportData = [];
        $grandTotals = array_fill(0, count($bucketRanges), 0);
        $grandTotal = 0;

        foreach ($rows as $row) {
            $balance = $row->total_debit - $row->total_credit; // asset = debit - credit
            if ((float) $balance == 0.0) {
                continue;
            }

            $customerId = (int) $row->customer_id;
            $entityKey = 'customer:' . $customerId;
            $bucketIndex = $this->bucketIndex((int) $row->age_days, $bucketRanges);
            $bucketVals = array_fill(0, count($bucketRanges), 0);
            $bucketVals[$bucketIndex] = $balance;

            if (! isset($reportData[$entityKey])) {
                $reportData[$entityKey] = [
                    'entity_type' => 'Customer',
                    'name' => $customers[$customerId] ?? 'Unknown Customer',
                    'vendor' => $customers[$customerId] ?? 'Unknown Customer', // legacy alias for shared PDF blade
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

        uasort($reportData, fn($a, $b) => strcasecmp($a['name'], $b['name']));
        $entities = array_values($reportData);

        // --- Paginate AFTER aggregation ----------------------------------------
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
