<?php

namespace App\Jobs;

use App\Exports\AttendanceMonthlyExport;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportAttendanceMonthlyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;
    protected $jobId;
    protected $format;
    protected $filters;

    /**
     * Create a new job instance.
     */
    public function __construct($userId, $jobId, $format, $filters)
    {
        $this->userId = $userId;
        $this->jobId = $jobId;
        $this->format = $format;
        $this->filters = $filters;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->updateProgress(10, 'Initializing summary calculation...');

            $duration = (int) ($this->filters['duration'] ?? 1);
            $user = User::find($this->userId);

            Log::info('ExportAttendanceMonthlyJob handle', [
                'filters' => $this->filters,
                'userId' => $this->userId,
                'company_id' => $user->company_id ?? 'N/A',
            ]);

            $fileName = 'attendance_monthly_summary_' . now()->format('YmdHis') . '.xlsx';
            $filePath = 'exports/' . $fileName;

            $this->updateProgress(30, 'Fetching attendance and leave data...');

            Log::info('Before Excel::store', [
                'disk' => 'public',
                'filePath' => $filePath,
                'fullPath' => Storage::disk('public')->path($filePath),
            ]);

            // The actual logic of summary is in the Export class
            Excel::store(new AttendanceMonthlyExport($duration, $user->company_id, $this->filters), $filePath, 'public');


            Log::info('After Excel::store', [
                'exists' => Storage::disk('public')->exists($filePath),
                'size' => Storage::disk('public')->exists($filePath)
                    ? Storage::disk('public')->size($filePath)
                    : null,
                'url' => Storage::disk('public')->url($filePath),
            ]);

            $this->updateProgress(90, 'Finalizing export...');

            Log::info('Caching export result', [
                'jobId' => $this->jobId,
                'url' => Storage::disk('public')->url($filePath),
                'filename' => $fileName,
            ]);

            // Store the file URL in cache (project standard)
            Cache::put(
                "export_result_{$this->jobId}",
                [
                    'url' => Storage::url($filePath),
                    'filename' => $fileName,
                ],
                now()->addHours(24)
            );

            $this->updateProgress(100, 'Export completed');
        } catch (\Throwable $th) {
            Log::error('Monthly Attendance Export Failed', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ]);

            $this->updateProgress(-1, 'Export failed: ' . $th->getMessage());
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
