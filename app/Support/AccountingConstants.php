<?php

namespace App\Support;

final class AccountingConstants
{
    public const TRANSACTION_TYPE_OPENING_BALANCE = 'OpeningBalance';

    public const SOURCE_XERO_CONVERSION = 'XERO_CONVERSION';
    public const SOURCE_CHART_OF_ACCOUNT_OPENING = 'CHART_OF_ACCOUNT_OPENING';

    public const SYSTEM_OPENING_BALANCE_EQUITY = 'OPENING_BALANCE_EQUITY';

    public const OPENING_SIDE_DEBIT = 'debit';
    public const OPENING_SIDE_CREDIT = 'credit';

    public const BATCH_STATUS_DRAFT = 'Draft';
    public const BATCH_STATUS_VALIDATED = 'Validated';
    public const BATCH_STATUS_APPROVED = 'Approved';
    public const BATCH_STATUS_POSTED = 'Posted';
    public const BATCH_STATUS_LOCKED = 'Locked';
    public const BATCH_STATUS_REVERSED = 'Reversed';

    public const PROFIT_AND_LOSS_CATEGORIES = ['Revenue', 'Expenses', 'COGS'];
}
