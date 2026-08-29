<?php

namespace App\Services\Account;

use App\Models\Bill;
use App\Models\ChartOfAccount;
use App\Models\RecurringBill;
use App\Models\RecurringInvoice;
use App\Models\Sale;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CashFlowForecastService
{
    /** How many weeks of history to analyse for trend/seasonality. */
    private const HISTORY_WEEKS = 52;

    /** Exponential decay factor for weighting recent weeks more heavily (0 < α < 1). */
    private const DECAY_ALPHA = 0.15;

    /** Blend weights: how much trend vs seasonality contributes beyond AR/AP. */
    private const WEIGHT_TREND    = 0.3;
    private const WEIGHT_SEASONAL = 0.2;

    /**
     * Build the full enriched forecast.
     *
     * @return array{
     *   currentCash: float,
     *   totals: array,
     *   buckets: array,
     *   insights: string[]
     * }
     */
    public function buildForecast(int $horizon): array
    {
        $today  = Carbon::today();
        $cutoff = $today->copy()->addDays($horizon);
        $maxDate = $cutoff->copy()->subDays(30)->format('Y-m-d');

        // ── Current cash ────────────────────────────────────────────
        $currentCash = $this->getCurrentCash();

        // ── Deterministic AR/AP (existing logic, preserved) ─────────
        $totalInflow  = (float) Sale::where('approval_status', 'approved')->where('remaining_amount', '>', 0)->sum('remaining_amount');
        $totalOutflow = (float) Bill::where('approval_status', 'approved')->where('remaining_amount', '>', 0)->sum('remaining_amount');

        $weekExpr = "TO_CHAR(DATE_TRUNC('week', \"date\" + INTERVAL '30 days'), 'YYYY-MM-DD')";

        $arWeekly = Sale::where('approval_status', 'approved')->where('remaining_amount', '>', 0)
            ->where('date', '<=', $maxDate)
            ->selectRaw("{$weekExpr} as week_start, SUM(remaining_amount) as total")
            ->groupBy('week_start')
            ->pluck('total', 'week_start');

        $apWeekly = Bill::where('approval_status', 'approved')->where('remaining_amount', '>', 0)
            ->where('date', '<=', $maxDate)
            ->selectRaw("{$weekExpr} as week_start, SUM(remaining_amount) as total")
            ->groupBy('week_start')
            ->pluck('total', 'week_start');

        // ── Historical weekly flows ─────────────────────────────────
        $historical = $this->getHistoricalWeeklyFlows(self::HISTORY_WEEKS);
        $trendAvg   = $this->exponentialWeightedAverage($historical->pluck('net')->toArray());
        $seasonalMap = $this->buildSeasonalMap($historical);
        $stddev      = $this->standardDeviation($historical->pluck('net')->toArray());

        // ── Recurring projections ───────────────────────────────────
        $recurringByWeek = $this->getRecurringProjections($today, $cutoff);

        // ── Build enriched weekly buckets ────────────────────────────
        $buckets = [];
        $cursor  = $today->copy()->startOfWeek();
        $insights = [];

        while ($cursor->lte($cutoff)) {
            $key     = $cursor->format('Y-m-d');
            $isoWeek = (int) $cursor->format('W');

            $arInflow  = round((float) ($arWeekly[$key] ?? 0), 2);
            $apOutflow = round((float) ($apWeekly[$key] ?? 0), 2);

            $recIn  = round((float) ($recurringByWeek[$key]['inflow'] ?? 0), 2);
            $recOut = round((float) ($recurringByWeek[$key]['outflow'] ?? 0), 2);

            $seasonalAvg = $seasonalMap[$isoWeek] ?? 0;
            $trendPad    = ($trendAvg * self::WEIGHT_TREND) + ($seasonalAvg * self::WEIGHT_SEASONAL);

            $inflow  = $arInflow + $recIn;
            $outflow = $apOutflow + $recOut;
            $net     = round(($inflow - $outflow) + $trendPad, 2);

            $buckets[] = [
                'label'             => $cursor->format('d M'),
                'inflow'            => round($inflow, 2),
                'outflow'           => round($outflow, 2),
                'recurring_inflow'  => $recIn,
                'recurring_outflow' => $recOut,
                'net'               => $net,
            ];

            $cursor->addWeek();
        }

        // Running balance + confidence bands.
        $running = $currentCash;
        foreach ($buckets as $i => &$b) {
            $running       += $b['net'];
            $b['balance']  = round($running, 2);
            $b['upper']    = round($running + $stddev, 2);
            $b['lower']    = round($running - $stddev, 2);
        }
        unset($b);

        // ── Insights ────────────────────────────────────────────────
        $insights = $this->generateInsights($buckets, $currentCash, $stddev);

        // ── Totals including recurring ──────────────────────────────
        $totalRecIn  = collect($recurringByWeek)->sum('inflow');
        $totalRecOut = collect($recurringByWeek)->sum('outflow');

        $totals = [
            'inflow'             => round($totalInflow + $totalRecIn, 2),
            'outflow'            => round($totalOutflow + $totalRecOut, 2),
            'net'                => round(($totalInflow + $totalRecIn) - ($totalOutflow + $totalRecOut), 2),
            'recurring_inflow'   => round($totalRecIn, 2),
            'recurring_outflow'  => round($totalRecOut, 2),
        ];

        return compact('currentCash', 'totals', 'buckets', 'insights');
    }

    // ─── Sub-queries ─────────────────────────────────────────────────

    public function getCurrentCash(): float
    {
        $companyId = loggedCompanyId()['company_id'];
        $bankAccounts = ChartOfAccount::forCompany($companyId)
            ->where('category', 'Assets')
            ->where(function ($q) {
                $q->where('name', 'like', '%Bank%')
                    ->orWhere('name', 'like', '%Cash%');
            })->get();

        $bankIds = $bankAccounts->pluck('id');

        $txnAggregates = Transaction::where('company_id', $companyId)
            ->whereIn('account_id', $bankIds)
            ->selectRaw('account_id, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');

        return $bankAccounts->sum(function ($account) use ($txnAggregates) {
            $agg = $txnAggregates->get($account->id);
            return (float) ($agg?->total_debit ?? 0)
                - (float) ($agg?->total_credit ?? 0);
        });
    }

    /**
     * Aggregate historical cash flows by ISO week on bank/cash accounts.
     * Returns collection of [{week_start, inflow, outflow, net}].
     */
    public function getHistoricalWeeklyFlows(int $weeks): Collection
    {
        $since = Carbon::today()->subWeeks($weeks)->startOfWeek()->format('Y-m-d');

        $companyId = loggedCompanyId()['company_id'];
        $bankIds = ChartOfAccount::forCompany($companyId)
            ->where('category', 'Assets')
            ->where(function ($q) {
                $q->where('name', 'like', '%Bank%')
                    ->orWhere('name', 'like', '%Cash%');
            })
            ->pluck('id');

        if ($bankIds->isEmpty()) return collect();

        $rows = Transaction::where('company_id', $companyId)
            ->whereIn('account_id', $bankIds)
            ->where('date', '>=', $since)
            ->whereNull('deleted_at')
            ->selectRaw("TO_CHAR(DATE_TRUNC('week', \"date\"), 'YYYY-MM-DD') as week_start")
            ->selectRaw("SUM(debit) as inflow")
            ->selectRaw("SUM(credit) as outflow")
            ->groupBy('week_start')
            ->orderBy('week_start')
            ->get();

        return $rows->map(function ($row) {
            $row->net = round((float) $row->inflow - (float) $row->outflow, 2);
            $row->iso_week = (int) Carbon::parse($row->week_start)->format('W');
            return $row;
        });
    }

    /**
     * Project active recurring invoices and bills into weekly buckets.
     *
     * @return array<string, array{inflow: float, outflow: float}> keyed by week_start (Y-m-d)
     */
    public function getRecurringProjections(Carbon $from, Carbon $to): array
    {
        $byWeek = [];

        // Recurring invoices → inflows
        $recurringInvoices = RecurringInvoice::active()->get();
        foreach ($recurringInvoices as $ri) {
            $this->expandRecurring($ri, $from, $to, $byWeek, 'inflow');
        }

        // Recurring bills → outflows
        $recurringBills = RecurringBill::active()->get();
        foreach ($recurringBills as $rb) {
            $this->expandRecurring($rb, $from, $to, $byWeek, 'outflow');
        }

        return $byWeek;
    }

    /**
     * Expand a single recurring schedule into weekly buckets.
     */
    private function expandRecurring($model, Carbon $from, Carbon $to, array &$byWeek, string $type): void
    {
        $next = $model->next_run_date ? Carbon::parse($model->next_run_date)->copy() : null;
        if (!$next || $next->gt($to)) return;

        $amount = (float) $model->total;
        $guard  = 0; // safety: max 200 iterations per schedule

        while ($next->lte($to) && $guard < 200) {
            if ($next->gte($from)) {
                $weekKey = $next->copy()->startOfWeek()->format('Y-m-d');
                if (!isset($byWeek[$weekKey])) {
                    $byWeek[$weekKey] = ['inflow' => 0, 'outflow' => 0];
                }
                $byWeek[$weekKey][$type] += $amount;
            }

            $next = $model->calculateNextRunDate($next);

            // Respect end_date and max_occurrences
            if ($model->end_date && $next->gt(Carbon::parse($model->end_date))) break;

            $guard++;
        }
    }

    // ─── Statistical helpers ─────────────────────────────────────────

    /**
     * Exponentially-weighted moving average: recent values count more.
     */
    private function exponentialWeightedAverage(array $values): float
    {
        if (empty($values)) return 0;

        $n = count($values);
        $weightSum = 0;
        $valueSum  = 0;

        foreach ($values as $i => $v) {
            $weight = pow(1 - self::DECAY_ALPHA, $n - 1 - $i);
            $weightSum += $weight;
            $valueSum  += $v * $weight;
        }

        return $weightSum > 0 ? $valueSum / $weightSum : 0;
    }

    /**
     * Build a map of ISO-week-number → average net flow from historical data.
     */
    private function buildSeasonalMap(Collection $historical): array
    {
        $weekSums   = [];
        $weekCounts = [];

        foreach ($historical as $row) {
            $w = $row->iso_week;
            $weekSums[$w]   = ($weekSums[$w] ?? 0) + $row->net;
            $weekCounts[$w] = ($weekCounts[$w] ?? 0) + 1;
        }

        $map = [];
        foreach ($weekSums as $w => $sum) {
            $map[$w] = $weekCounts[$w] > 0 ? $sum / $weekCounts[$w] : 0;
        }

        return $map;
    }

    /**
     * Population standard deviation of an array of numbers.
     */
    private function standardDeviation(array $values): float
    {
        $n = count($values);
        if ($n < 2) return 0;

        $mean   = array_sum($values) / $n;
        $sumSqr = array_sum(array_map(fn($v) => pow($v - $mean, 2), $values));

        return sqrt($sumSqr / $n);
    }

    /**
     * Generate human-readable insight strings from the forecast.
     */
    private function generateInsights(array $buckets, float $currentCash, float $stddev): array
    {
        $insights = [];

        // Detect weeks where balance dips below zero
        $deficitWeeks = [];
        foreach ($buckets as $b) {
            if ($b['balance'] < 0) {
                $deficitWeeks[] = $b['label'];
            }
        }

        if (!empty($deficitWeeks)) {
            $weeks = implode(', ', array_slice($deficitWeeks, 0, 3));
            $insights[] = "Cash deficit projected for weeks starting {$weeks}. Consider accelerating receivables or deferring payments.";
        }

        // Detect weeks where lower bound dips below zero (risk warning)
        $riskWeeks = [];
        foreach ($buckets as $b) {
            if ($b['lower'] < 0 && $b['balance'] >= 0) {
                $riskWeeks[] = $b['label'];
            }
        }

        if (!empty($riskWeeks)) {
            $weeks = implode(', ', array_slice($riskWeeks, 0, 3));
            $insights[] = "Weeks starting {$weeks} could dip negative under adverse conditions (lower confidence bound).";
        }

        // Historical volatility context
        if ($stddev > 0 && $currentCash > 0) {
            $weeksOfRunway = $stddev > 0 ? round($currentCash / $stddev, 1) : 0;
            if ($weeksOfRunway > 0 && $weeksOfRunway < 8) {
                $insights[] = "Weekly cash flow volatility is high relative to your balance — roughly {$weeksOfRunway} weeks of buffer at current variability.";
            }
        }

        // Recurring income/expenditure share
        $totalRecIn  = array_sum(array_column($buckets, 'recurring_inflow'));
        $totalRecOut = array_sum(array_column($buckets, 'recurring_outflow'));
        if ($totalRecIn > 0 || $totalRecOut > 0) {
            $insights[] = sprintf(
                'Recurring schedules contribute +%s inflows and -%s outflows over this horizon.',
                number_format($totalRecIn, 2),
                number_format($totalRecOut, 2)
            );
        }

        return $insights;
    }
}
