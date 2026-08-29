<?php

namespace App\Exports\Logistics;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GDTemplateExport implements WithHeadings, WithStyles
{
    public function headings(): array
    {
        return [
            'Declaration Type',
            'Collectorate',
            'BL/AWB Number',
            'Importer',
            'NTN/CNIC',
            'Exporter',
            'Country of Origin',
            'HS Code',
            'Description',
            'Quantity',
            'Unit',
            'Gross Weight',
            'Net Weight',
            'Customs Value',
            'Currency',
            'Exchange Rate',
            'Customs Duty',
            'Additional Duty',
            'Sales Tax',
            'Income Tax',
            'Total Duty',
            'Duty Payment Mode',
            'Landed Cost',
            'Filed Date (YYYY-MM-DD)',
            'Remarks'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1E40AF'] // Blue 800 for GD
                ]
            ],
        ];
    }
}
