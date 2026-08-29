<?php

namespace App\Services\Account;

use App\Models\AccountingPeriod;
use App\Support\AccountingDate;
use Illuminate\Validation\ValidationException;

class AccountingPeriodResolver
{
    public function resolve(int $companyId, string $date, bool $requireOpen = true): AccountingPeriod
    {
        $date = AccountingDate::fromIso($date);
        $matches = AccountingPeriod::query()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->orderByDesc('start_date')
            ->get();

        if ($matches->count() !== 1) {
            throw ValidationException::withMessages([
                'date' => $matches->isEmpty()
                    ? 'No configured accounting period contains the transaction date.'
                    : 'Overlapping accounting periods contain the transaction date; resolve the configuration before posting.',
            ]);
        }

        $period = $matches->first();
        if ($requireOpen && (! $period->is_active || $period->is_locked)) {
            throw ValidationException::withMessages(['date' => 'The accounting period containing the transaction date is closed.']);
        }

        return $period;
    }
}
