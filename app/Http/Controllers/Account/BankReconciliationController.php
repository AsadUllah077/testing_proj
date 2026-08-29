<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\AccountingPeriod;
use App\Models\BankStatementLine;
use App\Models\ChartOfAccount;
use App\Models\Transaction;
use App\Models\Sale;
use App\Models\Bill;
use App\Models\PayRun;
use App\Models\Payslip;
use App\Models\JournalEntry;
use App\Models\BankRule;
use App\Models\EmployeeSalaryAdvance;
use App\Services\Account\BankReconciliationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BankReconciliationController extends Controller
{
    use \App\Traits\ExportsToExcel;

    protected $service;

    public function __construct(BankReconciliationService $service)
    {
        $this->service = $service;
    }

    private function validateAccountingPeriod(string $date, ?int $companyId = null): ?AccountingPeriod
    {
        $period = AccountingPeriod::findPeriodForDate($date, $companyId ?? $this->currentCompanyId());

        if (! $period || $period->is_locked) {
            throw ValidationException::withMessages([
                'date' => 'The selected date does not fall within any unlocked accounting period.',
            ]);
        }

        return $period;
    }

    private function applyDateFilters($query, Request $request, string $column = 'date')
    {
        if ($request->filled('date_from')) {
            $query->whereDate($column, '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate($column, '<=', $request->date_to);
        }

        if (! $request->filled('date_from') && ! $request->filled('date_to') && $request->filled('date')) {
            $query->whereDate($column, $request->date);
        }

        return $query;
    }

    private function currentCompanyId(): int
    {
        return (int) (loggedCompanyId()['company_id'] ?? auth('web')->user()?->company_id);
    }

    private function chartAccountExistsRule(bool $bankOnly = false)
    {
        $rule = Rule::exists('chart_of_accounts', 'id')
            ->where('company_id', $this->currentCompanyId());
        if ($bankOnly) {
            $rule->where('type', 'Account')->where('sub_type', 'BANK');
        }
        return $rule;
    }

    private function statementLineExistsRule()
    {
        return Rule::exists('bank_statement_lines', 'id')
            ->where('company_id', $this->currentCompanyId());
    }

    private function bankAccountOrFail($accountId): ChartOfAccount
    {
        return ChartOfAccount::forCompany($this->currentCompanyId())
            ->where('type', 'Account')
            ->where('sub_type', 'BANK')
            ->findOrFail($accountId);
    }

    private function statementLineOrFail($lineId): BankStatementLine
    {
        return BankStatementLine::where('company_id', $this->currentCompanyId())->findOrFail($lineId);
    }

    private function statementLineQuery(Request $request, $accountId)
    {
        $query = BankStatementLine::where('company_id', $this->currentCompanyId())
            ->where('bank_account_id', $accountId);

        $this->applyDateFilters($query, $request);

        if ($request->filled('min_amount')) {
            $query->whereRaw('ABS(amount) >= ?', [$request->min_amount]);
        }

        if ($request->filled('max_amount')) {
            $query->whereRaw('ABS(amount) <= ?', [$request->max_amount]);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($sub) use ($search) {
                $sub->where('reference', 'ILIKE', "%{$search}%")
                    ->orWhere('description', 'ILIKE', "%{$search}%")
                    ->orWhere('payee', 'ILIKE', "%{$search}%")
                    ->orWhere('unique_no', 'ILIKE', "%{$search}%");

                if (is_numeric($search)) {
                    $sub->orWhere('amount', $search);
                }
            });
        }

        return $query;
    }

    private function sourceMetaForTransaction(?Transaction $txn): array
    {
        $type = strtolower((string) ($txn->type ?? ''));
        $uniqueNo = (string) ($txn->unique_no ?? '');

        if ($type === 'payroll' || str_starts_with($uniqueNo, 'PR-PAY-')) {
            return ['module' => 'Payroll', 'icon' => 'wallet', 'color' => 'indigo', 'document_label' => $txn?->invoice_no ?: $uniqueNo];
        }

        if ($type === 'salaryadvance' || str_starts_with($uniqueNo, 'ADV-')) {
            return ['module' => 'Employee Advance', 'icon' => 'user-plus', 'color' => 'purple', 'document_label' => $uniqueNo];
        }

        if (in_array($type, ['sale_payment', 'receipt'], true)) {
            return ['module' => 'Sales Invoice', 'icon' => 'receipt', 'color' => 'green', 'document_label' => $txn?->invoice_no ?: $uniqueNo];
        }

        if (in_array($type, ['bill_payment', 'payment'], true)) {
            return ['module' => 'Bill Payment', 'icon' => 'file-text', 'color' => 'amber', 'document_label' => $txn?->invoice_no ?: $uniqueNo];
        }

        if (str_contains($type, 'tax')) {
            return ['module' => 'Tax Settlement', 'icon' => 'landmark', 'color' => 'rose', 'document_label' => $uniqueNo];
        }

        if ($type === 'transfer') {
            return ['module' => 'Bank Transfer', 'icon' => 'repeat', 'color' => 'sky', 'document_label' => $uniqueNo];
        }

        return ['module' => 'Bank Transaction', 'icon' => 'banknote', 'color' => 'slate', 'document_label' => $uniqueNo];
    }

    private function sourceUrlForTransaction(?Transaction $txn): ?string
    {
        if (!$txn) {
            return null;
        }

        $type = strtolower((string) $txn->type);

        if ($type === 'payroll' && $txn->reference_id) {
            return route('payroll.pay-runs.show', $txn->reference_id);
        }

        if ($type === 'salaryadvance' || str_starts_with((string) $txn->unique_no, 'ADV-')) {
            return route('payroll.salary-advances.index', ['search' => $txn->unique_no]);
        }

        return null;
    }

    private function enrichTransaction(Transaction $txn): Transaction
    {
        $meta = $this->sourceMetaForTransaction($txn);
        $amount = (float) ($txn->debit > 0 ? $txn->debit : $txn->credit);

        $txn->formatted_date = $txn->date?->format('d M Y');
        $txn->bank_amount = $amount;
        $txn->source_module = $meta['module'];
        $txn->source_icon = $meta['icon'];
        $txn->source_color = $meta['color'];
        $txn->source_document = $meta['document_label'];
        $txn->source_url = $this->sourceUrlForTransaction($txn);
        $txn->journal_url = $txn->unique_no ? route('account.bank-reconciliation.show-transaction', $txn->unique_no) : null;
        $txn->matched_statement_reference = $txn->bankStatementLine?->reference ?: $txn->bankStatementLine?->unique_no;

        return $txn;
    }

    private function enrichStatementLine(BankStatementLine $line): BankStatementLine
    {
        $bankTxn = $line->transactions->first(fn($txn) => (int) $txn->account_id === (int) $line->bank_account_id)
            ?: $line->transactions->first();
        $meta = $this->sourceMetaForTransaction($bankTxn);

        $line->formatted_date = $line->date?->format('d M Y');
        $line->statement_reference = $line->reference;
        $line->matched_transaction_reference = $bankTxn?->unique_no;
        $line->source_module = $bankTxn ? $meta['module'] : 'Imported Statement';
        $line->source_icon = $bankTxn ? $meta['icon'] : 'file-down';
        $line->source_color = $bankTxn ? $meta['color'] : 'slate';
        $line->source_document = $bankTxn ? $meta['document_label'] : null;
        $line->source_url = $bankTxn ? $this->sourceUrlForTransaction($bankTxn) : null;
        $line->journal_url = $bankTxn?->unique_no ? route('account.bank-reconciliation.show-transaction', $bankTxn->unique_no) : null;
        $line->transactions->transform(fn($txn) => $this->enrichTransaction($txn));

        return $line;
    }

    private function enrichMatch($match, string $type)
    {
        $match->source_module = match ($type) {
            'invoice' => 'Sales Invoice',
            'bill' => 'Bill Payment',
            'payroll' => 'Payroll',
            default => $this->sourceMetaForTransaction($match)['module'],
        };
        $match->source_icon = match ($type) {
            'invoice' => 'receipt',
            'bill' => 'file-text',
            'payroll' => 'wallet',
            default => $this->sourceMetaForTransaction($match)['icon'],
        };
        $match->source_color = match ($type) {
            'invoice' => 'green',
            'bill' => 'amber',
            'payroll' => 'indigo',
            default => $this->sourceMetaForTransaction($match)['color'],
        };
        $match->match_reason = implode(', ', $match->reasons ?? ['Amount']);

        if ($type === 'payroll') {
            $employeeNames = $match->payslips?->pluck('employee.full_name')->filter()->unique()->values() ?? collect();
            $match->employee_name = $employeeNames->count() === 1 ? $employeeNames->first() : ($employeeNames->count() . ' employees');
            $match->source_url = route('payroll.pay-runs.show', $match->id);
            $match->journal_url = $match->unique_no ? route('payroll.pay-runs.view-transaction', $match->unique_no) : null;
        } elseif ($type === 'transaction') {
            $match->source_url = $this->sourceUrlForTransaction($match);
            $match->journal_url = $match->unique_no ? route('account.bank-reconciliation.show-transaction', $match->unique_no) : null;
            $match->bank_amount = (float) ($match->debit > 0 ? $match->debit : $match->credit);
        }

        if ($type === 'invoice' || $type === 'bill') {
            $match->source_url = null;
            $match->journal_url = null;
        }

        return $match;
    }

    private function detailPayload(?BankStatementLine $line, $transactions, string $uniqueNo): array
    {
        $bankTxn = $transactions->first(fn($txn) => $line && (int) $txn->account_id === (int) $line->bank_account_id)
            ?: $transactions->first();
        $meta = $this->sourceMetaForTransaction($bankTxn);
        $advance = $bankTxn && str_starts_with((string) $bankTxn->unique_no, 'ADV-')
            ? EmployeeSalaryAdvance::where('payment_reference', $bankTxn->unique_no)->first()
            : null;
        $payRun = $bankTxn && strtolower((string) $bankTxn->type) === 'payroll' && $bankTxn->reference_id
            ? PayRun::find($bankTxn->reference_id)
            : null;

        $timeline = [];
        if ($payRun) {
            $timeline[] = ['label' => 'Payroll Run', 'value' => $payRun->unique_no ?: ('PR-' . str_pad($payRun->id, 6, '0', STR_PAD_LEFT))];
            $timeline[] = ['label' => 'Payroll Payment', 'value' => $uniqueNo];
        } elseif ($advance) {
            $timeline[] = ['label' => 'Advance Created', 'value' => $advance->payment_reference];
            $timeline[] = ['label' => 'Bank Payment', 'value' => $uniqueNo];
        } else {
            $timeline[] = ['label' => $meta['module'], 'value' => $uniqueNo];
        }

        if ($line) {
            $timeline[] = ['label' => 'Imported Bank Statement', 'value' => $line->reference ?: $line->unique_no];
            $timeline[] = ['label' => $line->status, 'value' => $line->status === 'Reconciled' ? 'Matched and locked to ledger' : 'Open'];
        }

        if ($advance && $advance->recoveredPayRun) {
            $timeline[] = ['label' => 'Recovered by Payroll', 'value' => $advance->recoveredPayRun->unique_no ?: ('PR-' . str_pad($advance->recoveredPayRun->id, 6, '0', STR_PAD_LEFT))];
            $timeline[] = ['label' => 'Advance Balance Cleared', 'value' => number_format((float) $advance->remaining_amount, 2)];
        }

        return [
            'source_module' => $meta['module'],
            'source_document' => $meta['document_label'],
            'source_url' => $this->sourceUrlForTransaction($bankTxn),
            'amount' => $bankTxn ? (float) ($bankTxn->debit > 0 ? $bankTxn->debit : $bankTxn->credit) : (float) abs($line?->amount ?? 0),
            'payment_date' => $bankTxn?->date?->format('d M Y') ?: $line?->date?->format('d M Y'),
            'status' => $line?->status ?: 'Ledger Only',
            'timeline' => $timeline,
            'advance' => $advance,
            'pay_run' => $payRun,
        ];
    }

    public function index($accountId)
    {
        // 1. Fetch bank account with balance sums in one query
        $bankAccount = ChartOfAccount::forCompany($this->currentCompanyId())
            ->where('type', 'Account')
            ->where('sub_type', 'BANK')
            ->withSum(['transactions as ledger_debit_sum' => function ($query) {
            $query->where('status', 1);
        }], 'debit')
            ->withSum(['transactions as ledger_credit_sum' => function ($query) {
                $query->where('status', 1);
            }], 'credit')
            ->withSum('bankStatementLines as statement_sum', 'amount')
            ->findOrFail($accountId);

        // Calculate balances from pre-fetched sums
        $ledgerBalance = ($bankAccount->ledger_debit_sum ?? 0) - ($bankAccount->ledger_credit_sum ?? 0);
        $statementBalance = ($bankAccount->statement_sum ?? 0);

        // 2. Optimized Account Lookups: Fetch all "Account" type accounts once
        $allAccounts = ChartOfAccount::forCompany($this->currentCompanyId())
            ->where('type', 'Account')
            ->get();

        // Filter collections in PHP to avoid extra queries
        $accounts = $allAccounts;
        $otherBanks = $allAccounts->filter(function ($acc) {
            return $acc->sub_type === 'BANK';
        });
        $statements = \App\Models\BankStatementImport::where('company_id', $this->currentCompanyId())
            ->where('bank_account_id', $accountId)
            ->withCount(['lines', 'lines as unreconciled_count' => fn ($query) => $query->where('status', '!=', 'Reconciled')])
            ->latest('end_date')->limit(12)->get();

        return view('pages.account.bank_reconciliation.index', compact(
            'bankAccount',
            'accounts',
            'otherBanks',
            'ledgerBalance',
            'statementBalance',
            'statements'
        ));
    }

    /* public function getStatementLines(Request $request, $accountId)
    {
        $query = BankStatementLine::where('bank_account_id', $accountId)
            ->whereIn('status', ['Unreconciled', 'Partially Reconciled'])
            ->orderBy('date', 'asc')
            ->orderBy('id', 'asc');

        $lines = $query->paginate(20); // Faster with smaller pages

        // Optimization: Batch fetch matches to avoid N+1
        if ($lines->isNotEmpty()) {
            // For each line, Eager Load matched transactions
            $lines->getCollection()->each(function ($line) {
                $line->load('transactions');
                $line->matched_amount = $line->transactions->sum(function ($txn) {
                    return $txn->debit + $txn->credit;
                });

                // Re-run matching logic for suggestions (optional, could be improved)
                // For now, let's keep the basic suggestion logic but make it ignore uniqueNoNotNull
                $potentialTransactions = Transaction::withoutGlobalScope('uniqueNoNotNull')
                    ->where('account_id', $line->bank_account_id)
                    ->whereDoesntHave('bankStatementLine')
                    ->whereBetween('date', [$line->date->copy()->subDays(7), $line->date->copy()->addDays(7)])
                    ->get();

                $line->matches = $potentialTransactions->filter(function ($txn) use ($line) {
                    if ($line->amount > 0) {
                        return $txn->debit == $line->amount;
                    } else {
                        return $txn->credit == abs($line->amount);
                    }
                })->values();
            });
        }

        return response()->json($lines);
    }

    public function getAccountTransactions(Request $request, $accountId)
    {
        $accountTransactions = Transaction::withoutGlobalScope('uniqueNoNotNull')
            ->where('account_id', $accountId)
            ->with('bankStatementLine')
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(20);

        // Format dates for JSON
        $accountTransactions->getCollection()->transform(function ($txn) {
            $txn->formatted_date = $txn->date?->format('d M Y');
            return $txn;
        });

        return response()->json($accountTransactions);
    } */

    public function getStatementLines(Request $request, $accountId)
    {
        $this->bankAccountOrFail($accountId);

        $query = BankStatementLine::where('bank_account_id', $accountId)
            ->where('company_id', $this->currentCompanyId())
            ->whereIn('status', ['Unreconciled', 'Partially Reconciled'])
            ->with(['transactions.account', 'transactions.bankStatementLine', 'transactions.payable'])
            ->orderBy('date', 'asc')
            ->orderBy('id', 'asc');

        $this->applyDateFilters($query, $request);
        if ($request->filled('min_amount')) {
            $query->whereRaw('ABS(amount) >= ?', [$request->min_amount]);
        }
        if ($request->filled('max_amount')) {
            $query->whereRaw('ABS(amount) <= ?', [$request->max_amount]);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'ILIKE', "%{$search}%")
                    ->orWhere('description', 'ILIKE', "%{$search}%")
                    ->orWhere('payee', 'ILIKE', "%{$search}%")
                    ->orWhere('unique_no', 'ILIKE', "%{$search}%");

                if (is_numeric($search)) {
                    $q->orWhere('amount', $search);
                }
            });
        }

        $perPage = $request->input('per_page', 20);
        $lines = $query->paginate($perPage);

        $companyId = $this->currentCompanyId();

        // Pre-fetch all bank rules for this account to avoid N+1
        $bankRules = BankRule::where('company_id', $companyId)
            ->where('bank_account_id', $accountId)
            ->where('is_active', true)
            ->orderBy('priority')
            ->orderByDesc('usage_count')
            ->get()
            ->values();

        $lines->getCollection()->each(function ($line) use ($bankRules) {
            $line->matched_amount = $line->transactions->sum(function ($txn) {
                return $txn->debit + $txn->credit;
            });

            // Delegate all matching (transactions and invoices) to the service
            $allMatches = $this->service->findMatches($line);

            $line->matches = $allMatches['transactions'];
            $line->invoice_matches = $allMatches['invoices'];
            $line->bill_matches = $allMatches['bills'];
            $line->payroll_matches = $allMatches['payrolls'];

            $line->matches->transform(fn($match) => $this->enrichMatch($match, 'transaction'));
            $line->invoice_matches->transform(fn($match) => $this->enrichMatch($match, 'invoice'));
            $line->bill_matches->transform(fn($match) => $this->enrichMatch($match, 'bill'));
            $line->payroll_matches->transform(fn($match) => $this->enrichMatch($match, 'payroll'));

            // Xero-style: look up bank rule for auto-fill
            $line->suggested_account_id = null;
            $matchingRule = $bankRules->first(fn (BankRule $rule) => $rule->matchesStatementLine($line));
            if ($matchingRule) {
                $line->suggested_account_id = $matchingRule->contra_account_id;
                $line->suggested_rule_name = $matchingRule->name ?: $matchingRule->payee;
            }

            $this->enrichStatementLine($line);
        });

        return response()->json($lines);
    }

    public function getAllStatementLines(Request $request, $accountId)
    {
        $buildBaseQuery = fn() => $this->statementLineQuery($request, $accountId)
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc');

        $perPage = $request->input('per_page', 20);

        // Total balance across all matching lines
        $totalBalance = $buildBaseQuery()->sum('amount');

        // Paginate
        $lines = $buildBaseQuery()->paginate($perPage);

        // Running balance: compute sum of items on previous pages (they appear
        // above the current page in DESC order, i.e. newer transactions)
        $offset = ($lines->currentPage() - 1) * $lines->perPage();
        $previousPagesSum = 0;

        if ($offset > 0) {
            $previousPagesSum = $buildBaseQuery()
                ->limit($offset)
                ->pluck('amount')
                ->sum();
        }

        // Starting balance for the first row on this page
        $runningBalance = $totalBalance - $previousPagesSum;

        $lines->getCollection()->transform(function ($line) use (&$runningBalance) {
            $line->loadMissing(['transactions.account', 'transactions.bankStatementLine', 'transactions.payable']);
            $this->enrichStatementLine($line);
            $line->balance = round($runningBalance, 2);
            $runningBalance -= $line->amount;
            return $line;
        });

        return response()->json($lines);
    }

    public function exportStatementExcel(Request $request, $accountId)
    {
        set_time_limit(0);
        ini_set('max_execution_time', '0');

        $bankAccount = $this->bankAccountOrFail($accountId);

        $query = $this->statementLineQuery($request, $accountId)
            ->with('transactions:id,bank_statement_line_id,debit,credit')
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc');

        if (! (clone $query)->exists()) {
            abort(400, 'No bank statement lines found for export.');
        }

        $lines = $query->get();
        $totalBalance = $lines->sum('amount');
        $runningBalance = $totalBalance;

        $headers = [
            'Date',
            'Reference',
            'Payee',
            'Description',
            'Money In',
            'Money Out',
            'Amount',
            'Remaining',
            'Balance',
            'Status',
            'Matched Amount',
            'Note',
            'Unique No',
        ];
        $colCount = count($headers);

        $spreadsheet = $this->createReportSpreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Bank Statement');
        $row = 1;

        $this->writeTitle($sheet, 'Bank Statement Report', $row, $colCount);
        $this->writeCompanyRow($sheet, $row, $colCount);
        $this->writeMeta($sheet, [
            ['Bank Account:', $bankAccount->name . ' (' . ($bankAccount->acc_no ?? $bankAccount->account_number ?? '-') . ')'],
            ['Search:', $request->search ?: 'All'],
            ['Date From:', $request->date_from ?: '-'],
            ['Date To:', $request->date_to ?: '-'],
            ['Amount Range:', ($request->min_amount ?: '-') . ' to ' . ($request->max_amount ?: '-')],
            ['Generated:', now()->format('d M Y, h:i A')],
        ], $row);

        $this->writeColumnHeaders($sheet, $headers, $row);

        $totalIn = 0;
        $totalOut = 0;
        $totalMatched = 0;

        foreach ($lines as $line) {
            $amount = (float) $line->amount;
            $moneyIn = $amount > 0 ? $amount : 0;
            $moneyOut = $amount < 0 ? abs($amount) : 0;
            $matchedAmount = $line->transactions->sum(fn($txn) => (float) $txn->debit + (float) $txn->credit);

            $totalIn += $moneyIn;
            $totalOut += $moneyOut;
            $totalMatched += $matchedAmount;

            $this->writeDataRow($sheet, [
                dateFormat($line->date),
                $line->reference ?? '',
                $line->payee ?? '',
                $line->description ?? '',
                numberFormat($moneyIn),
                numberFormat($moneyOut),
                numberFormat($amount),
                numberFormat($line->remaining_amount ?? 0),
                numberFormat($runningBalance),
                $line->status ?? '',
                numberFormat($matchedAmount),
                $line->note ?? '',
                $line->unique_no ?? '',
            ], $row, $colCount);

            $runningBalance -= $amount;
        }

        $this->writeGrandTotalRow($sheet, [
            'TOTAL',
            '',
            '',
            '',
            numberFormat($totalIn),
            numberFormat($totalOut),
            numberFormat($lines->sum('amount')),
            '',
            numberFormat($totalBalance),
            '',
            numberFormat($totalMatched),
            '',
            '',
        ], $row, $colCount);

        $this->autoSizeColumns($sheet, $colCount);

        $filename = 'bank_statement_' . now()->format('Ymd_His') . '.xlsx';
        return $this->downloadExcel($spreadsheet, $filename);
    }

    public function getAccountTransactions(Request $request, $accountId)
    {
        $accountTransactions = Transaction::withoutGlobalScope('uniqueNoNotNull')
            ->where('account_id', $accountId)
            ->where(function ($query) {
                $query->where('debit', '>', 0)
                    ->orWhere('credit', '>', 0);
            })
            ->with(['bankStatementLine', 'account', 'payable'])
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc');

        $this->applyDateFilters($accountTransactions, $request);
        if ($request->filled('min_amount')) {
            $accountTransactions->whereRaw('GREATEST(debit, credit) >= ?', [$request->min_amount]);
        }
        if ($request->filled('max_amount')) {
            $accountTransactions->whereRaw('GREATEST(debit, credit) <= ?', [$request->max_amount]);
        }

        // Global search filter
        if ($request->filled('search')) {
            $search = trim($request->search);
            $accountTransactions->where(function ($q) use ($search) {
                $q->where('description', 'ILIKE', "%{$search}%")
                    ->orWhere('unique_no', 'ILIKE', "%{$search}%");

                if (is_numeric($search)) {
                    $q->orWhere('debit', $search)
                        ->orWhere('credit', $search);
                }
            });
        }

        $perPage = $request->input('per_page', 20);
        $accountTransactions = $accountTransactions->paginate($perPage);

        // Format dates for JSON
        $accountTransactions->getCollection()->transform(fn($txn) => $this->enrichTransaction($txn));

        return response()->json($accountTransactions);
    }

    private function parseCsvDate(string $dateStr): string
    {
        $dateStr = trim($dateStr);
        if (empty($dateStr)) {
            throw new \Exception("Empty date string provided.");
        }

        // Common formats to try explicitly first
        $formats = [
            'j/n/Y',   // 4/4/2026
            'j-n-Y',   // 4-4-2026
            'j.n.Y',   // 4.4.2026
            'd/m/Y',   // 04/04/2026
            'd-m-Y',   // 04-04-2026
            'd.m.Y',   // 04.04.2026
            'Y-m-d',   // 2026-04-04 (ISO)
            'm/d/Y',
            'm-d-Y',
            'm.d.Y',
            'd/m/y',
            'm/d/y',
            'd-m-y',
            'm-d-y',
            'd.m.y',
            'm.d.y',
            'j/n/y',
            'j-n-y',
            'd-M-Y',
            'd-M-y',
            'd/M/Y',
            'd/M/y',
            'd.M.Y',
            'd.M.y',
            'j F, Y',
            'd F, Y',
        ];

        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat($format, $dateStr);
                if ($date && $date->format($format) === $dateStr) {
                    return $date->format('Y-m-d');
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // If explicit formats fail, try generic Carbon parsing
        try {
            return Carbon::parse($dateStr)->format('Y-m-d');
        } catch (\Exception $e) {
            throw new \Exception("Invalid or unsupported date format: {$dateStr}");
        }
    }

    private function parseAmount($value)
    {
        $value = trim($value);

        // Remove currency symbols
        $value = preg_replace('/[^\d\-\(\)\.,]/', '', $value);

        $value = str_replace(',', '', $value);

        if (preg_match('/^\((.*)\)$/', $value, $matches)) {
            $value = '-' . $matches[1];
        }

        // Handle trailing minus (e.g. "6396.00-")
        if (str_ends_with($value, '-')) {
            $value = '-' . substr($value, 0, -1);
        }

        return (float) $value;
    }

    public function store(Request $request, $accountId)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:csv,txt',
            'statement_number' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'opening_balance' => 'required|numeric',
            'closing_balance' => 'required|numeric',
            'currency' => 'required|string|size:3',
        ]);

        $this->bankAccountOrFail($accountId);
        $file = $request->file('file');
        $metadata = $validated + [
            'source_filename' => $file->getClientOriginalName(),
            'file_hash' => hash_file('sha256', $file->getPathname()),
        ];
        $handle = fopen($file->getPathname(), 'r');
        $chunk = [];
        $statementId = null;
        $summary = ['imported' => 0, 'skipped' => 0, 'errors' => []];
        $firstRow = true;
        while (($row = fgetcsv($handle)) !== false) {
            if ($firstRow) {
                $firstRow = false;
                $heading = strtolower(implode(' ', $row));
                if (str_contains($heading, 'date') || str_contains($heading, 'amount')) continue;
            }
            $chunk[] = $row;
            if (count($chunk) < 1000) continue;
            $result = app(\App\Services\Account\BankStatementImportService::class)->importChunk(
                $this->currentCompanyId(), (int) $accountId, $chunk, $metadata + ['statement_import_id' => $statementId]
            );
            $statementId = $result['statement_import_id'];
            $summary['imported'] += $result['imported']; $summary['skipped'] += $result['skipped'];
            $summary['errors'] = array_merge($summary['errors'], $result['errors']);
            $chunk = [];
        }
        if ($chunk) {
            $result = app(\App\Services\Account\BankStatementImportService::class)->importChunk(
                $this->currentCompanyId(), (int) $accountId, $chunk, $metadata + ['statement_import_id' => $statementId]
            );
            $summary['imported'] += $result['imported']; $summary['skipped'] += $result['skipped'];
            $summary['errors'] = array_merge($summary['errors'], $result['errors']);
        }
        fclose($handle);
        return back()->with('success', "Imported {$summary['imported']} rows; skipped {$summary['skipped']} duplicates; " . count($summary['errors']) . ' row errors.');

        // Increase time limit for large imports
        set_time_limit(0);

        $file = $request->file('file');
        $handle = fopen($file->getPathname(), 'r');

        $companyId = $this->currentCompanyId();
        $batchSize = 1000;
        $batch = [];
        $rowNumber = 0;
        $now = now()->toDateTimeString();

        // Optimization: Fetch initial max ID once and increment in memory
        $maxId = BankStatementLine::max('id') ?? 0;

        // Optimization: Fetch all active accounting periods and pre-calculate their boundaries
        $activePeriods = AccountingPeriod::where('company_id', $companyId)->active()->get()->map(function ($p) {
            return (object) [
                'id' => $p->id,
                'start' => $p->start_date?->format('Y-m-d'),
                'end' => $p->end_date?->format('Y-m-d'),
            ];
        });

        $periodCache = [];

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                // Skip empty rows
                if (empty($row) || (count($row) === 1 && $row[0] === null)) {
                    continue;
                }

                // Header Detection / Skip first row logic
                if ($rowNumber === 0) {
                    $rowString = implode(' ', $row);
                    if (str_contains(strtolower($rowString), 'date') || str_contains(strtolower($rowString), 'amount')) {
                        $rowNumber++;
                        continue;
                    }
                }

                $dateStr = $row[0];
                $amountRaw = $row[1];
                $payee = $row[2] ?? null;
                $description = $row[3] ?? null;
                $reference = $row[4] ?? null;

                // Simple heuristic for amount
                $test = str_replace([',', '(', ')'], '', $amountRaw);

                if (!is_numeric($test)) {
                    foreach ($row as $index => $val) {
                        if ($index === 0) continue;

                        $valTest = str_replace([',', '(', ')'], '', $val);

                        if (is_numeric($valTest)) {
                            $amountRaw = $val;

                            if ($index > 1) {
                                $description = $row[1];
                            }

                            break;
                        }
                    }
                }

                // $amount = (float) str_replace(',', '', $amountRaw);
                $amount = $this->parseAmount($amountRaw);

                // $formattedDate = date('Y-m-d', strtotime($dateStr));
                $formattedDate = $this->parseCsvDate($dateStr);

                // Find period in memory (Cache by date string to avoid repetitive lookups)
                if (!isset($periodCache[$formattedDate])) {
                    $period = $activePeriods->first(function ($p) use ($formattedDate) {
                        return $formattedDate >= $p->start && $formattedDate <= $p->end;
                    });

                    if (!$period) {
                        $period = $this->validateAccountingPeriod($formattedDate, $companyId);
                    }
                    $periodCache[$formattedDate] = $period;
                }
                $period = $periodCache[$formattedDate];

                $maxId++;
                $uniqueNo = 'BK-' . str_pad($maxId, 5, '0', STR_PAD_LEFT);

                $batch[] = [
                    'company_id' => $companyId,
                    'accounting_period_id' => $period->id,
                    'bank_account_id' => (int) $accountId,
                    'date' => $formattedDate,
                    'amount' => $amount,
                    'remaining_amount' => $amount,
                    'payee' => $payee,
                    'description' => $description,
                    'reference' => $reference,
                    'status' => 'Unreconciled',
                    'unique_no' => $uniqueNo,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($batch) >= $batchSize) {
                    DB::table('bank_statement_lines')->insert($batch);
                    $batch = [];
                }

                $rowNumber++;
            }

            if (!empty($batch)) {
                DB::table('bank_statement_lines')->insert($batch);
            }

            DB::commit();
            fclose($handle);
        } catch (\Exception $e) {
            DB::rollBack();
            if ($handle) fclose($handle);
            return back()->with('error', 'Error importing statement: ' . $e->getMessage());
        }

        return back()->with('success', 'Bank statement imported successfully.');
    }

    public function importChunk(Request $request, $accountId)
    {
        try {
            $this->bankAccountOrFail($accountId);

            $validated = $request->validate([
                'rows' => 'required|array|max:1000',
                'statement_import_id' => [
                    'nullable',
                    Rule::exists('bank_statement_imports', 'id')->where('company_id', $this->currentCompanyId())->where('bank_account_id', $accountId),
                ],
                'statement_number' => 'required_without:statement_import_id|nullable|string|max:255',
                'start_date' => 'required_without:statement_import_id|nullable|date',
                'end_date' => 'required_without:statement_import_id|nullable|date|after_or_equal:start_date',
                'opening_balance' => 'required_without:statement_import_id|nullable|numeric',
                'closing_balance' => 'required_without:statement_import_id|nullable|numeric',
                'currency' => 'nullable|string|size:3',
                'source_filename' => 'nullable|string|max:255',
                'file_hash' => 'nullable|string|size:64',
            ]);

            $result = app(\App\Services\Account\BankStatementImportService::class)->importChunk(
                $this->currentCompanyId(), (int) $accountId, $validated['rows'], $validated
            );

            return response()->json(['success' => true] + $result);

            $rows = $request->input('rows');
            if (empty($rows)) {
                return response()->json(['success' => true, 'message' => 'Empty chunk ignored']);
            }

            $companyId = $this->currentCompanyId();
            $now = now()->toDateTimeString();

            // Optimization: Fetch initial max ID once and increment in memory
            // Note: Since we are in AJAX chunks, we fetch it per chunk. 
            // If chunks are sequential, this is safe and accurate.
            $maxId = BankStatementLine::max('id') ?? 0;

            $activePeriods = AccountingPeriod::where('company_id', $companyId)->active()->get()->map(function ($p) {
                return (object) [
                    'id' => $p->id,
                    'start' => $p->start_date?->format('Y-m-d'),
                    'end' => $p->end_date?->format('Y-m-d'),
                ];
            });

            $periodCache = [];
            $batch = [];

            foreach ($rows as $row) {
                $dateStr = $row[0] ?? null;
                $amountRaw = $row[1] ?? 0;
                $payee = $row[2] ?? null;
                $description = $row[3] ?? null;
                $reference = $row[4] ?? null;

                if (!$dateStr) continue;

                // $amount = (float) str_replace(',', '', $amountRaw);
                $amount = $this->parseAmount($amountRaw);
                // $formattedDate = date('Y-m-d', strtotime($dateStr));
                $formattedDate = $this->parseCsvDate($dateStr);

                if (!isset($periodCache[$formattedDate])) {
                    $period = $activePeriods->first(function ($p) use ($formattedDate) {
                        return $formattedDate >= $p->start && $formattedDate <= $p->end;
                    });

                    if (!$period) {
                        $period = $this->validateAccountingPeriod($formattedDate, $companyId);
                    }
                    $periodCache[$formattedDate] = $period;
                }
                $period = $periodCache[$formattedDate];

                $maxId++;
                $uniqueNo = 'BK-' . str_pad($maxId, 5, '0', STR_PAD_LEFT);

                $batch[] = [
                    'company_id' => $companyId,
                    'accounting_period_id' => $period->id,
                    'bank_account_id' => (int) $accountId,
                    'date' => $formattedDate,
                    'amount' => $amount,
                    'remaining_amount' => $amount,
                    'payee' => $payee,
                    'description' => $description,
                    'reference' => $reference,
                    'status' => 'Unreconciled',
                    'unique_no' => $uniqueNo,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (!empty($batch)) {
                DB::table('bank_statement_lines')->insert($batch);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show detail page for a bank transaction and its associated ledger transactions.
     */
    public function showTransaction($unique_no)
    {
        $companyId = $this->currentCompanyId();
        $line = BankStatementLine::where('company_id', $companyId)
            ->where('unique_no', $unique_no)
            ->first();
        $transactions = collect();

        if (!$line) {
            // It might be a transaction that wasn't created via a statement line directly
            $txn = Transaction::withoutGlobalScope('uniqueNoNotNull')
                ->where('company_id', $companyId)
                ->where('unique_no', $unique_no)
                ->first();
            if ($txn && $txn->bank_statement_line_id) {
                $line = BankStatementLine::where('company_id', $companyId)->find($txn->bank_statement_line_id);
            }
        }

        if ($line) {
            $transactions = Transaction::withoutGlobalScope('uniqueNoNotNull')
                ->where('company_id', $companyId)
                ->where(function ($query) use ($line, $unique_no) {
                    $query->where('bank_statement_line_id', $line->id)
                        ->orWhere('unique_no', $unique_no);
                })
                ->with(['account', 'vendor', 'customer', 'bankStatementLine', 'payable'])
                ->get();
        } else {
            // Just show the transactions if no line found
            $transactions = Transaction::withoutGlobalScope('uniqueNoNotNull')
                ->where('company_id', $companyId)
                ->where('unique_no', $unique_no)
                ->with(['account', 'vendor', 'customer', 'bankStatementLine', 'payable'])
                ->get();

            if ($transactions->isEmpty()) {
                abort(404, 'Transaction not found');
            }
        }

        $transactions->transform(fn($txn) => $this->enrichTransaction($txn));
        $detail = $this->detailPayload($line, $transactions, $unique_no);

        return view('pages.account.bank_reconciliation.show_transaction', compact('line', 'transactions', 'unique_no', 'detail'));
    }

    public function match(Request $request)
    {
        $request->validate([
            'line_id' => ['required', $this->statementLineExistsRule()],
            'transaction_id' => 'nullable', // Single ID
            'transaction_ids' => 'nullable|array', // Multiple IDs for split
            'confidence' => 'nullable|integer|min:0|max:100',
        ]);

        $line = $this->statementLineOrFail($request->line_id);
        $transactionIds = $request->transaction_ids ?? ($request->transaction_id ? [$request->transaction_id] : []);

        if (empty($transactionIds)) {
            return back()->with('error', 'No transactions selected for matching.');
        }

        app(\App\Services\Account\BankReconciliationControlService::class)->reconcileExisting($line, $transactionIds, $request->integer('confidence') ?: null);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Reconciled successfully.']);
        }
        return back()->with('success', 'Reconciled successfully.');
    }

    public function matchInvoice(Request $request)
    {
        $request->validate([
            'line_id' => ['required', $this->statementLineExistsRule()],
            'invoice_id' => 'nullable',
            'type' => 'required|in:Sale,Bill,Payroll',
            'allocations' => 'nullable|string',
            'adjustment_account_id' => ['nullable', $this->chartAccountExistsRule()],
            'adjustment_amount' => 'nullable|numeric|min:0',
            'adjustment_description' => 'nullable|string',
        ]);

        $line = $this->statementLineOrFail($request->line_id);
        $uniqueNo = $line->unique_no;

        $adjustment = null;
        if ($request->filled('adjustment_account_id') && $request->filled('adjustment_amount')) {
            $adjustment = [
                'account_id' => $request->adjustment_account_id,
                'amount' => $request->adjustment_amount,
                'description' => $request->adjustment_description,
            ];
        }

        if ($request->type === 'Payroll') {
            if (!$request->filled('invoice_id')) {
                return back()->with('error', 'Pay Run ID is required.');
            }

            $this->service->reconcileWithPayroll($line, $request->invoice_id, $uniqueNo);
        } elseif ($request->filled('allocations')) {
            $allocations = json_decode($request->input('allocations'), true);
            if (empty($allocations) || !is_array($allocations)) {
                return back()->with('error', 'No allocations provided.');
            }
            $this->service->reconcileWithInvoiceAllocations($line, $allocations, $request->type, $uniqueNo, $adjustment);
        } else {
            if (!$request->filled('invoice_id')) {
                return back()->with('error', 'Invoice ID is required if no allocations are provided.');
            }
            $this->service->reconcileWithInvoiceAllocations($line, [[
                'id' => $request->invoice_id,
                'amount' => abs((float) ($line->remaining_amount ?? $line->amount)),
            ]], $request->type, $uniqueNo);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Invoice matched and reconciled successfully.']);
        }
        return back()->with('success', 'Invoice matched and reconciled successfully.');
    }


    public function create(Request $request)
    {
        $request->validate([
            'line_id' => ['required', $this->statementLineExistsRule()],
            // convert to transaction validation
            'contra_account_id' => ['required', $this->chartAccountExistsRule()],
            // other fields like tax_rate_id etc.
        ]);

        $line = $this->statementLineOrFail($request->line_id);

        $uniqueNo = $line->unique_no;

        // Logic to create transaction via service
        DB::transaction(fn () => $this->service->createTransaction($line, $request->all(), $uniqueNo ?? null), 3);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Transaction created and reconciled.']);
        }
        return back()->with('success', 'Transaction created and reconciled.');
    }

    public function transfer(Request $request)
    {
        $request->validate([
            'line_id' => ['required', $this->statementLineExistsRule()],
            'target_account_id' => ['required', $this->chartAccountExistsRule(true)],
        ]);

        $line = $this->statementLineOrFail($request->line_id);

        $uniqueNo = $line->unique_no;

        DB::transaction(fn () => $this->service->createTransfer($line, $request->target_account_id, $request->reference, $uniqueNo ?? null), 3);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Transfer recorded and reconciled.']);
        }
        return back()->with('success', 'Transfer recorded and reconciled.');
    }

    public function updateNote(Request $request)
    {
        $request->validate([
            'line_id' => ['required', $this->statementLineExistsRule()],
            'note' => 'nullable|string',
        ]);

        $line = $this->statementLineOrFail($request->line_id);
        $this->service->updateNote($line, $request->note);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Note saved.']);
        }
        return back()->with('success', 'Note saved.');
    }

    // --- Manual Transaction Methods ---

    public function spendMoney($accountId)
    {
        $bankAccount = $this->bankAccountOrFail($accountId);
        $accounts = ChartOfAccount::forCompany($this->currentCompanyId())
            ->where('type', '!=', 'Bank')
            ->get(); // Spend to Expense/Liability/etc
        return view('pages.account.bank_reconciliation.spend-money', compact('bankAccount', 'accounts'));
    }

    public function storeSpendMoney(Request $request, $accountId)
    {
        $request->validate([
            'contra_account_id' => ['required', $this->chartAccountExistsRule()],
            'date' => 'required|date',
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $this->bankAccountOrFail($accountId);
        DB::transaction(fn () => $this->service->createDirectTransaction($accountId, $request->all(), 'payment'), 3);
        return redirect()->route('account.bank-reconciliation.index', $accountId)->with('success', 'Spend Money transaction created and reconciled.');
    }

    public function receiveMoney($accountId)
    {
        $bankAccount = $this->bankAccountOrFail($accountId);
        $accounts = ChartOfAccount::forCompany($this->currentCompanyId())
            ->where('type', '!=', 'Bank')
            ->get(); // Receive from Revenue/Asset/etc
        return view('pages.account.bank_reconciliation.receive-money', compact('bankAccount', 'accounts'));
    }

    public function storeReceiveMoney(Request $request, $accountId)
    {
        $request->validate([
            'contra_account_id' => ['required', $this->chartAccountExistsRule()],
            'date' => 'required|date',
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $this->bankAccountOrFail($accountId);
        DB::transaction(fn () => $this->service->createDirectTransaction($accountId, $request->all(), 'receipt'), 3);
        return redirect()->route('account.bank-reconciliation.index', $accountId)->with('success', 'Receive Money transaction created and reconciled.');
    }

    public function transferMoney($accountId)
    {
        $bankAccount = $this->bankAccountOrFail($accountId);
        $otherBanks = ChartOfAccount::forCompany($this->currentCompanyId())
            ->where('type', 'Account')
            ->where('sub_type', 'BANK')
            ->where('id', '!=', $accountId)
            ->get();
        // $otherBanks = ChartOfAccount::where('id', '!=', $accountId)
        // ->where(function($q) {
        //      $q->whereBetween('account_number', [110, 119])
        //        ->orWhere('name', 'like', '%Bank%');
        // })->get();
        return view('pages.account.bank_reconciliation.transfer-money', compact('bankAccount', 'otherBanks'));
    }

    public function storeTransferMoney(Request $request, $accountId)
    {
        $request->validate([
            'target_account_id' => ['required', $this->chartAccountExistsRule(true)],
            'date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $this->bankAccountOrFail($accountId);
        DB::transaction(fn () => $this->service->createDirectTransfer($accountId, $request->target_account_id, $request->all()), 3);

        return redirect()->route('account.bank-reconciliation.index', $accountId)->with('success', 'Transfer recorded and reconciled in both accounts.');
    }

    public function reconciliationReport(Request $request, $accountId)
    {
        $bankAccount = $this->bankAccountOrFail($accountId);
        $data = app(\App\Services\Account\BankReconciliationReportService::class)->build(
            $this->currentCompanyId(), (int) $accountId, $request->integer('statement_id') ?: null
        );
        return view('pages.account.bank_reconciliation.report', ['bankAccount' => $bankAccount] + $data);

        // 1. Balance in Xero (Ledger Balance)
        $ledgerBalance = Transaction::where('account_id', $accountId)->sum(DB::raw('debit - credit'));

        // 2. Statement Balance (Imported)
        $statementBalance = BankStatementLine::where('company_id', $this->currentCompanyId())
            ->where('bank_account_id', $accountId)
            ->sum('amount');

        // 3. Outstanding Ledger Transactions (In Xero but not in Bank)
        $outstandingReceipts = Transaction::where('account_id', $accountId)
            ->whereDoesntHave('bankStatementLine')
            ->where('debit', '>', 0)
            ->get();

        $outstandingPayments = Transaction::where('account_id', $accountId)
            ->whereDoesntHave('bankStatementLine')
            ->where('credit', '>', 0)
            ->get();

        // 4. Unreconciled Statement Lines (In Bank but not in Xero)
        $unreconciledStatementLines = BankStatementLine::where('company_id', $this->currentCompanyId())
            ->where('bank_account_id', $accountId)
            ->whereDoesntHave('transactions')
            ->get();

        // Calculate "Adjusted Statement Balance" starting from Ledger Balance
        $unreconciledReceiptsSum = $unreconciledStatementLines->where('amount', '>', 0)->sum('amount');
        $unreconciledPaymentsSum = abs($unreconciledStatementLines->where('amount', '<', 0)->sum('amount'));

        $adjustedBalance = $ledgerBalance
            + $outstandingPayments->sum('credit')
            - $outstandingReceipts->sum('debit')
            + $unreconciledReceiptsSum
            - $unreconciledPaymentsSum;

        $variance = round($adjustedBalance - $statementBalance, 2);

        return view('pages.account.bank_reconciliation.report', compact(
            'bankAccount',
            'ledgerBalance',
            'statementBalance',
            'outstandingReceipts',
            'outstandingPayments',
            'unreconciledStatementLines',
            'adjustedBalance',
            'variance'
        ));
    }

    public function exportPdf(Request $request, $accountId)
    {
        $bankAccount = $this->bankAccountOrFail($accountId);
        $data = app(\App\Services\Account\BankReconciliationReportService::class)->build(
            $this->currentCompanyId(), (int) $accountId, $request->integer('statement_id') ?: null
        );
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pages.account.bank_reconciliation.report_formal_pdf', ['bankAccount' => $bankAccount] + $data);
        $suffix = $data['statement']?->statement_number ?: $data['cutoff'];
        return $pdf->download('Bank_Reconciliation_Report_' . $bankAccount->name . '_' . $suffix . '.pdf');

        $ledgerBalance = Transaction::where('account_id', $accountId)->sum(DB::raw('debit - credit'));
        $statementBalance = BankStatementLine::where('company_id', $this->currentCompanyId())
            ->where('bank_account_id', $accountId)
            ->sum('amount');

        $outstandingReceipts = Transaction::where('account_id', $accountId)
            ->whereDoesntHave('bankStatementLine')
            ->where('debit', '>', 0)
            ->get();

        $outstandingPayments = Transaction::where('account_id', $accountId)
            ->whereDoesntHave('bankStatementLine')
            ->where('credit', '>', 0)
            ->get();

        $unreconciledStatementLines = BankStatementLine::where('company_id', $this->currentCompanyId())
            ->where('bank_account_id', $accountId)
            ->whereDoesntHave('transactions')
            ->get();

        $unreconciledReceiptsSum = $unreconciledStatementLines->where('amount', '>', 0)->sum('amount');
        $unreconciledPaymentsSum = abs($unreconciledStatementLines->where('amount', '<', 0)->sum('amount'));

        $adjustedBalance = $ledgerBalance
            + $outstandingPayments->sum('credit')
            - $outstandingReceipts->sum('debit')
            + $unreconciledReceiptsSum
            - $unreconciledPaymentsSum;

        $variance = round($adjustedBalance - $statementBalance, 2);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pages.account.bank_reconciliation.report_pdf', compact(
            'bankAccount',
            'ledgerBalance',
            'statementBalance',
            'outstandingReceipts',
            'outstandingPayments',
            'unreconciledStatementLines',
            'adjustedBalance',
            'variance'
        ));

        return $pdf->download('Bank_Reconciliation_Report_' . $bankAccount->name . '.pdf');
    }


    private function storeManualTransaction(Request $request, $accountId, $type)
    {
        $request->validate([
            'contra_account_id' => ['required', $this->chartAccountExistsRule()],
            'date' => 'required|date',
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $this->bankAccountOrFail($accountId);

        DB::transaction(function () use ($request, $accountId, $type) {
            $isSpend = $type === 'payment';
            $companyId = $this->currentCompanyId();

            // 1. Bank Leg
            $txn = new Transaction();
            $txn->company_id = $companyId;
            $txn->account_id = $accountId; // Bank
            $txn->date = $request->date;
            $txn->type = $type;
            $txn->description = $request->description;
            $txn->reference_id = $request->reference;
            $txn->status = 1; // Active

            if ($isSpend) {
                $txn->credit = $request->amount;
                $txn->debit = 0.00;
            } else {
                $txn->debit = $request->amount;
                $txn->credit = 0.00;
            }
            $txn->save();

            // 2. Account Leg
            $contra = new Transaction();
            $contra->company_id = $companyId;
            $contra->account_id = $request->contra_account_id; // Expense/income
            $contra->date = $request->date;
            $contra->type = $type;
            $contra->description = $request->description;
            $contra->reference_id = $request->reference;
            $contra->status = 1; // Active

            if ($isSpend) {
                $contra->debit = $request->amount; // Expense debit
                $contra->credit = 0.00;
            } else {
                $contra->credit = $request->amount; // Income credit
                $contra->debit = 0.00;
            }
            $contra->save();
        });
    }

    public function downloadTemplate()
    {
        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=bank_statement_template.csv",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $columns = ['Date', 'Amount', 'Payee', 'Description', 'Reference'];

        $callback = function () use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            $data = [
                [date('d-m-Y'), '-6396.00', 'ATM Withdrawal', 'ATM Withdrawal transaction', 'REF-100000']
            ];

            foreach ($data as $row) {
                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function createLine($accountId)
    {
        $bankAccount = $this->bankAccountOrFail($accountId);
        return view('pages.account.bank_reconciliation.create-line', compact('bankAccount'));
    }

    public function storeLine(Request $request, $accountId)
    {
        $request->validate([
            'date' => 'required|date',
            'amount' => 'required|numeric', // Can be negative for spend, positive for receive
            'payee' => 'nullable|string',
            'description' => 'nullable|string',
            'reference' => 'nullable|string',
        ]);

        $this->bankAccountOrFail($accountId);
        $companyId = $this->currentCompanyId();
        $controls = app(\App\Services\Account\BankReconciliationControlService::class);
        $uniqueNo = $controls->uniqueNumber($companyId);
        $period = $this->validateAccountingPeriod($request->date, $companyId);

        BankStatementLine::create([
            'company_id' => $companyId,
            'accounting_period_id' => $period->id,
            'bank_account_id' => $accountId,
            'date' => $request->date,
            'amount' => $request->amount,
            'remaining_amount' => $request->amount,
            'payee' => $request->payee,
            'description' => $request->description,
            'reference' => $request->reference,
            'status' => 'Unreconciled',
            'unique_no' => $uniqueNo,
        ]);

        return redirect()->route('account.bank-reconciliation.index', $accountId)->with('success', 'Statement line added manually.');
    }

    public function matchTransactions(Request $request, $accountId)
    {
        $request->validate([
            'line_id' => ['required', $this->statementLineExistsRule()],
        ]);

        $this->bankAccountOrFail($accountId);
        $line = $this->statementLineOrFail($request->line_id);

        $matches = Transaction::where('company_id', $this->currentCompanyId())
            ->where('account_id', $accountId)
            ->whereDoesntHave('bankStatementLine')
            ->where(function ($query) use ($line) {
                if ($line->amount > 0) { // Income
                    $query->where('debit', $line->amount);
                } else { // Expense
                    $query->where('credit', abs($line->amount));
                }
            })
            ->get()
            ->filter(function ($txn) use ($line) {
                if ($line->amount > 0) {
                    return (string)$txn->debit === (string)$line->amount;
                } else {
                    return (string)$txn->credit === (string)abs($line->amount);
                }
            })->values();

        return response()->json($matches);
    }

    public function searchTransactions(Request $request, $accountId)
    {
        $this->bankAccountOrFail($accountId);

        $companyId = $this->currentCompanyId();
        $search = $request->search;
        $limit = 50;

        // 1. Ledger Transactions
        $txnQuery = Transaction::withoutGlobalScope('uniqueNoNotNull')
            ->where('company_id', $companyId)
            ->where('account_id', $accountId)
            ->whereDoesntHave('bankStatementLine');

        $this->applyDateFilters($txnQuery, $request);
        if ($request->filled('min_amount')) {
            $txnQuery->where(function ($q) use ($request) {
                $q->where('debit', '>=', $request->min_amount)->orWhere('credit', '>=', $request->min_amount);
            });
        }
        if ($request->filled('max_amount')) {
            $txnQuery->where(function ($q) use ($request) {
                $q->where('debit', '<=', $request->max_amount)->orWhere('credit', '<=', $request->max_amount);
            });
        }

        if ($search) {
            $txnQuery->where(function ($q) use ($search) {
                $q->where('description', 'ILIKE', "%{$search}%")
                    ->orWhere('unique_no', 'ILIKE', "%{$search}%")
                    ->orWhereRaw('debit::text ILIKE ?', ["%{$search}%"])
                    ->orWhereRaw('credit::text ILIKE ?', ["%{$search}%"]);
            });
        }
        $transactions = $txnQuery->with(['bankStatementLine', 'account', 'payable'])->limit($limit)->get()->map(function ($t) {
            $this->enrichTransaction($t);

            return [
                'id' => $t->id,
                'type' => 'transaction',
                'source' => $t->source_module,
                'source_module' => $t->source_module,
                'source_icon' => $t->source_icon,
                'source_color' => $t->source_color,
                'date' => $t->date,
                'description' => $t->description,
                'reference' => $t->unique_no,
                'unique_no' => $t->unique_no,
                'amount' => (float) $t->debit > 0 ? $t->debit : $t->credit,
                'journal_url' => $t->journal_url,
                'source_url' => $t->source_url,
            ];
        });

        // 2. Unpaid Sales Invoices
        $saleQuery = Sale::where('company_id', $companyId)
            ->where('approval_status', 'approved')
            ->where('remaining_amount', '>', 0);

        $this->applyDateFilters($saleQuery, $request);
        if ($request->filled('min_amount')) {
            $saleQuery->where('remaining_amount', '>=', $request->min_amount);
        }
        if ($request->filled('max_amount')) {
            $saleQuery->where('remaining_amount', '<=', $request->max_amount);
        }

        if ($search) {
            $saleQuery->where(function ($q) use ($search) {
                $q->where('unique_no', 'ILIKE', "%{$search}%")
                    ->orWhereRaw('total::text ILIKE ?', ["%{$search}%"])
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'ILIKE', "%{$search}%");
                    });
            });
        }
        $invoices = $saleQuery->limit($limit)->with('customer')->get()->map(function ($s) {
            return [
                'id' => $s->id,
                'type' => 'invoice',
                'source' => 'Sales Invoice',
                'source_module' => 'Sales Invoice',
                'source_icon' => 'receipt',
                'source_color' => 'green',
                'date' => $s->date,
                'description' => "Sales Invoice: " . $s->unique_no . ($s->customer ? " ({$s->customer->name})" : ""),
                'reference' => $s->unique_no,
                'amount' => $s->remaining_amount,
                'match_reason' => 'Open invoice amount',
            ];
        });

        // 3. Unpaid Bills
        $billQuery = Bill::where('company_id', $companyId)
            ->where('approval_status', 'approved')
            ->where('remaining_amount', '>', 0);

        $this->applyDateFilters($billQuery, $request);
        if ($request->filled('min_amount')) {
            $billQuery->where('remaining_amount', '>=', $request->min_amount);
        }
        if ($request->filled('max_amount')) {
            $billQuery->where('remaining_amount', '<=', $request->max_amount);
        }

        if ($search) {
            $billQuery->where(function ($q) use ($search) {
                $q->where('unique_no', 'ILIKE', "%{$search}%")
                    ->orWhereRaw('total::text ILIKE ?', ["%{$search}%"])
                    ->orWhereHas('vendor', function ($vq) use ($search) {
                        $vq->where('name', 'ILIKE', "%{$search}%");
                    });
            });
        }
        $bills = $billQuery->limit($limit)->with('vendor')->get()->map(function ($b) {
            return [
                'id' => $b->id,
                'type' => 'bill',
                'source' => 'Bill Payment',
                'source_module' => 'Bill Payment',
                'source_icon' => 'file-text',
                'source_color' => 'amber',
                'date' => $b->date,
                'description' => "Bill: " . $b->unique_no . ($b->vendor ? " ({$b->vendor->name})" : ""),
                'reference' => $b->unique_no,
                'amount' => $b->remaining_amount,
                'match_reason' => 'Open bill amount',
            ];
        });

        // 4. Posted Pay Runs waiting to be paid
        $payrollQuery = PayRun::with('payslips.employee')
            ->withSum('payslips as net_pay_total', 'net_pay')
            ->where('company_id', $companyId)
            ->where('status', 'posted');

        $this->applyDateFilters($payrollQuery, $request, 'payment_date');

        if ($search && !is_numeric($search)) {
            $payrollQuery->where(function ($q) use ($search) {
                $q->where('unique_no', 'ILIKE', "%{$search}%")
                    ->orWhereRaw('frequency ILIKE ?', ["%{$search}%"])
                    ->orWhereHas('payslips.employee', function ($eq) use ($search) {
                        $eq->where('full_name', 'ILIKE', "%{$search}%");
                    });
            });
        }

        $requiresAmountFilter = ($search && is_numeric($search)) || $request->filled('min_amount') || $request->filled('max_amount');

        $payrolls = $requiresAmountFilter
            ? $payrollQuery->get()
            : $payrollQuery->limit($limit)->get();

        if ($search && is_numeric($search)) {
            $amountSearch = (float) $search;
            $payrolls = $payrolls->filter(function ($payRun) use ($amountSearch) {
                return abs((float) ($payRun->net_pay_total ?? $payRun->payslips->sum('net_pay')) - $amountSearch) < 0.01;
            });
        }

        if ($request->filled('min_amount')) {
            $minAmt = (float) $request->min_amount;
            $payrolls = $payrolls->filter(function ($payRun) use ($minAmt) {
                return (float) ($payRun->net_pay_total ?? $payRun->payslips->sum('net_pay')) >= $minAmt;
            });
        }

        if ($request->filled('max_amount')) {
            $maxAmt = (float) $request->max_amount;
            $payrolls = $payrolls->filter(function ($payRun) use ($maxAmt) {
                return (float) ($payRun->net_pay_total ?? $payRun->payslips->sum('net_pay')) <= $maxAmt;
            });
        }

        if ($requiresAmountFilter) {
            $payrolls = $payrolls->take($limit)->values();
        }

        $payrolls = $payrolls->map(function ($payRun) {
            return [
                'id' => $payRun->id,
                'type' => 'payroll',
                'source' => 'Payroll',
                'source_module' => 'Payroll',
                'source_icon' => 'wallet',
                'source_color' => 'indigo',
                'date' => $payRun->payment_date,
                'description' => "Pay Run: " . ($payRun->unique_no ?? ('#' . $payRun->id)),
                'reference' => $payRun->unique_no,
                'amount' => (float) ($payRun->net_pay_total ?? $payRun->payslips->sum('net_pay')),
                'source_url' => route('payroll.pay-runs.show', $payRun->id),
                'journal_url' => $payRun->unique_no ? route('payroll.pay-runs.view-transaction', $payRun->unique_no) : null,
                'match_reason' => 'Posted payroll ready for payment',
            ];
        });

        return response()->json([
            'transactions' => $transactions,
            'invoices' => $invoices,
            'bills' => $bills,
            'payrolls' => $payrolls,
            'all' => $transactions->concat($invoices)->concat($bills)->concat($payrolls)->sortByDesc('date')->values()
        ]);
    }


    public function unreconcile(Request $request)
    {
        $request->validate([
            'line_id' => ['required', $this->statementLineExistsRule()],
            'reason' => 'required|string|min:5|max:1000',
        ]);

        $line = $this->statementLineOrFail($request->line_id);
        $this->service->unreconcile($line, $request->reason);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Transaction unreconciled successfully.']);
        }

        return back()->with('success', 'Transaction unreconciled successfully.');
    }

    public function unreconcileAll(Request $request, $accountId)
    {
        $request->validate(['reason' => 'required|string|min:5|max:1000']);
        $this->bankAccountOrFail($accountId);
        $this->service->unreconcileAll($accountId, $request->reason);

        return back()->with('success', 'All transactions unreconciled successfully.');
    }

    public function bulkUnreconcile(Request $request)
    {
        $request->validate([
            'line_ids' => 'required|array',
            'line_ids.*' => $this->statementLineExistsRule(),
            'reason' => 'required|string|min:5|max:1000',
        ]);

        foreach ($request->line_ids as $id) {
            $line = BankStatementLine::where('company_id', $this->currentCompanyId())->find($id);
            if ($line) {
                $this->service->unreconcile($line, $request->reason);
            }
        }

        return response()->json(['success' => true]);
    }

    public function destroyLine($id)
    {
        $line = BankStatementLine::where('company_id', $this->currentCompanyId())->findOrFail($id);
        $this->service->destroyLine($line);

        return back()->with('success', 'Entry deleted successfully.');
    }
    public function destroyTxn($uniqueNo)
    {
        throw ValidationException::withMessages([
            'transaction' => 'Direct deletion of posted bank transactions is disabled. Unreconcile the statement line with a reason to create a controlled reversal.',
        ]);

        $companyId = $this->currentCompanyId();
        $txns = Transaction::where('company_id', $companyId)
            ->where('unique_no', $uniqueNo)
            ->get();

        if ($txns->isEmpty()) {
            return back()->with('error', 'Entry not found.');
        }

        $saleIds = $txns->whereIn('type', ['sale_payment', 'receipt'])->pluck('reference_id')->filter()->unique();
        if ($saleIds->isNotEmpty()) {
            Sale::where('company_id', $companyId)->whereIn('id', $saleIds)->get()->each(function (Sale $sale) {
                $sale->update([
                    'paid_amount' => 0,
                    'remaining_amount' => $sale->total_after_discount ?? $sale->total ?? 0,
                    'status' => 1,
                    'approval_status' => 'approved',
                ]);
            });
        }

        $billIds = $txns->whereIn('type', ['bill_payment', 'payment'])->pluck('reference_id')->filter()->unique();
        if ($billIds->isNotEmpty()) {
            Bill::where('company_id', $companyId)->whereIn('id', $billIds)->get()->each(function (Bill $bill) {
                $bill->update([
                    'paid_amount' => 0,
                    'remaining_amount' => $bill->total_after_discount ?? $bill->total ?? 0,
                    'status' => 1,
                    'approval_status' => 'approved',
                ]);
            });
        }

        $payRunIds = $txns->where('type', 'Payroll')->pluck('reference_id')->filter()->unique();
        if ($payRunIds->isNotEmpty()) {
            PayRun::where('company_id', $companyId)->whereIn('id', $payRunIds)->update(['status' => 'posted']);
            Payslip::whereIn('pay_run_id', $payRunIds)
                ->whereHas('payRun', fn ($query) => $query->where('company_id', $companyId))
                ->update(['status' => 'processed']);
            JournalEntry::where('company_id', $companyId)->where('unique_no', $uniqueNo)->delete();
        }

        $bankStatementLineIds = $txns
            ->pluck('bank_statement_line_id')
            ->filter()
            ->unique();

        // Unreconcile related bank statement lines
        BankStatementLine::where('company_id', $companyId)
            ->whereIn('id', $bankStatementLineIds)
            ->update([
                'status' => 'Unreconciled',
            ]);

        // Nullify relation before delete
        Transaction::where('company_id', $companyId)
            ->where('unique_no', $uniqueNo)
            ->update([
                'bank_statement_line_id' => null,
            ]);

        // Delete transactions
        Transaction::where('company_id', $companyId)->where('unique_no', $uniqueNo)->delete();

        return back()->with('success', 'Entries deleted successfully.');
    }

    private function statementImportOrFail($accountId, $statementId): \App\Models\BankStatementImport
    {
        $this->bankAccountOrFail($accountId);
        return \App\Models\BankStatementImport::where('company_id', $this->currentCompanyId())
            ->where('bank_account_id', $accountId)->findOrFail($statementId);
    }

    public function reviewStatement(Request $request, $accountId, $statementId)
    {
        $request->validate(['review_note' => 'nullable|string|max:2000']);
        $statement = $this->statementImportOrFail($accountId, $statementId);
        app(\App\Services\Account\BankReconciliationControlService::class)->review($statement, $request->review_note);
        return back()->with('success', 'Statement marked as reviewed.');
    }

    public function finalizeStatement($accountId, $statementId)
    {
        $statement = $this->statementImportOrFail($accountId, $statementId);
        $report = app(\App\Services\Account\BankReconciliationReportService::class)->build(
            $this->currentCompanyId(), (int) $accountId, (int) $statementId
        );
        if (abs((float) $report['variance']) >= 0.005) {
            throw ValidationException::withMessages(['statement' => 'Adjusted bank and book balances must agree before finalization.']);
        }
        app(\App\Services\Account\BankReconciliationControlService::class)->finalize($statement);
        return back()->with('success', 'Statement finalized and locked.');
    }

    public function reopenStatement(Request $request, $accountId, $statementId)
    {
        $request->validate(['reason' => 'required|string|min:5|max:2000']);
        $statement = $this->statementImportOrFail($accountId, $statementId);
        app(\App\Services\Account\BankReconciliationControlService::class)->reopen($statement, $request->reason);
        return back()->with('success', 'Statement reopened. All subsequent changes will remain audited.');
    }

    public function statementExceptions($accountId, $statementId)
    {
        $statement = $this->statementImportOrFail($accountId, $statementId);
        return response()->json([
            'statement' => $statement,
            'unreconciled_lines' => $statement->lines()->where('status', '!=', 'Reconciled')->get(),
            'orphaned_reconciliations' => $statement->lines()->where('status', 'Reconciled')->whereDoesntHave('transactions')->get(),
            'events' => \App\Models\BankReconciliationEvent::where('bank_statement_import_id', $statement->id)->latest()->get(),
        ]);
    }

    public function updateLine(Request $request, $id)
    {
        $request->validate([
            'date' => 'required|date',
            'amount' => 'required|numeric',
            'payee' => 'nullable|string',
            'description' => 'nullable|string',
            'reference' => 'nullable|string',
        ]);

        $line = BankStatementLine::where('company_id', $this->currentCompanyId())->findOrFail($id);
        $this->service->updateLine($line, $request->all());

        return back()->with('success', 'Entry updated successfully.');
    }
}
