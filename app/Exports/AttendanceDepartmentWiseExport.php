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
        $this->status = $filters['status'] ?? null;
        $this->search = $filters['search'] ?? null;
    }

    public function sheets(): array
    {
        $sheets = [];

        // ONLY get departments that have attendance records matching your filters
        $departments = Department::whereHas('employees.attendances', function ($query) {
            if ($this->fromDate) {
                $query->whereDate('attendance_date', '>=', $this->fromDate);
            }
            if ($this->toDate) {
                $query->whereDate('attendance_date', '<=', $this->toDate);
            }
        })
            ->orderBy('department')
            ->get();

        foreach ($departments as $department) {
            $sheets[] = new AttendanceDepartmentSheet($department, [
                'from_date' => $this->fromDate,
                'to_date' => $this->toDate,
                'status' => $this->status,
                'search' => $this->search,
            ]);
        }

        return $sheets;
    }
}
