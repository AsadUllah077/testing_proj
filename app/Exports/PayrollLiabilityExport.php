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

class PayrollLiabilityExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithStyles
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
        return $this->data;
    }

    /**
     * Define headings
     */
    public function headings(): array
    {
        return [
            'S.No',
            'Employee',
            'Pay Item',
            'Original Amount',
            'Paid Amount',
            'Outstanding',
            'Statutory Due',
            'Pay Run',
            'Pay Period',
            'Payment Status',
            'Payment Reference',
        ];
    }

    /**
     * Map each row
     */
    public function map($item): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $item->payslip->employee->full_name ?? '—',
            $item->payItem->name ?? '—',
            number_format($item->amount, 2),
            number_format($item->paid_amount, 2),
            number_format($item->outstanding_amount, 2),
            $item->statutory_due_date?->format('M d, Y') ?? '—',
            $item->payslip->payRun->name ?? '—',
            ($item->payslip->payRun->period_start ? $item->payslip->payRun->period_start->format('M d, Y') : '—') .
                ' - ' .
                ($item->payslip->payRun->period_end ? $item->payslip->payRun->period_end->format('M d, Y') : '—'),
            $item->is_paid
                ? 'Paid'
                : ((float) $item->paid_amount > 0
                    ? 'Partial'
                    : ($item->payslip->payRun->status === 'draft'
                        ? 'Draft'
                        : ($item->payslip->payRun->status === 'cancelled'
                            ? 'Cancelled'
                            : 'Unpaid'))),
            $item->payment_reference,

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
                // Add title at the top
                $event->sheet->insertNewRowBefore(1, 2);

                // Add title
                $event->sheet->setCellValue('A1', 'PAYROLL LIABILITIES REPORT');
                $event->sheet->mergeCells('A1:K1');
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

                // Add generated date
                $event->sheet->setCellValue('A2', 'Generated on: ' . now()->format('M d, Y H:i'));
                $event->sheet->mergeCells('A2:K2');
                $event->sheet->getStyle('A2')->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);
            },
        ];
    }
}
