<?php

namespace App\Support;

use App\Models\ChartOfAccount;

final class OpeningBalanceAccountEligibility
{
    public static function isEligible(ChartOfAccount $account): bool
    {
        return self::reason($account) === null;
    }

    public static function reason(ChartOfAccount $account): ?string
    {
        if ($account->type !== 'Account') {
            return 'Group accounts cannot receive conversion balances.';
        }
        if ($account->system_account_type === 'CURRENT_YEAR_EARNINGS') {
            return 'Current Year Earnings is calculated from revenue, COGS, and expense activity.';
        }

        return null;
    }

    public static function label(ChartOfAccount $account): string
    {
        if ($reason = self::reason($account)) {
            return 'Not eligible - ' . $reason;
        }
        if (in_array($account->category, AccountingConstants::PROFIT_AND_LOSS_CATEGORIES, true)) {
            return 'Eligible - profit and loss conversion balance';
        }
        if (in_array($account->system_account_type, ['ACCOUNTS_RECEIVABLE', 'ACCOUNTS_PAYABLE'], true)) {
            return 'Eligible - control total; must reconcile to posted outstanding documents';
        }

        return 'Eligible';
    }
}
