<?php

namespace App\Services\Account;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SmartReminderService
{
    /** Minimum confirmed payments to classify as non-new. */
    private const MIN_HISTORY = 3;

    private const RELIABLE_MAX_DAYS   = 35;
    private const RELIABLE_MAX_RATIO  = 0.20;
    private const CHRONIC_MIN_DAYS    = 60;
    private const CHRONIC_MIN_RATIO   = 0.50;
    private const OVERDUE_THRESHOLD   = 30;

    /**
     * Segment multiple customers at once.
     *
     * @param  int[]  $customerIds
     * @return array<int, array{segment, tone, subject_prefix, escalation, avg_days, payment_count}>
     */
    public function segmentCustomers(array $customerIds): array
    {
        if (empty($customerIds)) return [];

        $history = $this->getPaymentStats($customerIds);
        $segments = [];

        foreach ($customerIds as $id) {
            $stats = $history[$id] ?? null;
            $segments[$id] = $this->classify($stats);
        }

        return $segments;
    }

    /**
     * Segment a single customer.
     */
    public function segmentCustomer(int $customerId): array
    {
        return $this->segmentCustomers([$customerId])[$customerId];
    }

    /**
     * Classify based on payment stats.
     */
    private function classify(?object $stats): array
    {
        if (!$stats || $stats->payment_count < self::MIN_HISTORY) {
            return [
                'segment'        => 'new',
                'tone'           => 'neutral',
                'subject_prefix' => 'Payment Reminder',
                'escalation'     => null,
                'avg_days'       => $stats->avg_days ?? null,
                'payment_count'  => $stats->payment_count ?? 0,
                'overdue_ratio'  => null,
            ];
        }

        $avg   = (float) $stats->avg_days;
        $ratio = $stats->payment_count > 0
            ? (int) $stats->overdue_count / (int) $stats->payment_count
            : 0;

        if ($avg > self::CHRONIC_MIN_DAYS || $ratio > self::CHRONIC_MIN_RATIO) {
            return [
                'segment'        => 'chronic',
                'tone'           => 'urgent',
                'subject_prefix' => 'Urgent: Payment Required',
                'escalation'     => 'Consider direct phone follow-up or account hold for this customer.',
                'avg_days'       => round($avg, 1),
                'payment_count'  => (int) $stats->payment_count,
                'overdue_ratio'  => round($ratio * 100, 1),
            ];
        }

        if ($avg <= self::RELIABLE_MAX_DAYS && $ratio < self::RELIABLE_MAX_RATIO) {
            return [
                'segment'        => 'reliable',
                'tone'           => 'friendly',
                'subject_prefix' => 'Friendly Reminder',
                'escalation'     => null,
                'avg_days'       => round($avg, 1),
                'payment_count'  => (int) $stats->payment_count,
                'overdue_ratio'  => round($ratio * 100, 1),
            ];
        }

        return [
            'segment'        => 'occasionally_late',
            'tone'           => 'firm',
            'subject_prefix' => 'Payment Reminder',
            'escalation'     => null,
            'avg_days'       => round($avg, 1),
            'payment_count'  => (int) $stats->payment_count,
            'overdue_ratio'  => round($ratio * 100, 1),
        ];
    }

    /**
     * Query payment stats per customer: avg days-to-pay, count, overdue count.
     *
     * @return array<int, object>  Keyed by customer_id
     */
    private function getPaymentStats(array $customerIds): array
    {
        $rows = DB::table('customer_payment_items as cpi')
            ->join('customer_payments as cp', 'cpi.customer_payment_id', '=', 'cp.id')
            ->join('sales as s', 'cpi.sale_id', '=', 's.id')
            ->where('cp.status', 'confirmed')
            ->whereNotNull('cp.confirmed_at')
            ->whereNull('s.deleted_at')
            ->whereIn('s.customer_id', $customerIds)
            ->select([
                's.customer_id',
                DB::raw('COUNT(*) as payment_count'),
                DB::raw('AVG((cp.payment_date::date - s.date::date)) as avg_days'),
                DB::raw("SUM(CASE WHEN (cp.payment_date::date - s.date::date) > " . self::OVERDUE_THRESHOLD . " THEN 1 ELSE 0 END) as overdue_count"),
            ])
            ->groupBy('s.customer_id')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[$row->customer_id] = $row;
        }

        return $map;
    }
}
