<?php

namespace App\Services\Account;

use App\Models\BillItem;
use App\Models\ChartOfAccount;
use App\Models\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AccountSuggestionService
{
    /** Minimum description length (after normalization) to trigger suggestions. */
    private const MIN_DESCRIPTION_LENGTH = 3;

    /** How many suggestions to return. */
    private const MAX_SUGGESTIONS = 3;

    /** Vendor-history weight multiplier relative to description-match. */
    private const VENDOR_WEIGHT = 2.0;

    private const STOPWORDS = [
        'the', 'and', 'for', 'from', 'to', 'of', 'on', 'at', 'by', 'in', 'with',
        'payment', 'pay', 'paid', 'invoice', 'inv', 'ref', 'no', 'number', 'bill',
        'amount', 'total', 'qty', 'item', 'per', 'unit', 'each', 'a', 'an', 'is',
    ];

    /**
     * Suggest accounts based on description and/or vendor/customer history.
     *
     * @return array{suggestions: array[]}
     */
    public function suggest(?string $description = null, ?int $vendorId = null, ?int $customerId = null): array
    {
        $companyId = loggedCompanyId()['company_id'];
        $scores = []; // account_id => ['score' => float, 'reasons' => string[]]

        // --- Signal 1: Vendor/Customer history (strongest signal) ---
        if ($vendorId) {
            $this->addVendorHistory($scores, $companyId, $vendorId);
        }
        if ($customerId) {
            $this->addCustomerHistory($scores, $companyId, $customerId);
        }

        // --- Signal 2: Description frequency matching ---
        $normalized = $this->normalizeText($description ?? '');
        if (mb_strlen($normalized) >= self::MIN_DESCRIPTION_LENGTH) {
            $tokens = $this->tokenize($normalized);
            if (!empty($tokens)) {
                $this->addDescriptionMatches($scores, $companyId, $tokens);
            }
        }

        if (empty($scores)) {
            return ['suggestions' => []];
        }

        // Sort by score descending, take top N.
        arsort($scores);
        $topIds = array_slice(array_keys($scores), 0, self::MAX_SUGGESTIONS);

        // Fetch account details.
        $accounts = ChartOfAccount::forCompany($companyId)
            ->whereIn('id', $topIds)
            ->get(['id', 'name', 'account_number', 'category'])
            ->keyBy('id');

        // Compute max score for confidence bucketing.
        $maxScore = max(array_column($scores, 'score'));

        $suggestions = [];
        foreach ($topIds as $id) {
            $acct = $accounts[$id] ?? null;
            if (!$acct) continue;

            $entry = $scores[$id];
            $pct = $maxScore > 0 ? round(($entry['score'] / $maxScore) * 100) : 0;

            $suggestions[] = [
                'account_id'     => $acct->id,
                'account_name'   => $acct->name,
                'account_number' => $acct->account_number,
                'category'       => $acct->category,
                'confidence'     => $pct >= 80 ? 'high' : ($pct >= 50 ? 'medium' : 'low'),
                'score'          => $pct,
                'reason'         => implode(', ', $entry['reasons']),
            ];
        }

        return ['suggestions' => $suggestions];
    }

    /**
     * Add scores from vendor-specific bill-item history.
     */
    private function addVendorHistory(array &$scores, int $companyId, int $vendorId): void
    {
        $rows = BillItem::query()
            ->join('bills', 'bills.id', '=', 'bill_items.bill_id')
            ->where('bills.company_id', $companyId)
            ->where('bills.vendor_id', $vendorId)
            ->whereNull('bills.deleted_at')
            ->whereNull('bill_items.deleted_at')
            ->whereNotNull('bill_items.account_id')
            ->select('bill_items.account_id', DB::raw('COUNT(*) as freq'))
            ->groupBy('bill_items.account_id')
            ->orderByDesc('freq')
            ->limit(10)
            ->get();

        foreach ($rows as $row) {
            $id = $row->account_id;
            $weighted = $row->freq * self::VENDOR_WEIGHT;

            if (!isset($scores[$id])) {
                $scores[$id] = ['score' => 0, 'reasons' => []];
            }
            $scores[$id]['score'] += $weighted;
            $scores[$id]['reasons'][] = "Used {$row->freq}x for this vendor";
        }
    }

    /**
     * Add scores from customer-specific transaction history (for sales).
     */
    private function addCustomerHistory(array &$scores, int $companyId, int $customerId): void
    {
        $rows = Transaction::query()
            ->where('company_id', $companyId)
            ->where('customer_id', $customerId)
            ->whereNotNull('account_id')
            ->whereNull('deleted_at')
            ->select('account_id', DB::raw('COUNT(*) as freq'))
            ->groupBy('account_id')
            ->orderByDesc('freq')
            ->limit(10)
            ->get();

        foreach ($rows as $row) {
            $id = $row->account_id;
            $weighted = $row->freq * self::VENDOR_WEIGHT;

            if (!isset($scores[$id])) {
                $scores[$id] = ['score' => 0, 'reasons' => []];
            }
            $scores[$id]['score'] += $weighted;
            $scores[$id]['reasons'][] = "Used {$row->freq}x for this customer";
        }
    }

    /**
     * Add scores from description token frequency in bill_items and transactions.
     */
    private function addDescriptionMatches(array &$scores, int $companyId, array $tokens): void
    {
        // Build ILIKE conditions for the significant tokens (max 5 to keep queries reasonable).
        $searchTokens = array_slice($tokens, 0, 5);

        // --- Bill items ---
        $billQuery = BillItem::query()
            ->join('bills', 'bills.id', '=', 'bill_items.bill_id')
            ->where('bills.company_id', $companyId)
            ->whereNull('bill_items.deleted_at')
            ->whereNotNull('bill_items.account_id')
            ->where(function ($q) use ($searchTokens) {
                foreach ($searchTokens as $token) {
                    $q->orWhere('bill_items.description', 'ILIKE', "%{$token}%");
                }
            })
            ->select('bill_items.account_id', DB::raw('COUNT(*) as freq'))
            ->groupBy('bill_items.account_id')
            ->orderByDesc('freq')
            ->limit(10);

        foreach ($billQuery->get() as $row) {
            $id = $row->account_id;
            if (!isset($scores[$id])) {
                $scores[$id] = ['score' => 0, 'reasons' => []];
            }
            $scores[$id]['score'] += $row->freq;
            $scores[$id]['reasons'][] = "Description matched {$row->freq}x in bills";
        }

        // --- Transactions ---
        $txnQuery = Transaction::query()
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->whereNotNull('account_id')
            ->where(function ($q) use ($searchTokens) {
                foreach ($searchTokens as $token) {
                    $q->orWhere('description', 'ILIKE', "%{$token}%");
                }
            })
            ->select('account_id', DB::raw('COUNT(*) as freq'))
            ->groupBy('account_id')
            ->orderByDesc('freq')
            ->limit(10);

        foreach ($txnQuery->get() as $row) {
            $id = $row->account_id;
            if (!isset($scores[$id])) {
                $scores[$id] = ['score' => 0, 'reasons' => []];
            }
            $scores[$id]['score'] += $row->freq;
            if (!in_array("Description matched in transactions", array_map(fn($r) => preg_replace('/\d+x/', 'Nx', $r), $scores[$id]['reasons']))) {
                $scores[$id]['reasons'][] = "Description matched {$row->freq}x in transactions";
            }
        }
    }

    private function normalizeText(string $s): string
    {
        $s = mb_strtolower($s);
        $s = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $s) ?? '';
        $s = preg_replace('/\s+/', ' ', $s) ?? '';
        return trim($s);
    }

    private function tokenize(string $normalized): array
    {
        if ($normalized === '') return [];
        $tokens = explode(' ', $normalized);
        $tokens = array_filter($tokens, fn($t) => mb_strlen($t) >= 2 && !in_array($t, self::STOPWORDS, true));
        return array_values(array_unique($tokens));
    }
}
