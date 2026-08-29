<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TaskImportTemplateExport implements FromArray, WithHeadings, WithTitle, WithColumnWidths, WithStyles, WithColumnFormatting
{
    public function array(): array
    {
        // Return empty array - just template with headings
        return [];
    }

    public function headings(): array
    {
        return [
            'Title',
            'Description',
            'Deadline',
            'Priority',
            'Category',
            'Department Name',
            'Assigned Employee Full Names', // Changed from Emails to Full Names
            'Manager Remarks',
        ];
    }

    public function title(): string
    {
        return 'Task Import Template';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30, // Title
            'B' => 50, // Description
            'C' => 25, // Deadline
            'D' => 15, // Priority
            'E' => 15, // Category
            'F' => 25, // Department Name
            'G' => 40, // Assigned Employee Full Names
            'H' => 40, // Manager Remarks
        ];
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_DATE_DATETIME, // Format for deadline column
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Style the header row
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0078C8'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Add instructions
        $sheet->mergeCells('A2:H2');
        $sheet->setCellValue('A2', 'INSTRUCTIONS:');

        $sheet->mergeCells('A3:H3');
        $sheet->setCellValue('A3', '1. Fill in all required columns (Title, Deadline, Priority, Category are required)');

        $sheet->mergeCells('A4:H4');
        $sheet->setCellValue('A4', '2. Deadline: Enter as date/time (e.g., 2024-12-31 17:00:00) or use Excel date picker');

        $sheet->mergeCells('A5:H5');
        $sheet->setCellValue('A5', '3. Priority: Enter "low", "medium", or "high" (case insensitive)');

        $sheet->mergeCells('A6:H6');
        $sheet->setCellValue('A6', '4. Category: Enter "daily" or "weekly" (case insensitive)');

        $sheet->mergeCells('A7:H7');
        $sheet->setCellValue('A7', '5. Department Name: Must match existing department name exactly');

        $sheet->mergeCells('A8:H8');
        $sheet->setCellValue('A8', '6. Employee Full Names: Separate multiple names with commas (e.g., John Doe,Jane Smith)');

        $sheet->mergeCells('A9:H9');
        $sheet->setCellValue('A9', '7. Description and Manager Remarks are optional');

        $sheet->mergeCells('A10:H10');
        $sheet->setCellValue('A10', '8. Do not modify or delete column headers');

        // Style instructions
        $sheet->getStyle('A2:H10')->applyFromArray([
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFF2CC'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'DDDDDD'],
                ],
            ],
        ]);

        $sheet->getStyle('A2')->getFont()->setBold(true);

        // Add example data starting from row 12
        $exampleData = [
            ['Q4 Financial Report', 'Prepare financial report for Q4', '2024-12-31 17:00:00', 'high', 'weekly', 'Finance', 'John Doe,Jane Smith', 'Please ensure accuracy'],
            ['Daily Standup', 'Team daily standup meeting', '2024-12-20 10:00:00', 'medium', 'daily', 'IT', 'Alex Johnson', 'Discuss project progress'],
        ];

        $sheet->fromArray($exampleData, null, 'A12', true);

        // Style example data
        $sheet->getStyle('A12:H' . (11 + count($exampleData)))->applyFromArray([
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F0F8FF'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'DDDDDD'],
                ],
            ],
        ]);

        // Add data validation for priority and category columns
        // Priority validation
        $priorityValidation = $sheet->getCell('D12')->getDataValidation();
        $priorityValidation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
        $priorityValidation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
        $priorityValidation->setAllowBlank(false);
        $priorityValidation->setShowInputMessage(true);
        $priorityValidation->setShowErrorMessage(true);
        $priorityValidation->setShowDropDown(true);
        $priorityValidation->setFormula1('"low,medium,high"');

        // Category validation
        $categoryValidation = $sheet->getCell('E12')->getDataValidation();
        $categoryValidation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
        $categoryValidation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
        $categoryValidation->setAllowBlank(false);
        $categoryValidation->setShowInputMessage(true);
        $categoryValidation->setShowErrorMessage(true);
        $categoryValidation->setShowDropDown(true);
        $categoryValidation->setFormula1('"daily,weekly"');

        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
