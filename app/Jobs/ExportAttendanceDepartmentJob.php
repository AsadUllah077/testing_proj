<?php

namespace App\Jobs;

use App\Exports\AttendanceDepartmentWiseExport;
use App\Models\Department;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportAttendanceDepartmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $format;

    protected $userId;

    protected $jobId;

    protected $filters;

    /**
     * Create a new job instance.
     */
    public function __construct($userId, $jobId, $format, $filters = [])
    {
        $this->userId = $userId;
        $this->jobId = $jobId;
        $this->format = $format;
        $this->filters = $this->normalizeFilters($filters);
    }

    /**
     * Normalize and validate filters
     */
    protected function normalizeFilters($filters)
    {
        if (! is_array($filters)) {
            return [];
        }

        return [
            'from_date' => $filters['from'] ?? null,
            'to_date' => $filters['to'] ?? null,
            'search' => $filters['search'] ?? null,
            'status' => $filters['status'] ?? null,
        ];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Update progress: Starting
            $this->updateProgress(10, 'Starting export...');

            $fileName = 'attendance_department_wise_' . now()->format('Y_m_d_His') . '.' . $this->format;
            $filePath = 'exports/' . $fileName;

            // Update progress: Processing data
            $this->updateProgress(30, 'Processing customer data...');

            if ($this->format === 'xlsx') {
                // Export to Excel
                Excel::store(
                    new AttendanceDepartmentWiseExport($this->filters),
                    $filePath,
                    'public'
                );
            } elseif ($this->format === 'pdf') {
                // Export to PDF
                $this->exportToPDF($filePath);
            }

            // Update progress: Finalizing
            $this->updateProgress(90, 'Finalizing export...');

            // Store the file URL in cache
            Cache::put(
                "export_result_{$this->jobId}",
                [
                    'url' => Storage::url($filePath),
                    'filename' => $fileName,
                ],
                now()->addHours(24)
            );

            // Update progress: Complete
            $this->updateProgress(100, 'Export completed!');
        } catch (\Throwable $th) {
            // Update progress: Failed
            $this->updateProgress(-1, 'Export failed: ' . $th->getMessage());
            throw $th;
        }
    }

    protected function exportToPDF($filePath)
    {

        $fromDate = $this->filters['from_date'] ?? null;
        $toDate = $this->filters['to_date'] ?? null;
        $search = $this->filters['search'] ?? null;

        // 1. Get ONLY departments that have attendance within your filter criteria
        $attendanceData = Department::whereHas('employees.attendances', function ($query) use ($fromDate, $toDate, $search) {
            if ($fromDate) {
                $query->whereDate('attendance_date', '>=', $fromDate);
            }
            if ($toDate) {
                $query->whereDate('attendance_date', '<=', $toDate);
            }
            if ($search) {
                $query->whereHas('employee', function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%");
                });
            }
        })
            ->with(['employees.attendances' => function ($query) use ($fromDate, $toDate) {
                // 2. Only load the specific attendance records we need (saves massive RAM)
                if ($fromDate) {
                    $query->whereDate('attendance_date', '>=', $fromDate);
                }
                if ($toDate) {
                    $query->whereDate('attendance_date', '<=', $toDate);
                }
            }])
            ->orderBy('department')
            ->get();

        $this->updateProgress(90, 'Rendering PDF');

        // 3. Map it to the format your view expects: Group by Department, then by Employee within Dept
        $attendance = $attendanceData->map(function ($dept) {
            return [
                'department_name' => $dept->department,
                'employees' => $dept->employees->map(function ($employee) {
                    return [
                        'employee_name' => $employee->full_name,
                        'records' => $employee->attendances->sortBy('attendance_date'),
                    ];
                })->filter(function ($emp) {
                    return $emp['records']->isNotEmpty();
                })->values()
            ];
        });

        $pdf = Pdf::loadView('pdf.department-wise-attendance', compact('attendance'));
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
