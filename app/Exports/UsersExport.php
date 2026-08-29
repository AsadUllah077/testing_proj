<?php

namespace App\Exports;

use App\Models\User;
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

class UsersExport implements
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
     * Query for users
     */
    public function query()
    {
        return User::query()
            ->when($this->companyId, function ($query) {
                return $query->where('company_id', $this->companyId);
            })
            ->when($this->search, function ($query) {
                $searchFilter = strtolower(trim($this->search));

                $query->where(function ($q) use ($searchFilter) {
                    $q->whereRaw('LOWER(first_name) LIKE ?', ["%{$searchFilter}%"])
                        ->orWhereRaw('LOWER(last_name) LIKE ?', ["%{$searchFilter}%"])
                        ->orWhereRaw('LOWER(email) LIKE ?', ["%{$searchFilter}%"])
                        ->orWhereRaw('LOWER(username) LIKE ?', ["%{$searchFilter}%"])
                        ->orWhereRaw('LOWER(phone_number) LIKE ?', ["%{$searchFilter}%"]);
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
            'Full Name',
            'Username',
            'Email',
            'Phone',
            'Designation',
            'Role',
            'Status',
            'Created At',
        ];
    }

    /**
     * Map each row
     */
    public function map($user): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $user->full_name,
            $user->username,
            $user->email,
            $user->phone_number ?? '—',
            $user->designation ?? '—',
            $user->role->name ?? '—',
            $user->status == 1 ? 'Active' : 'Inactive',
            $user->created_at->format('Y-m-d H:i:s'),
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
                $cellRange = 'A1:I' . ($this->rowNumber + 1);
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
