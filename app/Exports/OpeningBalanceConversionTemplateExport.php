<?php

namespace App\Exports;

use App\Models\ChartOfAccount;
use App\Support\OpeningBalanceAccountEligibility;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OpeningBalanceConversionTemplateExport implements FromArray, WithHeadings
{
    public function __construct(
        private readonly int $companyId,
        private readonly array $accountIds = [],
        private readonly string $eligibilityScope = 'both'
    )
    {
    }

    public function array(): array
    {
        return ChartOfAccount::query()
            ->forCompany($this->companyId)
            ->when($this->accountIds, fn ($query) => $query->whereIn('id', $this->accountIds))
            ->orderBy('account_number')
            ->orderBy('id')
            ->get()
            ->filter(fn (ChartOfAccount $account) => match ($this->eligibilityScope) {
                'eligible' => self::isEligible($account),
                'ineligible' => ! self::isEligible($account),
                default => true,
            })
            ->map(fn (ChartOfAccount $account) => [
                $account->account_number,
                $account->name,
                '',
                '',
                '',
                '',
                $account->category,
                self::eligibility($account),
            ])
            ->values()
            ->all();
    }

    public function headings(): array
    {
        return [
            'erp_account_number',
            'erp_account_name',
            'debit',
            'credit',
            'description',
            'source_reference',
            'category',
            'opening_balance_eligibility',
        ];
    }

    public static function isEligible(ChartOfAccount $account): bool
    {
        return OpeningBalanceAccountEligibility::isEligible($account);
    }

    public static function eligibility(ChartOfAccount $account): string
    {
        return OpeningBalanceAccountEligibility::label($account);
    }
}
