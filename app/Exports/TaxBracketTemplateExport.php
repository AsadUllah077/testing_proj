<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class TaxBracketTemplateExport implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    public function __construct(protected string $fiscalYear)
    {
    }

    public function headings(): array
    {
        return [
            'min_income',
            'max_income',
            'rate',
        ];
    }

    public function array(): array
    {
        return [
            [0, 600000, 0],
            [600000, 1200000, 1],
            [1200000, 2200000, 11],
            [2200000, 3200000, 23],
            [3200000, 4100000, 30],
            [4100000, null, 35],
        ];
    }

    public function title(): string
    {
        return 'Tax Brackets ' . $this->fiscalYear;
    }
}
