<?php

namespace App\Services\Account;

use App\Models\AccountingPeriod;
use App\Models\Budget;
use App\Models\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BudgetInsightService
{
    /** Max insights to return. */
    private const MAX_INSIGHTS = 6;

    /**
     * Generate natural-language insights from budget vs actual data.
     *
     * @param  Collection  $rows    Each row: {account, budget, actual, variance, pct, over_budget}
     * @param  array       $totals  {budget, actual, variance}
     * @param  AccountingPeriod|null  $period   Current period
     * @return array{insights: array[], health: string}
     */
    public function generate(Collection $rows, array $totals, ?AccountingPeriod $period = null): array
    {
        $insights = [];

        // --- 1. Overall health assessment ---
        $insights[] = $this->overallHealth($totals, $rows);

        // --- 2. Largest unfavorable variances (over budget) ---
        $overBudget = $rows->filter(fn($r) => $r['over_budget'] && $r['budget'] > 0)
            ->sortBy('variance') // most negative first
            ->take(2);

        foreach ($overBudget as $row) {
            $insights[] = $this->overBudgetInsight($row);
        }

        // --- 3. Largest favorable variance (under budget) ---
        $underBudget = $rows->filter(fn($r) => $r['variance'] > 0 && $r['budget'] > 0)
            ->sortByDesc('variance')
            ->first();

        if ($underBudget) {
            $insights[] = $this->underBudgetInsight($underBudget);
        }

        // --- 4. Category breakdown ---
        $categoryInsight = $this->categoryBreakdown($rows);
        if ($categoryInsight) {
            $insights[] = $categoryInsight;
        }

        // --- 5. Prior period comparison (if data exists) ---
        if ($period) {
            $priorInsight = $this->priorPeriodComparison($period, $rows);
            if ($priorInsight) {
                $insights[] = $priorInsight;
            }
        }

        // --- 6. Unbudgeted spending detection ---
        $unbudgetedInsight = $this->unbudgetedSpending($rows);
        if ($unbudgetedInsight) {
            $insights[] = $unbudgetedInsight;
        }

        // Determine health label
        $healthLabel = $this->healthLabel($totals, $rows);

        return [
            'insights' => array_slice(array_filter($insights), 0, self::MAX_INSIGHTS),
            'health'   => $healthLabel,
        ];
    }

    // ─── Insight generators ──────────────────────────────────────────

    private function overallHealth(array $totals, Collection $rows): array
    {
        $budget = $totals['budget'];
        $actual = $totals['actual'];

        if ($budget <= 0) {
            return [
                'type'    => 'info',
                'icon'    => 'info',
                'message' => 'No budget targets set for this period. Consider setting budgets to enable variance tracking.',
            ];
        }

        $pctUsed = round(($actual / $budget) * 100, 1);
        $overCount = $rows->where('over_budget', true)->count();
        $totalAccounts = $rows->filter(fn($r) => $r['budget'] > 0)->count();

        if ($pctUsed <= 80) {
            return [
                'type'    => 'success',
                'icon'    => 'check',
                'message' => "Overall spending is at {$pctUsed}% of budget — well within targets. {$overCount} of {$totalAccounts} budgeted accounts are over budget.",
            ];
        }

        if ($pctUsed <= 100) {
            return [
                'type'    => 'warning',
                'icon'    => 'alert',
                'message' => "Spending has reached {$pctUsed}% of total budget. {$overCount} of {$totalAccounts} budgeted accounts have exceeded their targets. Monitor closely for the remainder of the period.",
            ];
        }

        $overAmount = number_format(abs($totals['variance']), 2);
        return [
            'type'    => 'danger',
            'icon'    => 'alert',
            'message' => "Total spending exceeds budget by {$overAmount} ({$pctUsed}% of target). {$overCount} accounts are over budget. Immediate review recommended.",
        ];
    }

    private function overBudgetInsight(array $row): array
    {
        $name     = $row['account']->name;
        $overAmt  = number_format(abs($row['variance']), 2);
        $pct      = $row['pct'];
        $category = $row['account']->category;

        $severity = $pct > 150 ? 'danger' : 'warning';

        $message = "{$name} ({$category}) is {$pct}% of budget — {$overAmt} over target.";

        if ($pct > 200) {
            $message .= ' This account has more than doubled its budget — investigate for misclassified transactions or scope changes.';
        } elseif ($pct > 150) {
            $message .= ' Significantly over budget. Review recent transactions for this account.';
        } else {
            $message .= ' Consider reallocating budget from under-utilized accounts.';
        }

        return [
            'type'    => $severity,
            'icon'    => 'trending_up',
            'message' => $message,
        ];
    }

    private function underBudgetInsight(array $row): array
    {
        $name    = $row['account']->name;
        $savings = number_format($row['variance'], 2);
        $pct     = $row['pct'] ?? 0;

        return [
            'type'    => 'success',
            'icon'    => 'trending_down',
            'message' => "{$name} is {$savings} under budget ({$pct}% used). This surplus could be reallocated to over-budget accounts if needed.",
        ];
    }

    private function categoryBreakdown(Collection $rows): ?array
    {
        $byCategory = $rows->filter(fn($r) => $r['budget'] > 0)->groupBy(fn($r) => $r['account']->category);

        if ($byCategory->count() < 2) return null;

        $parts = [];
        foreach ($byCategory as $cat => $catRows) {
            $catBudget = $catRows->sum('budget');
            $catActual = $catRows->sum('actual');
            if ($catBudget > 0) {
                $catPct = round(($catActual / $catBudget) * 100, 1);
                $parts[] = "{$cat} at {$catPct}%";
            }
        }

        if (empty($parts)) return null;

        return [
            'type'    => 'info',
            'icon'    => 'pie_chart',
            'message' => 'Budget utilization by category: ' . implode(', ', $parts) . '.',
        ];
    }

    private function priorPeriodComparison(AccountingPeriod $currentPeriod, Collection $rows): ?array
    {
        // Find the immediately preceding period.
        $priorPeriod = AccountingPeriod::where('end_date', '<', $currentPeriod->start_date)
            ->orderByDesc('end_date')
            ->first();

        if (!$priorPeriod) return null;

        $companyId = auth('web')->user()?->company_id;

        $priorActuals = Transaction::where('company_id', $companyId)
            ->where('accounting_period_id', $priorPeriod->id)
            ->select('account_id', DB::raw('SUM(debit) as total_debit'), DB::raw('SUM(credit) as total_credit'))
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');

        if ($priorActuals->isEmpty()) return null;

        // Compare total expense spending.
        $currentExpenses = $rows->filter(fn($r) => in_array($r['account']->category, ['Expenses', 'COGS']))
            ->sum('actual');

        $priorExpenses = 0;
        foreach ($priorActuals as $accountId => $pa) {
            $account = $rows->first(fn($r) => $r['account']->id === $accountId);
            if ($account && in_array($account['account']->category, ['Expenses', 'COGS'])) {
                $priorExpenses += (float) $pa->total_debit - (float) $pa->total_credit;
            }
        }

        if ($priorExpenses <= 0) return null;

        $changePct = round((($currentExpenses - $priorExpenses) / $priorExpenses) * 100, 1);

        if (abs($changePct) < 3) {
            return [
                'type'    => 'info',
                'icon'    => 'compare_arrows',
                'message' => "Expense spending is roughly flat compared to the prior period ({$priorPeriod->name}) — {$changePct}% change.",
            ];
        }

        $direction = $changePct > 0 ? 'increased' : 'decreased';
        $type = $changePct > 15 ? 'warning' : ($changePct < -10 ? 'success' : 'info');

        return [
            'type'    => $type,
            'icon'    => 'compare_arrows',
            'message' => "Total expense spending has {$direction} by " . abs($changePct) . "% compared to the prior period ({$priorPeriod->name}).",
        ];
    }

    private function unbudgetedSpending(Collection $rows): ?array
    {
        $unbudgeted = $rows->filter(fn($r) => $r['budget'] == 0 && $r['actual'] > 0);

        if ($unbudgeted->isEmpty()) return null;

        $totalUnbudgeted = $unbudgeted->sum('actual');
        $count = $unbudgeted->count();

        $topAccounts = $unbudgeted->sortByDesc('actual')
            ->take(3)
            ->map(fn($r) => $r['account']->name)
            ->implode(', ');

        return [
            'type'    => 'warning',
            'icon'    => 'warning',
            'message' => "{$count} account" . ($count > 1 ? 's have' : ' has') . " actual spending (" . number_format($totalUnbudgeted, 2) . ") with no budget set: {$topAccounts}. Consider adding budgets for these.",
        ];
    }

    // ─── Helpers ─────────────────────────────────────────────────────

    private function healthLabel(array $totals, Collection $rows): string
    {
        if ($totals['budget'] <= 0) return 'no_budget';

        $pctUsed = ($totals['actual'] / $totals['budget']) * 100;
        $overCount = $rows->where('over_budget', true)->count();
        $totalBudgeted = $rows->filter(fn($r) => $r['budget'] > 0)->count();

        if ($pctUsed <= 80 && $overCount === 0) return 'excellent';
        if ($pctUsed <= 90 && $overCount <= 1) return 'good';
        if ($pctUsed <= 100) return 'caution';
        return 'over_budget';
    }
}
