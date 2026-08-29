<?php

namespace App\Services\Account;

use App\Models\Bill;
use App\Models\JournalEntry;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DuplicateDetectionService
{
    /** Amount tolerance: match if within ±5%. */
    private const AMOUNT_TOLERANCE = 0.05;

    /** Date window: match if within ±7 days. */
    private const DATE_WINDOW_DAYS = 7;

    /** Max results to return. */
    private const MAX_RESULTS = 5;

    /**
     * Check for near-duplicate bills.
     *
     * Signals: same vendor_id + amount within 5% + date within 7 days.
     * Optionally checks invoice_no similarity.
     *
     * @return array{duplicates: array[], count: int}
     */
    public function checkBill(?int $vendorId, float $amount, string $date, ?string $invoiceNo = null, ?int $excludeId = null): array
    {
        if (!$vendorId || $amount <= 0) {
            return ['duplicates' => [], 'count' => 0];
        }

        $dateObj = Carbon::parse($date);
        $minAmount = $amount * (1 - self::AMOUNT_TOLERANCE);
        $maxAmount = $amount * (1 + self::AMOUNT_TOLERANCE);

        $query = Bill::with('vendor')
            ->where('vendor_id', $vendorId)
            ->whereNull('deleted_at')
            ->whereBetween('date', [
                $dateObj->copy()->subDays(self::DATE_WINDOW_DAYS)->format('Y-m-d'),
                $dateObj->copy()->addDays(self::DATE_WINDOW_DAYS)->format('Y-m-d'),
            ])
            ->whereBetween('total', [$minAmount, $maxAmount]);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $matches = $query->limit(self::MAX_RESULTS)->get();

        $duplicates = $matches->map(function (Bill $bill) use ($amount, $dateObj, $invoiceNo) {
            $amountDiff = abs($bill->total - $amount);
            $dateDiff   = abs($dateObj->diffInDays($bill->date));
            $invoiceMatch = $invoiceNo && $bill->invoice_no
                && $this->similarStrings($invoiceNo, $bill->invoice_no);

            $confidence = $this->computeConfidence($amountDiff / max($amount, 1), $dateDiff, $invoiceMatch);

            return [
                'id'         => $bill->id,
                'type'       => 'bill',
                'ref'        => $bill->invoice_no ?: $bill->unique_no,
                'vendor'     => $bill->vendor->name ?? 'Unknown',
                'amount'     => (float) $bill->total,
                'date'       => $bill->date->format('d M Y'),
                'status'     => $bill->approval_status ?? 'draft',
                'confidence' => $confidence,
                'url'        => route('account.bill-invoice.show', $bill->id),
                'reasons'    => $this->buildReasons($amountDiff, $dateDiff, $invoiceMatch, $amount),
            ];
        })->sortByDesc('confidence')->values()->all();

        return ['duplicates' => $duplicates, 'count' => count($duplicates)];
    }

    /**
     * Check for near-duplicate sales invoices.
     *
     * Signals: same customer_id + amount within 5% + date within 7 days.
     */
    public function checkSale(?int $customerId, float $amount, string $date, ?string $invoiceNo = null, ?int $excludeId = null): array
    {
        if (!$customerId || $amount <= 0) {
            return ['duplicates' => [], 'count' => 0];
        }

        $dateObj = Carbon::parse($date);
        $minAmount = $amount * (1 - self::AMOUNT_TOLERANCE);
        $maxAmount = $amount * (1 + self::AMOUNT_TOLERANCE);

        $query = Sale::with('customer')
            ->where('customer_id', $customerId)
            ->whereNull('deleted_at')
            ->whereBetween('date', [
                $dateObj->copy()->subDays(self::DATE_WINDOW_DAYS)->format('Y-m-d'),
                $dateObj->copy()->addDays(self::DATE_WINDOW_DAYS)->format('Y-m-d'),
            ])
            ->whereBetween('total', [$minAmount, $maxAmount]);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $matches = $query->limit(self::MAX_RESULTS)->get();

        $duplicates = $matches->map(function (Sale $sale) use ($amount, $dateObj, $invoiceNo) {
            $amountDiff = abs($sale->total - $amount);
            $dateDiff   = abs($dateObj->diffInDays($sale->date));
            $invoiceMatch = $invoiceNo && $sale->invoice_no
                && $this->similarStrings($invoiceNo, $sale->invoice_no);

            $confidence = $this->computeConfidence($amountDiff / max($amount, 1), $dateDiff, $invoiceMatch);

            return [
                'id'         => $sale->id,
                'type'       => 'sale',
                'ref'        => $sale->invoice_no ?: $sale->unique_no,
                'customer'   => $sale->customer->name ?? 'Unknown',
                'amount'     => (float) $sale->total,
                'date'       => $sale->date->format('d M Y'),
                'status'     => $sale->approval_status ?? 'draft',
                'confidence' => $confidence,
                'url'        => route('account.sale-invoice.show', $sale->id),
                'reasons'    => $this->buildReasons($amountDiff, $dateDiff, $invoiceMatch, $amount),
            ];
        })->sortByDesc('confidence')->values()->all();

        return ['duplicates' => $duplicates, 'count' => count($duplicates)];
    }

    /**
     * Check for near-duplicate journal entries.
     *
     * Signals: same total debit amount + date within 7 days + similar narration.
     */
    public function checkJournalEntry(float $totalDebit, string $date, ?string $narration = null, ?string $excludeUniqueNo = null): array
    {
        if ($totalDebit <= 0) {
            return ['duplicates' => [], 'count' => 0];
        }

        $dateObj = Carbon::parse($date);
        $minAmount = $totalDebit * (1 - self::AMOUNT_TOLERANCE);
        $maxAmount = $totalDebit * (1 + self::AMOUNT_TOLERANCE);

        $query = JournalEntry::whereNull('deleted_at')
            ->whereBetween('date', [
                $dateObj->copy()->subDays(self::DATE_WINDOW_DAYS)->format('Y-m-d'),
                $dateObj->copy()->addDays(self::DATE_WINDOW_DAYS)->format('Y-m-d'),
            ])
            ->where('debit', '>', 0)
            ->whereBetween('debit', [$minAmount, $maxAmount]);

        if ($excludeUniqueNo) {
            $query->where('unique_no', '!=', $excludeUniqueNo);
        }

        // Journal entries share unique_no per batch — group to find distinct entries.
        $matches = $query->select('unique_no', 'date', 'narration', 'debit')
            ->groupBy('unique_no', 'date', 'narration', 'debit')
            ->limit(self::MAX_RESULTS)
            ->get();

        $duplicates = $matches->map(function ($je) use ($totalDebit, $dateObj, $narration) {
            $amountDiff = abs($je->debit - $totalDebit);
            $dateDiff   = abs($dateObj->diffInDays(Carbon::parse($je->date)));
            $narrationMatch = $narration && $je->narration
                && $this->similarStrings($narration, $je->narration);

            $confidence = $this->computeConfidence($amountDiff / max($totalDebit, 1), $dateDiff, $narrationMatch);

            return [
                'id'         => null,
                'type'       => 'journal_entry',
                'ref'        => $je->unique_no,
                'amount'     => (float) $je->debit,
                'date'       => Carbon::parse($je->date)->format('d M Y'),
                'narration'  => $je->narration,
                'confidence' => $confidence,
                'url'        => route('account.journal-entries.edit', $je->unique_no),
                'reasons'    => $this->buildReasons($amountDiff, $dateDiff, $narrationMatch, $totalDebit),
            ];
        })->sortByDesc('confidence')->values()->all();

        return ['duplicates' => $duplicates, 'count' => count($duplicates)];
    }

    // ─── Helpers ─────────────────────────────────────────────────────

    /**
     * Compute a confidence score (0–100) from the signal strengths.
     */
    private function computeConfidence(float $amountRatio, int $dateDiff, bool $refMatch): int
    {
        // Amount closeness: 0% diff = 50, 5% diff = 25
        $amountScore = max(0, 50 - ($amountRatio * 1000));

        // Date closeness: same day = 30, 7 days = 0
        $dateScore = max(0, 30 - ($dateDiff * (30 / self::DATE_WINDOW_DAYS)));

        // Reference/invoice match: 20 bonus
        $refScore = $refMatch ? 20 : 0;

        return (int) min(100, $amountScore + $dateScore + $refScore);
    }

    /**
     * Build human-readable reason strings.
     */
    private function buildReasons(float $amountDiff, int $dateDiff, bool $refMatch, float $baseAmount): array
    {
        $reasons = [];

        if ($amountDiff < 0.01) {
            $reasons[] = 'Exact same amount';
        } else {
            $pctDiff = $baseAmount > 0 ? round(($amountDiff / $baseAmount) * 100, 1) : 0;
            $reasons[] = "Amount within {$pctDiff}%";
        }

        if ($dateDiff === 0) {
            $reasons[] = 'Same date';
        } else {
            $reasons[] = "{$dateDiff} day" . ($dateDiff > 1 ? 's' : '') . " apart";
        }

        if ($refMatch) {
            $reasons[] = 'Similar reference/invoice number';
        }

        return $reasons;
    }

    /**
     * Fuzzy string comparison — true if strings are >60% similar.
     */
    private function similarStrings(string $a, string $b): bool
    {
        $a = mb_strtolower(trim($a));
        $b = mb_strtolower(trim($b));

        if ($a === $b) return true;

        similar_text($a, $b, $pct);
        return $pct >= 60;
    }
}
