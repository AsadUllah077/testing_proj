<?php

namespace App\Services\Account;

use App\Models\BankStatementImport;
use App\Models\BankStatementLine;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class BankReconciliationReportService
{
    public function build(int $companyId, int $bankAccountId, ?int $statementId = null): array
    {
        $statementQuery = BankStatementImport::where('company_id', $companyId)
            ->where('bank_account_id', $bankAccountId);
        $statement = $statementId ? $statementQuery->findOrFail($statementId) : $statementQuery->latest('end_date')->first();
        $cutoff = $statement?->end_date?->format('Y-m-d') ?? now()->toDateString();

        $ledgerQuery = Transaction::where('company_id', $companyId)
            ->where('account_id', $bankAccountId)->whereDate('date', '<=', $cutoff);
        $ledgerBalance = (float) (clone $ledgerQuery)->sum(DB::raw('debit - credit'));

        $outstandingQuery = (clone $ledgerQuery)->whereDoesntHave('bankStatementLine');
        $outstandingReceipts = (clone $outstandingQuery)->where('debit', '>', 0)->get();
        $outstandingPayments = (clone $outstandingQuery)->where('credit', '>', 0)->get();

        $lineQuery = BankStatementLine::where('company_id', $companyId)->where('bank_account_id', $bankAccountId);
        if ($statement) $lineQuery->where('bank_statement_import_id', $statement->id);
        else $lineQuery->whereDate('date', '<=', $cutoff);

        $unreconciledStatementLines = (clone $lineQuery)->whereIn('status', ['Unreconciled', 'Partially Reconciled'])->get();
        $statementMovements = (float) (clone $lineQuery)->sum('amount');
        $openingBalance = (float) ($statement?->opening_balance ?? 0);
        $declaredClosingBalance = (float) ($statement?->closing_balance ?? ($openingBalance + $statementMovements));
        $calculatedClosingBalance = $openingBalance + $statementMovements;

        $depositsInTransit = (float) $outstandingReceipts->sum('debit');
        $outstandingCheques = (float) $outstandingPayments->sum('credit');
        $bankOnlyReceipts = (float) $unreconciledStatementLines->where('amount', '>', 0)->sum('remaining_amount');
        $bankOnlyPayments = abs((float) $unreconciledStatementLines->where('amount', '<', 0)->sum('remaining_amount'));
        $adjustedBankBalance = $declaredClosingBalance + $depositsInTransit - $outstandingCheques;
        $adjustedBookBalance = $ledgerBalance + $bankOnlyReceipts - $bankOnlyPayments;

        return [
            'statement' => $statement,
            'cutoff' => $cutoff,
            'ledgerBalance' => $ledgerBalance,
            'statementBalance' => $declaredClosingBalance,
            'openingBalance' => $openingBalance,
            'statementMovements' => $statementMovements,
            'calculatedClosingBalance' => $calculatedClosingBalance,
            'declaredClosingBalance' => $declaredClosingBalance,
            'outstandingReceipts' => $outstandingReceipts,
            'outstandingPayments' => $outstandingPayments,
            'unreconciledStatementLines' => $unreconciledStatementLines,
            'depositsInTransit' => $depositsInTransit,
            'outstandingCheques' => $outstandingCheques,
            'bankOnlyReceipts' => $bankOnlyReceipts,
            'bankOnlyPayments' => $bankOnlyPayments,
            'adjustedBankBalance' => $adjustedBankBalance,
            'adjustedBookBalance' => $adjustedBookBalance,
            'adjustedBalance' => $adjustedBankBalance,
            'variance' => round($adjustedBankBalance - $adjustedBookBalance, 4),
            'statementVariance' => round($calculatedClosingBalance - $declaredClosingBalance, 4),
        ];
    }
}
