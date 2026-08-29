<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\EmployeeAttendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class AttendanceImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row)
    {
        // Find employee by name - trimmed for robustness
        $employee = Employee::where('full_name', trim($row['employee_name']))
            ->first();

        if (!$employee) {
            // Skip if employee not found
            return null;
        }

        $attendanceDate = null;
        $dateValue = trim($row['attendance_date']);

        if (empty($dateValue)) {
            return null;
        }

        try {
            // Try to parse date from common formats
            if (strpos($dateValue, '/') !== false) {
                $attendanceDate = Carbon::createFromFormat('d/m/Y', $dateValue)->format('Y-m-d');
            } elseif (strpos($dateValue, '-') !== false) {
                // Check if it's DD-MM-YYYY or YYYY-MM-DD
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateValue)) {
                    $attendanceDate = $dateValue;
                } else {
                    $attendanceDate = Carbon::createFromFormat('d-m-Y', $dateValue)->format('Y-m-d');
                }
            } else {
                // Fallback to default Carbon parsing
                $attendanceDate = Carbon::parse($dateValue)->format('Y-m-d');
            }
        } catch (\Exception $e) {
            // If parsing fails, skip this row
            return null;
        }


        // Check if attendance already exists for this employee on this date
        $existing = EmployeeAttendance::where('employee_id', $employee->id)
            ->whereDate('attendance_date', $attendanceDate)
            ->first();

        if ($existing) {
            // Update existing record
            $existing->update([
                'check_in' => isset($row['check_in']) && !empty($row['check_in']) ? Carbon::parse($row['check_in'])->format('H:i:s') : null,
                'check_out' => isset($row['check_out']) && !empty($row['check_out']) ? Carbon::parse($row['check_out'])->format('H:i:s') : null,
            ]);

            return null; // Don't create new model
        }

        return new EmployeeAttendance([
            'employee_id' => $employee->id,
            'company_id' => Auth::user()->company_id ?? 1, // Fallback if Auth::user() is null in some contexts
            'attendance_date' => $attendanceDate,
            'check_in' => isset($row['check_in']) && !empty($row['check_in']) ? Carbon::parse($row['check_in'])->format('H:i:s') : null,
            'check_out' => isset($row['check_out']) && !empty($row['check_out']) ? Carbon::parse($row['check_out'])->format('H:i:s') : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'employee_name' => 'required',
            'attendance_date' => 'required',
            'check_in' => 'nullable',
            'check_out' => 'nullable',
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'employee_name.required' => 'Employee name is required.',
            'attendance_date.required' => 'Attendance date is required.',
        ];
    }

    public function prepareForValidation($data, $index)
    {
        // Flexible date validation/formatting
        if (isset($data['attendance_date']) && !empty($data['attendance_date'])) {
            $dateValue = trim($data['attendance_date']);
            try {
                $parsedDate = null;
                if (strpos($dateValue, '/') !== false) {
                    $parsedDate = Carbon::createFromFormat('d/m/Y', $dateValue);
                } elseif (strpos($dateValue, '-') !== false) {
                    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateValue)) {
                        $parsedDate = Carbon::createFromFormat('Y-m-d', $dateValue);
                    } else {
                        $parsedDate = Carbon::createFromFormat('d-m-Y', $dateValue);
                    }
                } else {
                    $parsedDate = Carbon::parse($dateValue);
                }

                if ($parsedDate) {
                    $data['attendance_date'] = $parsedDate->format('d/m/Y');
                }
            } catch (\Exception $e) {
                // Leave as is, validation will handle it
            }
        }

        return $data;
    }

}

