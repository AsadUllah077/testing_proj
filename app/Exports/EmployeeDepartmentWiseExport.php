<?php

namespace App\Exports;

use App\Models\Department;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class EmployeeDepartmentWiseExport implements WithMultipleSheets
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
        $this->status = $filters['status'] ?? null;
        $this->search = $filters['search'] ?? null;
        $this->departmentId = $filters['department_id'] ?? null;
    }

    public function sheets(): array
    {
        $sheets = [];
        $departments = null;
        if ($this->departmentId) {
            $departments = Department::where('id', $this->departmentId)->whereHas('employees', function ($query) {
                if ($this->fromDate) {
                    $query->whereDate('created_at', '>=', $this->fromDate);
                }
                if ($this->toDate) {
                    $query->whereDate('created_at', '<=', $this->toDate);
                }
            })
                ->orderBy('department')
                ->get();
        } else {
            $departments = Department::whereHas('employees', function ($query) {
                if ($this->fromDate) {
                    $query->whereDate('created_at', '>=', $this->fromDate);
                }
                if ($this->toDate) {
                    $query->whereDate('created_at', '<=', $this->toDate);
                }
            })
                ->orderBy('department')
                ->get();
        }

        foreach ($departments as $department) {
            $sheets[] = new EmployeeDepartmentSheet($department, [
                'from_date' => $this->fromDate,
                'to_date' => $this->toDate,
                'status' => $this->status,
                'search' => $this->search,
            ]);
        }

        return $sheets;
    }
}
