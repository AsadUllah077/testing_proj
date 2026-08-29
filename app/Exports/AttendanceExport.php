<?php

namespace App\Exports;

use App\Models\EmployeeAttendance;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $fromDate;

    protected $toDate;

    protected $departmentId;

    protected $status;

    protected $search;

    public function __construct($filters = [])
    {
        $this->fromDate = $filters['from_date'] ?? null;
        $this->toDate = $filters['to_date'] ?? null;
        $this->departmentId = $filters['department_id'] ?? null;
        $this->status = $filters['status'] ?? null;
        $this->search = $filters['search'] ?? null;
    }

    public function collection()
    {
        $query = EmployeeAttendance::with(['employee.department', 'employee.employmentType'])
            ->orderBy('attendance_date', 'asc');

        // Apply filters
        if ($this->fromDate) {
            $query->whereDate('attendance_date', '>=', $this->fromDate);
        }

        if ($this->toDate) {
            $query->whereDate('attendance_date', '<=', $this->toDate);
        }

        if ($this->departmentId) {
            $query->whereHas('employee.department', function ($q) {
                $q->where('id', $this->departmentId);
            });
        }

        if ($this->search) {
            $query->whereHas('employee', function ($q) {
                $q->where('full_name', 'like', "%{$this->search}%");
            });
        }

        if ($this->status) {
            switch ($this->status) {
                case 'present':
                    $query->whereNotNull('check_in');
                    break;
                case 'absent':
                    $query->whereNull('check_in');
                    break;
                case 'late':
                    $lateIds = (clone $query)->with('employee')
                        ->whereNotNull('check_in')
                        ->get()
                        ->filter(function ($a) {
                            $start = $a->start_time ?? '09:00:00';
                            $ci = \Carbon\Carbon::parse($a->check_in);
                            $ciMin = $ci->hour * 60 + $ci->minute;
                            $s = explode(':', $start);
                            $sMin = (isset($s[0]) ? (int) $s[0] : 0) * 60 + (isset($s[1]) ? (int) $s[1] : 0);
                            $grace = $sMin + 15;
                            return $ciMin > $grace;
                        })
                        ->pluck('id')
                        ->all();
                    $query->whereIn('id', $lateIds ?: [-1]);
                    break;
                case 'remote':
                    $query->where('working_type', 'remote');
                    break;
            }
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Date',
            'Employee ID',
            'Employee Name',
            'Department',
            'Employment Type',
            'Check In',
            'Check Out',
            // 'Work Type',
            'Total Hours',
            'Status',
        ];
    }

    public function map($attendance): array
    {
        return [
            $attendance->attendance_date ? Carbon::parse($attendance->attendance_date)->format('Y-m-d') : ' ',
            $attendance->employee->id ?? ' ',
            $attendance->employee->full_name ?? ' ',
            $attendance->employee->department->department ?? ' ',
            $attendance->employee->employmentType->title ?? ' ',
            $attendance->formatted_check_in,
            $attendance->formatted_check_out,
            // ucfirst($attendance->working_type ?? 'on-site'),
            $attendance->formatted_work_hours,
            ucfirst($attendance->status ?? 'Leave'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Attendance Report';
    }
}
