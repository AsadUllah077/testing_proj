<?php

namespace App\Exports;

use App\Models\Vendor;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class VendorsExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    WithStyles,
    ShouldAutoSize,
    WithEvents
{
    protected $search;
    protected $companyId;
    protected $rowNumber = 0;

    public function __construct($search = null, $companyId = null)
    {
        $this->search = $search;
        $this->companyId = $companyId;
    }

    /**
     * Query for vendors
     */
    public function query()
    {
        return Vendor::query()
            ->withoutGlobalScope('company')
            ->where('company_id', $this->companyId)
            ->when($this->search, function ($query) {
                $searchFilter = strtolower(trim($this->search));

                $query->where(function ($q) use ($searchFilter) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchFilter}%"])
                        ->orWhereRaw('LOWER(email) LIKE ?', ["%{$searchFilter}%"])
                        ->orWhereRaw('LOWER(phone) LIKE ?', ["%{$searchFilter}%"])
                        ->orWhereRaw('LOWER(address) LIKE ?', ["%{$searchFilter}%"]);
                });
            })
            ->orderBy('created_at', 'desc');
    }

    /**
     * Define headings
     */
    public function headings(): array
    {
        return [
            'S.No',
            'Name',
            'Email',
            'Phone',
            'Address',
            'Due Balance',
            'Created At',
        ];
    }

    /**
     * Map each row
     */
    public function map($vendor): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $vendor->name,
            $vendor->email,
            $vendor->phone ?? '—',
            $vendor->address ?? '—',
            number_format($vendor->due_balance ?? 0, 2),
            $vendor->created_at->format('Y-m-d H:i:s'),
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
                // Add borders to all cells
                $cellRange = 'A1:G' . ($this->rowNumber + 1);
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
