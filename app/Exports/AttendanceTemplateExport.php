<?php

namespace App\Exports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class AttendanceTemplateExport implements FromCollection, WithHeadings, WithTitle, WithStrictNullComparison, ShouldAutoSize
{
    public function collection()
    {
        // Return empty collection with just headers
        return collect([]);
    }

    public function headings(): array
    {
        return [
            'employee_name',
            'attendance_date',
            'check_in',
            'check_out',
            'working_type'
        ];
    }

    public function title(): string
    {
        return 'Attendance Template';
    }
}
