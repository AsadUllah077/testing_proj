<?php

namespace App\Exports;

use App\Models\SaleItem;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class SaleTransactionExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    WithStyles,
    ShouldAutoSize,
    WithEvents
{
    protected $search;
    protected $rowNumber = 0;

    public function __construct($search = null)
    {
        $this->search = $search;
    }

    /**
     * Query for customers
     */
    public function query()
    {
        return SaleItem::with(['sale.customer', 'account'])->orderBy('created_at', 'desc');
    }

    /**
     * Define headings
     */
    public function headings(): array
    {
        return [
            'S.No',
            'Date',
            'Reference No',
            'Customer',
            'Product',
            'Account',
            'KG',
            'Quantity',
            'Price',
            'Total Amount'
        ];
    }

    /**
     * Map each row
     */
    public function map($sale): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $sale->sale?->date,
            $sale->unique_no,
            $sale->sale->customer->name ?? '—',
            $sale->item ?? '—',
            $sale->account->name ?? '—',
            $sale->kg ?? '—',
            $sale->quantity ?? '—',
            $sale->price ?? '—',
            $sale->total ?? '—',
        ];
    }

    /**
     * Style the sheet
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0078C8'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    /**
     * Register events
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Add borders to all cells
                $cellRange = 'A1:G' . ($this->rowNumber + 1);
                $event->sheet->getStyle($cellRange)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => 'DDDDDD'],
                        ],
                    ],
                ]);

                // Set row height for header
                $event->sheet->getRowDimension(1)->setRowHeight(25);
            },
        ];
    }
}
