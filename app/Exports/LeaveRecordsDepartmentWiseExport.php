<?php

namespace App\Exports;

use App\Models\Department;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class LeaveRecordsDepartmentWiseExport implements WithMultipleSheets
{
    protected $companyId;

    protected $search;

    protected $employeeId;

    protected $leaveTypeId;

    protected $status;

    protected $fromDate;

    protected $toDate;

    protected $isManual;

    protected $departmentId;

    public function __construct($filters = [])
    {
        $this->search = $filters['search'] ?? null;
        $this->employeeId = $filters['employee_id'] ?? null;
        $this->companyId = $filters['company_id'] ?? null;
        $this->leaveTypeId = $filters['leave_type_id'] ?? null;
        $this->status = $filters['status'] ?? null;
        $this->fromDate = $filters['from_date'] ?? null;
        $this->toDate = $filters['to_date'] ?? null;
    }

    public function sheets(): array
    {
        $sheets = [];

        $departments = Department::where('company_id', $this->companyId)
            // Only get departments that have leaves matching these criteria
            ->whereHas('employees.leaves', function ($query) {
                if ($this->fromDate) {
                    $query->whereDate('created_at', '>=', $this->fromDate);
                }
                if ($this->toDate) {
                    $query->whereDate('created_at', '<=', $this->toDate);
                }
                if ($this->employeeId) {
                    $query->where('employee_id', $this->employeeId);
                }
                if ($this->leaveTypeId) {
                    $query->where('leave_type_id', $this->leaveTypeId);
                }
                if ($this->status) {
                    $query->where('status', $this->status);
                }
                if ($this->search) {
                    $query->whereHas('employee', function ($q) {
                        $q->where('full_name', 'like', "%{$this->search}%");
                    });
                }
            })
            ->orderBy('department')
            ->get();

        foreach ($departments as $department) {
            $sheets[] = new LeaveRecordsDepartmentSheet($department, [
                'company_id' => $this->companyId,
                'search' => $this->search,
                'employee_id' => $this->employeeId,
                'leave_type_id' => $this->leaveTypeId,
                'status' => $this->status,
                'from_date' => $this->fromDate,
                'to_date' => $this->toDate,
            ]);
        }

        return $sheets;
    }
}
