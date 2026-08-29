<?php
// before ui enhancement like xero have
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
use App\Services\Account\BankReconciliationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class BankReconciliationController extends Controller
{
    use \App\Traits\ExportsToExcel;

    protected $service;

    public function __construct(BankReconciliationService $service)
    {
        $this->service = $service;
    }

    private function validateAccountingPeriod(string $date): ?AccountingPeriod
    {
        $period = AccountingPeriod::findPeriodForDate($date);

        if (! $period) {
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

    private function statementLineQuery(Request $request, $accountId)
    {
        $query = BankStatementLine::where('bank_account_id', $accountId);

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

    public function index($accountId)
    {
        // 1. Fetch bank account with balance sums in one query
        $bankAccount = ChartOfAccount::withSum(['transactions as ledger_debit_sum' => function ($query) {
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
        $allAccounts = ChartOfAccount::where('type', 'Account')->get();

        // Filter collections in PHP to avoid extra queries
        $accounts = $allAccounts;
        $otherBanks = $allAccounts->filter(function ($acc) {
            return $acc->sub_type === 'BANK';
        });

        return view('pages.account.bank_reconciliation.index', compact(
            'bankAccount',
            'accounts',
            'otherBanks',
            'ledgerBalance',
            'statementBalance'
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
        $query = BankStatementLine::where('bank_account_id', $accountId)
            ->whereIn('status', ['Unreconciled', 'Partially Reconciled'])
            ->with('transactions')
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

        $companyId = auth('web')->user()->company_id ?? 1;

        // Pre-fetch all bank rules for this account to avoid N+1
        $bankRules = BankRule::where('company_id', $companyId)
            ->where('bank_account_id', $accountId)
            ->get()
            ->keyBy('payee');

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

            // Xero-style: look up bank rule for auto-fill
            $normalizedPayee = BankRule::normalizePayee($line->payee);
            $line->suggested_account_id = null;
            if (!empty($normalizedPayee) && $bankRules->has($normalizedPayee)) {
                $line->suggested_account_id = $bankRules->get($normalizedPayee)->contra_account_id;
            }
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
            $line->formatted_date = $line->date?->format('d M Y');
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

        $bankAccount = ChartOfAccount::findOrFail($accountId);

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
            ->with('bankStatementLine')
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
        $accountTransactions->getCollection()->transform(function ($txn) {
            $txn->formatted_date = $txn->date?->format('d M Y');
            return $txn;
        });

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
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
        ]);

        // Increase time limit for large imports
        set_time_limit(0);

        $file = $request->file('file');
        $handle = fopen($file->getPathname(), 'r');

        $companyId = auth('web')->user()->company_id ?? 1;
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
                        $period = $this->validateAccountingPeriod($formattedDate);
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
            $rows = $request->input('rows');
            if (empty($rows)) {
                return response()->json(['success' => true, 'message' => 'Empty chunk ignored']);
            }

            $companyId = auth('web')->user()->company_id ?? 1;
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
                        $period = $this->validateAccountingPeriod($formattedDate);
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
        $line = BankStatementLine::where('unique_no', $unique_no)->first();
        $transactions = collect();

        if (!$line) {
            // It might be a transaction that wasn't created via a statement line directly
            $txn = Transaction::withoutGlobalScope('uniqueNoNotNull')->where('unique_no', $unique_no)->first();
            if ($txn && $txn->bank_statement_line_id) {
                $line = BankStatementLine::find($txn->bank_statement_line_id);
            }
        }

        if ($line) {
            $transactions = Transaction::withoutGlobalScope('uniqueNoNotNull')
                ->where('bank_statement_line_id', $line->id)
                ->orWhere('unique_no', $unique_no)
                ->with(['account', 'vendor', 'customer'])
                ->get();
        } else {
            // Just show the transactions if no line found
            $transactions = Transaction::withoutGlobalScope('uniqueNoNotNull')
                ->where('unique_no', $unique_no)
                ->with(['account', 'vendor', 'customer'])
                ->get();

            if ($transactions->isEmpty()) {
                abort(404, 'Transaction not found');
            }
        }

        return view('pages.account.bank_reconciliation.show_transaction', compact('line', 'transactions', 'unique_no'));
    }

    public function match(Request $request)
    {
        $request->validate([
            'line_id' => 'required|exists:bank_statement_lines,id',
            'transaction_id' => 'nullable', // Single ID
            'transaction_ids' => 'nullable|array', // Multiple IDs for split
        ]);

        $line = BankStatementLine::findOrFail($request->line_id);
        $transactionIds = $request->transaction_ids ?? ($request->transaction_id ? [$request->transaction_id] : []);

        if (empty($transactionIds)) {
            return back()->with('error', 'No transactions selected for matching.');
        }

        $this->service->reconcile($line, $transactionIds);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Reconciled successfully.']);
        }
        return back()->with('success', 'Reconciled successfully.');
    }

    public function matchInvoice(Request $request)
    {
        $request->validate([
            'line_id' => 'required|exists:bank_statement_lines,id',
            'invoice_id' => 'nullable',
            'type' => 'required|in:Sale,Bill,Payroll',
            'allocations' => 'nullable|string',
            'adjustment_account_id' => 'nullable|exists:chart_of_accounts,id',
            'adjustment_amount' => 'nullable|numeric|min:0',
            'adjustment_description' => 'nullable|string',
        ]);

        $line = BankStatementLine::findOrFail($request->line_id);
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
            // For legacy automated match, we just use the original method (adjustments not supported here)
            $this->service->reconcileWithInvoice($line, $request->invoice_id, $request->type, $uniqueNo);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Invoice matched and reconciled successfully.']);
        }
        return back()->with('success', 'Invoice matched and reconciled successfully.');
    }


    public function create(Request $request)
    {
        $request->validate([
            'line_id' => 'required|exists:bank_statement_lines,id',
            // convert to transaction validation
            'contra_account_id' => 'required|exists:chart_of_accounts,id',
            // other fields like tax_rate_id etc.
        ]);

        $line = BankStatementLine::findOrFail($request->line_id);

        $uniqueNo = $line->unique_no;

        // Logic to create transaction via service
        $this->service->createTransaction($line, $request->all(), $uniqueNo ?? null);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Transaction created and reconciled.']);
        }
        return back()->with('success', 'Transaction created and reconciled.');
    }

    public function transfer(Request $request)
    {
        $request->validate([
            'line_id' => 'required|exists:bank_statement_lines,id',
            'target_account_id' => 'required|exists:chart_of_accounts,id',
        ]);

        $line = BankStatementLine::findOrFail($request->line_id);

        $uniqueNo = $line->unique_no;

        $this->service->createTransfer($line, $request->target_account_id, $request->reference, $uniqueNo ?? null);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Transfer recorded and reconciled.']);
        }
        return back()->with('success', 'Transfer recorded and reconciled.');
    }

    public function updateNote(Request $request)
    {
        $request->validate([
            'line_id' => 'required|exists:bank_statement_lines,id',
            'note' => 'nullable|string',
        ]);

        $line = BankStatementLine::findOrFail($request->line_id);
        $this->service->updateNote($line, $request->note);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Note saved.']);
        }
        return back()->with('success', 'Note saved.');
    }

    // --- Manual Transaction Methods ---

    public function spendMoney($accountId)
    {
        $bankAccount = ChartOfAccount::findOrFail($accountId);
        $accounts = ChartOfAccount::where('type', '!=', 'Bank')->get(); // Spend to Expense/Liability/etc
        return view('pages.account.bank_reconciliation.spend-money', compact('bankAccount', 'accounts'));
    }

    public function storeSpendMoney(Request $request, $accountId)
    {
        $request->validate([
            'contra_account_id' => 'required|exists:chart_of_accounts,id',
            'date' => 'required|date',
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $this->service->createDirectTransaction($accountId, $request->all(), 'payment');
        return redirect()->route('account.bank-reconciliation.index', $accountId)->with('success', 'Spend Money transaction created and reconciled.');
    }

    public function receiveMoney($accountId)
    {
        $bankAccount = ChartOfAccount::findOrFail($accountId);
        $accounts = ChartOfAccount::where('type', '!=', 'Bank')->get(); // Receive from Revenue/Asset/etc
        return view('pages.account.bank_reconciliation.receive-money', compact('bankAccount', 'accounts'));
    }

    public function storeReceiveMoney(Request $request, $accountId)
    {
        $request->validate([
            'contra_account_id' => 'required|exists:chart_of_accounts,id',
            'date' => 'required|date',
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $this->service->createDirectTransaction($accountId, $request->all(), 'receipt');
        return redirect()->route('account.bank-reconciliation.index', $accountId)->with('success', 'Receive Money transaction created and reconciled.');
    }

    public function transferMoney($accountId)
    {
        $bankAccount = ChartOfAccount::findOrFail($accountId);
        $otherBanks = ChartOfAccount::where('type', 'Account')
            ->where('sub_type', 'BANK')
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
            'target_account_id' => 'required|exists:chart_of_accounts,id',
            'date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $this->service->createDirectTransfer($accountId, $request->target_account_id, $request->all());

        return redirect()->route('account.bank-reconciliation.index', $accountId)->with('success', 'Transfer recorded and reconciled in both accounts.');
    }

    public function reconciliationReport($accountId)
    {
        $bankAccount = ChartOfAccount::findOrFail($accountId);

        // 1. Balance in Xero (Ledger Balance)
        // Calculated in controller/model typically
        // For now: Sum of all transactions
        $ledgerBalance = Transaction::where('account_id', $accountId)->sum(DB::raw('debit - credit')); // Asset: Debit increases

        // 2. Statement Balance (Imported)
        $statementBalance = BankStatementLine::where('bank_account_id', $accountId)->sum('amount');

        // 3. Unreconciled Items (Plus/Minus)
        // Ledger transactions not yet reconciled (status = 0)
        // AND Statement lines not yet reconciled? (Actually Report is usually: Statement Balance +/- Outstanding Items = Ledger Balance)

        // Details:
        // Plus Outstanding Receipts (Debits in ledger, not reconciled)
        // Details:
        // Plus Outstanding Receipts (Debits in ledger, not reconciled)
        $outstandingReceipts = Transaction::where('account_id', $accountId)
            ->whereDoesntHave('bankStatementLine')
            ->where('debit', '>', 0)
            ->get();

        // Less Outstanding Payments (Credits in ledger, not reconciled)
        $outstandingPayments = Transaction::where('account_id', $accountId)
            ->whereDoesntHave('bankStatementLine')
            ->where('credit', '>', 0)
            ->get();

        // Calculate "Adjusted Statement Balance"
        // Statement Balance + Outstanding Receipts - Outstanding Payments
        $adjustedBalance = $statementBalance + $outstandingReceipts->sum('debit') - $outstandingPayments->sum('credit');

        // Discrepancy?
        $variance = $adjustedBalance - $ledgerBalance; // Should be 0 ideally if Statement Balance is accurate to what's been reconciled

        return view('pages.account.bank_reconciliation.report', compact('bankAccount', 'ledgerBalance', 'statementBalance', 'outstandingReceipts', 'outstandingPayments', 'adjustedBalance', 'variance'));
    }

    private function storeManualTransaction(Request $request, $accountId, $type)
    {
        $request->validate([
            'contra_account_id' => 'required|exists:chart_of_accounts,id',
            'date' => 'required|date',
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
        ]);

        DB::transaction(function () use ($request, $accountId, $type) {
            $isSpend = $type === 'payment';

            // 1. Bank Leg
            $txn = new Transaction();
            $txn->company_id = auth('web')->user()->company_id ?? 1;
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
            $contra->company_id = auth('web')->user()->company_id ?? 1;
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
        $bankAccount = ChartOfAccount::findOrFail($accountId);
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

        $nextID = (BankStatementLine::max('id') ?? 0) + 1;
        $uniqueNo = 'BK-' . str_pad($nextID, 5, '0', STR_PAD_LEFT);

        BankStatementLine::create([
            'company_id' => auth('web')->user()->company_id ?? 1,
            'bank_account_id' => $accountId,
            'date' => $request->date,
            'amount' => $request->amount,
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
            'line_id' => 'required|exists:bank_statement_lines,id',
        ]);

        $line = BankStatementLine::findOrFail($request->line_id);

        $matches = Transaction::where('company_id', auth('web')->user()->company_id ?? 1)
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
        $search = $request->search;
        $limit = 50;

        // 1. Ledger Transactions
        $txnQuery = Transaction::withoutGlobalScope('uniqueNoNotNull')
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
        $transactions = $txnQuery->limit($limit)->get()->map(function ($t) {
            return [
                'id' => $t->id,
                'type' => 'transaction',
                'source' => strtolower($t->type) === 'payroll' ? 'Payroll' : 'Transaction',
                'date' => $t->date,
                'description' => $t->description,
                'reference' => $t->unique_no,
                'unique_no' => $t->unique_no,
                'amount' => (float) $t->debit > 0 ? $t->debit : $t->credit,
            ];
        });

        // 2. Unpaid Sales Invoices
        $saleQuery = Sale::where('remaining_amount', '>', 0);

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
                'date' => $s->date,
                'description' => "Sales Invoice: " . $s->unique_no . ($s->customer ? " ({$s->customer->name})" : ""),
                'reference' => $s->unique_no,
                'amount' => $s->remaining_amount,
            ];
        });

        // 3. Unpaid Bills
        $billQuery = Bill::where('remaining_amount', '>', 0);

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
                'date' => $b->date,
                'description' => "Bill: " . $b->unique_no . ($b->vendor ? " ({$b->vendor->name})" : ""),
                'reference' => $b->unique_no,
                'amount' => $b->remaining_amount,
            ];
        });

        // 4. Posted Pay Runs waiting to be paid
        $payrollQuery = PayRun::with('payslips.employee')
            ->withSum('payslips as net_pay_total', 'net_pay')
            ->where('company_id', auth('web')->user()->company_id ?? 1)
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
                'date' => $payRun->payment_date,
                'description' => "Pay Run: " . ($payRun->unique_no ?? ('#' . $payRun->id)),
                'reference' => $payRun->unique_no,
                'amount' => (float) ($payRun->net_pay_total ?? $payRun->payslips->sum('net_pay')),
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
            'line_id' => 'required|exists:bank_statement_lines,id',
        ]);

        $line = BankStatementLine::findOrFail($request->line_id);
        $this->service->unreconcile($line);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Transaction unreconciled successfully.']);
        }

        return back()->with('success', 'Transaction unreconciled successfully.');
    }

    public function unreconcileAll($accountId)
    {
        $this->service->unreconcileAll($accountId);

        return back()->with('success', 'All transactions unreconciled successfully.');
    }

    public function bulkUnreconcile(Request $request)
    {
        $request->validate([
            'line_ids' => 'required|array',
            'line_ids.*' => 'exists:bank_statement_lines,id',
        ]);

        foreach ($request->line_ids as $id) {
            $line = BankStatementLine::find($id);
            if ($line) {
                $this->service->unreconcile($line);
            }
        }

        return response()->json(['success' => true]);
    }

    public function destroyLine($id)
    {
        $line = BankStatementLine::findOrFail($id);
        $this->service->destroyLine($line);

        return back()->with('success', 'Entry deleted successfully.');
    }
    public function destroyTxn($uniqueNo)
    {
        $txns = Transaction::where('unique_no', $uniqueNo)->get();

        if ($txns->isEmpty()) {
            return back()->with('error', 'Entry not found.');
        }

        $saleIds = $txns->whereIn('type', ['sale_payment', 'receipt'])->pluck('reference_id')->filter()->unique();
        if ($saleIds->isNotEmpty()) {
            Sale::whereIn('id', $saleIds)->get()->each(function (Sale $sale) {
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
            Bill::whereIn('id', $billIds)->get()->each(function (Bill $bill) {
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
            PayRun::whereIn('id', $payRunIds)->update(['status' => 'posted']);
            Payslip::whereIn('pay_run_id', $payRunIds)->update(['status' => 'processed']);
            JournalEntry::where('unique_no', $uniqueNo)->delete();
        }

        $bankStatementLineIds = $txns
            ->pluck('bank_statement_line_id')
            ->filter()
            ->unique();

        // Unreconcile related bank statement lines
        BankStatementLine::whereIn('id', $bankStatementLineIds)
            ->update([
                'status' => 'Unreconciled',
            ]);

        // Nullify relation before delete
        Transaction::where('unique_no', $uniqueNo)
            ->update([
                'bank_statement_line_id' => null,
            ]);

        // Delete transactions
        Transaction::where('unique_no', $uniqueNo)->delete();

        return back()->with('success', 'Entries deleted successfully.');
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

        $line = BankStatementLine::findOrFail($id);
        $this->service->updateLine($line, $request->all());

        return back()->with('success', 'Entry updated successfully.');
    }
}
