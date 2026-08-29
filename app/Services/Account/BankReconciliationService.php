<?php

namespace App\Services\Account;

use App\Models\AccountingPeriod;
use App\Models\BankStatementLine;
use App\Models\Transaction;
use App\Models\ChartOfAccount;
use App\Models\Sale;
use App\Models\Bill;
use App\Models\JournalEntry;
use App\Models\BankRule;
use App\Models\PayRun;
use App\Models\Payslip;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class BankReconciliationService
{
    private function currentCompanyId(): int
    {
        return (int) (loggedCompanyId()['company_id'] ?? auth('web')->user()?->company_id);
    }

    private function bankAccountForCompanyOrFail($accountId, int $companyId): ChartOfAccount
    {
        return ChartOfAccount::forCompany($companyId)
            ->where('type', 'Account')
            ->where('sub_type', 'BANK')
            ->findOrFail($accountId);
    }

    /**
     * Validate accounting period
     */
    private function validateAccountingPeriod(string $date, ?int $companyId = null): ?AccountingPeriod
    {
        /** @var AccountingPeriod|null $period */
        $period = AccountingPeriod::findPeriodForDate($date, $companyId ?? $this->currentCompanyId());

        if (! $period || $period->is_locked) {
            throw ValidationException::withMessages([
                'date' => 'The selected date does not fall within any unlocked accounting period.',
            ]);
        }

        return $period;
    }

    private function applyInvoicePayment(Sale|Bill $item, float $amount): void
    {
        $remainingBefore = (float) ($item->remaining_amount ?? $item->total_after_discount ?? $item->total ?? 0);
        $appliedAmount = min(abs($amount), max(0, $remainingBefore));

        $item->paid_amount = (float) ($item->paid_amount ?? 0) + $appliedAmount;
        $item->remaining_amount = max(0, $remainingBefore - $appliedAmount);
        $item->status = 1;
        if ($item instanceof Sale) {
            $item->approval_status = 'approved';
            $item->payment_status = app(\App\Services\SaleService::class)->paymentStatus(
                (float) $item->paid_amount,
                (float) $item->remaining_amount
            );
        } else {
            if ($item->approval_status !== 'approved') throw new \RuntimeException('Only approved bills can be reconciled.');
            $item->payment_status = app(\App\Services\BillService::class)->paymentStatus(
                (float) $item->paid_amount,
                (float) $item->remaining_amount
            );
        }
        $item->save();
    }

    private function resetInvoicePayment(Sale|Bill $item): void
    {
        $item->paid_amount = 0;
        $item->remaining_amount = (float) ($item->total_after_discount ?? $item->total ?? 0);
        $item->status = 1;
        $item->approval_status = 'approved';
        if ($item instanceof Sale) {
            $item->payment_status = 'unpaid';
        } elseif ($item instanceof Bill) {
            $item->payment_status = 'unpaid';
        }
        $item->save();
    }

    /**
     * Smart-match scoring config. Weights sum to 100.
     * See scoreMatches() for the full algorithm.
     */
    private const MATCH_DATE_WINDOW_DAYS = 14;   // ± window for candidate fetch
    private const MATCH_WEIGHT_DATE      = 30;
    private const MATCH_WEIGHT_TEXT      = 40;
    private const MATCH_WEIGHT_REFERENCE = 30;
    private const MATCH_HIGH_THRESHOLD   = 85;
    private const MATCH_MEDIUM_THRESHOLD = 60;

    /** Tokens stripped from descriptions before similarity scoring (low-signal noise). */
    private const TEXT_STOPWORDS = [
        'the', 'and', 'for', 'from', 'to', 'of', 'on', 'at', 'by', 'in', 'with',
        'payment', 'pmt', 'pay', 'paid', 'invoice', 'inv', 'ref', 'reference',
        'transfer', 'tfr', 'txn', 'transaction', 'no', 'number', 'bill',
        'payroll', 'salary', 'salaries', 'wage', 'wages',
    ];

    /**
     * Find suggested matches for a single bank statement line.
     * Includes existing transactions and unpaid invoices/bills.
     */
    public function findMatches(BankStatementLine $line): Collection
    {
        $dateWindow = max(1, (int) config('bank_reconciliation.date_window_days', self::MATCH_DATE_WINDOW_DAYS));
        $amountTolerance = max(0, (float) config('bank_reconciliation.amount_tolerance', 0.01));
        $start = $line->date->copy()->subDays($dateWindow);
        $end   = $line->date->copy()->addDays($dateWindow);
        $absAmount = abs($line->remaining_amount ?? $line->amount);

        if ($absAmount < $amountTolerance) {
            return collect([
                'transactions' => collect(),
                'invoices' => collect(),
                'bills' => collect(),
                'payrolls' => collect(),
            ]);
        }

        // 1. Existing Ledger Transactions
        $transactions = Transaction::where('company_id', $line->company_id)
            ->where('account_id', $line->bank_account_id)
            ->whereBetween('date', [$start, $end])
            ->whereDoesntHave('bankStatementLine')
            ->where(function ($q) use ($line, $absAmount, $amountTolerance) {
                if ($line->amount > 0) {
                    $q->whereBetween('debit', [$absAmount - $amountTolerance, $absAmount + $amountTolerance]);
                } else {
                    $q->whereBetween('credit', [$absAmount - $amountTolerance, $absAmount + $amountTolerance]);
                }
            })
            ->get();

        $scoredTransactions = $this->scoreMatches($line, $transactions);

        // 2. Unpaid Invoices (Sales) - Only if it's a receipt (amount > 0)
        $invoices = collect();
        if ($line->amount > 0) {
            $invoices = \App\Models\Sale::where('company_id', $line->company_id)
                ->where('approval_status', 'approved')
                ->where('remaining_amount', '>', 0)
                ->whereBetween('date', [$start->copy()->subDays(config('bank_reconciliation.document_days_before', 30)), $end->copy()->addDays(config('bank_reconciliation.document_days_after', 7))])
                ->where(function($q) use ($absAmount, $amountTolerance) {
                    $q->whereBetween('total', [$absAmount - $amountTolerance, $absAmount + $amountTolerance])
                      ->orWhereBetween('remaining_amount', [$absAmount - $amountTolerance, $absAmount + $amountTolerance]);
                })
                ->get();
        }

        // 3. Unpaid Bills (Purchases) - Only if it's a payment (amount < 0)
        $bills = collect();
        if ($line->amount < 0) {
            $bills = \App\Models\Bill::where('company_id', $line->company_id)
                ->where('approval_status', 'approved')
                ->where('remaining_amount', '>', 0)
                ->whereBetween('date', [$start->copy()->subDays(config('bank_reconciliation.document_days_before', 30)), $end->copy()->addDays(config('bank_reconciliation.document_days_after', 7))])
                ->where(function($q) use ($absAmount, $amountTolerance) {
                    $q->whereBetween('total', [$absAmount - $amountTolerance, $absAmount + $amountTolerance])
                      ->orWhereBetween('remaining_amount', [$absAmount - $amountTolerance, $absAmount + $amountTolerance]);
                })
                ->get();
        }

        // 4. Posted payroll pay runs - Only if it's a payment (amount < 0)
        $payrolls = collect();
        if ($line->amount < 0) {
            $payrolls = PayRun::with('payslips.employee')
                ->withSum('payslips as net_pay_total', 'net_pay')
                ->where('company_id', $line->company_id)
                ->where('status', 'posted')
                ->whereBetween('payment_date', [$start->copy()->subDays(config('bank_reconciliation.document_days_before', 30)), $end->copy()->addDays(config('bank_reconciliation.document_days_after', 7))])
                ->get()
                ->filter(function (PayRun $payRun) use ($absAmount, $amountTolerance) {
                    return abs((float) ($payRun->net_pay_total ?? 0) - $absAmount) <= $amountTolerance;
                })
                ->values();
        }

        return collect([
            'transactions' => $scoredTransactions,
            'invoices' => $this->scoreInvoices($line, $invoices, 'Sale'),
            'bills' => $this->scoreInvoices($line, $bills, 'Bill'),
            'payrolls' => $this->scorePayrolls($line, $payrolls),
        ]);
    }

    /**
     * Score posted pay runs against a statement line.
     */
    public function scorePayrolls(BankStatementLine $line, Collection $payRuns): Collection
    {
        if ($payRuns->isEmpty()) return collect();

        $lineText = $this->normalizeText(trim(($line->payee ?? '') . ' ' . ($line->description ?? '') . ' ' . ($line->reference ?? '')));
        $lineTokens = $this->tokenize($lineText);
        $lineRef = $this->normalizeReference($line->reference ?? '');

        return $payRuns->map(function (PayRun $payRun) use ($line, $lineText, $lineTokens, $lineRef) {
            $reasons = ['Amount matches'];

            $paymentDate = $payRun->payment_date ?? $payRun->period_end;
            $daysDiff = abs($line->date->diffInDays($paymentDate));
            $dateScore = self::MATCH_WEIGHT_DATE * max(0, 1 - $daysDiff / max(1, config('bank_reconciliation.document_days_before', 30)));

            if ($daysDiff === 0) $reasons[] = 'Same payment date';
            elseif ($daysDiff <= 7) $reasons[] = 'Payment date within 7 days';

            $employeeNames = $payRun->payslips
                ->map(fn (Payslip $payslip) => $payslip->employee->full_name ?? '')
                ->filter()
                ->implode(' ');

            $payrollText = $this->normalizeText(trim(implode(' ', [
                $payRun->unique_no,
                $payRun->frequency,
                optional($payRun->period_start)->format('Y-m-d'),
                optional($payRun->period_end)->format('Y-m-d'),
                $employeeNames,
            ])));
            $payrollTokens = $this->tokenize($payrollText);
            $textPct = $this->textSimilarity($lineText, $payrollText, $lineTokens, $payrollTokens);
            $textScore = self::MATCH_WEIGHT_TEXT * $textPct;

            if ($textPct >= 0.5) $reasons[] = 'Payroll details match';

            $referenceScore = 0;
            if ($lineRef !== '') {
                $payRunRef = $this->normalizeReference($payRun->unique_no ?? '');
                if ($payRunRef !== '' && str_contains($payRunRef, $lineRef)) {
                    $referenceScore = self::MATCH_WEIGHT_REFERENCE;
                    $reasons[] = 'Reference matched';
                }
            }

            $score = (int) round(min(100, $dateScore + $textScore + $referenceScore + 30));

            $payRun->net_pay_total = (float) ($payRun->net_pay_total ?? $payRun->payslips->sum('net_pay'));
            $payRun->remaining_amount = $payRun->net_pay_total;
            $payRun->score = $score;
            $payRun->confidence = $this->bucketConfidence($score);
            $payRun->reasons = $reasons;
            $payRun->match_type = 'Payroll';

            return $payRun;
        })->sortByDesc('score')->values();
    }

    /**
     * Score invoices/bills against a statement line.
     */
    public function scoreInvoices(BankStatementLine $line, Collection $items, string $type): Collection
    {
        if ($items->isEmpty()) return collect();

        $lineText = $this->normalizeText(trim(($line->payee ?? '') . ' ' . ($line->description ?? '')));
        $lineTokens = $this->tokenize($lineText);

        return $items->map(function ($item) use ($line, $lineText, $lineTokens, $type) {
            $reasons = ['Amount matches'];
            
            // Date proximity
            $daysDiff = abs($line->date->diffInDays($item->date));
            $dateScore = self::MATCH_WEIGHT_DATE * max(0, 1 - $daysDiff / max(1, config('bank_reconciliation.document_days_before', 30)));
            
            if ($daysDiff === 0) $reasons[] = 'Same date';
            elseif ($daysDiff <= 7) $reasons[] = 'Date within 7 days';

            // Text similarity
            $itemName = $type === 'Sale' ? ($item->customer->name ?? '') : ($item->vendor->name ?? '');
            $itemText = $this->normalizeText($itemName . ' ' . ($item->invoice_no ?? ''));
            $itemTokens = $this->tokenize($itemText);
            $textPct = $this->textSimilarity($lineText, $itemText, $lineTokens, $itemTokens);
            $textScore = self::MATCH_WEIGHT_TEXT * $textPct;

            if ($textPct >= 0.5) $reasons[] = 'Reference/Contact matches';

            $score = (int) round(min(100, $dateScore + $textScore + (abs($item->total - abs($line->remaining_amount ?? $line->amount)) < 0.01 ? 30 : 0)));

            $item->score = $score;
            $item->confidence = $this->bucketConfidence($score);
            $item->reasons = $reasons;
            $item->match_type = $type;
            return $item;
        })->sortByDesc('score')->values();
    }

    /**
     * Score a pre-fetched candidate set against a statement line.
     *
     * Caller is responsible for the amount gate (exact debit/credit equality)
     * and for excluding already-reconciled transactions. This method only
     * ranks candidates that are already known to be amount-eligible.
     *
     * Returns the candidates sorted by score descending, each enriched with:
     *   - score      (int 0..100)
     *   - confidence ('high' | 'medium' | 'low')
     *   - reasons    (string[])  human-readable factor breakdown
     *
     * TODO(ml): once we have enough accepted/rejected pairs logged, replace
     * the rule-based scoring here with a learned model. The contract of
     * (line, candidates) -> ranked candidates with confidence stays the same.
     */
    public function scoreMatches(BankStatementLine $line, Collection $candidates): Collection
    {
        if ($candidates->isEmpty()) {
            return collect();
        }

        $lineText = $this->normalizeText(trim(($line->payee ?? '') . ' ' . ($line->description ?? '')));
        $lineTokens = $this->tokenize($lineText);
        $lineRef = $this->normalizeReference($line->reference ?? '');

        $scored = $candidates->map(function (Transaction $txn) use ($line, $lineText, $lineTokens, $lineRef) {
            $reasons = ['Amount exact'];

            // --- Date proximity (max MATCH_WEIGHT_DATE) ---
            $daysDiff = abs($line->date->diffInDays($txn->date));
            $dateScore = self::MATCH_WEIGHT_DATE
                * max(0, 1 - $daysDiff / max(1, (int) config('bank_reconciliation.date_window_days', self::MATCH_DATE_WINDOW_DAYS)));
            if ($daysDiff === 0) {
                $reasons[] = 'Same date';
            } elseif ($daysDiff <= 2) {
                $reasons[] = "Date within {$daysDiff} day" . ($daysDiff === 1 ? '' : 's');
            } else {
                $reasons[] = "Date {$daysDiff} days off";
            }

            // --- Text similarity (max MATCH_WEIGHT_TEXT) ---
            $txnText = $this->normalizeText($txn->description ?? '');
            $txnTokens = $this->tokenize($txnText);
            $textPct = $this->textSimilarity($lineText, $txnText, $lineTokens, $txnTokens);
            $textScore = self::MATCH_WEIGHT_TEXT * $textPct;
            if ($textPct >= 0.6) {
                $reasons[] = 'Description ' . round($textPct * 100) . '% similar';
            }

            // --- Reference match (all-or-nothing, MATCH_WEIGHT_REFERENCE) ---
            $referenceScore = 0;
            if ($lineRef !== '') {
                $txnUnique = $this->normalizeReference($txn->unique_no ?? '');
                $txnDescRef = $this->normalizeReference($txn->description ?? '');
                if (
                    ($txnUnique !== '' && str_contains($txnUnique, $lineRef))
                    || ($txnDescRef !== '' && str_contains($txnDescRef, $lineRef))
                ) {
                    $referenceScore = self::MATCH_WEIGHT_REFERENCE;
                    $reasons[] = 'Reference matched';
                }
            }

            $score = (int) round(min(100, $dateScore + $textScore + $referenceScore));

            $txn->score = $score;
            $txn->confidence = $this->bucketConfidence($score);
            $txn->reasons = $reasons;
            return $txn;
        });

        return $scored
            ->sortByDesc('score')
            ->values();
    }

    private function bucketConfidence(int $score): string
    {
        if ($score >= config('bank_reconciliation.confidence.high', self::MATCH_HIGH_THRESHOLD)) return 'high';
        if ($score >= config('bank_reconciliation.confidence.medium', self::MATCH_MEDIUM_THRESHOLD)) return 'medium';
        return 'low';
    }

    /** Lowercase, drop punctuation, collapse whitespace. */
    private function normalizeText(string $s): string
    {
        $s = mb_strtolower($s);
        $s = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $s) ?? '';
        $s = preg_replace('/\s+/', ' ', $s) ?? '';
        return trim($s);
    }

    /** Tokenize normalized text; drop stopwords and tokens shorter than 2 chars. */
    private function tokenize(string $normalized): array
    {
        if ($normalized === '') return [];
        $tokens = explode(' ', $normalized);
        $tokens = array_filter($tokens, fn ($t) => strlen($t) >= 2 && !in_array($t, self::TEXT_STOPWORDS, true));
        return array_values(array_unique($tokens));
    }

    /** Strip separators so "INV-2024/001" and "INV2024001" compare equal. */
    private function normalizeReference(string $ref): string
    {
        $ref = mb_strtolower($ref);
        return preg_replace('/[\s\-\/_.]+/u', '', $ref) ?? '';
    }

    /**
     * Combined text similarity in [0, 1].
     * Returns max of (similar_text percent, Jaccard token overlap) so that
     * a strong signal in either dimension carries the score.
     */
    private function textSimilarity(string $a, string $b, array $tokensA, array $tokensB): float
    {
        if ($a === '' || $b === '') return 0.0;

        // Char-level similarity via PHP's similar_text (returns matching-char count).
        similar_text($a, $b, $charPct);

        // Jaccard on tokens.
        $jaccard = 0.0;
        if (!empty($tokensA) && !empty($tokensB)) {
            $intersect = count(array_intersect($tokensA, $tokensB));
            $union = count(array_unique(array_unique(array_merge($tokensA, $tokensB))));
            $jaccard = $union > 0 ? $intersect / $union : 0.0;
        }

        return max($charPct / 100, $jaccard);
    }

    /**
     * Reconcile a line with one or more transactions.
     */
    public function reconcile(BankStatementLine $line, $transactions)
    {
        $ids = collect($transactions instanceof Transaction ? [$transactions] : $transactions)->map(fn ($transaction) => $transaction instanceof Transaction ? $transaction->id : $transaction)->all();
        app(BankReconciliationControlService::class)->reconcileExisting($line, $ids);
    }

    /**
     * Reconcile a statement line with an unpaid Sales Invoice or Purchase Bill.
     */
    public function reconcileWithInvoice(BankStatementLine $line, $itemId, string $type, string $uniqueNo)
    {
        if ($type === 'Payroll') {
            $this->reconcileWithPayroll($line, $itemId, $uniqueNo);
            return;
        }

        $this->reconcileWithInvoiceAllocations($line, [[
            'id' => $itemId,
            'amount' => abs((float) ($line->remaining_amount ?? $line->amount)),
        ]], $type, $uniqueNo);
        return;

        /** @var AccountingPeriod $period */
        $period = $this->validateAccountingPeriod($line->date->format('Y-m-d'), $line->company_id);
        $absAmount = abs($line->amount);

        DB::transaction(function () use ($line, $itemId, $type, $period, $absAmount, $uniqueNo) {
            $item = ($type === 'Sale') 
                ? Sale::findOrFail($itemId) 
                : Bill::findOrFail($itemId);

            // 1. Determine contra account (AR or AP)
            $contraAccountId = ($type === 'Sale') ? $item->customer->account_id : $item->vendor->account_id;
            
            // Fallback to standard AR/AP if contact doesn't have one
            if (!$contraAccountId) {
                if ($type === 'Sale') {
                    $contraAccountId = ChartOfAccount::getAccountsReceivableId($line->company_id);
                } else {
                    $contraAccountId = ChartOfAccount::getAccountsPayableId($line->company_id);
                }
            }

            DB::beginTransaction();
            try {
                // 2. Create the Payment Transactions (Double Entry)
                // Bank Leg
                $txnA = Transaction::create([
                    'company_id' => $line->company_id,
                    'accounting_period_id' => $period->id,
                    'account_id' => $line->bank_account_id,
                    'vendor_id' => ($type === 'Bill') ? $item->vendor_id : null,
                    'customer_id' => ($type === 'Sale') ? $item->customer_id : null,
                    'date' => $line->date,
                    'description' => ($type === 'Sale' ? 'Payment received for ' : 'Payment made for ') . $item->unique_no,
                    'debit' => ($line->amount > 0) ? $absAmount : 0,
                    'credit' => ($line->amount < 0) ? $absAmount : 0,
                    'reference_id' => $item->id,
                    'unique_no' => $uniqueNo,
                    'invoice_no' => $item->invoice_no,
                    'bank_statement_line_id' => $line->id,
                    'type' => strtolower($type) . '_payment',
                    'status' => 1,
                    'payable_type' => ($type === 'Bill') ? 'vendor' : null,
                    'payable_id' => ($type === 'Bill') ? $item->vendor_id : null,
                    'created_by' => auth('web')->user()->id ?? 1,
                    'updated_by' => auth('web')->user()->id ?? 1,
                ]);

                // Contra Leg (AR/AP)
                $txnB = Transaction::create([
                    'company_id' => $line->company_id,
                    'accounting_period_id' => $period->id,
                    'account_id' => $contraAccountId,
                    'vendor_id' => ($type === 'Bill') ? $item->vendor_id : null,
                    'customer_id' => ($type === 'Sale') ? $item->customer_id : null,
                    'date' => $line->date,
                    'description' => ($type === 'Sale' ? 'Payment received for ' : 'Payment made for ') . $item->unique_no,
                    'debit' => ($line->amount < 0) ? $absAmount : 0,
                    'credit' => ($line->amount > 0) ? $absAmount : 0,
                    'reference_id' => $item->id,
                    'unique_no' => $uniqueNo,
                    'invoice_no' => $item->invoice_no,
                    'type' => strtolower($type) . '_payment',
                    'status' => 1,
                    'payable_type' => ($type === 'Bill') ? 'vendor' : null,
                    'payable_id' => ($type === 'Bill') ? $item->vendor_id : null,
                    'created_by' => auth('web')->user()->id ?? 1,
                    'updated_by' => auth('web')->user()->id ?? 1,
                ]);

                DB::commit();

                Log::info('Transaction created successfully', [
                    'line_id' => $line->id,
                    'item' => $item->unique_no,
                    'amount' => $absAmount
                ]);

                Log::info('Transactions updated', [
                    'txnA' =>  $txnA,
                    'txnB' => $txnB,
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Transaction failed', [
                    'error' => $e->getMessage(),
                    'line_id' => $line->id,
                    'item' => $item->unique_no
                ]);
            }

            // 3. Update the Invoice/Bill payment balances
            $this->applyInvoicePayment($item, $absAmount);

            // 4. Mark Bank Statement Line as Reconciled
            $netMatched = Transaction::where('bank_statement_line_id', $line->id)->sum(DB::raw('debit - credit'));
            $line->remaining_amount = $line->amount - $netMatched;

            if (abs($line->remaining_amount) < 0.01) {
                $line->status = 'Reconciled';
                $line->remaining_amount = 0;
            } else {
                $line->status = 'Partially Reconciled';
            }
            $line->save();
        });
    }

    /**
     * Reconcile a statement line with a posted payroll pay run by recording the payroll payment.
     */
    public function reconcileWithPayroll(BankStatementLine $line, $payRunId, string $uniqueNo)
    {
        if ($line->amount >= 0) {
            throw ValidationException::withMessages([
                'line_id' => 'Payroll payments can only be reconciled against spent statement lines.',
            ]);
        }

        /** @var AccountingPeriod $period */
        $period = $this->validateAccountingPeriod($line->date->format('Y-m-d'), $line->company_id);
        $absAmount = abs($line->remaining_amount ?? $line->amount);

        DB::transaction(function () use ($line, $payRunId, $period, $absAmount, $uniqueNo) {
            $payRun = PayRun::with('payslips.employee')
                ->where('company_id', $line->company_id)
                ->findOrFail($payRunId);

            if ($payRun->status !== 'posted') {
                throw ValidationException::withMessages([
                    'pay_run' => 'Only posted pay runs can be paid from bank reconciliation.',
                ]);
            }

            $totalNetPay = (float) $payRun->payslips->sum('net_pay');
            if (abs($totalNetPay - $absAmount) >= 0.01) {
                throw ValidationException::withMessages([
                    'pay_run' => 'The pay run net pay does not match the selected statement line amount.',
                ]);
            }

            $wagesPayableAccount = ChartOfAccount::getWagesPayableAccount($payRun->company_id);
            if (!$wagesPayableAccount) {
                throw ValidationException::withMessages([
                    'pay_run' => "Accounting failure: 'Wages Payable' or 'Accrued Salaries' account not found.",
                ]);
            }

            $authId = auth('web')->id() ?: 1;
            $now = now();
            $startDateStr = $payRun->period_start ? Carbon::parse($payRun->period_start)->format('Y-m-d') : '';
            $endDateStr = $payRun->period_end ? Carbon::parse($payRun->period_end)->format('Y-m-d') : '';
            $narration = 'Payment of Payroll for period ' . $startDateStr . ' to ' . $endDateStr;
            $description = 'Payroll PMT: ' . ($payRun->unique_no ?: $narration);

            JournalEntry::insert([
                [
                    'company_id' => $payRun->company_id,
                    'accounting_period_id' => $period->id,
                    'account_id' => $wagesPayableAccount->id,
                    'date' => $line->date,
                    'unique_no' => $uniqueNo,
                    'narration' => $narration,
                    'description' => $description,
                    'debit' => $totalNetPay,
                    'credit' => 0,
                    'created_by' => $authId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'company_id' => $payRun->company_id,
                    'accounting_period_id' => $period->id,
                    'account_id' => $line->bank_account_id,
                    'date' => $line->date,
                    'unique_no' => $uniqueNo,
                    'narration' => $narration,
                    'description' => $description,
                    'debit' => 0,
                    'credit' => $totalNetPay,
                    'created_by' => $authId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);

            $transactions = [];
            foreach ($payRun->payslips as $payslip) {
                $employeeId = $payslip->employee_id;
                $amount = (float) $payslip->net_pay;

                if ($amount <= 0) {
                    continue;
                }

                $transactions[] = [
                    'company_id' => $payRun->company_id,
                    'accounting_period_id' => $period->id,
                    'account_id' => $wagesPayableAccount->id,
                    'payable_id' => $employeeId,
                    'payable_type' => 'employee',
                    'date' => $line->date,
                    'unique_no' => $uniqueNo,
                    'invoice_no' => $payRun->unique_no,
                    'reference_id' => $payRun->id,
                    'description' => $description,
                    'type' => 'Payroll',
                    'source_system' => 'bank_reconciliation',
                    'debit' => $amount,
                    'credit' => 0,
                    'status' => 1,
                    'bank_statement_line_id' => null,
                    'created_by' => $authId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $transactions[] = [
                    'company_id' => $payRun->company_id,
                    'accounting_period_id' => $period->id,
                    'account_id' => $line->bank_account_id,
                    'payable_id' => $employeeId,
                    'payable_type' => 'employee',
                    'date' => $line->date,
                    'unique_no' => $uniqueNo,
                    'invoice_no' => $payRun->unique_no,
                    'reference_id' => $payRun->id,
                    'description' => $description,
                    'type' => 'Payroll',
                    'source_system' => 'bank_reconciliation',
                    'debit' => 0,
                    'credit' => $amount,
                    'status' => 1,
                    'bank_statement_line_id' => $line->id,
                    'created_by' => $authId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            Transaction::insert($transactions);
            $controls = app(BankReconciliationControlService::class);
            Transaction::where('company_id', $line->company_id)
                ->where('bank_statement_line_id', $line->id)
                ->where('unique_no', $uniqueNo)
                ->get()
                ->each(fn (Transaction $transaction) => $controls->recordGeneratedMatch($line, $transaction, (float) $transaction->credit, 'payroll', $payRun));

            $payRun->payslips()->update(['status' => 'paid']);
            $payRun->update(['status' => 'paid']);

            $netMatched = Transaction::where('bank_statement_line_id', $line->id)->sum(DB::raw('debit - credit'));
            $line->remaining_amount = $line->amount - $netMatched;

            if (abs($line->remaining_amount) < 0.01) {
                $line->status = 'Reconciled';
                $line->remaining_amount = 0;
            } else {
                $line->status = 'Partially Reconciled';
            }

            $line->save();
        });
    }

    /**
     * Reconcile a statement line with an array of partial/full invoice allocations.
     * Allocations is an array of ['id' => invoiceId, 'amount' => allocatedAmount].
     * Payroll allocations delegate to the payroll reconciler because pay runs are paid as a batch.
     */
    public function reconcileWithInvoiceAllocations(BankStatementLine $line, array $allocations, string $type, string $uniqueNo, array $adjustment = null)
    {
        if ($type === 'Payroll') {
            $payrollAllocation = collect($allocations)->first();

            if (empty($payrollAllocation['id'])) {
                throw ValidationException::withMessages([
                    'pay_run' => 'Pay Run ID is required for payroll reconciliation.',
                ]);
            }

            if ($adjustment) {
                throw ValidationException::withMessages([
                    'pay_run' => 'Payroll reconciliation does not support bank adjustments.',
                ]);
            }

            $this->reconcileWithPayroll($line, $payrollAllocation['id'], $uniqueNo);
            return;
        }

        /** @var AccountingPeriod $period */
        $period = $this->validateAccountingPeriod($line->date->format('Y-m-d'), $line->company_id);

        DB::transaction(function () use ($line, $allocations, $type, $period, $uniqueNo, $adjustment) {
            $line = BankStatementLine::where('company_id', $line->company_id)->lockForUpdate()->findOrFail($line->id);
            $controls = app(BankReconciliationControlService::class);
            $controls->assertEditable($line);
            if (($type === 'Sale' && $line->amount <= 0) || ($type === 'Bill' && $line->amount >= 0)) {
                throw ValidationException::withMessages(['type' => 'The document type does not match the statement direction.']);
            }

            $totalAllocatedAmount = 0;
            $availableAmount = abs((float) ($line->remaining_amount ?? $line->amount));

            foreach ($allocations as $allocation) {
                $itemId = $allocation['id'];
                $allocatedAmount = abs((float) $allocation['amount']);

                if ($allocatedAmount <= 0) {
                    continue;
                }
                if ($totalAllocatedAmount + $allocatedAmount - $availableAmount > 0.005) {
                    throw ValidationException::withMessages(['allocations' => 'Allocations exceed the statement line remaining amount.']);
                }

                $item = ($type === 'Sale') 
                    ? Sale::where('company_id', $line->company_id)->where('approval_status', 'approved')->lockForUpdate()->findOrFail($itemId)
                    : Bill::where('company_id', $line->company_id)->where('approval_status', 'approved')->lockForUpdate()->findOrFail($itemId);
                if ($allocatedAmount - (float) $item->remaining_amount > 0.005) {
                    throw ValidationException::withMessages(['allocations' => 'An allocation exceeds the selected document outstanding balance.']);
                }

                // 1. Determine contra account (AR or AP)
                $contraAccountId = ($type === 'Sale') ? $item->customer->account_id : $item->vendor->account_id;
                
                // Fallback to standard AR/AP if contact doesn't have one
                if (!$contraAccountId) {
                    if ($type === 'Sale') {
                        $contraAccountId = ChartOfAccount::getAccountsReceivableId($line->company_id);
                    } else {
                        $contraAccountId = ChartOfAccount::getAccountsPayableId($line->company_id);
                    }
                }

                DB::beginTransaction();
                try {
                    // 2. Create the Payment Transactions (Double Entry)
                    // Bank Leg
                    $txnA = Transaction::create([
                        'company_id' => $line->company_id,
                        'accounting_period_id' => $period->id,
                        'account_id' => $line->bank_account_id,
                        'vendor_id' => ($type === 'Bill') ? $item->vendor_id : null,
                        'customer_id' => ($type === 'Sale') ? $item->customer_id : null,
                        'date' => $line->date,
                        'description' => ($type === 'Sale' ? 'Payment received for ' : 'Payment made for ') . $item->unique_no,
                        'debit' => ($line->amount > 0) ? $allocatedAmount : 0,
                        'credit' => ($line->amount < 0) ? $allocatedAmount : 0,
                        'reference_id' => $item->id,
                        'unique_no' => $uniqueNo,
                        'source_system' => 'bank_reconciliation',
                        'invoice_no' => $item->invoice_no,
                        'bank_statement_line_id' => $line->id,
                        'type' => strtolower($type) . '_payment',
                        'status' => 1,
                        'payable_type' => ($type === 'Bill') ? 'vendor' : null,
                        'payable_id' => ($type === 'Bill') ? $item->vendor_id : null,
                        'created_by' => auth('web')->user()->id ?? 1,
                        'updated_by' => auth('web')->user()->id ?? 1,
                    ]);

                    // Contra Leg (AR/AP)
                    $txnB = Transaction::create([
                        'company_id' => $line->company_id,
                        'accounting_period_id' => $period->id,
                        'account_id' => $contraAccountId,
                        'vendor_id' => ($type === 'Bill') ? $item->vendor_id : null,
                        'customer_id' => ($type === 'Sale') ? $item->customer_id : null,
                        'date' => $line->date,
                        'description' => ($type === 'Sale' ? 'Payment received for ' : 'Payment made for ') . $item->unique_no,
                        'debit' => ($line->amount < 0) ? $allocatedAmount : 0,
                        'credit' => ($line->amount > 0) ? $allocatedAmount : 0,
                        'reference_id' => $item->id,
                        'unique_no' => $uniqueNo,
                        'source_system' => 'bank_reconciliation',
                        'invoice_no' => $item->invoice_no,
                        'type' => strtolower($type) . '_payment',
                        'status' => 1,
                        'payable_type' => ($type === 'Bill') ? 'vendor' : null,
                        'payable_id' => ($type === 'Bill') ? $item->vendor_id : null,
                        'created_by' => auth('web')->user()->id ?? 1,
                        'updated_by' => auth('web')->user()->id ?? 1,
                    ]);

                    DB::commit();

                    Log::info('Transaction created successfully for allocation', [
                        'line_id' => $line->id,
                        'item' => $item->unique_no,
                        'amount' => $allocatedAmount
                    ]);
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Allocation transaction failed', [
                        'error' => $e->getMessage(),
                        'line_id' => $line->id,
                        'item' => $item->unique_no
                    ]);
                    throw $e;
                }

                // 3. Update the Invoice/Bill payment balances
                $this->applyInvoicePayment($item, $allocatedAmount);
                $controls->recordGeneratedMatch($line, $txnA, $allocatedAmount, 'generated', $item);

                $totalAllocatedAmount += $allocatedAmount;
            }

            if ($adjustment && !empty($adjustment['account_id']) && !empty($adjustment['amount']) && (float) $adjustment['amount'] > 0) {
                $adjAmount = (float) $adjustment['amount'];
                $adjAccountId = $adjustment['account_id'];
                $adjDescription = $adjustment['description'] ?? 'Reconciliation Adjustment';
                
                $signL = $line->amount > 0 ? 1 : -1;
                $remainingBankRequired = $line->amount - ($totalAllocatedAmount * $signL);
                
                if (abs($remainingBankRequired) >= 0.01) {
                    $adjAmount = abs($remainingBankRequired); // Strictly use calculated difference to balance
                    
                    // Bank Leg for Adjustment
                    $adjustmentTxn = Transaction::create([
                        'company_id' => $line->company_id,
                        'accounting_period_id' => $period->id,
                        'account_id' => $line->bank_account_id,
                        'date' => $line->date,
                        'description' => $adjDescription,
                        'debit' => ($remainingBankRequired > 0) ? $adjAmount : 0,
                        'credit' => ($remainingBankRequired < 0) ? $adjAmount : 0,
                        'unique_no' => $uniqueNo . '-ADJ',
                        'source_system' => 'bank_reconciliation',
                        'bank_statement_line_id' => $line->id,
                        'type' => 'reconciliation_adjustment',
                        'status' => 1,
                        'created_by' => auth('web')->user()->id ?? 1,
                        'updated_by' => auth('web')->user()->id ?? 1,
                    ]);
                    
                    // Expense/Income Leg for Adjustment
                    Transaction::create([
                        'company_id' => $line->company_id,
                        'accounting_period_id' => $period->id,
                        'account_id' => $adjAccountId,
                        'date' => $line->date,
                        'description' => $adjDescription,
                        'debit' => ($remainingBankRequired < 0) ? $adjAmount : 0,
                        'credit' => ($remainingBankRequired > 0) ? $adjAmount : 0,
                        'unique_no' => $uniqueNo . '-ADJ',
                        'source_system' => 'bank_reconciliation',
                        'type' => 'reconciliation_adjustment',
                        'status' => 1,
                        'created_by' => auth('web')->user()->id ?? 1,
                        'updated_by' => auth('web')->user()->id ?? 1,
                    ]);
                    $controls->recordGeneratedMatch($line, $adjustmentTxn, $adjAmount, 'adjustment');
                    
                    Log::info('Adjustment transaction created successfully', [
                        'line_id' => $line->id,
                        'amount' => $adjAmount,
                        'account_id' => $adjAccountId
                    ]);
                }
            }

            // 4. Determine Bank Statement Line Reconciled Status
            $controls->refreshLine($line);
        });
    }

    /**
     * Create a new transaction from a statement line.
     */
    public function createTransaction(BankStatementLine $line, array $data, $uniqueNo = null): Transaction
    {
        $period = $this->validateAccountingPeriod($line->date, $line->company_id);

        // 1. Create the primary transaction (Bank Leg)
        $isSpend = $line->amount < 0;
        $absAmount = abs($line->remaining_amount ?? $line->amount);

        $transaction = new Transaction();
        $transaction->company_id = $line->company_id;
        $transaction->accounting_period_id = $period->id;
        $transaction->account_id = $line->bank_account_id;
        $transaction->unique_no = $uniqueNo;
        $transaction->invoice_no = $uniqueNo;
        $transaction->source_system = 'bank_reconciliation';
        $transaction->date = $line->date;
        $transaction->created_by = auth('web')->user()->id;
        $transaction->updated_by = auth('web')->user()->id;
        $transaction->type = $isSpend ? 'payment' : 'receipt';
        $transaction->description = $data['description'] ?? $line->description;
        if ($line->payee) {
            $transaction->description .= ' - ' . $line->payee;
        }
        $transaction->status = 1;

        if ($isSpend) {
            $transaction->credit = $absAmount;
            $transaction->debit = 0;
        } else {
            $transaction->debit = $absAmount;
            $transaction->credit = 0;
        }
        $transaction->save();

        // 2. Create the contra entry (Double Entry)
        $contra = new Transaction();
        $contra->company_id = $line->company_id;
        $contra->accounting_period_id = $period->id;
        $contra->account_id = $data['contra_account_id'];
        $contra->unique_no = $uniqueNo;
        $contra->invoice_no = $uniqueNo;
        $contra->source_system = 'bank_reconciliation';
        $contra->date = $line->date;
        $contra->created_by = auth('web')->user()->id;
        $contra->updated_by = auth('web')->user()->id;
        $contra->type = $isSpend ? 'payment' : 'receipt';
        $contra->description = $transaction->description;
        $contra->status = 1;

        if ($isSpend) {
            $contra->debit = $absAmount;
            $contra->credit = 0;
        } else {
            $contra->credit = $absAmount;
            $contra->debit = 0;
        }
        $contra->save();

        $transaction->update(['bank_statement_line_id' => $line->id]);
        $controls = app(BankReconciliationControlService::class);
        $controls->recordGeneratedMatch($line, $transaction, $absAmount, 'generated');
        $controls->refreshLine($line);

        // Auto-learn the payee → account mapping (Xero-style bank rule)
        if (!empty($line->payee)) {
            BankRule::learnRule(
                $line->company_id,
                $line->bank_account_id,
                $line->payee,
                (int) $data['contra_account_id']
            );
        }

        return $transaction;
    }


    public function createTransfer(BankStatementLine $line, $targetAccountId, $reference = null, $uniqueNo = null)
    {
        $period = $this->validateAccountingPeriod($line->date, $line->company_id);
        $targetAccount = $this->bankAccountForCompanyOrFail($targetAccountId, (int) $line->company_id);

        $isSpend = $line->amount < 0;
        $absAmount = abs($line->remaining_amount ?? $line->amount);

        // 1. Bank A (Current Account) Ledger Entry
        $txnA = new Transaction();
        $txnA->company_id = $line->company_id;
        $txnA->account_id = $line->bank_account_id;
        $txnA->accounting_period_id = $period->id;
        $txnA->date = $line->date;
        $txnA->unique_no = $uniqueNo;
        $txnA->invoice_no = $uniqueNo;
        $txnA->source_system = 'bank_reconciliation';
        $txnA->created_by = auth('web')->user()->id;
        $txnA->updated_by = auth('web')->user()->id;
        $txnA->type = 'transfer';
        $txnA->description = 'Transfer ' . ($isSpend ? 'to' : 'from') . ' other bank';
        $txnA->reference_id = $reference; // Store ref
        $txnA->status = 1;

        if ($isSpend) {
            $txnA->credit = $absAmount; // Money leaving Bank A
            $txnA->debit = 0;
        } else {
            $txnA->debit = $absAmount; // Money entering Bank A
            $txnA->credit = 0;
        }
        $txnA->save();

        // 2. Bank B (Target Account) Ledger Entry
        $txnB = new Transaction();
        $txnB->company_id = $line->company_id;
        $txnB->account_id = $targetAccountId;
        $txnB->accounting_period_id = $period->id;
        $txnB->date = $line->date;
        $txnB->unique_no = $uniqueNo;
        $txnB->invoice_no = $uniqueNo;
        $txnB->source_system = 'bank_reconciliation';
        $txnB->created_by = auth('web')->user()->id;
        $txnB->updated_by = auth('web')->user()->id;
        $txnB->type = 'transfer';
        // Description from Bank B perspective
        $txnB->description = 'Transfer ' . ($isSpend ? 'from' : 'to') . ' ' . $line->bankAccount->name;
        $txnB->reference_id = $reference;
        // Mark as Active (1) but it will be Unreconciled in Bank B because it has no bankStatementLine yet.
        $txnB->status = 1;

        if ($isSpend) {
            $txnB->debit = $absAmount; // Incoming to Bank B
            $txnB->credit = 0;
        } else {
            $txnB->credit = $absAmount; // Outgoing from Bank B
            $txnB->debit = 0;
        }
        $txnB->save();

        // 3. Reconcile current line
        $txnA->update(['bank_statement_line_id' => $line->id]);
        $controls = app(BankReconciliationControlService::class);
        $controls->recordGeneratedMatch($line, $txnA, $absAmount, 'generated');
        $controls->refreshLine($line);

        return $txnA;
    }

    public function updateNote(BankStatementLine $line, $note)
    {
        $oldNote = $line->note;
        $line->note = $note;
        $line->save();
        app(BankReconciliationControlService::class)->event($line, 'note_updated', ['old' => $oldNote, 'new' => $note]);
        return $line;
    }

    /**
     * Handle "Spend Money" / "Receive Money" manually.
     * Creates a Statement Line AND a Reconciled Transaction.
     */
    public function createDirectTransaction($accountId, array $data, string $type)
    {
        $companyId = $this->currentCompanyId();
        $period = $this->validateAccountingPeriod($data['date'], $companyId);
        $this->bankAccountForCompanyOrFail($accountId, $companyId);

        $isSpend = $type === 'payment';
        $amount = $isSpend ? -abs($data['amount']) : abs($data['amount']);

        $uniqueNo = app(BankReconciliationControlService::class)->uniqueNumber($companyId);

        // 1. Create Manual Statement Line
        $line = BankStatementLine::create([
            'company_id' => $companyId,
            'accounting_period_id' => $period->id,
            'bank_account_id' => $accountId,
            'date' => $data['date'],
            'amount' => $amount,
            'remaining_amount' => $amount,
            'payee' => $data['payee'] ?? null, // Assuming payee might be passed or part of description
            'description' => $data['description'],
            'reference' => $data['reference'] ?? null,
            'status' => 'Unreconciled', // Will be reconciled below
            'note' => 'Manual Entry via Dashboard',
            'unique_no' => $uniqueNo,
        ]);

        // 2. Create Transactions using existing logic
        // We can reuse createTransaction method but we need to massage the data slightly
        // createTransaction expects a Line and an array with 'description' and 'contra_account_id'

        $txnData = [
            'description' => $data['description'],
            'contra_account_id' => $data['contra_account_id'],
        ];

        return $this->createTransaction($line, $txnData, $uniqueNo ?? null);
    }

    /**
     * Handle "Transfer Money" manually.
     * Creates Statement Lines for both banks (optional?) or at least the source, and Reconciles.
     */
    public function createDirectTransfer($sourceAccountId, $targetAccountId, array $data)
    {
        $companyId = $this->currentCompanyId();
        $period = $this->validateAccountingPeriod($data['date'], $companyId);
        $sourceAccount = $this->bankAccountForCompanyOrFail($sourceAccountId, $companyId);
        $targetAccount = $this->bankAccountForCompanyOrFail($targetAccountId, $companyId);

        $amount = -abs($data['amount']); // Transfer OUT from Source

        $uniqueNo = app(BankReconciliationControlService::class)->uniqueNumber($companyId);

        // 1. Create Manual Statement Line for Source Bank
        $line = BankStatementLine::create([
            'company_id' => $companyId,
            'accounting_period_id' => $period->id,
            'bank_account_id' => $sourceAccountId,
            'date' => $data['date'],
            'amount' => $amount,
            'remaining_amount' => $amount,
            'description' => 'Transfer to ' . $targetAccount->name,
            'reference' => $data['reference'] ?? null,
            'status' => 'Unreconciled',
            'note' => 'Manual Transfer via Dashboard',
            'unique_no' => $uniqueNo,
        ]);

        // 2. Execute Transfer logic (Creates Txn A and Txn B, and reconciles Txn A to Line)
        $this->createTransfer($line, $targetAccountId, $data['reference'] ?? null, $uniqueNo ?? null);

        // Optional: Should we create a Statement Line for the Target Bank too? 
        // User asked "store in statement line". Ideally yes, for the target bank it appears as a deposit.

        $targetAmount = abs($data['amount']);
        $targetLine = BankStatementLine::create([
            'company_id' => $companyId,
            'accounting_period_id' => $period->id,
            'bank_account_id' => $targetAccountId,
            'date' => $data['date'],
            'amount' => $targetAmount,
            'remaining_amount' => $targetAmount,
            'description' => 'Transfer from ' . $sourceAccount->name,
            'reference' => $data['reference'] ?? null,
            'status' => 'Unreconciled',
            'note' => 'Manual Transfer via Dashboard (Target)',
            'unique_no' => app(BankReconciliationControlService::class)->uniqueNumber($companyId),
        ]);

        // Find the Transaction B that was just created by createTransfer?
        // createTransfer returns $txnA. We need to find $txnB.
        // Or we can modify createTransfer to return both?
        // Quick fix: Find the transaction created for target account with same ref/date/amount

        $txnB = Transaction::where('account_id', $targetAccountId)
            ->where('company_id', $companyId)
            ->where('date', $data['date'])
            ->where('debit', $targetAmount)
            ->where('type', 'transfer')
            ->where('reference_id', $data['reference'])
            ->latest()
            ->first();

        if ($txnB) {
            $txnB->update(['bank_statement_line_id' => $targetLine->id]);
            $controls = app(BankReconciliationControlService::class);
            $controls->recordGeneratedMatch($targetLine, $txnB, $targetAmount, 'generated');
            $controls->refreshLine($targetLine);
        }

        return $line;
    }
    public function unreconcile(BankStatementLine $line, string $reason = 'Reopened from bank reconciliation')
    {
        app(BankReconciliationControlService::class)->unreconcile($line, $reason);
        return;
        DB::transaction(function () use ($line) {
            $companyId = (int) $line->company_id;

            // Find and restore Sale/Bill payment status and remaining amount
            $payments = Transaction::where('company_id', $companyId)
                ->where('unique_no', $line->unique_no)
                ->whereIn('type', ['sale_payment', 'bill_payment', 'payment', 'receipt', 'Payroll'])
                ->get();

            $saleIds = $payments->whereIn('type', ['sale_payment', 'receipt'])->pluck('reference_id')->unique();
            if ($saleIds->isNotEmpty()) {
                Sale::where('company_id', $companyId)->whereIn('id', $saleIds)->get()->each(function (Sale $sale) {
                    $this->resetInvoicePayment($sale);
                });
            }

            $billIds = $payments->whereIn('type', ['bill_payment', 'payment'])->pluck('reference_id')->unique();
            if ($billIds->isNotEmpty()) {
                Bill::where('company_id', $companyId)->whereIn('id', $billIds)->get()->each(function (Bill $bill) {
                    $this->resetInvoicePayment($bill);
                });
            }

            $payRunIds = $payments->where('type', 'Payroll')->pluck('reference_id')->filter()->unique();
            if ($payRunIds->isNotEmpty()) {
                PayRun::where('company_id', $companyId)->whereIn('id', $payRunIds)->update(['status' => 'posted']);
                Payslip::whereIn('pay_run_id', $payRunIds)
                    ->whereHas('payRun', fn ($query) => $query->where('company_id', $companyId))
                    ->update(['status' => 'processed']);
            }

            // Disconnect matched ledger transactions (if any)
            Transaction::where('company_id', $companyId)->where('bank_statement_line_id', $line->id)->update([
                'bank_statement_line_id' => null,
            ]);

            // Delete payment/created transactions
            Transaction::where('company_id', $companyId)->where('unique_no', $line->unique_no)->delete();
            JournalEntry::where('company_id', $companyId)->where('unique_no', $line->unique_no)->delete();

            $line->status = 'Unreconciled';
            $line->remaining_amount = $line->amount;
            $line->save();
        });
    }

    public function unreconcileAll($bankAccountId, string $reason = 'Bulk unreconciliation')
    {
        $companyId = $this->currentCompanyId();
        $this->bankAccountForCompanyOrFail($bankAccountId, $companyId);
        BankStatementLine::where('company_id', $companyId)
            ->where('bank_account_id', $bankAccountId)
            ->whereIn('status', ['Reconciled', 'Partially Reconciled'])
            ->each(fn (BankStatementLine $line) => app(BankReconciliationControlService::class)->unreconcile($line, $reason));
        return;

        DB::transaction(function () use ($bankAccountId) {
            $companyId = $this->currentCompanyId();
            $this->bankAccountForCompanyOrFail($bankAccountId, $companyId);

            $reconciledLines = BankStatementLine::where('company_id', $companyId)
                ->where('bank_account_id', $bankAccountId)
                ->where('status', 'Reconciled')
                ->get();

            $lineIds = $reconciledLines->pluck('unique_no');
            $lineDatabaseIds = $reconciledLines->pluck('id');

            // Find and restore Sale/Bill payment status and remaining amount
            $payments = Transaction::where('company_id', $companyId)
                ->whereIn('unique_no', $lineIds)
                ->whereIn('type', ['sale_payment', 'bill_payment', 'payment', 'receipt', 'Payroll'])
                ->get();

            $saleIds = $payments->whereIn('type', ['sale_payment', 'receipt'])->pluck('reference_id')->unique();
            if ($saleIds->isNotEmpty()) {
                Sale::where('company_id', $companyId)->whereIn('id', $saleIds)->get()->each(function (Sale $sale) {
                    $this->resetInvoicePayment($sale);
                });
            }

            $billIds = $payments->whereIn('type', ['bill_payment', 'payment'])->pluck('reference_id')->unique();
            if ($billIds->isNotEmpty()) {
                Bill::where('company_id', $companyId)->whereIn('id', $billIds)->get()->each(function (Bill $bill) {
                    $this->resetInvoicePayment($bill);
                });
            }

            $payRunIds = $payments->where('type', 'Payroll')->pluck('reference_id')->filter()->unique();
            if ($payRunIds->isNotEmpty()) {
                PayRun::where('company_id', $companyId)->whereIn('id', $payRunIds)->update(['status' => 'posted']);
                Payslip::whereIn('pay_run_id', $payRunIds)
                    ->whereHas('payRun', fn ($query) => $query->where('company_id', $companyId))
                    ->update(['status' => 'processed']);
            }

            // Disconnect matched ledger transactions (if any)
            Transaction::where('company_id', $companyId)->whereIn('bank_statement_line_id', $lineDatabaseIds)->update([
                'bank_statement_line_id' => null,
            ]);

            // Delete payment/created transactions
            Transaction::where('company_id', $companyId)->whereIn('unique_no', $lineIds)->delete();
            JournalEntry::where('company_id', $companyId)->whereIn('unique_no', $lineIds)->delete();

            // Mark statement lines as Unreconciled
            BankStatementLine::where('company_id', $companyId)->whereIn('id', $lineDatabaseIds)->update([
                'status' => 'Unreconciled',
                'remaining_amount' => DB::raw('amount')
            ]);
        });
    }

    /**
     * Delete a statement line and its associated ledger transactions.
     */
    public function destroyLine(BankStatementLine $line)
    {
        $controls = app(BankReconciliationControlService::class);
        $controls->assertEditable($line);
        if ($line->status !== 'Unreconciled' || $line->transactions()->exists()) {
            throw ValidationException::withMessages([
                'line_id' => 'Reconciled statement lines cannot be deleted. Unreconcile them with a reason first.',
            ]);
        }
        $controls->event($line, 'deleted', ['unique_no' => $line->unique_no]);
        $line->delete();
        return;

        $this->validateAccountingPeriod($line->date instanceof \Carbon\Carbon ? $line->date->format('Y-m-d') : $line->date, $line->company_id);

        DB::transaction(function () use ($line) {
            $companyId = (int) $line->company_id;
            $transactions = Transaction::withoutGlobalScope('uniqueNoNotNull')
                ->where('company_id', $companyId)
                ->where('bank_statement_line_id', $line->id)
                ->get();

            // Handle Sale/Bill payment reversal for matched invoices/bills
            $saleIds = $transactions->whereIn('type', ['sale_payment', 'receipt'])->pluck('reference_id')->unique();
            if ($saleIds->isNotEmpty()) {
                Sale::where('company_id', $companyId)->whereIn('id', $saleIds)->get()->each(function (Sale $sale) {
                    $this->resetInvoicePayment($sale);
                });
            }

            $billIds = $transactions->whereIn('type', ['bill_payment', 'payment'])->pluck('reference_id')->unique();
            if ($billIds->isNotEmpty()) {
                Bill::where('company_id', $companyId)->whereIn('id', $billIds)->get()->each(function (Bill $bill) {
                    $this->resetInvoicePayment($bill);
                });
            }

            $payRunIds = $transactions->where('type', 'Payroll')->pluck('reference_id')->filter()->unique();
            if ($payRunIds->isNotEmpty()) {
                PayRun::where('company_id', $companyId)->whereIn('id', $payRunIds)->update(['status' => 'posted']);
                Payslip::whereIn('pay_run_id', $payRunIds)
                    ->whereHas('payRun', fn ($query) => $query->where('company_id', $companyId))
                    ->update(['status' => 'processed']);
                JournalEntry::where('company_id', $companyId)->where('unique_no', $line->unique_no)->delete();
            }

            foreach ($transactions as $txn) {
                // Find and delete contra or related transactions
                $relatedQuery = Transaction::withoutGlobalScope('uniqueNoNotNull')
                    ->where('company_id', $txn->company_id)
                    ->where('date', $txn->date)
                    ->where('type', $txn->type)
                    ->where('description', $txn->description)
                    ->where('id', '!=', $txn->id);

                if ($txn->reference_id) {
                    $relatedQuery->where('reference_id', $txn->reference_id);
                }

                $related = $relatedQuery->get()->filter(function ($r) use ($txn) {
                    return ($r->debit == $txn->credit && $r->credit == $txn->debit);
                });

                foreach ($related as $relTxn) {
                    /** @var BankStatementLine|null $relLine */
                    $relLine = BankStatementLine::where('company_id', $companyId)->where('id', $relTxn->bank_statement_line_id)->first();
                    if ($relLine instanceof BankStatementLine) {
                        $relLine->delete();
                    }
                    $relTxn->delete();
                }

                $txn->delete();
            }

            $line->delete();
        });
    }

    /**
     * Update a statement line and its associated ledger transactions.
     */
    public function updateLine(BankStatementLine $line, array $data)
    {
        app(BankReconciliationControlService::class)->assertEditable($line);
        if ($line->status !== 'Unreconciled' || $line->transactions()->exists()) {
            throw ValidationException::withMessages([
                'line_id' => 'Financial fields are locked after matching. Unreconcile the line before editing it.',
            ]);
        }

        // 1. Validate Existing Date
        $this->validateAccountingPeriod($line->date instanceof \Carbon\Carbon ? $line->date->format('Y-m-d') : $line->date, $line->company_id);

        // 2. Validate New Date
        if (isset($data['date'])) {
            $this->validateAccountingPeriod($data['date'], $line->company_id);
        }

        DB::transaction(function () use ($line, $data) {
            // 1. Update the line itself
            $line->update([
                'date' => $data['date'] ?? $line->date,
                'amount' => $data['amount'] ?? $line->amount,
                'payee' => $data['payee'] ?? $line->payee,
                'description' => $data['description'] ?? $line->description,
                'reference' => $data['reference'] ?? $line->reference,
            ]);

            // 2. If it has associated transactions, update them and their contra/transfer equivalents
            $transactions = Transaction::withoutGlobalScope('uniqueNoNotNull')
                ->where('company_id', $line->company_id)
                ->where('bank_statement_line_id', $line->id)
                ->get();

            foreach ($transactions as $txn) {
                // Note: For split reconciliation, updating the line amount doesn't automatically 
                // update ALL matched transactions because we don't know which one to change.
                // However, for single matches (common case), we can still do it.
                // For now, let's update date/description/ref for all.

                $txn->update([
                    'date' => $line->date,
                    'description' => $line->description . ($line->payee ? ' - ' . $line->payee : ''),
                    'reference_id' => $line->reference,
                ]);

                // Find and update contra or related transactions
                $related = Transaction::withoutGlobalScope('uniqueNoNotNull')
                    ->where('company_id', $txn->company_id)
                    ->where('date', $txn->getOriginal('date'))
                    ->where('description', $txn->getOriginal('description'))
                    ->where('id', '!=', $txn->id)
                    ->get()
                    ->filter(function ($r) use ($txn) {
                        return ($r->debit == $txn->getOriginal('credit') && $r->credit == $txn->getOriginal('debit'));
                    });

                foreach ($related as $relTxn) {
                    $relTxn->update([
                        'date' => $line->date,
                        'description' => $txn->description,
                        'reference_id' => $line->reference,
                    ]);

                    /** @var BankStatementLine|null $relLine */
                    $relLine = BankStatementLine::where('id', $relTxn->bank_statement_line_id)->first();
                    if ($relLine instanceof BankStatementLine) {
                        $relLine->update([
                            'date' => $line->date,
                            'reference' => $line->reference,
                        ]);
                    }
                }
            }
        });
    }
}
