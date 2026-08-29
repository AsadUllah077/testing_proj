<?php

namespace App\Exports;

use App\Models\ProductIssue;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductIssueExport implements FromCollection, WithHeadings, WithMapping
{
    protected $search;

    public function __construct($search = null)
    {
        $this->search = $search;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = ProductIssue::with(['product', 'employee', 'issuedBy']);

        if ($this->search) {
            $search = strtolower($this->search);
            $query->whereHas('product', function ($q) use ($search) {
                $q->whereRaw('LOWER(title) LIKE ?', ['%' . $search . '%'])
                  ->orWhereRaw('LOWER(unique_id) LIKE ?', ['%' . $search . '%']);
            })->orWhereHas('employee', function ($q) use ($search) {
                $q->whereRaw('LOWER(full_name) LIKE ?', ['%' . $search . '%']);
            });
        }

        return $query->latest()->get();
    }

    public function headings(): array
    {
        return [
            'Date Issued',
            'Product ID',
            'Product Title',
            'Employee',
            'Quantity',
            'Issued By',
            'Status',
            'Returned On',
            'Notes'
        ];
    }

    public function map($issue): array
    {
        return [
            $issue->issue_date ? $issue->issue_date->format('Y-m-d H:i') : '-',
            $issue->product->unique_id ?? '-',
            $issue->product->title ?? '-',
            $issue->employee->full_name ?? '-',
            $issue->quantity,
            $issue->issuedBy->username ?? 'System',
            $issue->status,
            $issue->return_date ? $issue->return_date->format('Y-m-d H:i') : '-',
            $issue->notes ?? '-',
        ];
    }
}
