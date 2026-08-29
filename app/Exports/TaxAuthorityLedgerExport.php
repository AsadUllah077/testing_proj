<?php

namespace App\Exports;

use App\Models\TaxPeriod;
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

class TaxAuthorityLedgerExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithStyles
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
        // Return tax periods for the table
        return collect($this->data['taxPeriods']);
    }

    /**
     * Define headings
     */
    public function headings(): array
    {
        return [
            'S.No',
            'Period Name',
            'Type',
            'Start Date',
            'End Date',
            'Status',
            'Total Sales Tax',
            'Total Purchase Tax',
            'Carried Forward',
            'Net Amount',
            'Paid Amount',
            'Remaining Amount',
        ];
    }

    /**
     * Map each row
     */
    public function map($taxPeriod): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $taxPeriod->name ?? '—',
            ucfirst($taxPeriod->type ?? '—'),
            $taxPeriod->start_date ? $taxPeriod->start_date->format('M d, Y') : '—',
            $taxPeriod->end_date ? $taxPeriod->end_date->format('M d, Y') : '—',
            $this->getStatusDisplay($taxPeriod),
            $taxPeriod->status !== 'open' ? number_format($taxPeriod->total_payable, 2) : '—',
            $taxPeriod->status !== 'open' ? number_format($taxPeriod->total_receivable, 2) : '—',
            $taxPeriod->carried_forward_amount > 0 ? number_format($taxPeriod->carried_forward_amount, 2) : '—',
            $taxPeriod->status !== 'open' ? number_format(abs($taxPeriod->net_amount), 2) : '—',
            $taxPeriod->paid_amount > 0 ? number_format($taxPeriod->paid_amount, 2) : '—',
            $taxPeriod->status !== 'open' ? number_format(max(0, abs($taxPeriod->net_amount) - $taxPeriod->paid_amount), 2) : '—',
        ];
    }

    /**
     * Get status display
     */
    protected function getStatusDisplay($taxPeriod)
    {
        if ($taxPeriod->status === 'open') {
            return 'Open';
        } elseif ($taxPeriod->status === 'settled') {
            if ($taxPeriod->paid_amount > 0 && $taxPeriod->paid_amount < abs($taxPeriod->net_amount)) {
                return 'Settled (Partial)';
            } else {
                return 'Settled';
            }
        } elseif ($taxPeriod->status === 'paid') {
            return 'Paid';
        }

        return ucfirst($taxPeriod->status);
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
                // Add summary information at the top
                $event->sheet->insertNewRowBefore(1, 6);

                // Add title
                $event->sheet->setCellValue('A1', 'TAX AUTHORITY LEDGER EXPORT');
                $event->sheet->mergeCells('A1:L1');
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

                // Add summary data
                $event->sheet->setCellValue('A2', 'Total Sales Tax:');
                $event->sheet->setCellValue('B2', number_format($this->data['totalSalesTax'], 2));

                $event->sheet->setCellValue('A3', 'Total Purchase Tax:');
                $event->sheet->setCellValue('B3', number_format($this->data['totalPurchaseTax'], 2));

                $event->sheet->setCellValue('A4', 'Total Settlements:');
                $event->sheet->setCellValue('B4', number_format($this->data['totalSettlements'], 2));

                $event->sheet->setCellValue('A5', 'Total Payments:');
                $event->sheet->setCellValue('B5', number_format($this->data['totalPayments'], 2));

                $event->sheet->setCellValue('A6', 'Total Refunds:');
                $event->sheet->setCellValue('B6', number_format($this->data['totalRefunds'], 2));

                $event->sheet->setCellValue('D2', 'Remaining Balance:');
                $event->sheet->setCellValue('E2', number_format(abs($this->data['remainingBalance']), 2));

                $event->sheet->setCellValue('D3', 'Export Date:');
                $event->sheet->setCellValue('E3', now()->format('M d, Y H:i'));

                // Style summary section
                $event->sheet->getStyle('A2:A6')->applyFromArray([
                    'font' => ['bold' => true],
                ]);

                $event->sheet->getStyle('D2:D3')->applyFromArray([
                    'font' => ['bold' => true],
                ]);

                // Add borders to all cells
                $totalRows = $this->rowNumber + 7; // +7 for summary + header
                $cellRange = 'A1:L' . $totalRows;
                $event->sheet->getStyle($cellRange)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => 'DDDDDD'],
                        ],
                    ],
                ]);

                // Set row height for header
                $event->sheet->getRowDimension(7)->setRowHeight(25); // Header row is now at position 7
            },
        ];
    }
}
