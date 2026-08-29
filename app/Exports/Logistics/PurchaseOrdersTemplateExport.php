<?php

namespace App\Exports\Logistics;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PurchaseOrdersTemplateExport implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    public function array(): array
    {
        return [
            [
                // Order Info
                'LPO-2026-TEST-01',    // po_number
                'Test Supplier V2',    // supplier_name (Will be matched to company_name)
                '2026-05-01',          // order_date
                '2026-05-15',          // expected_delivery_date
                'USD',                 // currency
                'Net 30',              // payment_terms
                'Sample order notes',  // notes
                
                // Item Info
                'SKU-ABC-001',         // sku
                'Sample Product Item', // description
                '1234.56.78',          // hs_code
                '10',                  // quantity
                '5.5',                 // weight_kg
                '150.00',              // price
                '5.0',                 // tax_percentage
            ],
            [
                // Second item for the same PO
                'LPO-2026-TEST-01',    // Same po_number to group
                'Test Supplier V2',
                '2026-05-01',
                '2026-05-15',
                'USD',
                'Net 30',
                '',
                
                'SKU-XYZ-002',
                'Another Product',
                '8765.43.21',
                '20',
                '2.0',
                '45.00',
                '0.0',
            ]
        ];
    }

    public function headings(): array
    {
        return [
            'po_number',
            'supplier_name',
            'order_date',
            'expected_delivery_date',
            'currency',
            'payment_terms',
            'notes',
            'sku',
            'description',
            'hs_code',
            'quantity',
            'weight_kg',
            'price',
            'tax_percentage',
        ];
    }

    public function title(): string
    {
        return 'Purchase Orders Template';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20, 'B' => 25, 'C' => 15, 'D' => 15, 'E' => 10,
            'F' => 15, 'G' => 30, 'H' => 15, 'I' => 25, 'J' => 12,
            'K' => 10, 'L' => 10, 'M' => 12, 'N' => 10,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('1:1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0078C8'],
            ],
        ]);

        $sheet->freezePane('A2');

        return [];
    }
}
