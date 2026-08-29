<?php

namespace App\Exports;

use App\Models\DebitNote;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DebitNoteSingleExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithStyles
{
    protected $debitNote;
    protected $columns;

    public function __construct(DebitNote $debitNote, array $columns = [])
    {
        $this->debitNote = $debitNote;
        $this->columns = $columns;
    }

    /**
     * Get the data to export
     */
    public function collection()
    {
        $rows = [];

        // Build header row
        $headers = $this->getColumnHeaders();
        $rows[] = $headers;

        // Build data row
        $dataRow = $this->getDataRow();
        $rows[] = $dataRow;

        return collect($rows);
    }

    /**
     * Get column headers based on selection
     */
    protected function getColumnHeaders()
    {
        $allColumns = [
            'unique_no' => 'Debit Note No',
            'date' => 'Date',
            'ref_no' => 'Reference No',
            'vendor' => 'Vendor',
            'bill_unique_no' => 'Linked Bill',
            'sub_total' => 'Sub Total',
            'tax' => 'Tax',
            'total' => 'Total',
            'allocated_amount' => 'Allocated Amount',
            'remaining_amount' => 'Available Credit',
            'status' => 'Status',
            'note' => 'Note',
            'created_at' => 'Created At',
        ];

        $headers = [];
        if (!empty($this->columns)) {
            foreach ($this->columns as $col) {
                if (isset($allColumns[$col])) {
                    $headers[] = $allColumns[$col];
                }
            }
        } else {
            $headers = array_values($allColumns);
        }

        return $headers;
    }

    /**
     * Get data row based on selected columns
     */
    protected function getDataRow()
    {
        $data = [];

        // Map of column keys to data getters
        $columnGetters = [
            'unique_no' => $this->debitNote->unique_no ?? '—',
            'date' => $this->debitNote->date ? \Carbon\Carbon::parse($this->debitNote->date)->format('d-m-Y') : '—',
            'ref_no' => $this->debitNote->ref_no ?? '—',
            'vendor' => $this->debitNote->vendor?->name ?? '—',
            'bill_unique_no' => $this->debitNote->bill_unique_no ?? '—',
            'sub_total' => number_format($this->debitNote->sub_total ?? 0, 2),
            'tax' => number_format($this->debitNote->tax ?? 0, 2),
            'total' => number_format($this->debitNote->total ?? 0, 2),
            'allocated_amount' => number_format($this->debitNote->allocated_amount ?? 0, 2),
            'remaining_amount' => number_format($this->debitNote->remaining_amount ?? 0, 2),
            'status' => $this->getStatusText($this->debitNote->status),
            'note' => $this->debitNote->note ?? '—',
            'created_at' => $this->debitNote->created_at ? \Carbon\Carbon::parse($this->debitNote->created_at)->format('d-m-Y H:i:s') : '—',
        ];

        if (!empty($this->columns)) {
            foreach ($this->columns as $col) {
                $data[] = $columnGetters[$col] ?? '—';
            }
        } else {
            $data = array_values($columnGetters);
        }

        return $data;
    }

    /**
     * Get status text
     */
    protected function getStatusText($status)
    {
        return match ($status) {
            0 => 'Draft',
            1 => 'Confirmed',
            default => 'Unknown',
        };
    }

    /**
     * Define headings
     */
    public function headings(): array
    {
        return [];
    }

    /**
     * Style the sheet
     */
    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('1')->getFont()->setBold(true);
        $sheet->getStyle('1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E2EFDA');

        return [];
    }

    /**
     * Register events
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->getColumnDimension('A')->setWidth(25);
            },
        ];
    }
}
