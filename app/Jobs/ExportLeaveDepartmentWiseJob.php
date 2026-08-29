<?php

namespace App\Jobs;

use App\Exports\LeaveRecordsDepartmentWiseExport;
use App\Models\Department;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportLeaveDepartmentWiseJob implements ShouldQueue
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
            'employee_id' => $filters['employee_id'] ?? null,
            'leave_type_id' => $filters['leave_type_id'] ?? null,
            'company_id' => $this->userId,
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

            $fileName = 'employee_exports_data_' . now()->format('Y_m_d_His') . '.' . $this->format;
            $filePath = 'exports/' . $fileName;

            // Update progress: Processing data
            $this->updateProgress(30, 'Processing customer data...');

            if ($this->format === 'xlsx') {
                // Export to Excel
                Excel::store(
                    new LeaveRecordsDepartmentWiseExport($this->filters),
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
        $employee_id = $this->filters['employee_id'] ?? null;
        $leave_type_id = $this->filters['leave_type_id'] ?? null;
        $leaveData = Department::with(['employees.leaves' => function ($query) use ($fromDate, $toDate, $employee_id, $leave_type_id) {
            // This filters the ACTUAL data being returned
            if ($fromDate) {
                $query->whereDate('created_at', '>=', $fromDate);
            }
            if ($toDate) {
                $query->whereDate('created_at', '<=', $toDate);
            }
            if ($employee_id) {
                $query->where('employee_id', $employee_id);
            }
            if ($leave_type_id) {
                $query->where('leave_type_id', $leave_type_id);
            }
        }])
            ->whereHas('employees.leaves', function ($query) use ($fromDate, $toDate, $search, $employee_id, $leave_type_id) {
                // This ensures the Department only shows up if it matches the criteria
                if ($fromDate) {
                    $query->whereDate('created_at', '>=', $fromDate);
                }
                if ($toDate) {
                    $query->whereDate('created_at', '<=', $toDate);
                }
                if ($employee_id) {
                    $query->where('employee_id', $employee_id);
                }
                if ($leave_type_id) {
                    $query->where('leave_type_id', $leave_type_id);
                }

                if ($search) {
                    $query->whereHas('employee', function ($q) use ($search) {
                        $q->where('full_name', 'like', "%{$search}%");
                    });
                }
            })
            ->orderBy('department')
            ->get();

        $this->updateProgress(90, 'Rendering PDF');

        // 3. Map it to the format your view expects
        $leaves = $leaveData->map(function ($dept) {
            return [
                'department' => $dept,
                'records' => $dept->employees->flatMap->leaves,
            ];
        });
        Log::info($leaves);

        $pdf = Pdf::loadView('pdf.department-wise-leave', compact('leaves'));
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
