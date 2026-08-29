<?php

namespace App\Exports;

use App\Models\LeaveRecord;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LeaveRecordsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $companyId;

    protected $search;

    protected $employeeId;

    protected $leaveTypeId;

    protected $status;

    protected $fromDate;

    protected $toDate;

    protected $isManual;

    public function __construct($filters = [])
    {
        $this->companyId = $filters['company_id'] ?? null;
        $this->search = $filters['search'] ?? null;
        $this->employeeId = $filters['employee_id'] ?? null;
        $this->leaveTypeId = $filters['leave_type_id'] ?? null;
        $this->status = $filters['status'] ?? null;
        $this->fromDate = $filters['from_date'] ?? null;
        $this->toDate = $filters['to_date'] ?? null;
        $this->isManual = $filters['is_manual'] ?? false;
    }

    public function collection()
    {
        $query = LeaveRecord::with(['employee.department', 'leaveType', 'createdBy'])
            ->where('company_id', $this->companyId)
            ->latest('created_at');

        // Apply search filter
        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->whereHas('employee', function ($subQuery) {
                    $subQuery->where('full_name', 'LIKE', "%{$this->search}%")
                        ->orWhere('employee_id', 'LIKE', "%{$this->search}%");
                })
                    ->orWhere('adjustment_reason', 'LIKE', "%{$this->search}%");
            });
        }

        // Apply employee filter
        if (! empty($this->employeeId)) {
            $query->where('employee_id', $this->employeeId);
        }

        // Apply leave type filter
        if (! empty($this->leaveTypeId)) {
            $query->where('leave_type_id', $this->leaveTypeId);
        }

        // Apply date range filter
        if (! empty($this->fromDate)) {
            $query->whereDate('start_date', '>=', $this->fromDate);
        }

        if (! empty($this->toDate)) {
            $query->whereDate('end_date', '<=', $this->toDate);
        }

        // Apply manual adjustments filter
        if ($this->isManual) {
            $query->whereNotNull('adjustment_reason');
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Employee ID',
            'Employee Name',
            'Department',
            'Leave Type',
            'Start Date',
            'End Date',
            'Total Days',
            'Duration Type',
            'Status',
            'Reason',
            'Adjustment Reason',
            'Created By',
            'Created Date',
        ];
    }

    public function map($leaveRecord): array
    {
        return [
            $leaveRecord->employee->id ?? 'N/A',
            $leaveRecord->employee->full_name ?? 'N/A',
            $leaveRecord->employee->department->department ?? 'N/A',
            $leaveRecord->leaveType->name ?? 'N/A',
            $leaveRecord->start_date ? Carbon::parse($leaveRecord->start_date)->format('Y-m-d') : 'N/A',
            $leaveRecord->end_date ? Carbon::parse($leaveRecord->end_date)->format('Y-m-d') : 'N/A',
            $leaveRecord->calculateTotalDays(),
            $this->getDurationType($leaveRecord),
            ucfirst($leaveRecord->status ?? 'N/A'),
            $leaveRecord->reason ?? 'N/A',
            $leaveRecord->adjustment_reason ?? 'N/A',
            $leaveRecord->createdBy->name ?? 'System',
            $leaveRecord->created_at ? Carbon::parse($leaveRecord->created_at)->format('Y-m-d H:i') : 'N/A',
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
        return 'Leave Records';
    }
}
