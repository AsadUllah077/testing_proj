<?php

namespace App\Exports;

use App\Models\Employee;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeeDepartmentSheet implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $department;

    protected $fromDate;

    protected $toDate;

    protected $status;

    protected $search;

    public function __construct($department, $filters = [])
    {
        $this->department = $department;
        $this->fromDate = $filters['from_date'] ?? null;
        $this->toDate = $filters['to_date'] ?? null;
        $this->status = $filters['status'] ?? null;
        $this->search = $filters['search'] ?? null;
    }

    public function query()
    {
        return Employee::query()
            ->with(['department', 'employmentType'])
            ->whereHas('department', function ($q) {
                $q->where('id', $this->department->id);
            })
            ->when($this->fromDate, function ($q) {
                $q->whereDate('created_at', '>=', $this->fromDate);
            })
            ->when($this->toDate, function ($q) {
                $q->whereDate('created_at', '<=', $this->toDate);
            })
            ->when($this->search, function ($q) {
                $q->where('full_name', 'like', "%{$this->search}%");
            })
            ->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'Employee ID',
            'Employee Name',
            'Employment Type',
            'Phone',
            'Email',
            'City',
            'State',
            'Country',
            'Status',
            'Joining Date',
            'Resign Date',
            'Tenure',
        ];
    }

    public function map($employee): array
    {
        $tenure = 'N/A';
        if ($employee->date_of_joining) {
            $join    = Carbon::parse($employee->date_of_joining);
            $endDate = $employee->resign_date ? Carbon::parse($employee->resign_date) : Carbon::now();
            $diff   = $join->diff($endDate);
            $years  = $diff->y;
            $months = $diff->m;
            $days   = $diff->d;
            $parts  = [];
            if ($years  > 0) $parts[] = "{$years}y";
            if ($months > 0) $parts[] = "{$months}m";
            if ($days   > 0) $parts[] = "{$days}d";
            $tenure = $parts ? implode(' ', $parts) : '< 1d';
        }

        return [
            $employee->employee_id ?? 'N/A',
            $employee->full_name ?? 'N/A',
            $employee->employmentType->employee_type ?? 'N/A',
            $employee->phone_number ?? 'N/A',
            $employee->email ?? 'N/A',
            $employee->city ?? 'N/A',
            $employee->state ?? 'N/A',
            $employee->country ?? 'N/A',
            ucfirst($employee->status),
            $employee->date_of_joining ? Carbon::parse($employee->date_of_joining)->format('d M Y') : 'N/A',
            $employee->resign_date ? Carbon::parse($employee->resign_date)->format('d M Y') : '--',
            $tenure,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true],
            ],
        ];
    }

    public function title(): string
    {
        return substr($this->department->department, 0, 31); // Excel sheet name limit
    }
}
