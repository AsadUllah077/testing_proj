<?php

namespace App\Exports;

use App\Models\Department;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AttendanceDepartmentWiseExport implements WithMultipleSheets
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

    public function sheets(): array
    {
        $sheets = [];

        // If specific department is selected
        if ($this->departmentId) {
            $department = Department::find($this->departmentId);
            if ($department) {
                $sheets[] = new AttendanceDepartmentSheet($department, [
                    'from_date' => $this->fromDate,
                    'to_date' => $this->toDate,
                    'status' => $this->status,
                    'search' => $this->search,
                ]);
            }
        } else {
            // Export all departments
            $departments = Department::orderBy('department')->get();

            foreach ($departments as $department) {
                $sheets[] = new AttendanceDepartmentSheet($department, [
                    'from_date' => $this->fromDate,
                    'to_date' => $this->toDate,
                    'status' => $this->status,
                    'search' => $this->search,
                ]);
            }
        }

        return $sheets;
    }
}
