<?php

namespace App\Services\Account;

use App\Models\CustomerPaymentItem;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PaymentBehaviorService
{
    /** Number of recent payments to use for trend calculation. */
    private const RECENT_WINDOW = 5;

    /** Invoices paid beyond this many days are considered "overdue". */
    private const OVERDUE_THRESHOLD_DAYS = 30;

    /**
     * Build payment predictions for all customers that have outstanding receivables.
     *
     * @param  int[]|null  $customerIds  Limit to specific customers (null = all with history)
     * @return array<int, array>  Keyed by customer_id
     */
    public function predictAll(?array $customerIds = null): array
    {
        $history = $this->getPaymentHistory($customerIds);

        $predictions = [];

        foreach ($history->groupBy('customer_id') as $customerId => $payments) {
            $predictions[$customerId] = $this->analyzeCustomer($payments);
        }

        return $predictions;
    }

    /**
     * Predict the payment date for a specific outstanding invoice.
     */
    public function predictInvoicePayDate(string $invoiceDate, int $customerId, array $predictions): ?string
    {
        $profile = $predictions[$customerId] ?? null;
        if (!$profile || $profile['avg_days_to_pay'] === null) return null;

        $adjustedDays = max(1, round($profile['avg_days_to_pay'] + $profile['trend_adjustment']));
        $predicted = Carbon::parse($invoiceDate)->addDays($adjustedDays);

        return $predicted->format('Y-m-d');
    }

    /**
     * Analyze a single customer's payment collection.
     *
     * @param  Collection  $payments  Rows with days_to_pay, amount, payment_date
     * @return array{avg_days_to_pay, stddev, recent_avg, trend_adjustment, overdue_ratio, risk_score, risk_label, payment_count}
     */
    private function analyzeCustomer(Collection $payments): array
    {
        $days = $payments->pluck('days_to_pay')->filter(fn($d) => $d !== null)->values();
        $count = $days->count();

        if ($count === 0) {
            return $this->noDataProfile();
        }

        $avg = $days->avg();
        $stddev = $this->stddev($days->all());

        // Recent trend: last N payments vs overall
        $recent = $days->slice(-self::RECENT_WINDOW)->values();
        $recentAvg = $recent->avg();
        $trendAdjustment = $recentAvg - $avg; // positive = slowing down

        // Overdue ratio
        $overdueCount = $days->filter(fn($d) => $d > self::OVERDUE_THRESHOLD_DAYS)->count();
        $overdueRatio = $count > 0 ? $overdueCount / $count : 0;

        // Risk score (0-100)
        $base = min(100, $avg * 1.5);
        $variability = min(20, $stddev / 5);
        $trendPenalty = max(0, $trendAdjustment * 0.5);
        $overdueBonus = $overdueRatio * 20;

        $riskScore = (int) round(min(100, max(0, $base + $variability + $trendPenalty + $overdueBonus)));

        return [
            'avg_days_to_pay'  => round($avg, 1),
            'stddev'           => round($stddev, 1),
            'recent_avg'       => round($recentAvg, 1),
            'trend_adjustment' => round($trendAdjustment, 1),
            'overdue_ratio'    => round($overdueRatio * 100, 1),
            'risk_score'       => $riskScore,
            'risk_label'       => $this->riskLabel($riskScore),
            'payment_count'    => $count,
        ];
    }

    /**
     * Query the historical payment chain: Sale → CustomerPaymentItem → CustomerPayment.
     */
    private function getPaymentHistory(?array $customerIds): Collection
    {
        $query = DB::table('customer_payment_items as cpi')
            ->join('customer_payments as cp', 'cpi.customer_payment_id', '=', 'cp.id')
            ->join('sales as s', 'cpi.sale_id', '=', 's.id')
            ->where('cp.status', 'confirmed')
            ->whereNotNull('cp.confirmed_at')
            ->whereNull('s.deleted_at')
            ->select([
                's.customer_id',
                's.date as invoice_date',
                'cp.payment_date',
                DB::raw("(cp.payment_date::date - s.date::date) as days_to_pay"),
                'cpi.amount_to_apply as amount',
                's.invoice_no',
            ])
            ->orderBy('s.customer_id')
            ->orderBy('cp.payment_date');

        if ($customerIds) {
            $query->whereIn('s.customer_id', $customerIds);
        }

        return $query->get();
    }

    private function noDataProfile(): array
    {
        return [
            'avg_days_to_pay'  => null,
            'stddev'           => null,
            'recent_avg'       => null,
            'trend_adjustment' => 0,
            'overdue_ratio'    => 0,
            'risk_score'       => null,
            'risk_label'       => 'no_data',
            'payment_count'    => 0,
        ];
    }

    private function riskLabel(int $score): string
    {
        if ($score >= 70) return 'high';
        if ($score >= 40) return 'medium';
        return 'low';
    }

    private function stddev(array $values): float
    {
        $n = count($values);
        if ($n < 2) return 0;

        $mean = array_sum($values) / $n;
        $sumSqr = array_sum(array_map(fn($v) => pow($v - $mean, 2), $values));

        return sqrt($sumSqr / $n);
    }
}
