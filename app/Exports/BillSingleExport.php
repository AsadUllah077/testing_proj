<?php

namespace App\Exports;

use App\Models\Bill;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BillSingleExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithStyles
{
    protected $bill;
    protected $columns;

    public function __construct(Bill $bill, array $columns = [])
    {
        $this->bill = $bill;
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
            'invoice_no' => 'Invoice No',
            'date' => 'Date',
            'ref_no' => 'Reference No',
            'vendor' => 'Vendor',
            'account' => 'Account',
            'sub_total' => 'Sub Total',
            'tax' => 'Tax',
            'total' => 'Total',
            'discount' => 'Discount',
            'total_after_discount' => 'Total After Discount',
            'paid_amount' => 'Paid Amount',
            'remaining_amount' => 'Remaining Amount',
            'status' => 'Status',
            'note' => 'Note',
            'created_by' => 'Created By',
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
            'invoice_no' => $this->bill->invoice_no ?? '—',
            'date' => $this->bill->date ? \Carbon\Carbon::parse($this->bill->date)->format('d-m-Y') : '—',
            'ref_no' => $this->bill->ref_no ?? '—',
            'vendor' => $this->bill->vendor?->name ?? '—',
            'account' => $this->bill->account?->name ?? '—',
            'sub_total' => number_format($this->bill->sub_total ?? 0, 2),
            'tax' => number_format($this->bill->tax ?? 0, 2),
            'total' => number_format($this->bill->total ?? 0, 2),
            'discount' => number_format($this->bill->discount ?? 0, 2),
            'total_after_discount' => number_format($this->bill->total_after_discount ?? 0, 2),
            'paid_amount' => number_format($this->bill->paid_amount ?? 0, 2),
            'remaining_amount' => number_format($this->bill->remaining_amount ?? 0, 2),
            'status' => $this->getStatusText($this->bill->status),
            'note' => $this->bill->note ?? '—',
            'created_by' => $this->bill->creator?->name ?? '—',
            'created_at' => $this->bill->created_at ? \Carbon\Carbon::parse($this->bill->created_at)->format('d-m-Y H:i:s') : '—',
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
            0 => 'Pending',
            1 => 'Partial',
            2 => 'Paid',
            3 => 'Cancelled',
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
