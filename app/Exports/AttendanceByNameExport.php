<?php

namespace App\Exports;

use App\Models\EmployeeAttendance;
use App\Models\LeaveRecord;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

// By individual export file
class AttendanceByNameExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $fromDate;
    protected $toDate;
    protected $status;
    protected $search;

    public function __construct($filters = [])
    {
        $this->fromDate = $filters['from_date'] ?? null;
        $this->toDate = $filters['to_date'] ?? null;
        $this->status = $filters['status'] ?? null;
        $this->search = $filters['search'] ?? null;
    }

    public function query()
    {
        $query = EmployeeAttendance::query()
            ->with(['employee.department'])
            ->when($this->fromDate, function ($q) {
                $q->whereDate('attendance_date', '>=', $this->fromDate);
            })
            ->when($this->toDate, function ($q) {
                $q->whereDate('attendance_date', '<=', $this->toDate);
            })
            ->when($this->search, function ($q) {
                $q->whereHas('employee', function ($sq) {
                    $sq->where('full_name', 'like', "%{$this->search}%");
                });
            });

        if ($this->status === 'present') {
            $query->whereNotNull('check_in');
        } elseif ($this->status === 'absent') {
            $query->whereNull('check_in');
        }

        // Order by department name then employee name alphabetically, then by date
        return $query
            ->join('employees', 'employee_attendances.employee_id', '=', 'employees.id')
            ->leftJoin('departments', 'employees.department_id', '=', 'departments.id')
            ->orderBy('departments.department', 'asc')
            ->orderBy('employees.full_name', 'asc')
            ->orderBy('employee_attendances.attendance_date', 'asc')
            ->select('employee_attendances.*');
    }

    public function headings(): array
    {
        return [
            'Date',
            'Employee Name',
            'Department',
            'Check In',
            'Check Out',
            // 'Work Type',
            'Total Hours',
            'Status',
            'Leave/Remarks',
        ];
    }

    public function map($attendance): array
    {
        $status = ucfirst($attendance->status ?? '');
        $leaveInfo = ' ';

        // Check for approved leave on this date
        $date = $attendance->attendance_date->toDateString();
        $leave = LeaveRecord::where('employee_id', $attendance->employee_id)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->with('leaveType')
            ->first();
        // if showing leave of a half day then revomve is_null($attendance->check_in)
        if ($leave && is_null($attendance->check_in)) {
            $status = 'Leave';
            $leaveInfo = ($leave->leaveType?->name ?? 'Leave')
                . ($leave->adjustment_reason ? ': ' . $leave->adjustment_reason : '')
                . (' (' . $leave->status . ')');
        } elseif (is_null($attendance->check_in)) {
            $status = 'Absent';
        }

        return [
            $attendance->attendance_date ? Carbon::parse($attendance->attendance_date)->format('Y-m-d') : ' ',
            $attendance->employee?->full_name ?? ' ',
            $attendance->employee?->department?->department ?? ' ',
            $attendance->check_in ? Carbon::parse($attendance->check_in)->format('h:i A') : ' ',
            $attendance->check_out ? Carbon::parse($attendance->check_out)->format('h:i A') : ' ',
            // ucfirst($attendance->working_type ?? 'on-site'),
            $attendance->formatted_work_hours ?? ' ',
            $status,
            $leaveInfo,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        for ($row = 2; $row <= $highestRow; $row++) {
            $status = strtolower($sheet->getCell('G' . $row)->getValue());
            $color = null;

            switch ($status) {
                case 'late':
                    $color = 'FFFEF9C3'; // Yellow
                    break;
                case 'absent':
                    $color = 'FFFEE2E2'; // Red
                    break;
                case 'present':
                    $color = 'FFDCFCE7'; // Green
                    break;
                case 'leave':
                    $color = 'FFDBEAFE'; // Light Blue
                    break;
            }

            if ($color) {
                $sheet->getStyle('G' . $row)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB($color);
            }
        }

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
        return 'Attendance By Name';
    }
}
