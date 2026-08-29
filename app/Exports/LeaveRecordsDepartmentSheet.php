<?php

namespace App\Exports;

use App\Models\LeaveRecord;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LeaveRecordsDepartmentSheet implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $department;

    protected $companyId;

    protected $search;

    protected $employeeId;

    protected $leaveTypeId;

    protected $status;

    protected $fromDate;

    protected $toDate;

    protected $isManual;

    public function __construct($department, $filters = [])
    {
        $this->department = $department;
        $this->companyId = $filters['company_id'] ?? null;
        $this->search = $filters['search'] ?? null;
        $this->employeeId = $filters['employee_id'] ?? null;
        $this->leaveTypeId = $filters['leave_type_id'] ?? null;
        $this->status = $filters['status'] ?? null;
        $this->fromDate = $filters['from_date'] ?? null;
        $this->toDate = $filters['to_date'] ?? null;
    }

    public function query()
    {
        return LeaveRecord::query()
            ->with(['employee.department', 'leaveType', 'createdBy'])
            ->where('company_id', $this->companyId)
            ->whereHas('employee.department', function ($q) {
                $q->where('id', $this->department->id);
            })
            // Filter by Search Term (Name, ID, or Reason)
            ->when($this->search, function (Builder $query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('employee', function ($sub) use ($search) {
                        $sub->where('full_name', 'LIKE', "%{$search}%")
                            ->orWhere('employee_id', 'LIKE', "%{$search}%");
                    })
                        ->orWhere('adjustment_reason', 'LIKE', "%{$search}%");
                });
            })
            // Filter by Specific Employee
            ->when($this->employeeId, fn ($q) => $q->where('employee_id', $this->employeeId))
            // Filter by Leave Type
            ->when($this->leaveTypeId, fn ($q) => $q->where('leave_type_id', $this->leaveTypeId))
            // Date Range Filters
            ->when($this->fromDate, fn ($q) => $q->whereDate('start_date', '>=', $this->fromDate))
            ->when($this->toDate, fn ($q) => $q->whereDate('end_date', '<=', $this->toDate))
            ->latest();
    }

    public function headings(): array
    {
        return [
            'Employee ID',
            'Employee Name',
            'Leave Type',
            'Start Date',
            'End Date',
            'Total Days',
            'Duration Type',
            'Adjustment Reason',
            // 'Created By',
            // 'Created Date',
        ];
    }

    public function map($leaveRecord): array
    {
        return [
            $leaveRecord->employee->id ?? 'N/A',
            $leaveRecord->employee->full_name ?? 'N/A',
            $leaveRecord->leaveType->name ?? 'N/A',
            $leaveRecord->start_date ? Carbon::parse($leaveRecord->start_date)->format('Y-m-d') : 'N/A',
            $leaveRecord->end_date ? Carbon::parse($leaveRecord->end_date)->format('Y-m-d') : 'N/A',
            $leaveRecord->calculateTotalDays(),
            $this->getDurationType($leaveRecord),
            // ucfirst($leaveRecord->status ?? 'N/A'),
            // $leaveRecord->reason ?? 'N/A',
            $leaveRecord->adjustment_reason ?? 'N/A',
            // $leaveRecord->createdBy->name ?? 'System',
            // $leaveRecord->created_at ? Carbon::parse($leaveRecord->created_at)->format('Y-m-d H:i') : 'N/A',
        ];
    }

    protected function getDurationType($leaveRecord)
    {
        if ($leaveRecord->isHalfDay()) {
            return $leaveRecord->half === LeaveRecord::HALF_FIRST ? 'First Half Day' : 'Second Half Day';
        }

        if ($leaveRecord->isEarlyLeave()) {
            $time = $leaveRecord->early_leave_time
                ? Carbon::parse($leaveRecord->early_leave_time)->format('h:i A')
                : 'N/A';

            return "Early Leave at {$time}";
        }

        if ($leaveRecord->start_date->eq($leaveRecord->end_date)) {
            return 'Full Day';
        }

        return 'Multiple Days';
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
