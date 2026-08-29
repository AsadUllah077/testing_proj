<?php

namespace App\Services\Account;

use App\Models\Bill;
use App\Models\BillItem;
use App\Models\PurchaseOrder;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class ApprovalRiskService
{
    // ── Thresholds (configurable per-company in future) ──────────────
    private const LOW_THRESHOLD  = 30;
    private const HIGH_THRESHOLD = 70;

    // Amount scaling
    private const AMOUNT_FLOOR   = 5000;
    private const AMOUNT_CEILING = 50000;

    // Vendor average multiplier for variance flag
    private const VENDOR_AVG_MULTIPLIER = 2.0;

    // PO date matching window (days)
    private const PO_DATE_WINDOW = 30;

    /**
     * Score a bill for approval risk.
     *
     * @return array{score: int, tier: string, reasons: string[], signals: array}
     */
    public function scoreBill(Bill $bill): array
    {
        $bill->loadMissing(['vendor', 'billItems']);

        $signals = [];
        $reasons = [];

        // 1. Amount risk (0–25)
        $amountScore = $this->amountRisk((float) $bill->total, 25);
        $signals['amount'] = $amountScore;
        if ($amountScore >= 15) {
            $reasons[] = 'High-value bill (' . number_format($bill->total, 2) . ')';
        }

        // 2. New vendor (0 or 20)
        $vendorBillCount = Bill::where('vendor_id', $bill->vendor_id)
            ->where('id', '!=', $bill->id)
            ->whereNull('deleted_at')
            ->count();
        $newVendor = $vendorBillCount === 0 ? 20 : 0;
        $signals['new_vendor'] = $newVendor;
        if ($newVendor > 0) {
            $reasons[] = 'First bill from this vendor';
        }

        // 3. No PO match (0 or 15)
        $poMatch = PurchaseOrder::where('vendor_id', $bill->vendor_id)
            ->whereBetween('order_date', [
                $bill->date->copy()->subDays(self::PO_DATE_WINDOW)->format('Y-m-d'),
                $bill->date->copy()->addDays(self::PO_DATE_WINDOW)->format('Y-m-d'),
            ])
            ->whereNull('deleted_at')
            ->exists();
        $noPo = $poMatch ? 0 : 15;
        $signals['no_po'] = $noPo;
        if ($noPo > 0) {
            $reasons[] = 'No matching purchase order found';
        }

        // 4. Unusual account (0–15)
        $unusualAccount = $this->unusualAccountRisk($bill);
        $signals['unusual_account'] = $unusualAccount['score'];
        if ($unusualAccount['score'] > 0) {
            $reasons[] = $unusualAccount['reason'];
        }

        // 5. Vendor average variance (0 or 15)
        $varianceScore = $this->vendorVarianceRisk($bill, $vendorBillCount);
        $signals['vendor_variance'] = $varianceScore;
        if ($varianceScore > 0) {
            $reasons[] = 'Amount significantly above vendor average';
        }

        // 6. First-time line items (0 or 10)
        $firstTimeItem = $this->firstTimeItemRisk($bill);
        $signals['first_time_item'] = $firstTimeItem['score'];
        if ($firstTimeItem['score'] > 0) {
            $reasons[] = $firstTimeItem['reason'];
        }

        $score = (int) min(100, array_sum($signals));
        $tier = $this->tier($score);

        return compact('score', 'tier', 'reasons', 'signals');
    }

    /**
     * Lighter scoring for sales invoices (fewer fraud signals).
     *
     * @return array{score: int, tier: string, reasons: string[], signals: array}
     */
    public function scoreSale(Sale $sale): array
    {
        $sale->loadMissing(['customer']);

        $signals = [];
        $reasons = [];

        // 1. Amount risk (0–25)
        $amountScore = $this->amountRisk((float) $sale->total, 25);
        $signals['amount'] = $amountScore;
        if ($amountScore >= 15) {
            $reasons[] = 'High-value invoice (' . number_format($sale->total, 2) . ')';
        }

        // 2. New customer (0 or 20)
        $custSaleCount = Sale::where('customer_id', $sale->customer_id)
            ->where('id', '!=', $sale->id)
            ->whereNull('deleted_at')
            ->count();
        $newCust = $custSaleCount === 0 ? 20 : 0;
        $signals['new_customer'] = $newCust;
        if ($newCust > 0) {
            $reasons[] = 'First invoice for this customer';
        }

        // 3. Customer average variance (0 or 15)
        if ($custSaleCount >= 3) {
            $avgSale = (float) Sale::where('customer_id', $sale->customer_id)
                ->where('id', '!=', $sale->id)
                ->whereNull('deleted_at')
                ->avg('total');
            if ($avgSale > 0 && $sale->total > $avgSale * self::VENDOR_AVG_MULTIPLIER) {
                $signals['customer_variance'] = 15;
                $reasons[] = 'Amount significantly above customer average';
            } else {
                $signals['customer_variance'] = 0;
            }
        } else {
            $signals['customer_variance'] = 0;
        }

        // 4. Customer credit exposure (0 or 30)
        $signals['credit_limit'] = $sale->credit_limit_exceeded ? 30 : 0;
        if ($sale->credit_limit_exceeded) {
            $reasons[] = 'Customer credit limit exceeded';
        }

        $score = (int) min(100, array_sum($signals));
        $tier = $this->tier($score);

        return compact('score', 'tier', 'reasons', 'signals');
    }

    /**
     * Score multiple bills at once (batch-friendly for index views).
     *
     * @return array<int, array>  Keyed by bill id
     */
    public function scoreBills($bills): array
    {
        $billCollection = method_exists($bills, 'getCollection') ? $bills->getCollection() : collect($bills);
        if ($billCollection->isEmpty()) {
            return [];
        }

        $billCollection->loadMissing(['vendor', 'billItems']);

        $billIds = $billCollection->pluck('id')->filter()->values();
        $vendorIds = $billCollection->pluck('vendor_id')->filter()->unique()->values();

        $vendorStats = Bill::query()
            ->whereIn('vendor_id', $vendorIds)
            ->select('vendor_id', DB::raw('COUNT(*) as bill_count'), DB::raw('SUM(total) as total_sum'))
            ->groupBy('vendor_id')
            ->get()
            ->keyBy('vendor_id');

        $dates = $billCollection->pluck('date')->filter();
        $purchaseOrders = collect();
        if ($dates->isNotEmpty()) {
            $purchaseOrders = PurchaseOrder::query()
                ->whereIn('vendor_id', $vendorIds)
                ->whereBetween('order_date', [
                    $dates->min()->copy()->subDays(self::PO_DATE_WINDOW)->format('Y-m-d'),
                    $dates->max()->copy()->addDays(self::PO_DATE_WINDOW)->format('Y-m-d'),
                ])
                ->get(['vendor_id', 'order_date'])
                ->groupBy('vendor_id');
        }

        $historicalItems = BillItem::query()
            ->join('bills', 'bills.id', '=', 'bill_items.bill_id')
            ->whereIn('bills.vendor_id', $vendorIds)
            ->whereNotIn('bills.id', $billIds)
            ->whereNull('bills.deleted_at')
            ->whereNull('bill_items.deleted_at')
            ->get(['bills.vendor_id', 'bill_items.account_id', 'bill_items.description'])
            ->groupBy('vendor_id');

        $scores = [];
        foreach ($bills as $bill) {
            // $scores[$bill->id] = $this->scoreBill($bill);
            $scores[$bill->id] = $this->scoreBillFromBatch(
                $bill,
                $vendorStats->get($bill->vendor_id),
                $purchaseOrders->get($bill->vendor_id, collect()),
                $historicalItems->get($bill->vendor_id, collect())
            );
        }
        return $scores;
    }

    /**
     * Score multiple sales at once.
     *
     * @return array<int, array>  Keyed by sale id
     */
    public function scoreSales($sales): array
    {
        $saleCollection = method_exists($sales, 'getCollection') ? $sales->getCollection() : collect($sales);
        if ($saleCollection->isEmpty()) {
            return [];
        }

        $saleCollection->loadMissing(['customer']);

        $customerIds = $saleCollection->pluck('customer_id')->filter()->unique()->values();
        $customerStats = Sale::query()
            ->whereIn('customer_id', $customerIds)
            ->select('customer_id', DB::raw('COUNT(*) as sale_count'), DB::raw('SUM(total) as total_sum'))
            ->groupBy('customer_id')
            ->get()
            ->keyBy('customer_id');

        $scores = [];
        foreach ($sales as $sale) {
            // $scores[$sale->id] = $this->scoreSale($sale);
            $scores[$sale->id] = $this->scoreSaleFromBatch($sale, $customerStats->get($sale->customer_id));
        }
        return $scores;
    }

    private function scoreBillFromBatch(Bill $bill, $vendorStats, $vendorPurchaseOrders, $vendorHistoricalItems): array
    {
        $signals = [];
        $reasons = [];

        $amountScore = $this->amountRisk((float) $bill->total, 25);
        $signals['amount'] = $amountScore;
        if ($amountScore >= 15) {
            $reasons[] = 'High-value bill (' . number_format($bill->total, 2) . ')';
        }

        $vendorBillCount = max(0, (int) ($vendorStats->bill_count ?? 0) - 1);
        $newVendor = $vendorBillCount === 0 ? 20 : 0;
        $signals['new_vendor'] = $newVendor;
        if ($newVendor > 0) {
            $reasons[] = 'First bill from this vendor';
        }

        $poMatch = false;
        if ($bill->date) {
            $poStart = $bill->date->copy()->subDays(self::PO_DATE_WINDOW);
            $poEnd = $bill->date->copy()->addDays(self::PO_DATE_WINDOW);
            $poMatch = $vendorPurchaseOrders->contains(function ($purchaseOrder) use ($poStart, $poEnd) {
                return $purchaseOrder->order_date
                    && $purchaseOrder->order_date->betweenIncluded($poStart, $poEnd);
            });
        }

        $noPo = $poMatch ? 0 : 15;
        $signals['no_po'] = $noPo;
        if ($noPo > 0) {
            $reasons[] = 'No matching purchase order found';
        }

        $unusualAccount = $this->unusualAccountRiskFromBatch($bill, $vendorHistoricalItems);
        $signals['unusual_account'] = $unusualAccount['score'];
        if ($unusualAccount['score'] > 0) {
            $reasons[] = $unusualAccount['reason'];
        }

        $varianceScore = 0;
        if ($vendorBillCount >= 3) {
            $avgTotal = ((float) ($vendorStats->total_sum ?? 0) - (float) $bill->total) / $vendorBillCount;
            if ($avgTotal > 0 && $bill->total > $avgTotal * self::VENDOR_AVG_MULTIPLIER) {
                $varianceScore = 15;
            }
        }
        $signals['vendor_variance'] = $varianceScore;
        if ($varianceScore > 0) {
            $reasons[] = 'Amount significantly above vendor average';
        }

        $firstTimeItem = $this->firstTimeItemRiskFromBatch($bill, $vendorHistoricalItems);
        $signals['first_time_item'] = $firstTimeItem['score'];
        if ($firstTimeItem['score'] > 0) {
            $reasons[] = $firstTimeItem['reason'];
        }

        $score = (int) min(100, array_sum($signals));
        $tier = $this->tier($score);

        return compact('score', 'tier', 'reasons', 'signals');
    }

    private function scoreSaleFromBatch(Sale $sale, $customerStats): array
    {
        $signals = [];
        $reasons = [];

        $amountScore = $this->amountRisk((float) $sale->total, 25);
        $signals['amount'] = $amountScore;
        if ($amountScore >= 15) {
            $reasons[] = 'High-value invoice (' . number_format($sale->total, 2) . ')';
        }

        $custSaleCount = max(0, (int) ($customerStats->sale_count ?? 0) - 1);
        $newCust = $custSaleCount === 0 ? 20 : 0;
        $signals['new_customer'] = $newCust;
        if ($newCust > 0) {
            $reasons[] = 'First invoice for this customer';
        }

        $signals['customer_variance'] = 0;
        if ($custSaleCount >= 3) {
            $avgSale = ((float) ($customerStats->total_sum ?? 0) - (float) $sale->total) / $custSaleCount;
            if ($avgSale > 0 && $sale->total > $avgSale * self::VENDOR_AVG_MULTIPLIER) {
                $signals['customer_variance'] = 15;
                $reasons[] = 'Amount significantly above customer average';
            }
        }

        $signals['credit_limit'] = $sale->credit_limit_exceeded ? 30 : 0;
        if ($sale->credit_limit_exceeded) {
            $reasons[] = 'Customer credit limit exceeded';
        }

        $score = (int) min(100, array_sum($signals));
        $tier = $this->tier($score);

        return compact('score', 'tier', 'reasons', 'signals');
    }

    // ── Signal helpers ───────────────────────────────────────────────

    private function amountRisk(float $amount, int $maxScore): int
    {
        if ($amount <= self::AMOUNT_FLOOR) return 0;
        if ($amount >= self::AMOUNT_CEILING) return $maxScore;

        $range = self::AMOUNT_CEILING - self::AMOUNT_FLOOR;
        return (int) round(($amount - self::AMOUNT_FLOOR) / $range * $maxScore);
    }

    private function unusualAccountRisk(Bill $bill): array
    {
        if (!$bill->billItems || $bill->billItems->isEmpty()) {
            return ['score' => 0, 'reason' => ''];
        }

        $currentAccountIds = $bill->billItems->pluck('account_id')->unique()->filter()->all();
        if (empty($currentAccountIds)) {
            return ['score' => 0, 'reason' => ''];
        }

        // Historical accounts used by this vendor
        $historicalAccounts = BillItem::query()
            ->join('bills', 'bills.id', '=', 'bill_items.bill_id')
            ->where('bills.vendor_id', $bill->vendor_id)
            ->where('bills.id', '!=', $bill->id)
            ->whereNull('bills.deleted_at')
            ->whereNull('bill_items.deleted_at')
            ->distinct()
            ->pluck('bill_items.account_id')
            ->all();

        if (empty($historicalAccounts)) {
            return ['score' => 0, 'reason' => '']; // No history = can't flag as unusual
        }

        $newAccounts = array_diff($currentAccountIds, $historicalAccounts);
        if (!empty($newAccounts)) {
            return [
                'score' => 15,
                'reason' => count($newAccounts) . ' account(s) never used for this vendor before',
            ];
        }

        return ['score' => 0, 'reason' => ''];
    }

    private function unusualAccountRiskFromBatch(Bill $bill, $vendorHistoricalItems): array
    {
        if (!$bill->billItems || $bill->billItems->isEmpty()) {
            return ['score' => 0, 'reason' => ''];
        }

        $currentAccountIds = $bill->billItems->pluck('account_id')->unique()->filter()->all();
        if (empty($currentAccountIds)) {
            return ['score' => 0, 'reason' => ''];
        }

        $historicalAccounts = $vendorHistoricalItems->pluck('account_id')->unique()->filter()->all();
        if (empty($historicalAccounts)) {
            return ['score' => 0, 'reason' => ''];
        }

        $newAccounts = array_diff($currentAccountIds, $historicalAccounts);
        if (!empty($newAccounts)) {
            return [
                'score' => 15,
                'reason' => count($newAccounts) . ' account(s) never used for this vendor before',
            ];
        }

        return ['score' => 0, 'reason' => ''];
    }

    private function vendorVarianceRisk(Bill $bill, int $vendorBillCount): int
    {
        if ($vendorBillCount < 3) return 0;

        $avgTotal = (float) Bill::where('vendor_id', $bill->vendor_id)
            ->where('id', '!=', $bill->id)
            ->whereNull('deleted_at')
            ->avg('total');

        if ($avgTotal > 0 && $bill->total > $avgTotal * self::VENDOR_AVG_MULTIPLIER) {
            return 15;
        }

        return 0;
    }

    private function firstTimeItemRisk(Bill $bill): array
    {
        if (!$bill->billItems || $bill->billItems->isEmpty()) {
            return ['score' => 0, 'reason' => ''];
        }

        $currentDescriptions = $bill->billItems
            ->pluck('description')
            ->filter()
            ->map(fn($d) => mb_strtolower(trim($d)))
            ->unique()
            ->all();

        if (empty($currentDescriptions)) {
            return ['score' => 0, 'reason' => ''];
        }

        $historicalDescriptions = BillItem::query()
            ->join('bills', 'bills.id', '=', 'bill_items.bill_id')
            ->where('bills.vendor_id', $bill->vendor_id)
            ->where('bills.id', '!=', $bill->id)
            ->whereNull('bills.deleted_at')
            ->whereNull('bill_items.deleted_at')
            ->whereNotNull('bill_items.description')
            ->pluck('bill_items.description')
            ->map(fn($d) => mb_strtolower(trim($d)))
            ->unique()
            ->all();

        if (empty($historicalDescriptions)) {
            return ['score' => 0, 'reason' => ''];
        }

        $newItems = array_diff($currentDescriptions, $historicalDescriptions);
        if (!empty($newItems)) {
            return [
                'score' => 10,
                'reason' => count($newItems) . ' line item(s) never billed by this vendor before',
            ];
        }

        return ['score' => 0, 'reason' => ''];
    }

    private function firstTimeItemRiskFromBatch(Bill $bill, $vendorHistoricalItems): array
    {
        if (!$bill->billItems || $bill->billItems->isEmpty()) {
            return ['score' => 0, 'reason' => ''];
        }

        $currentDescriptions = $bill->billItems
            ->pluck('description')
            ->filter()
            ->map(fn($d) => mb_strtolower(trim($d)))
            ->unique()
            ->all();

        if (empty($currentDescriptions)) {
            return ['score' => 0, 'reason' => ''];
        }

        $historicalDescriptions = $vendorHistoricalItems
            ->pluck('description')
            ->filter()
            ->map(fn($d) => mb_strtolower(trim($d)))
            ->unique()
            ->all();

        if (empty($historicalDescriptions)) {
            return ['score' => 0, 'reason' => ''];
        }

        $newItems = array_diff($currentDescriptions, $historicalDescriptions);
        if (!empty($newItems)) {
            return [
                'score' => 10,
                'reason' => count($newItems) . ' line item(s) never billed by this vendor before',
            ];
        }

        return ['score' => 0, 'reason' => ''];
    }

    private function tier(int $score): string
    {
        if ($score < self::LOW_THRESHOLD)  return 'low';
        if ($score < self::HIGH_THRESHOLD) return 'medium';
        return 'high';
    }
}
