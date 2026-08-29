<?php

namespace App\Traits;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

trait ExportsToExcel
{
    // Brand colors
    private string $primaryColor = '0078C8';
    private string $headerBg = '0078C8';
    private string $headerFont = 'FFFFFF';
    private string $sectionBg = 'E8F4FD';
    private string $subtotalBg = 'F0F0F0';
    private string $grandTotalBg = '0078C8';
    private string $grandTotalFont = 'FFFFFF';
    private string $borderColor = 'D0D0D0';
    private string $metaLabelColor = '666666';

    /**
     * Create a new spreadsheet with standard report setup
     */
    private function createReportSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10);
        return $spreadsheet;
    }

    /**
     * Write the report title row (merged, colored banner)
     */
    private function writeTitle($sheet, string $title, int &$row, int $colCount): void
    {
        $lastCol = $this->colLetter($colCount);
        $sheet->setCellValue("A{$row}", $title);
        $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => $this->headerFont]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $this->headerBg]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(30);
        $row++;
    }

    /**
     * Write company name row
     */
    private function writeCompanyRow($sheet, int &$row, int $colCount): void
    {
        $companyName = auth('web')->user()->company->name ?? '';
        $lastCol = $this->colLetter($colCount);
        $sheet->setCellValue("A{$row}", $companyName);
        $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
        $sheet->getStyle("A{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '333333']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $row++;
    }

    /**
     * Write meta info rows (Period, Generated, etc.)
     */
    private function writeMeta($sheet, array $meta, int &$row): void
    {
        foreach ($meta as [$label, $value]) {
            $sheet->setCellValue("A{$row}", $label);
            $sheet->setCellValue("B{$row}", $value);
            $sheet->getStyle("A{$row}")->applyFromArray([
                'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => $this->metaLabelColor]],
            ]);
            $sheet->getStyle("B{$row}")->applyFromArray([
                'font' => ['size' => 9],
            ]);
            $row++;
        }
        $row++; // Extra spacer
    }

    /**
     * Write column header row (dark background, white text)
     */
    private function writeColumnHeaders($sheet, array $headers, int &$row): void
    {
        $col = 1;
        foreach ($headers as $header) {
            $sheet->setCellValueByColumnAndRow($col, $row, $header);
            $col++;
        }
        $lastCol = $this->colLetter(count($headers));
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => $this->headerFont]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $this->headerBg]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $this->primaryColor]],
            ],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(22);
        $row++;
    }

    /**
     * Write a section header row (e.g., "ASSETS", "REVENUE")
     */
    private function writeSectionHeader($sheet, string $label, int &$row, int $colCount): void
    {
        $lastCol = $this->colLetter($colCount);
        $sheet->setCellValue("A{$row}", $label);
        $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '333333']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $this->sectionBg]],
            'borders' => [
                'bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $this->primaryColor]],
            ],
        ]);
        $row++;
    }

    /**
     * Write a data row
     */
    private function writeDataRow($sheet, array $values, int &$row, int $colCount, bool $indent = false): void
    {
        $col = 1;
        foreach ($values as $value) {
            $sheet->setCellValueByColumnAndRow($col, $row, $value);
            $col++;
        }
        if ($indent) {
            $sheet->getStyle("A{$row}")->getAlignment()->setIndent(2);
        }
        $lastCol = $this->colLetter($colCount);
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
            'borders' => [
                'bottom' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => $this->borderColor]],
            ],
        ]);
        $row++;
    }

    /**
     * Write a subtotal row (light gray background, bold)
     */
    private function writeSubtotalRow($sheet, array $values, int &$row, int $colCount): void
    {
        $col = 1;
        foreach ($values as $value) {
            $sheet->setCellValueByColumnAndRow($col, $row, $value);
            $col++;
        }
        $lastCol = $this->colLetter($colCount);
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $this->subtotalBg]],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $this->borderColor]],
                'bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $this->borderColor]],
            ],
        ]);
        $row++;
    }

    /**
     * Write a grand total row (brand color background, white bold)
     */
    private function writeGrandTotalRow($sheet, array $values, int &$row, int $colCount): void
    {
        $col = 1;
        foreach ($values as $value) {
            $sheet->setCellValueByColumnAndRow($col, $row, $value);
            $col++;
        }
        $lastCol = $this->colLetter($colCount);
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => $this->grandTotalFont]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $this->grandTotalBg]],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $this->primaryColor]],
            ],
        ]);
        $row++;
    }

    /**
     * Auto-size columns and finalize
     */
    private function autoSizeColumns($sheet, int $colCount): void
    {
        for ($i = 1; $i <= $colCount; $i++) {
            $sheet->getColumnDimension($this->colLetter($i))->setAutoSize(true);
        }
    }

    /**
     * Return the XLSX response
     */
    private function downloadExcel(Spreadsheet $spreadsheet, string $filename)
    {
        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Convert column number (1-based) to letter: 1=A, 2=B, ..., 27=AA
     */
    private function colLetter(int $num): string
    {
        $letter = '';
        while ($num > 0) {
            $num--;
            $letter = chr(65 + ($num % 26)) . $letter;
            $num = intdiv($num, 26);
        }
        return $letter;
    }
}
