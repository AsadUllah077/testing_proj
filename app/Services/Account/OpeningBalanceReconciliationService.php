<?php

namespace App\Services\Account;

use App\Models\OpeningBalanceBatch;
use App\Support\AccountingConstants;
use App\Support\DecimalAmount;

class OpeningBalanceReconciliationService
{
    public function __construct(private readonly OpeningBalanceControlReconciliationService $controlReconciliation)
    {
    }

    public function forBatch(OpeningBalanceBatch $batch): array
    {
        $batch->loadMissing('lines.account');
        $debit = DecimalAmount::sum($batch->lines->pluck('debit'));
        $credit = DecimalAmount::sum($batch->lines->pluck('credit'));
        $duplicates = $batch->lines->groupBy('account_id')->filter(fn ($lines) => $lines->count() > 1);
        $pnlLines = $batch->lines->filter(fn ($line) => $line->account && in_array($line->account->category, AccountingConstants::PROFIT_AND_LOSS_CATEGORIES, true));
        $retainedLines = $batch->lines->filter(fn ($line) => $line->account && ($line->account->system_account_type === 'RETAINED_EARNINGS' || strcasecmp(trim((string) $line->account->name), 'Retained Earnings') === 0));
        $adjustmentLines = $batch->lines->filter(fn ($line) => $line->account && $line->account->system_account_type === AccountingConstants::SYSTEM_OPENING_BALANCE_EQUITY);
        $revenue = $pnlLines->filter(fn ($line) => $line->account->category === 'Revenue')->sum(fn ($line) => (float) $line->credit - (float) $line->debit);
        $expenses = $pnlLines->filter(fn ($line) => in_array($line->account->category, ['Expenses', 'COGS'], true))->sum(fn ($line) => (float) $line->debit - (float) $line->credit);
        $historicalAdjustment = $adjustmentLines->sum(fn ($line) => (float) $line->credit - (float) $line->debit);
        $warnings = [];
        $controlAccounts = $this->controlReconciliation->forBatch($batch);

        if ($pnlLines->isNotEmpty() && $retainedLines->isNotEmpty()) {
            $warnings[] = 'P&L and Retained Earnings balances are both present. Confirm Retained Earnings excludes the current-period result to prevent double counting.';
        }
        if ($duplicates->isNotEmpty()) {
            $warnings[] = 'Each account must appear once. Merge duplicate account rows before validation.';
        }
        if (abs($historicalAdjustment) >= 0.0001) {
            $warnings[] = 'Opening Balance Equity/Historical Adjustment is not zero. Confirm the final equity mapping and clear this temporary balance when the complete trial balance is available.';
        }
        if (collect($controlAccounts)->contains(fn (array $row) => ! $row['matched'])) {
            $warnings[] = 'Accounts Receivable and Accounts Payable targets must equal the posted outstanding-document ledger before validation.';
        }

        return [
            'debit' => $debit,
            'credit' => $credit,
            'difference' => bcsub($debit, $credit, 4),
            'balanced' => DecimalAmount::equals($debit, $credit),
            'pnl_net_result' => number_format($revenue - $expenses, 4, '.', ''),
            'historical_adjustment' => number_format($historicalAdjustment, 4, '.', ''),
            'duplicate_account_ids' => $duplicates->keys()->values()->all(),
            'control_accounts' => $controlAccounts,
            'control_accounts_matched' => collect($controlAccounts)->every(fn (array $row) => $row['matched']),
            'warnings' => $warnings,
        ];
    }
}
