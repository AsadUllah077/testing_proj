<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\PayItem;
use App\Models\Vendor;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Relations\Relation;

class AgedReportService
{
    public function resolveBuckets(string $period): array
    {
        return match ($period) {
            '4x1' => [
                [[0, 30], [31, 60], [61, 90], [91, 9999]],
                ['0-30 Days', '31-60 Days', '61-90 Days', '90+ Days']
            ],
            '5x15' => [
                [[0, 15], [16, 30], [31, 45], [46, 60], [61, 9999]],
                ['0-15', '16-30', '31-45', '46-60', '60+']
            ],
            '6x1' => [
                [[0, 30], [31, 60], [61, 90], [91, 120], [121, 150], [151, 9999]],
                ['0-30', '31-60', '61-90', '91-120', '121-150', '150+']
            ],
            default => [
                [[0, 30], [31, 60], [61, 90], [91, 9999]],
                ['0-30 Days', '31-60 Days', '61-90 Days', '90+ Days']
            ],
        };
    }

    public function getAgedData(
        string $type,
        string $asOf,
        string $period = '4x1',
        ?string $fromDate = null,
        bool $includeStatutory = false,
        int $perPage = 20,
        string $groupBy = 'unique_no'
    ): array {
        [$bucketRanges, $labels] = $this->resolveBuckets($period);
        $companyId = loggedCompanyId()['company_id'];

        $taxAccounts = array_filter([
            ChartOfAccount::getSalesTaxPayableId($companyId),
            ChartOfAccount::getPurchaseTaxAccountId($companyId),
        ]);

        $payItemAccounts = PayItem::where('company_id', $companyId)->pluck('account_id')->toArray();
        $allStatutory = array_unique(array_filter(array_merge($taxAccounts, $payItemAccounts)));

        if ($type === 'payable') {
            $accounts = ChartOfAccount::forCompany($companyId)
                ->where(function ($query) {
                    $query->where('sub_type', 'PAYABLE')
                        ->orWhere('system_account_type', 'WAGES_PAYABLE')
                        ->orWhere(function ($q) {
                            $q->where('category', 'Liabilities')
                                ->where(function ($sq) {
                                    $sq->where('name', 'LIKE', '%Payable%')
                                        ->orWhere('name', 'LIKE', '%Accrued Salaries%')
                                        ->orWhere('name', 'LIKE', '%Wages%');
                                });
                        });
                })
                ->pluck('id')->toArray();

            // Also include the wages payable account used by payroll
            $wagesPayable = ChartOfAccount::getWagesPayableAccount($companyId);
            if ($wagesPayable && !in_array($wagesPayable->id, $accounts)) {
                $accounts[] = $wagesPayable->id;
            }
        } else {
            $accounts = ChartOfAccount::forCompany($companyId)
                ->where(function ($query) {
                    $query->where('sub_type', 'RECEIVABLE')
                        ->orWhere(function ($q) {
                            $q->where('category', 'Assets')
                                ->where('name', 'LIKE', '%Receivable%');
                        });
                })
                ->pluck('id')->toArray();
        }

        // Preserve the wages payable account — it's a core payable, not purely statutory
        $wagesPayableId = ($type === 'payable' && isset($wagesPayable)) ? $wagesPayable->id : null;

        if ($includeStatutory) {
            $reportCategory = $type === 'payable' ? 'Liabilities' : 'Assets';
            $relevantStatutory = ChartOfAccount::forCompany($companyId)
                ->whereIn('id', $allStatutory)
                ->where('category', $reportCategory)
                ->pluck('id')
                ->toArray();
            $accounts = array_unique(array_merge($accounts, $relevantStatutory));
        } else {
            $accounts = array_diff($accounts, $allStatutory);
            // Always keep wages payable so employee payroll data shows
            if ($wagesPayableId && !in_array($wagesPayableId, $accounts)) {
                $accounts[] = $wagesPayableId;
            }
        }

        $query = Transaction::query()
            ->selectRaw("
                account_id,
                payable_type,
                payable_id,
                customer_id,
                unique_no,
                MIN(date) as invoice_date,
                SUM(debit) as total_debit,
                SUM(credit) as total_credit,
                (?::date - MIN(date))::int as age_days
            ", [$asOf])
            ->whereIn('account_id', $accounts)
            ->where('company_id', $companyId)
            ->whereDate('date', '<=', $asOf)
            ->when($fromDate, fn($q) => $q->whereDate('date', '>=', $fromDate))
            ->groupBy(
                'account_id',
                'payable_type',
                'payable_id',
                'customer_id',
                'unique_no'
            );


        /** @var LengthAwarePaginator $paginator */
        $paginator = $query->paginate($perPage);

        // Batch-load all related entities to avoid N+1 queries
        $items = collect($paginator->items());

        $accountIds = $items->pluck('account_id')->unique()->filter()->all();
        $accountNames = ChartOfAccount::forCompany($companyId)->whereIn('id', $accountIds)->pluck('name', 'id');

        if ($type === 'payable') {
            $vendorIds = [];
            $employeeIds = [];
            foreach ($items as $item) {
                $pt = $item->payable_type;
                $pid = $item->payable_id;
                if ($pid) {
                    $morphClass = Relation::getMorphedModel($pt) ?? $pt;
                    if ($morphClass === Vendor::class) {
                        $vendorIds[] = $pid;
                    } elseif ($morphClass === Employee::class) {
                        $employeeIds[] = $pid;
                    }
                }
            }
            $vendorIds = array_unique($vendorIds);
            $employeeIds = array_unique($employeeIds);

            $vendors = $vendorIds ? Vendor::where('company_id', $companyId)->whereIn('id', $vendorIds)->pluck('name', 'id') : collect();
            $employees = $employeeIds ? Employee::where('company_id', $companyId)->whereIn('id', $employeeIds)->pluck('full_name', 'id') : collect();
        } else {
            $customerIds = $items->pluck('customer_id')->unique()->filter()->all();
            $customers = Customer::where('company_id', $companyId)->whereIn('id', $customerIds)->pluck('name', 'id');
        }

        $reportData   = [];
        $grandTotals  = array_fill(0, count($bucketRanges), 0);
        $grandTotal   = 0;

        foreach ($items as $row) {
            $balance = $type === 'payable'
                ? ($row->total_credit - $row->total_debit)
                : ($row->total_debit - $row->total_credit);

            if ($balance == 0) {
                continue;
            }

            $bucketIndex = $this->bucketIndex($row->age_days, $bucketRanges);
            $bucketVals  = array_fill(0, count($bucketRanges), 0);
            $bucketVals[$bucketIndex] = $balance;

            $entityKey = $type === 'payable'
                ? $row->payable_type . ':' . $row->payable_id
                : 'customer:' . $row->customer_id;

            if ($type === 'payable') {

                $modelClass = Relation::getMorphedModel($row->payable_type) ?? $row->payable_type;
                $entityType = class_basename($modelClass);

                $entityName = match ($modelClass) {
                    Vendor::class   => $vendors[(int) $row->payable_id] ?? null,
                    Employee::class => $employees[(int) $row->payable_id] ?? null,
                    default         => 'Tax Authority / Statutory',
                };
            } else {
                $entityType = 'Customer';
                $entityName = $customers[(int) $row->customer_id] ?? null;
            }

            if (!isset($reportData[$entityKey])) {
                $reportData[$entityKey] = [
                    'entity_type' => $entityType,
                    'name'        => $entityName,
                    'vendor'      => $entityName, // backward compatibility
                    'rows'        => [],
                    'totals'      => array_fill(0, count($bucketRanges), 0),
                    'total'       => 0,
                ];
            }

            $reportData[$entityKey]['rows'][] = [
                'invoice_no'   => $row->unique_no,
                'invoice_date' => $row->invoice_date,
                'buckets'      => $bucketVals,
                'total'        => $balance,
                'account_name' => $accountNames[$row->account_id] ?? null,
            ];

            foreach ($bucketVals as $i => $val) {
                $reportData[$entityKey]['totals'][$i] += $val;
                $grandTotals[$i] += $val;
            }

            $reportData[$entityKey]['total'] += $balance;
            $grandTotal += $balance;
        }

        $paginator->setCollection(collect(array_values($reportData)));

        return [
            'reportData'        => $paginator,   // 👈 paginator again
            'grandTotals'       => $grandTotals,
            'grandTotal'        => $grandTotal,
            'labels'            => $labels,
            'as_of_date'        => $asOf,
            'from_date'         => $fromDate,
            'period'            => $period,
            'groupBy'           => $groupBy,
            'includeStatutory'  => $includeStatutory,
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
