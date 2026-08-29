<?php

namespace App\Exports;

use App\Models\Sale;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SaleExport implements FromQuery, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithStyles
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
        return Sale::withoutGlobalScopes()->with(['customer', 'createdBy', 'updatedBy'])->orderBy('created_at', 'desc');
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
            'Invoice No',
            'Customer Name',
            'Description',
            'Total Amount',
            'Remaining Amount',
            'Created By',
            'Updated By',
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
            $sale->date ?? '—',
            $sale->ref_no ?? '—',
            $sale->invoice_no ?? '—',
            $sale->customer?->name ?? '—',
            $sale->note ?? '—',
            $sale->total ?? '—',
            $sale->credit_note_status == 1 ? 'CRN Applied' : $sale->remaining_amount,
            $sale->createdBy?->fullName ?? '—',
            $sale->updatedBy?->fullName ?? '—',
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
                $cellRange = 'A1:G'.($this->rowNumber + 1);
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
