<?php

namespace App\Exports;

use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\EmployeeAttendance;
use App\Models\LeaveRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceMonthlyExport implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    protected $duration;
    protected $companyId;
    protected $leaveTypes;
    protected $fromDate;
    protected $toDate;

    public function __construct($duration, $companyId, $filters = [])
    {
        $this->duration = $duration;
        $this->companyId = $companyId;
        $this->leaveTypes = LeaveType::where('company_id', $this->companyId)->get();

        $fromDate = $filters['date_from'] ?? $filters['from_date'] ?? null;
        $toDate = $filters['date_to'] ?? $filters['to_date'] ?? null;

        if ($fromDate && $toDate) {
            $this->fromDate = Carbon::parse($fromDate)->startOfDay();
            $this->toDate   = Carbon::parse($toDate)->endOfDay();
        }
    }

    public function collection()
    {
        if ($this->fromDate && $this->toDate) {
            $startDate = $this->fromDate;
            $endDate   = $this->toDate;
        } else {
            $startDate = now()->subMonths($this->duration)->startOfMonth();
            $endDate = now()->subMonth()->endOfMonth(); // Assuming export is for previous full months

            if ($this->duration == 0) {
                $startDate = now()->startOfMonth();
                $endDate = now()->endOfMonth();
            }
        }

        $employees = Employee::select('employees.*')
            ->join('departments', 'employees.department_id', '=', 'departments.id')
            ->with(['department', 'attendances' => function ($q) use ($startDate, $endDate) {
                $q->whereBetween('attendance_date', [$startDate, $endDate]);
            }, 'leaves' => function ($q) use ($startDate, $endDate) {
                // $q->where('status', 'approved')
                $q->where(function ($sq) use ($startDate, $endDate) {
                    $sq->whereBetween('start_date', [$startDate, $endDate])
                        ->orWhereBetween('end_date', [$startDate, $endDate])
                        ->orWhere(function ($ssq) use ($startDate, $endDate) {
                            $ssq->where('start_date', '<=', $startDate)
                                ->where('end_date', '>=', $endDate);
                        });
                });
            }])
            ->where('employees.company_id', $this->companyId)
            ->where('employees.status', 'active')
            ->orderBy('departments.department')
            ->orderBy('employees.full_name')
            ->get();

        $data = $employees->map(function ($employee) use ($startDate, $endDate) {
            $presentCount = $employee->attendances->whereNotNull('check_in')->count();

            // Total days in range (rough estimate or precise based on attendance records)
            // For a formal "Absent" count, we usually count workdays minuts present.
            // Here we'll count records with status 'absent' if they are generated, 
            // otherwise we'll assume days not present and not on leave are absent.

            /* old
            $absentCount = $employee->attendances->where('status', 'absent')->count(); */

            /* old_old
            $absentLeaves = $employee->leaves
                ->whereIn('status', ['rejected', 'pending']);

            $absentCount = 0;

            foreach ($absentLeaves as $leave) {

                $leaveStart = Carbon::parse($leave->start_date);
                $leaveEnd = Carbon::parse($leave->end_date);

                $overlapStart = $leaveStart->max($startDate);
                $overlapEnd = $leaveEnd->min($endDate);

                if ($overlapStart <= $overlapEnd) {
                    $absentCount += $overlapStart->diffInDays($overlapEnd) + 1;
                }
            } */

            $absentCount = 0;
            $countedDates = [];

            // 1. Rejected/pending leave days where employee has no check_in
            foreach ($employee->leaves->whereIn('status', ['rejected', 'pending']) as $leave) {
                $overlapStart = Carbon::parse($leave->start_date)->max($startDate);
                $overlapEnd   = Carbon::parse($leave->end_date)->min($endDate);

                if ($overlapStart <= $overlapEnd) {
                    for ($date = $overlapStart->copy(); $date <= $overlapEnd; $date->addDay()) {
                        $dateStr   = $date->format('Y-m-d');
                        $attendance = $employee->attendances->where('attendance_date', $dateStr)->first();

                        if ((!$attendance || is_null($attendance->check_in)) && !in_array($dateStr, $countedDates)) {
                            $absentCount++;
                            $countedDates[] = $dateStr;
                        }
                    }
                }
            }

            // 2. Attendance records with no check_in and no leave record at all
            foreach ($employee->attendances->filter(fn($att) => is_null($att->check_in)) as $att) {

                $date = Carbon::parse($att->attendance_date)->startOfDay();
                $dateStr = $date->format('Y-m-d');

                $hasLeave = $employee->leaves->contains(function ($leave) use ($date) {
                    $start = Carbon::parse($leave->start_date)->startOfDay();
                    $end   = Carbon::parse($leave->end_date)->endOfDay();

                    return $date->between($start, $end);
                });

                if (!$hasLeave && !in_array($dateStr, $countedDates)) {
                    $absentCount++;
                    $countedDates[] = $dateStr;
                }

                // Log::info('leave 2', [
                //     'employee' => $employee->full_name,
                //     'att' => $att,
                //     'hasLeave' => $hasLeave,
                //     'absentCount' => $absentCount,
                //     'countedDates' => $countedDates,
                // ]);
            }

            $leaveTotals = [];
            foreach ($this->leaveTypes as $type) {
                // Precise leave day calculation for the specific type within the range
                $totalLeaveDays = 0;
                $records = $employee->leaves->where('leave_type_id', $type->id);

                foreach ($records as $record) {
                    $recordStart = Carbon::parse($record->start_date);
                    $recordEnd = Carbon::parse($record->end_date);

                    $overlapStart = $recordStart->max($startDate);
                    $overlapEnd = $recordEnd->min($endDate);

                    if ($overlapStart <= $overlapEnd) {
                        $totalLeaveDays += $overlapStart->diffInDays($overlapEnd) + 1;
                    }
                }
                $leaveTotals[] = $totalLeaveDays;
            }

            $rowData = [
                $employee->department->department ?? ' ',
                $employee->full_name,
                $employee->designation ?? ' ',
            ];

            // Merge leave types
            $rowData = array_merge($rowData, $leaveTotals);

            // Add Abset and Present
            $rowData[] = $absentCount;
            $rowData[] = $presentCount;

            return $rowData;
        });

        return $data;
    }

    public function headings(): array
    {
        $headings = [
            'Department',
            'Employee Name',
            'Designation',
        ];

        foreach ($this->leaveTypes as $type) {
            $headings[] = 'Total ' . $type->name;
        }

        $headings[] = 'Total Absent';
        $headings[] = 'Total Present';

        return $headings;
    }

    public function styles(Worksheet $sheet)
    {
        $lastColumn = $sheet->getHighestColumn();
        $lastRow = $sheet->getHighestRow();
        $range = 'A1:' . $lastColumn . $lastRow;

        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ],
            $range => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Monthly Attendance Summary';
    }
}
