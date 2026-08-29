<?php

namespace App\Services\Account;

use App\Models\ChartOfAccount;
use App\Models\OpeningBalanceBatch;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;

class OpeningBalanceControlReconciliationService
{
    private const CONTROL_TYPES = ['ACCOUNTS_RECEIVABLE', 'ACCOUNTS_PAYABLE'];

    public function forBatch(OpeningBalanceBatch $batch): array
    {
        $batch->loadMissing('lines.account');
        $controlAccounts = ChartOfAccount::query()
            ->forCompany((int) $batch->company_id)
            ->where('type', 'Account')
            ->whereIn('system_account_type', self::CONTROL_TYPES)
            ->orderBy('account_number')
            ->get();

        if ($controlAccounts->isEmpty()) {
            return [];
        }

        $lines = $batch->lines->keyBy('account_id');
        $results = [];

        foreach ($controlAccounts as $account) {
            $line = $lines->get($account->id);
            $targetNetDebit = bcsub((string) ($line?->debit ?? 0), (string) ($line?->credit ?? 0), 4);
            $isPayable = $account->system_account_type === 'ACCOUNTS_PAYABLE';
            $ledgerQuery = DB::table('transactions')
                ->where('company_id', $batch->company_id)
                ->where('account_id', $account->id)
                ->where('status', 1)
                ->whereNull('deleted_at')
                ->whereDate('date', '<=', $batch->conversion_date->toDateString())
                ->whereNull('opening_balance_batch_id')
                ->where(function ($query) {
                    $query->whereNull('is_opening_balance')->orWhere('is_opening_balance', false);
                })
                ->where(function ($query) {
                    $query->whereNull('type')->orWhereNotIn('type', ['OpeningBalance', 'Opening Balance']);
                });
            if ($isPayable) {
                $ledgerQuery->whereIn('payable_type', $this->vendorMorphAliases())->whereNotNull('payable_id');
            } else {
                $ledgerQuery->whereNotNull('customer_id');
            }
            $ledgerNetDebit = (string) ($ledgerQuery
                ->selectRaw('COALESCE(SUM(debit), 0) - COALESCE(SUM(credit), 0) AS net_debit')
                ->value('net_debit') ?? '0');
            $expected = $isPayable ? bcmul($targetNetDebit, '-1', 4) : $targetNetDebit;
            $subledger = $isPayable ? bcmul($ledgerNetDebit, '-1', 4) : $ledgerNetDebit;
            $difference = bcsub($expected, $subledger, 4);

            if (bccomp($expected, '0', 4) === 0 && bccomp($subledger, '0', 4) === 0) {
                continue;
            }

            $results[] = [
                'account_id' => $account->id,
                'account_number' => $account->account_number,
                'account_name' => $account->name,
                'system_account_type' => $account->system_account_type,
                'expected' => $expected,
                'subledger' => $subledger,
                'difference' => $difference,
                'matched' => bccomp($difference, '0', 4) === 0,
            ];
        }

        return $results;
    }

    public function validationErrors(OpeningBalanceBatch $batch): array
    {
        return collect($this->forBatch($batch))
            ->where('matched', false)
            ->map(function (array $row) {
                $label = $row['system_account_type'] === 'ACCOUNTS_RECEIVABLE'
                    ? 'Accounts Receivable'
                    : 'Accounts Payable';

                return sprintf(
                    '%s control account %s - %s must equal posted outstanding documents as of the effective reporting date. Target: %s; document ledger: %s; difference: %s.',
                    $label,
                    $row['account_number'],
                    $row['account_name'],
                    $row['expected'],
                    $row['subledger'],
                    $row['difference']
                );
            })
            ->values()
            ->all();
    }

    private function vendorMorphAliases(): array
    {
        $aliases = [Vendor::class];
        foreach (Relation::morphMap() as $alias => $class) {
            if ($class === Vendor::class) {
                $aliases[] = $alias;
            }
        }

        return array_values(array_unique($aliases));
    }
}
