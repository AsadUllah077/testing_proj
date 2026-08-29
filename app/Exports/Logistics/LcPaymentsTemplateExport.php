<?php

namespace App\Exports\Logistics;

use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LcPaymentsTemplateExport implements ShouldAutoSize, WithEvents, WithHeadings, WithStyles
{
    public function headings(): array
    {
        return [
            'Booking Number',
            'Payment Date (YYYY-MM-DD)',
            'Receipt No',
            'Payment Type',
            'Amount',
            'Currency',
            'Currency Value (PKR per 1 USD)',
            'Status',
            'Details',
            'Remarks',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0078C8'],
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->freezePane('A2');

                for ($row = 2; $row <= 500; $row++) {
                    $paymentTypeValidation = $sheet->getCell("D{$row}")->getDataValidation();
                    $paymentTypeValidation->setType(DataValidation::TYPE_LIST);
                    $paymentTypeValidation->setErrorStyle(DataValidation::STYLE_STOP);
                    $paymentTypeValidation->setAllowBlank(false);
                    $paymentTypeValidation->setShowDropDown(true);
                    $paymentTypeValidation->setFormula1('"Freight Payment,Product Payment"');

                    $currencyValidation = $sheet->getCell("F{$row}")->getDataValidation();
                    $currencyValidation->setType(DataValidation::TYPE_LIST);
                    $currencyValidation->setErrorStyle(DataValidation::STYLE_STOP);
                    $currencyValidation->setAllowBlank(false);
                    $currencyValidation->setShowDropDown(true);
                    $currencyValidation->setFormula1('"PKR,USD"');

                    $statusValidation = $sheet->getCell("H{$row}")->getDataValidation();
                    $statusValidation->setType(DataValidation::TYPE_LIST);
                    $statusValidation->setErrorStyle(DataValidation::STYLE_STOP);
                    $statusValidation->setAllowBlank(true);
                    $statusValidation->setShowDropDown(true);
                    $statusValidation->setFormula1('"pending,completed"');
                }
            },
        ];
    }
}
