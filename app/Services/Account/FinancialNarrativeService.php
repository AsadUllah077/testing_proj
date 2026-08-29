<?php

namespace App\Services\Account;

class FinancialNarrativeService
{
    /**
     * Generate narrative insights for a Profit & Loss report.
     *
     * @param  array  $pl  The profitLoss data array from ProfitLossController
     * @return string[]     3-5 sentences of executive summary
     */
    public function profitLoss(array $pl): array
    {
        $insights = [];

        $revenue    = (float) ($pl['total_revenue']['current'] ?? 0);
        $otherInc   = (float) ($pl['total_other_income']['current'] ?? 0);
        $cogs       = (float) ($pl['total_cogs']['current'] ?? 0);
        $expenses   = (float) ($pl['total_expenses']['current'] ?? 0);
        $gross      = (float) ($pl['gross_profit']['current'] ?? 0);
        $operating  = (float) ($pl['operating_profit']['current'] ?? 0);
        $net        = (float) ($pl['net_profit']['current'] ?? 0);

        // 1. Revenue headline
        if ($revenue > 0) {
            $grossMargin = $revenue > 0 ? round(($gross / $revenue) * 100, 1) : 0;
            $netMargin   = $revenue > 0 ? round(($net / $revenue) * 100, 1) : 0;

            $insights[] = sprintf(
                'Total revenue is %s with a gross margin of %s%% and a net margin of %s%%.',
                $this->fmt($revenue), $grossMargin, $netMargin
            );
        } else {
            $insights[] = 'No revenue recorded for this period.';
        }

        // 2. Prior-period variance (if available)
        if (isset($pl['total_revenue']['compare_1']) && $pl['total_revenue']['compare_1'] > 0) {
            $priorRev  = (float) $pl['total_revenue']['compare_1'];
            $revChange = $revenue - $priorRev;
            $revPct    = round(($revChange / $priorRev) * 100, 1);
            $direction = $revPct >= 0 ? 'grew' : 'declined';

            $insights[] = sprintf(
                'Revenue %s by %s%% compared to the prior period (%s vs %s).',
                $direction, abs($revPct), $this->fmt($revenue), $this->fmt($priorRev)
            );
        }

        // 3. COGS / gross profit analysis
        if ($revenue > 0 && $cogs > 0) {
            $cogsRatio = round(($cogs / $revenue) * 100, 1);
            if ($cogsRatio > 70) {
                $insights[] = sprintf(
                    'Cost of goods sold consumes %s%% of revenue (%s) — gross margins are under pressure.',
                    $cogsRatio, $this->fmt($cogs)
                );
            } elseif ($cogsRatio > 50) {
                $insights[] = sprintf(
                    'COGS represents %s%% of revenue. Gross profit stands at %s.',
                    $cogsRatio, $this->fmt($gross)
                );
            }
        }

        // 4. Operating expenses insight
        if ($revenue > 0 && $expenses > 0) {
            $opexRatio = round(($expenses / $revenue) * 100, 1);
            if ($opexRatio > 40) {
                $insights[] = sprintf(
                    'Operating expenses are %s%% of revenue (%s) — review for cost optimization opportunities.',
                    $opexRatio, $this->fmt($expenses)
                );
            }

            // Prior-period expense comparison
            if (isset($pl['total_expenses']['compare_1']) && $pl['total_expenses']['compare_1'] > 0) {
                $priorExp  = (float) $pl['total_expenses']['compare_1'];
                $expChange = round((($expenses - $priorExp) / $priorExp) * 100, 1);
                if (abs($expChange) >= 5) {
                    $dir = $expChange > 0 ? 'increased' : 'decreased';
                    $insights[] = sprintf(
                        'Total expenses %s by %s%% versus the prior period.',
                        $dir, abs($expChange)
                    );
                }
            }
        }

        // 5. Net profit headline
        if ($net < 0) {
            $insights[] = sprintf(
                'The business recorded a net loss of %s this period. Investigate revenue shortfalls and discretionary expenses.',
                $this->fmt(abs($net))
            );
        } elseif ($net > 0 && isset($pl['net_profit']['compare_1'])) {
            $priorNet = (float) $pl['net_profit']['compare_1'];
            if ($priorNet != 0) {
                $netChange = round((($net - $priorNet) / abs($priorNet)) * 100, 1);
                if (abs($netChange) >= 5) {
                    $dir = $netChange > 0 ? 'improved' : 'declined';
                    $insights[] = sprintf(
                        'Net profit %s by %s%% period-over-period (%s vs %s).',
                        $dir, abs($netChange), $this->fmt($net), $this->fmt($priorNet)
                    );
                }
            }
        }

        return array_slice($insights, 0, 5);
    }

