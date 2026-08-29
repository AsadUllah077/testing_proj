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

class AttendanceByNameSheet implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
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
        $base = EmployeeAttendance::query()
            ->join('employees', 'employee_attendances.employee_id', '=', 'employees.id')
            ->with(['employee.department', 'employee.employmentType'])
            ->where('employees.department_id', $this->department->id)
            ->when($this->fromDate, function ($q) {
                $q->whereDate('attendance_date', '>=', $this->fromDate);
            })
            ->when($this->toDate, function ($q) {
                $q->whereDate('attendance_date', '<=', $this->toDate);
            })
            ->when($this->search, function ($q) {
                $q->where('employees.full_name', 'like', "%{$this->search}%");
            });

        if ($this->status === 'present') {
            $base->whereNotNull('check_in');
        } elseif ($this->status === 'absent') {
            $base->whereNull('check_in');
        } elseif ($this->status === 'late') {
            $collection = (clone $base)->get();
            $lateIds = $collection
                ->filter(function ($a) {
                    $start = $a->start_time ?? '09:00:00';
                    $end = $a->employee->end_time ?? $start;
                    if (!$a->check_in) {
                        return false;
                    }
                    $ci = \Carbon\Carbon::parse($a->check_in);
                    $ciMin = $ci->hour * 60 + $ci->minute;
                    $s = explode(':', $start);
                    $e = explode(':', $end);
                    $sMin = (isset($s[0]) ? (int) $s[0] : 0) * 60 + (isset($s[1]) ? (int) $s[1] : 0);
                    $eMin = (isset($e[0]) ? (int) $e[0] : 0) * 60 + (isset($e[1]) ? (int) $e[1] : 0);
                    $overnight = $eMin < $sMin;
                    if ($overnight && $ciMin < $sMin) {
                        $ciMin += 1440;
                    }
                    $grace = $sMin + 15;
                    return $ciMin > $grace;
                })
                ->pluck('id')
                ->all();
            $base->whereIn('employee_attendances.id', $lateIds ?: [-1]);
        }

        return $base
            ->select('employee_attendances.*')
            ->orderBy('employees.full_name', 'asc')
            ->orderBy('attendance_date', 'asc');
    }

    public function headings(): array
    {
        return [
            'Date',
            'Employee Name',
            'Check In',
            'Check Out',
            // 'Work Type',
            'Total Hours',
            'Status',
            // 'Leave/Remarks',
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
            $typeName = $leave->leaveType?->name;

            $words = explode(' ', $typeName);
            $shortCode = '';

            foreach ($words as $word) {
                $shortCode .= strtoupper(substr($word, 0, 1));
            }

            $status = 'Leave: ' . $shortCode;
            $leaveInfo = ($leave->leaveType?->name ?? 'Leave')
                . ($leave->adjustment_reason ? ': ' . $leave->adjustment_reason : '')
                . (' (' . $leave->status . ')');
        } elseif (is_null($attendance->check_in)) {
            $status = 'Absent';
        }

        return [
            $attendance->attendance_date ? Carbon::parse($attendance->attendance_date)->format('Y-m-d') : ' ',
            $attendance->employee?->full_name ?? ' ',
            $attendance->check_in ? Carbon::parse($attendance->check_in)->format('h:i A') : ' ',
            $attendance->check_out ? Carbon::parse($attendance->check_out)->format('h:i A') : ' ',
            // ucfirst($attendance->working_type ?? 'on-site'),
            $attendance->formatted_work_hours,
            $status,
            // $leaveInfo,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        for ($row = 2; $row <= $highestRow; $row++) {
            $status = strtolower($sheet->getCell('F' . $row)->getValue());
            $color = null;

            if ($status === 'late') {
                $color = 'FFFEF9C3';
            } elseif ($status === 'absent') {
                $color = 'FFFEE2E2';
            } elseif ($status === 'present') {
                $color = 'FFDCFCE7';
            } elseif (str_contains($status, 'leave')) {
                $color = 'FFDBEAFE';
            }

            if ($color) {
                $sheet->getStyle('F' . $row)->getFill()
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
        return substr($this->department->department, 0, 31);
    }
}
