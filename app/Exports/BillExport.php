<?php

namespace App\Exports;

use App\Models\Bill;
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

class BillExport implements FromQuery, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithStyles
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
        return Bill::withoutGlobalScopes()->with(['vendor', 'creator', 'updater'])->orderBy('created_at', 'desc');
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
            'Vendor Name',
            'Total Amount',
            'Remaining Amount',
            'Created By',
            'Updated By',
        ];
    }

    /**
     * Map each row
     */
    public function map($bill): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $bill->date ?? '—',
            $bill->ref_no ?? '—',
            $bill->invoice_no ?? '—',
            $bill->vendor?->name ?? '—',
            $bill->total ?? '—',
            $bill->credit_note_status == 1 ? 'CRN Applied' : $bill->remaining_amount,
            $bill->creator?->fullName ?? '—',
            $bill->updater?->fullName ?? '—',
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
