<?php

namespace App\Imports;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class TasksImport implements ToCollection, WithHeadingRow
{
    private $companyId;

    private $importResult = [
        'total' => 0,
        'success' => 0,
        'failed' => 0,
        'errors' => [],
        'successful_rows' => [],
    ];

    public function __construct($companyId)
    {
        $this->companyId = $companyId;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 because of header row and 0-based index
            $this->importResult['total']++;

            try {
                // Convert row to array and normalize
                $rowArray = $row->toArray();

                // Debug: Log the row data
                \Log::info('Importing row '.$rowNumber.':', $rowArray);

                // Normalize keys
                $normalizedRow = $this->normalizeRow($rowArray);

                // Debug: Log normalized data
                \Log::info('Normalized row '.$rowNumber.':', $normalizedRow);

                // Skip empty rows
                if (empty($normalizedRow['title'])) {
                    \Log::info('Skipping empty row '.$rowNumber);

                    continue;
                }

                // Prepare data
                $data = [
                    'title' => $this->cleanValue($normalizedRow['title']),
                    'description' => $this->cleanValue($normalizedRow['description']),
                    'deadline' => $this->parseDeadline($normalizedRow['deadline']),
                    'priority' => strtolower(trim($this->cleanValue($normalizedRow['priority']))),
                    'category' => strtolower(trim($this->cleanValue($normalizedRow['category']))),
                    'department_name' => $this->cleanValue($normalizedRow['department_name']),
                    'employee_names' => $this->cleanValue($normalizedRow['assigned_employee_names']), // Changed from emails to names
                    'manager_remarks' => $this->cleanValue($normalizedRow['manager_remarks']),
                ];

                // Debug: Log prepared data
                \Log::info('Prepared data for row '.$rowNumber.':', $data);

                // Validate row data
                $validator = Validator::make($data, [
                    'title' => 'required|string|max:255',
                    'description' => 'nullable|string',
                    'deadline' => 'required|date',
                    'priority' => 'required|in:low,medium,high',
                    'category' => 'required|in:daily,weekly',
                    'department_name' => 'nullable|string|max:255',
                    'employee_names' => 'nullable|string',
                    'manager_remarks' => 'nullable|string|max:1000',
                ], [
                    'title.required' => 'Title is required',
                    'deadline.required' => 'Deadline is required',
                    'deadline.date' => 'Invalid date format',
                    'priority.required' => 'Priority is required',
                    'priority.in' => 'Priority must be low, medium, or high',
                    'category.required' => 'Category is required',
                    'category.in' => 'Category must be daily or weekly',
                ]);

                if ($validator->fails()) {
                    $this->importResult['failed']++;
                    $this->importResult['errors'][] = [
                        'row' => $rowNumber,
                        'errors' => $validator->errors()->all(),
                        'data' => $data,
                    ];
                    \Log::error('Validation failed for row '.$rowNumber.':', $validator->errors()->all());

                    continue;
                }

                // Get department ID
                $departmentId = null;
                if (! empty($data['department_name'])) {
                    $department = Department::where('company_id', $this->companyId)
                        ->where('department', 'like', trim($data['department_name']))
                        ->first();

                    if ($department) {
                        $departmentId = $department->id;
                    } else {
                        $this->importResult['failed']++;
                        $this->importResult['errors'][] = [
                            'row' => $rowNumber,
                            'errors' => ['Department not found: '.$data['department_name']],
                            'data' => $data,
                        ];
                        \Log::error('Department not found for row '.$rowNumber.': '.$data['department_name']);

                        continue;
                    }
                }

                // Get assigned employee IDs by full name
                $assignedEmployeeIds = [];
                if (! empty($data['employee_names'])) {
                    $names = array_map('trim', explode(',', $data['employee_names']));

                    foreach ($names as $name) {
                        // Search for employee by full name (case-insensitive)
                        $employee = Employee::where('company_id', $this->companyId)
                            ->whereRaw('LOWER(full_name) = ?', [strtolower(trim($name))])
                            ->first();

                        if ($employee) {
                            $assignedEmployeeIds[] = $employee->id;
                        } else {
                            \Log::warning('Employee not found for name: '.$name.' in row '.$rowNumber);
                            // Optionally, you could add an error here if employee is required
                            // For now, just log and continue
                        }
                    }
                }

                // Create task
                $task = Task::create([
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'deadline' => $data['deadline'],
                    'department_id' => $departmentId,
                    'priority' => $data['priority'],
                    'category' => $data['category'],
                    'manager_remarks' => $data['manager_remarks'],
                    'status' => Task::STATUS_DRAFT,
                    'added_by' => Auth::id(),
                    'company_id' => $this->companyId,
                ]);

                // Assign employees
                if (! empty($assignedEmployeeIds)) {
                    $task->assignedEmployees()->sync($assignedEmployeeIds);
                }

                // Check if overdue
                if (Carbon::parse($data['deadline']) < now()) {
                    $task->update(['status' => Task::STATUS_OVERDUE]);
                }

                $this->importResult['success']++;
                $this->importResult['successful_rows'][] = [
                    'row' => $rowNumber,
                    'task_id' => $task->id,
                    'title' => $task->title,
                ];

                \Log::info('Successfully imported row '.$rowNumber.' as task ID: '.$task->id);

            } catch (\Exception $e) {
                $this->importResult['failed']++;
                $this->importResult['errors'][] = [
                    'row' => $rowNumber,
                    'errors' => [$e->getMessage()],
                    'data' => isset($data) ? $data : [],
                ];
                \Log::error('Exception importing row '.$rowNumber.': '.$e->getMessage());

                continue;
            }
        }
    }

    /**
     * Clean and normalize cell value
     */
    private function cleanValue($value)
    {
        if (is_null($value)) {
            return null;
        }

        // Handle Excel date serial numbers
        if (is_numeric($value) && $value > 25569) { // Excel date serial number (dates after 1970-01-01)
            try {
                // Try to convert Excel serial number to date
                if (class_exists('PhpOffice\PhpSpreadsheet\Shared\Date')) {
                    $value = Date::excelToDateTimeObject($value)->format('Y-m-d H:i:s');
                }
            } catch (\Exception $e) {
                // If conversion fails, keep original value
            }
        }

        // Convert to string and trim
        $value = trim((string) $value);

        // Remove any non-breaking spaces
        $value = str_replace(["\xc2\xa0", "\u{00A0}"], ' ', $value);

        // If empty after cleaning, return null
        return $value === '' ? null : $value;
    }

    /**
     * Normalize row keys to handle different header formats
     */
    private function normalizeRow($rowArray)
    {
        $normalized = [];

        // Clean all values first
        foreach ($rowArray as $key => $value) {
            $rowArray[$key] = $this->cleanValue($value);
        }

        // Define possible header patterns (simplified)
        $patterns = [
            'title' => ['title'],
            'description' => ['description'],
            'deadline' => ['deadline', 'deadline_yyyy_mm_dd_hh_mm_ss'],
            'priority' => ['priority', 'priority_low_medium_high'],
            'category' => ['category', 'category_daily_weekly'],
            'department_name' => ['department_name', 'department name'],
            'assigned_employee_names' => ['assigned_employee_names', 'assigned employee full names', 'assigned employee names', 'employee names'], // Updated patterns
            'manager_remarks' => ['manager_remarks', 'manager remarks'],
        ];

        foreach ($patterns as $key => $possibleHeaders) {
            $normalized[$key] = null;

            foreach ($possibleHeaders as $header) {
                // Try exact match
                if (isset($rowArray[$header])) {
                    $normalized[$key] = $rowArray[$header];
                    break;
                }

                // Try case-insensitive match
                foreach ($rowArray as $actualHeader => $value) {
                    $cleanActualHeader = strtolower(preg_replace('/[^a-z0-9]/', '', $header));
                    $cleanHeader = strtolower(preg_replace('/[^a-z0-9]/', '', $actualHeader));

                    if ($cleanActualHeader === $cleanHeader || str_contains($cleanHeader, $cleanActualHeader)) {
                        $normalized[$key] = $value;
                        break 2;
                    }
                }
            }
        }

        return $normalized;
    }

    /**
     * Parse deadline from various formats including Excel dates
     */
    private function parseDeadline($deadlineString)
    {
        if (empty($deadlineString)) {
            return null;
        }

        try {
            // If it's already a Carbon instance
            if ($deadlineString instanceof \Carbon\Carbon) {
                return $deadlineString;
            }

            // If it's an Excel serial number
            if (is_numeric($deadlineString) && $deadlineString > 25569) {
                try {
                    if (class_exists('PhpOffice\PhpSpreadsheet\Shared\Date')) {
                        $dateTime = Date::excelToDateTimeObject($deadlineString);

                        return Carbon::instance($dateTime);
                    }
                } catch (\Exception $e) {
                    // Continue to other formats
                }
            }

            // Try common date formats
            $formats = [
                'Y-m-d H:i:s',
                'Y-m-d H:i',
                'Y/m/d H:i:s',
                'Y/m/d H:i',
                'd-m-Y H:i:s',
                'd/m/Y H:i:s',
                'm-d-Y H:i:s',
                'm/d/Y H:i:s',
                'Y-m-d',
                'Y/m/d',
                'd-m-Y',
                'd/m/Y',
                'm-d-Y',
                'm/d/Y',
            ];

            foreach ($formats as $format) {
                try {
                    $date = Carbon::createFromFormat($format, $deadlineString);
                    if ($date !== false) {
                        return $date;
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            // Try strtotime as last resort
            $timestamp = strtotime($deadlineString);
            if ($timestamp !== false) {
                return Carbon::createFromTimestamp($timestamp);
            }

            return null;
        } catch (\Exception $e) {
            \Log::error('Error parsing deadline: '.$deadlineString.' - '.$e->getMessage());

            return null;
        }
    }

    public function getImportResult()
    {
        return $this->importResult;
    }
}
