<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TaxPeriodExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithStyles
{
    protected $data;
    protected $rowNumber = 0;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Return collection of data
     */
    public function collection()
    {
        // Return journal entries for this tax period
        return collect($this->data['taxPeriod']->journalEntries ?? []);
    }

    /**
     * Define headings
     */
    public function headings(): array
    {
        return [
            'S.No',
            'Date',
            'Account',
            'Description',
            'Debit',
            'Credit',
            'Reference',
        ];
    }

    /**
     * Map each row
     */
    public function map($entry): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $entry->date ? $entry->date->format('M d, Y') : '—',
            $entry->account?->name ?? '—',
            $entry->description ?? '—',
            $entry->debit > 0 ? number_format($entry->debit, 2) : '—',
            $entry->credit > 0 ? number_format($entry->credit, 2) : '—',
            $entry->unique_no ?? '—',
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
                // Add period information at the top
                $event->sheet->insertNewRowBefore(1, 8);

                $taxPeriod = $this->data['taxPeriod'];
                $balances = $this->data['balances'];

                // Add title
                $event->sheet->setCellValue('A1', 'TAX PERIOD DETAILS EXPORT');
                $event->sheet->mergeCells('A1:G1');
                $event->sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 16,
                        'color' => ['rgb' => '0078C8'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                // Add period info
                $event->sheet->setCellValue('A2', 'Period Name:');
                $event->sheet->setCellValue('B2', $taxPeriod->name ?? '—');

                $event->sheet->setCellValue('A3', 'Period Type:');
                $event->sheet->setCellValue('B3', ucfirst($taxPeriod->type ?? '—'));

                $event->sheet->setCellValue('A4', 'Date Range:');
                $dateRange = ($taxPeriod->start_date ? $taxPeriod->start_date->format('M d, Y') : '—') . ' - ' .
                    ($taxPeriod->end_date ? $taxPeriod->end_date->format('M d, Y') : '—');
                $event->sheet->setCellValue('B4', $dateRange);

                $event->sheet->setCellValue('A5', 'Status:');
                $status = $taxPeriod->status === 'settled' && $taxPeriod->paid_amount > 0 &&
                    $taxPeriod->paid_amount < abs($taxPeriod->net_amount) ? 'Settled (Partial)' : ucfirst($taxPeriod->status);
                $event->sheet->setCellValue('B5', $status);

                // Add financial summary
                $event->sheet->setCellValue('D2', 'Total Sales Tax:');
                $event->sheet->setCellValue('E2', $taxPeriod->status !== 'open' ? number_format($taxPeriod->total_payable, 2) : '—');

                $event->sheet->setCellValue('D3', 'Total Purchase Tax:');
                $event->sheet->setCellValue('E3', $taxPeriod->status !== 'open' ? number_format($taxPeriod->total_receivable, 2) : '—');

                $event->sheet->setCellValue('D4', 'Carried Forward:');
                $event->sheet->setCellValue('E4', $taxPeriod->carried_forward_amount > 0 ? number_format($taxPeriod->carried_forward_amount, 2) : '—');

                $event->sheet->setCellValue('D5', 'Net Amount:');
                $event->sheet->setCellValue('E5', $taxPeriod->status !== 'open' ? number_format(abs($taxPeriod->net_amount), 2) : '—');

                $event->sheet->setCellValue('D6', 'Paid Amount:');
                $event->sheet->setCellValue('E6', $taxPeriod->paid_amount > 0 ? number_format($taxPeriod->paid_amount, 2) : '—');

                $event->sheet->setCellValue('D7', 'Remaining:');
                $remaining = $taxPeriod->status !== 'open' ? max(0, abs($taxPeriod->net_amount) - $taxPeriod->paid_amount) : 0;
                $event->sheet->setCellValue('E7', number_format($remaining, 2));

                $event->sheet->setCellValue('A8', 'Export Date:');
                $event->sheet->setCellValue('B8', now()->format('M d, Y H:i'));

                // Style summary section
                $event->sheet->getStyle('A2:A8')->applyFromArray([
                    'font' => ['bold' => true],
                ]);

                $event->sheet->getStyle('D2:D7')->applyFromArray([
                    'font' => ['bold' => true],
                ]);

                // Add borders to all cells
                $totalRows = $this->rowNumber + 9; // +9 for summary + header
                $cellRange = 'A1:G' . $totalRows;
                $event->sheet->getStyle($cellRange)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => 'DDDDDD'],
                        ],
                    ],
                ]);

                // Set row height for header
                $event->sheet->getRowDimension(9)->setRowHeight(25); // Header row is now at position 9
            },
        ];
    }
}
