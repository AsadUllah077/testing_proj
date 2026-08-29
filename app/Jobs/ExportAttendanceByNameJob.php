<?php

namespace App\Jobs;

use App\Exports\AttendanceByNameExport;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportAttendanceByNameJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $format;
    protected $userId;
    protected $jobId;
    protected $filters;

    public function __construct($userId, $jobId, $format, $filters = [])
    {
        $this->userId = $userId;
        $this->jobId = $jobId;
        $this->format = $format;
        $this->filters = $this->normalizeFilters($filters);
    }

    protected function normalizeFilters($filters)
    {
        if (!is_array($filters)) {
            return [];
        }

        return [
            'from_date' => $filters['from'] ?? null,
            'to_date' => $filters['to'] ?? null,
            'search' => $filters['search'] ?? null,
            'status' => $filters['status'] ?? null,
        ];
    }

    public function handle(): void
    {
        try {
            $this->updateProgress(10, 'Starting export...');

            $fileName = 'attendance_by_name_' . now()->format('Y_m_d_His') . '.' . $this->format;
            $filePath = 'exports/' . $fileName;

            $this->updateProgress(30, 'Processing attendance data...');

            if ($this->format === 'xlsx') {
                Excel::store(
                    new \App\Exports\AttendanceByNameMultiSheetExport($this->filters),
                    $filePath,
                    'public'
                );
            } elseif ($this->format === 'pdf') {
                $this->exportToPDF($filePath);
            }

            $this->updateProgress(90, 'Finalizing export...');

            Cache::put(
                "export_result_{$this->jobId}",
                [
                    'url' => Storage::url($filePath),
                    'filename' => $fileName,
                ],
                now()->addHours(24)
            );

            $this->updateProgress(100, 'Export completed!');
        } catch (\Throwable $th) {
            $this->updateProgress(-1, 'Export failed: ' . $th->getMessage());
            throw $th;
        }
    }

    protected function exportToPDF($filePath)
    {
        $fromDate = $this->filters['from_date'] ?? null;
        $toDate = $this->filters['to_date'] ?? null;
        $search = $this->filters['search'] ?? null;

        // Query all attendance records with employee, ordered by employee name
        $query = EmployeeAttendance::with(['employee.department'])
            ->whereHas('employee', function ($q) use ($search) {
                if ($search) {
                    $q->where('full_name', 'like', "%{$search}%");
                }
            });

        if ($fromDate) {
            $query->whereDate('attendance_date', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('attendance_date', '<=', $toDate);
        }

        $records = $query->get();

        $this->updateProgress(60, 'Sorting by department and employee name...');

        // Group by department name, then by employee name
        $attendance = $records
            ->groupBy(function ($record) {
                return $record->employee->department->department ?? 'General';
            })
            ->sortKeys() // Sort departments alphabetically
            ->map(function ($departmentRecords, $deptName) {
                return [
                    'department_name' => $deptName,
                    'employees' => $departmentRecords
                        ->groupBy(function ($record) {
                            return $record->employee->full_name ?? 'Unknown';
                        })
                        ->sortKeys() // Sort employees alphabetically within department
                        ->map(function ($employeeRecords, $name) {
                            return [
                                'employee_name' => $name,
                                'employee' => $employeeRecords->first()->employee,
                                'records' => $employeeRecords->sortBy('attendance_date'),
                            ];
                        })
                        ->values()
                ];
            })
            ->values();

        $this->updateProgress(80, 'Rendering PDF...');

        $pdf = Pdf::loadView('pdf.name-wise-attendance', compact('attendance'));
        Storage::disk('public')->put($filePath, $pdf->output());
    }

    protected function updateProgress($percentage, $message)
    {
        Cache::put(
            "export_progress_{$this->jobId}",
            [
                'percentage' => $percentage,
                'message' => $message,
            ],
            now()->addHours(1)
        );
    }

    public function failed(\Throwable $exception)
    {
        $this->updateProgress(-1, 'Export failed: ' . $exception->getMessage());
    }
}
