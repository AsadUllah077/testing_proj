<?php

namespace App\Services\Account;

use App\Models\AccountingPeriod;
use App\Models\Bill;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnomalyDetectionService
{
    private ?int $companyId = null;

    /** How many days of history to scan for anomalies. */
    private const SCAN_DAYS = 90;

    /** Z-score threshold for amount outliers. */
    private const AMOUNT_Z_THRESHOLD = 3.0;

    /** Window (days) for duplicate invoice detection. */
    private const DUPLICATE_WINDOW_DAYS = 30;

    /** Hours before period end_date to flag as boundary entry. */
    private const PERIOD_BOUNDARY_HOURS = 48;

    /** Days of vendor inactivity to consider "dormant". */
    private const DORMANT_VENDOR_DAYS = 90;

    /** Minimum amount for round-number detection. */
    private const ROUND_NUMBER_MIN = 5000;

    /** Rapid-sequence: max minutes between transactions to count as "rapid". */
    private const RAPID_WINDOW_MINUTES = 5;

    /** Rapid-sequence: min count in window to flag. */
    private const RAPID_MIN_COUNT = 3;

    /**
     * Run all detection rules and return a unified list of anomalies.
     *
     * @return array{anomalies: array[], summary: array}
     */
    public function scan(?string $fromDate = null, ?string $toDate = null, ?int $companyId = null): array
    {
        $this->companyId = $companyId ?: auth('web')->user()?->company_id;

        $from = $fromDate ? Carbon::parse($fromDate) : Carbon::today()->subDays(self::SCAN_DAYS);
        $to   = $toDate   ? Carbon::parse($toDate)   : Carbon::today();

        $anomalies = collect();

        $anomalies = $anomalies->merge($this->detectAmountOutliers($from, $to));
        $anomalies = $anomalies->merge($this->detectDuplicateInvoices($from, $to));
        $anomalies = $anomalies->merge($this->detectPeriodBoundary($from, $to));
        $anomalies = $anomalies->merge($this->detectDormantVendors($from, $to));
        $anomalies = $anomalies->merge($this->detectRoundNumbers($from, $to));
        $anomalies = $anomalies->merge($this->detectRapidSequence($from, $to));

        // Sort: critical first, then warning, then info. Within same severity, newest first.
        $severityOrder = ['critical' => 0, 'warning' => 1, 'info' => 2];
        $sorted = $anomalies->sort(function ($a, $b) use ($severityOrder) {
            $sa = $severityOrder[$a['severity']] ?? 9;
            $sb = $severityOrder[$b['severity']] ?? 9;
            if ($sa !== $sb) return $sa - $sb;
            return strcmp($b['date'] ?? '', $a['date'] ?? '');
        })->values()->all();

        $summary = [
            'total'    => count($sorted),
            'critical' => collect($sorted)->where('severity', 'critical')->count(),
            'warning'  => collect($sorted)->where('severity', 'warning')->count(),
            'info'     => collect($sorted)->where('severity', 'info')->count(),
        ];

        return ['anomalies' => $sorted, 'summary' => $summary];
    }

    // ─── Rule 1: Amount outliers (per-account z-score) ───────────────

    private function detectAmountOutliers(Carbon $from, Carbon $to): Collection
    {
        // Compute per-account baseline from ALL historical transactions.
        $baselines = $this->forCompany(Transaction::query())
            ->withoutGlobalScope('transactionStatus')
            ->withTrashed()
            ->select(
                'account_id',
                DB::raw('AVG(debit + credit) as mean_amount'),
                DB::raw('STDDEV_POP(debit + credit) as stddev_amount'),
                DB::raw('COUNT(*) as txn_count')
            )
            ->groupBy('account_id')
            ->having(DB::raw('COUNT(*)'), '>=', 10) // Need enough data for meaningful stats
            ->having(DB::raw('STDDEV_POP(debit + credit)'), '>', 0)
            ->get()
            ->keyBy('account_id');

        if ($baselines->isEmpty()) return collect();

        // Find transactions in scan window that exceed threshold.
        $transactions = $this->forCompany(Transaction::withoutGlobalScope('transactionStatus'))
            ->withTrashed()
            ->with(['account', 'createdBy', 'auditAction'])
            ->whereIn('account_id', $baselines->keys())
            ->whereBetween('date', [$from->format('Y-m-d'), $to->format('Y-m-d')])
            ->get();

        $anomalies = collect();

        foreach ($transactions as $txn) {
            $baseline = $baselines[$txn->account_id] ?? null;
            if (!$baseline || $baseline->stddev_amount == 0) continue;

            $amount = (float) $txn->debit + (float) $txn->credit;
            $zScore = abs($amount - $baseline->mean_amount) / $baseline->stddev_amount;

            if ($zScore >= self::AMOUNT_Z_THRESHOLD) {
                $severity = $zScore >= 5 ? 'critical' : 'warning';
                $anomalies->push([
                    'rule'        => 'amount_outlier',
                    'severity'    => $severity,
                    'title'       => 'Unusual Amount',
                    'description' => sprintf(
                        'Transaction %s has amount %s, %.1f standard deviations from the account mean (%s) for "%s".',
                        $txn->unique_no ?? '#' . $txn->id,
                        number_format($amount, 2),
                        $zScore,
                        number_format($baseline->mean_amount, 2),
                        $txn->account->name ?? 'Unknown'
                    ),
                    'date'           => $txn->date?->format('Y-m-d'),
                    'transaction_id' => $txn->id,
                    'unique_no'      => $txn->unique_no,
                    'amount'         => $amount,
                    'account'        => $txn->account->name ?? null,
                    'user'           => $txn->createdBy->first_name ?? null,
                    'meta'           => ['z_score' => round($zScore, 2), 'mean' => round($baseline->mean_amount, 2)],
                    'audit'          => $txn->auditAction ? [
                        'action' => $txn->auditAction->action,
                        'status' => $txn->auditAction->status,
                        'notes'  => $txn->auditAction->notes,
                    ] : null,
                ]);
            }
        }

        return $anomalies;
    }

    // ─── Rule 2: Duplicate invoices ──────────────────────────────────

    private function detectDuplicateInvoices(Carbon $from, Carbon $to): Collection
    {
        // Find bills with same vendor_id + same amount + invoice_no within the window.
        $duplicates = $this->forCompany(Bill::query())
            ->withTrashed()
            ->whereBetween('date', [$from->format('Y-m-d'), $to->format('Y-m-d')])
            ->where('total', '>', 0)
            ->whereNotNull('invoice_no')
            ->where('invoice_no', '!=', '')
            ->select('vendor_id', 'total', 'invoice_no', DB::raw('COUNT(*) as cnt'))
            ->groupBy('vendor_id', 'total', 'invoice_no')
            ->having(DB::raw('COUNT(*)'), '>', 1)
            ->get();

        $anomalies = collect();

        foreach ($duplicates as $dup) {
            $billIds = $this->forCompany(Bill::query())
                ->withTrashed()
                ->where('vendor_id', $dup->vendor_id)
                ->where('total', $dup->total)
                ->where('invoice_no', $dup->invoice_no)
                ->whereBetween('date', [$from->format('Y-m-d'), $to->format('Y-m-d')])
                ->pluck('id')
                ->all();

            $anomalies->push([
                'rule'        => 'duplicate_invoice',
                'severity'    => 'critical',
                'title'       => 'Duplicate Invoice',
                'description' => sprintf(
                    'Invoice "%s" from vendor #%d appears %d times with the same amount (%s). Possible duplicate payment risk.',
                    $dup->invoice_no,
                    $dup->vendor_id,
                    $dup->cnt,
                    number_format($dup->total, 2)
                ),
                'date'           => $to->format('Y-m-d'),
                'transaction_id' => null,
                'unique_no'      => $dup->invoice_no,
                'amount'         => (float) $dup->total,
                'account'        => null,
                'user'           => null,
                'meta'           => ['vendor_id' => $dup->vendor_id, 'count' => $dup->cnt, 'bill_ids' => $billIds],
                'audit'          => null, // Collections of bills don't have a single audit action yet
            ]);
        }

        return $anomalies;
    }

    // ─── Rule 3: Period-boundary entries ──────────────────────────────

    private function detectPeriodBoundary(Carbon $from, Carbon $to): Collection
    {
        $lockedPeriods = $this->forCompany(AccountingPeriod::query())
            ->where('is_locked', true)
            ->get();
        if ($lockedPeriods->isEmpty()) return collect();

        $anomalies = collect();

        foreach ($lockedPeriods as $period) {
            $endDate    = Carbon::parse($period->end_date);
            $cutoffFrom = $endDate->copy()->subHours(self::PERIOD_BOUNDARY_HOURS);

            // Find transactions created within the boundary window.
            $txns = $this->forCompany(Transaction::withoutGlobalScope('transactionStatus'))
                ->withTrashed()
                ->with(['account', 'createdBy', 'auditAction'])
                ->where('accounting_period_id', $period->id)
                ->whereBetween('created_at', [$cutoffFrom, $endDate->endOfDay()])
                ->get();

            foreach ($txns as $txn) {
                $hoursBeforeLock = $endDate->diffInHours($txn->created_at);
                $anomalies->push([
                    'rule'        => 'period_boundary',
                    'severity'    => 'warning',
                    'title'       => 'Period-Boundary Entry',
                    'description' => sprintf(
                        'Transaction %s was created %dh before the end of locked period "%s". Review for potential period-stuffing.',
                        $txn->unique_no ?? '#' . $txn->id,
                        $hoursBeforeLock,
                        $period->name
                    ),
                    'date'           => $txn->date?->format('Y-m-d'),
                    'transaction_id' => $txn->id,
                    'unique_no'      => $txn->unique_no,
                    'amount'         => (float) $txn->debit + (float) $txn->credit,
                    'account'        => $txn->account->name ?? null,
                    'user'           => $txn->createdBy->first_name ?? null,
                    'meta'           => ['period' => $period->name, 'hours_before_lock' => $hoursBeforeLock],
                    'audit'          => $txn->auditAction ? [
                        'action' => $txn->auditAction->action,
                        'status' => $txn->auditAction->status,
                        'notes'  => $txn->auditAction->notes,
                    ] : null,
                ]);
            }
        }

        return $anomalies;
    }

    // ─── Rule 4: Dormant vendor reactivation ─────────────────────────

    private function detectDormantVendors(Carbon $from, Carbon $to): Collection
    {
        $dormantCutoff = $from->copy()->subDays(self::DORMANT_VENDOR_DAYS)->format('Y-m-d');

        // Find vendors who have transactions in the scan window but NO transactions
        // in the [dormantCutoff, scanFrom] range.
        $recentVendors = $this->forCompany(Transaction::query())
            ->withoutGlobalScope('transactionStatus')
            ->withTrashed()
            ->whereNotNull('vendor_id')
            ->whereBetween('date', [$from->format('Y-m-d'), $to->format('Y-m-d')])
            ->select('vendor_id')
            ->distinct()
            ->pluck('vendor_id');

        if ($recentVendors->isEmpty()) return collect();

        $activeInPrior = $this->forCompany(Transaction::query())
            ->withoutGlobalScope('transactionStatus')
            ->withTrashed()
            ->whereNotNull('vendor_id')
            ->whereIn('vendor_id', $recentVendors)
            ->whereBetween('date', [$dormantCutoff, $from->copy()->subDay()->format('Y-m-d')])
            ->select('vendor_id')
            ->distinct()
            ->pluck('vendor_id');

        $dormantReactivated = $recentVendors->diff($activeInPrior);

        if ($dormantReactivated->isEmpty()) return collect();

        // Get one representative transaction per dormant vendor.
        $txns = $this->forCompany(Transaction::withoutGlobalScope('transactionStatus'))
            ->withTrashed()
            ->with(['vendor', 'account', 'createdBy', 'auditAction'])
            ->whereIn('vendor_id', $dormantReactivated)
            ->whereBetween('date', [$from->format('Y-m-d'), $to->format('Y-m-d')])
            ->orderBy('date', 'desc')
            ->get()
            ->unique('vendor_id');

        $anomalies = collect();

        foreach ($txns as $txn) {
            $anomalies->push([
                'rule'        => 'dormant_vendor',
                'severity'    => 'warning',
                'title'       => 'Dormant Vendor Reactivated',
                'description' => sprintf(
                    'Vendor "%s" had no transactions for %d+ days before transaction %s on %s.',
                    $txn->vendor->name ?? 'Unknown',
                    self::DORMANT_VENDOR_DAYS,
                    $txn->unique_no ?? '#' . $txn->id,
                    $txn->date?->format('d M Y')
                ),
                'date'           => $txn->date?->format('Y-m-d'),
                'transaction_id' => $txn->id,
                'unique_no'      => $txn->unique_no,
                'amount'         => (float) $txn->debit + (float) $txn->credit,
                'account'        => $txn->account->name ?? null,
                'user'           => $txn->createdBy->first_name ?? null,
                'meta'           => ['vendor_name' => $txn->vendor->name ?? null, 'vendor_id' => $txn->vendor_id],
                'audit'          => $txn->auditAction ? [
                    'action' => $txn->auditAction->action,
                    'status' => $txn->auditAction->status,
                    'notes'  => $txn->auditAction->notes,
                ] : null,
            ]);
        }

        return $anomalies;
    }

    // ─── Rule 5: Round-number detection ──────────────────────────────

    private function detectRoundNumbers(Carbon $from, Carbon $to): Collection
    {
        $roundNumberModulo = DB::getDriverName() === 'pgsql'
            ? 'MOD((debit + credit)::numeric, 1000) = 0'
            : 'MOD(debit + credit, 1000) = 0';

        $txns = $this->forCompany(Transaction::withoutGlobalScope('transactionStatus'))
            ->withTrashed()
            ->with(['account', 'createdBy', 'auditAction'])
            ->whereBetween('date', [$from->format('Y-m-d'), $to->format('Y-m-d')])
            ->whereRaw('(debit + credit) >= ?', [self::ROUND_NUMBER_MIN])
            ->whereRaw($roundNumberModulo)
            ->orderBy('date', 'desc')
            ->limit(50)
            ->get();

        return $txns->map(fn(Transaction $txn) => [
            'rule'        => 'round_number',
            'severity'    => 'info',
            'title'       => 'Round Number',
            'description' => sprintf(
                'Transaction %s for %s is a round thousand and may warrant verification.',
                $txn->unique_no ?? '#' . $txn->id,
                number_format((float) $txn->debit + (float) $txn->credit, 2)
            ),
            'date'           => $txn->date?->format('Y-m-d'),
            'transaction_id' => $txn->id,
            'unique_no'      => $txn->unique_no,
            'amount'         => (float) $txn->debit + (float) $txn->credit,
            'account'        => $txn->account->name ?? null,
            'user'           => $txn->createdBy->first_name ?? null,
            'meta'           => [],
            'audit'          => $txn->auditAction ? [
                'action' => $txn->auditAction->action,
                'status' => $txn->auditAction->status,
                'notes'  => $txn->auditAction->notes,
            ] : null,
        ]);
    }

    // ─── Rule 6: Rapid sequence ──────────────────────────────────────

    private function detectRapidSequence(Carbon $from, Carbon $to): Collection
    {
        // Group transactions by (created_by, account_id, 5-minute window).
        $txns = $this->forCompany(Transaction::query())
            ->withoutGlobalScope('transactionStatus')
            ->withTrashed()
            ->whereNotNull('created_by')
            ->whereBetween('date', [$from->format('Y-m-d'), $to->format('Y-m-d')])
            ->orderBy('created_by')
            ->orderBy('account_id')
            ->orderBy('created_at')
            ->select('id', 'unique_no', 'created_by', 'account_id', 'created_at', 'debit', 'credit', 'date')
            ->get();

        $anomalies = collect();
        $flaggedGroups = [];

        foreach ($txns as $i => $txn) {
            $key = $txn->created_by . '-' . $txn->account_id;
            if (isset($flaggedGroups[$key])) continue;

            // Look ahead from this transaction.
            $window = [];
            $window[] = $txn;
            for ($j = $i + 1; $j < $txns->count(); $j++) {
                $next = $txns[$j];
                if ($next->created_by !== $txn->created_by || $next->account_id !== $txn->account_id) break;
                if (Carbon::parse($next->created_at)->diffInMinutes(Carbon::parse($txn->created_at)) > self::RAPID_WINDOW_MINUTES) break;
                $window[] = $next;
            }

            if (count($window) >= self::RAPID_MIN_COUNT) {
                $flaggedGroups[$key] = true;
                $totalAmount = collect($window)->sum(fn($t) => (float) $t->debit + (float) $t->credit);

                $anomalies->push([
                    'rule'        => 'rapid_sequence',
                    'severity'    => 'info',
                    'title'       => 'Rapid Transaction Sequence',
                    'description' => sprintf(
                        '%d transactions by the same user on the same account within %d minutes (total %s).',
                        count($window),
                        self::RAPID_WINDOW_MINUTES,
                        number_format($totalAmount, 2)
                    ),
                    'date'           => $txn->date?->format('Y-m-d') ?? Carbon::parse($txn->created_at)->format('Y-m-d'),
                    'transaction_id' => $txn->id,
                    'unique_no'      => $txn->unique_no,
                    'amount'         => $totalAmount,
                    'account'        => null,
                    'user'           => null,
                    'meta'           => ['count' => count($window), 'transaction_ids' => collect($window)->pluck('id')->all()],
                    'audit'          => null,
                ]);
            }
        }

        return $anomalies;
    }

    private function forCompany($query)
    {
        if ($this->companyId) {
            $query->where('company_id', $this->companyId);
        }

        return $query;
    }
}