    /**
     * Generate narrative insights for a Balance Sheet report.
     */
    public function balanceSheet(array $bs): array
    {
        $insights = [];

        $assets      = (float) ($bs['total_assets']['current'] ?? 0);
        $liabilities = (float) ($bs['total_liabilities']['current'] ?? 0);
        $equity      = (float) ($bs['total_equity']['current'] ?? 0);

        // 1. Size & composition
        if ($assets > 0) {
            $debtRatio = $liabilities > 0 ? round(($liabilities / $assets) * 100, 1) : 0;
            $insights[] = sprintf(
                'Total assets stand at %s. The debt-to-asset ratio is %s%%, meaning %s%% of assets are financed by equity.',
                $this->fmt($assets), $debtRatio, round(100 - $debtRatio, 1)
            );
        }

        // 2. Prior-period asset growth
        if (isset($bs['total_assets']['compare_1']) && $bs['total_assets']['compare_1'] > 0) {
            $priorAssets = (float) $bs['total_assets']['compare_1'];
            $assetChange = round((($assets - $priorAssets) / $priorAssets) * 100, 1);
            if (abs($assetChange) >= 3) {
                $dir = $assetChange > 0 ? 'grew' : 'contracted';
                $insights[] = sprintf(
                    'Total assets %s by %s%% compared to the prior period (%s vs %s).',
                    $dir, abs($assetChange), $this->fmt($assets), $this->fmt($priorAssets)
                );
            }
        }

        // 3. Leverage warning
        if ($assets > 0 && $liabilities > 0) {
            $leverageRatio = $equity > 0 ? round($liabilities / $equity, 2) : null;
            if ($leverageRatio !== null && $leverageRatio > 2) {
                $insights[] = sprintf(
                    'Debt-to-equity ratio is %s — liabilities exceed twice the equity base. Monitor leverage carefully.',
                    $leverageRatio
                );
            } elseif ($leverageRatio !== null) {
                $insights[] = sprintf(
                    'Debt-to-equity ratio is %s, indicating a %s leverage position.',
                    $leverageRatio,
                    $leverageRatio <= 1 ? 'conservative' : 'moderate'
                );
            }
        }

        // 4. Equity movement
        if (isset($bs['total_equity']['compare_1']) && $bs['total_equity']['compare_1'] != 0) {
            $priorEquity = (float) $bs['total_equity']['compare_1'];
            $equityChange = round((($equity - $priorEquity) / abs($priorEquity)) * 100, 1);
            if (abs($equityChange) >= 3) {
                $dir = $equityChange > 0 ? 'increased' : 'decreased';
                $insights[] = sprintf(
                    'Equity %s by %s%% period-over-period, driven by retained earnings movement.',
                    $dir, abs($equityChange)
                );
            }
        }

        // 5. Balance check
        $liabilitiesEquity = $liabilities + $equity;
        $imbalance = abs($assets - $liabilitiesEquity);
        if ($imbalance > 1) {
            $insights[] = sprintf(
                'Balance sheet is out of balance by %s — investigate unposted entries or rounding errors.',
                $this->fmt($imbalance)
            );
        }

        return array_slice($insights, 0, 5);
    }

    /**
     * Generate narrative insights for a Cash Flow Statement.
     */
    public function cashFlow(array $cf): array
    {
        $insights = [];

        // Use the first (current) period's data.
        $data = $cf['data'][0] ?? null;
        if (!$data) return ['No cash flow data available for this period.'];

        $opening    = (float) ($data['opening_balance'] ?? 0);
        $opCash     = (float) ($data['net_cash_from_operating'] ?? 0);
        $invCash    = (float) ($data['net_cash_from_investing'] ?? 0);
        $finCash    = (float) ($data['net_cash_from_financing'] ?? 0);
        $netMove    = (float) ($data['net_cash_movement'] ?? 0);
        $closing    = (float) ($data['closing_balance'] ?? 0);

        // 1. Overall cash position
        if ($netMove >= 0) {
            $insights[] = sprintf(
                'Cash position improved by %s during the period, closing at %s (up from %s opening).',
                $this->fmt($netMove), $this->fmt($closing), $this->fmt($opening)
            );
        } else {
            $insights[] = sprintf(
                'Cash decreased by %s during the period, closing at %s (down from %s opening).',
                $this->fmt(abs($netMove)), $this->fmt($closing), $this->fmt($opening)
            );
        }

        // 2. Operating cash flow quality
        if ($opCash > 0) {
            $insights[] = sprintf(
                'Operations generated %s in cash — the business is operationally cash-positive.',
                $this->fmt($opCash)
            );
        } elseif ($opCash < 0) {
            $insights[] = sprintf(
                'Operations consumed %s in cash. Negative operating cash flow may indicate collection issues or rising costs.',
                $this->fmt(abs($opCash))
            );
        }

        // 3. Investing activity
        if ($invCash < 0) {
            $insights[] = sprintf(
                'The business invested %s in assets during the period (net investing outflow).',
                $this->fmt(abs($invCash))
            );
        } elseif ($invCash > 0) {
            $insights[] = sprintf(
                'Investing activities generated %s — likely from asset disposals or investment returns.',
                $this->fmt($invCash)
            );
        }

        // 4. Financing activity
        if (abs($finCash) > 0) {
            if ($finCash > 0) {
                $insights[] = sprintf(
                    'Financing activities brought in %s — new borrowings or equity injections.',
                    $this->fmt($finCash)
                );
            } else {
                $insights[] = sprintf(
                    'Financing outflows of %s — loan repayments, distributions, or dividend payments.',
                    $this->fmt(abs($finCash))
                );
            }
        }

        // 5. Cash runway concern
        if ($closing < 0) {
            $insights[] = 'Cash balance is negative — immediate attention required to avoid liquidity issues.';
        } elseif ($opCash < 0 && $closing > 0) {
            $burnRate = abs($opCash);
            $months = $burnRate > 0 ? round($closing / ($burnRate / 1), 1) : 0;
            if ($months > 0 && $months < 6) {
                $insights[] = sprintf(
                    'At the current operating burn rate, remaining cash covers approximately %s period(s) of operations.',
                    $months
                );
            }
        }

        return array_slice($insights, 0, 5);
    }

    private function fmt(float $value): string
    {
        return number_format(abs($value), 2);
    }
}
