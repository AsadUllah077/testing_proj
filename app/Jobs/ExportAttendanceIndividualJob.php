<?php

namespace App\Jobs;

use App\Models\Employee;
use App\Models\EmployeeAttendance;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AttendanceByNameExport;
use App\Models\LeaveRecord;
use Barryvdh\DomPDF\Facade\Pdf;

class ExportAttendanceIndividualJob implements ShouldQueue
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
            'status' => $filters['status'] ?? null,
            'employee_id' => $filters['employee_id'] ?? null,
        ];
    }

    public function handle(): void
    {
        try {
            $this->updateProgress(10, 'Starting export...');

            $employeeId = $this->filters['employee_id'];

            if (!$employeeId) {
                throw new \Exception('Missing selected employee ID for individual export.');
            }

            $employee = Employee::with('department')->find($employeeId);

            if (!$employee) {
                throw new \Exception("Employee not found with ID: {$employeeId}");
            }

            $fileName = 'attendance_individual_' . str_replace(' ', '_', $employee->full_name) . '_' . now()->format('Y_m_d_His') . '.' . $this->format;
            $filePath = 'exports/' . $fileName;

            $this->updateProgress(30, 'Fetching attendance records...');

            $query = EmployeeAttendance::with(['employee.department'])
                ->where('employee_id', $employeeId);

            if ($this->filters['from_date']) {
                $query->whereDate('attendance_date', '>=', $this->filters['from_date']);
            }
            if ($this->filters['to_date']) {
                $query->whereDate('attendance_date', '<=', $this->filters['to_date']);
            }

            if ($this->filters['status'] === 'present') {
                $query->whereNotNull('check_in');
            } elseif ($this->filters['status'] === 'absent') {
                $query->whereNull('check_in');
            }

            $records = $query->orderBy('attendance_date', 'asc')->get()->map(function ($record) {
                $leaveInfo = null;
                if (is_null($record->check_in)) {
                    $date = $record->attendance_date->toDateString();
                    $leave = LeaveRecord::where('employee_id', $record->employee_id)
                        ->whereDate('start_date', '<=', $date)
                        ->whereDate('end_date', '>=', $date)
                        ->with('leaveType')
                        ->first();

                    if ($leave) {
                        $leaveInfo = ($leave->leaveType->name ?? 'Leave') . ($leave->adjustment_reason ? ': ' . $leave->adjustment_reason : '') . (' (' . $leave->status . ')');
                    }
                }
                $record->leave_info = $leaveInfo;
                return $record;
            });

            $this->updateProgress(60, 'Generating export...');

            if ($this->format === 'xlsx') {
                // Create a single-sheet export filtered to this employee
                $xlsFilters = $this->filters;
                $xlsFilters['search'] = $employee->full_name;
                Excel::store(
                    new AttendanceByNameExport($xlsFilters),
                    $filePath,
                    'public'
                );
            } elseif ($this->format === 'pdf') {
                $pdf = Pdf::loadView('pdf.individual-attendance', compact('employee', 'records'));
                Storage::disk('public')->put($filePath, $pdf->output());
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
