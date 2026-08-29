<?php

namespace App\Exports;

use App\Models\ProgressSheet;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProgressSheetExport implements FromCollection, WithHeadings, WithMapping
{
    protected $search;
    protected $date;
    protected $name;

    public function __construct($search = null, $date = null, $name = null)
    {
        $this->search = $search;
        $this->date = $date;
        $this->name = $name;
    }

    public function collection()
    {
        $query = ProgressSheet::latest();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'LIKE', "%{$this->search}%")
                    ->orWhere('working', 'LIKE', "%{$this->search}%")
                    ->orWhere('remarks', 'LIKE', "%{$this->search}%");
            });
        }
        if ($this->date) {
            $query->whereDate('date', $this->date);
        }
        if ($this->name) {
            $query->where('name', $this->name);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Date',
            'Name',
            'Working',
            'Quantity',
            'Remarks'
        ];
    }

    public function map($sheet): array
    {
        return [
            $sheet->date ? $sheet->date->format('d-m-Y') : '',
            $sheet->name,
            $sheet->working,
            $sheet->quantity ?? '',
            $sheet->remarks ?? '',
        ];
    }
}
