<?php

namespace App\Exports\Logistics;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BankContractTemplateExport implements WithHeadings, WithStyles
{
    public function headings(): array
    {
        return [
            'Contract Type',
            'Issuing Bank',
            'Advising Bank',
            'Reimbursing Bank',
            'Applicant',
            'Beneficiary',
            'Currency',
            'Amount',
            'Tolerance %',
            'Payment Terms',
            'Issue Date (YYYY-MM-DD)',
            'Expiry Date (YYYY-MM-DD)',
            'Place of Expiry',
            'Latest Shipment Date (YYYY-MM-DD)',
            'Incoterms',
            'Port of Loading',
            'Port of Discharge',
            'Goods Description',
            'Documents Required (Comma separated)',
            'Special Conditions'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '059669'] // Emerald 600 for Bank Contracts
                ]
            ],
        ];
    }
}
