<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ChartOfAccountTemplateExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        return [
            [
                '1010',
                'Main Checking Account',
                'Bank',
                'Primary business checking account',
                'Tax Exempt (0%)',
            ],
            [
                '2000',
                'Accounts Payable',
                'Current Liability',
                'Outstanding vendor bills',
                'Tax Exempt (0%)',
            ],
            [
                '4000',
                'Sales Revenue',
                'Revenue',
                'Revenue from products and services',
                'Tax on Sales (0%)',
            ],
            [
                '6000',
                'Office Rent',
                'Expense',
                'Monthly rent for head office',
                'Tax on Purchases (0%)',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'Code',
            'Name',
            'Type',
            'Description',
            'Tax Code',
        ];
    }
}
