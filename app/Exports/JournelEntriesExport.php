<?php

namespace App\Exports;

use App\Models\JournalEntry;
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

class JournelEntriesExport implements FromQuery, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithStyles
{
    protected $columns;
    protected $rowNumber;

    public function __construct(array $columns = [])
    {
        $this->columns = $columns;
    }

    public function query()
    {
        return JournalEntry::withoutGlobalScopes()->where('unique_no', 'LIKE', '%JV-%')->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        $columnLabels = [
            'date' => 'Date',
            'unique_no' => 'Reference No',
            'narration' => 'Narration',
            'item' => 'Item',
            'description' => 'Description',
            'account' => 'Account',
            'debit' => 'Debit',
            'credit' => 'Credit',
            'created_by_user' => 'Created By',
        ];

        // If no columns selected, return default headings
        if (empty($this->columns)) {
            return [
                'S.No',
                'Date',
                'Reference No',
                'Narration',
                'Item',
                'Description',
                'Account',
                'Debit',
                'Credit',
            ];
        }

        // Build headings from selected columns
        $headings = ['S.No'];
        foreach ($this->columns as $col) {
            $headings[] = $columnLabels[$col] ?? ucfirst($col);
        }
        return $headings;
    }

    public function map($entry): array
    {
        $this->rowNumber++;

        $row = [$this->rowNumber];

        foreach ($this->columns as $col) {
            if ($col === 'account') {
                $row[] = $entry->account->name ?? '—';
            } elseif ($col === 'created_by_user') {
                $row[] = $entry->createdBy->name ?? '—';
            } else {
                $row[] = $entry->$col ?? '—';
            }
        }

        return $row;
    }

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

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $columnCount = count($this->columns) + 1;
                $cellRange = 'A1:' . chr(64 + $columnCount) . ($this->rowNumber + 1);
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
