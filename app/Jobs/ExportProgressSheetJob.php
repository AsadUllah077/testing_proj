<?php

namespace App\Jobs;

use App\Exports\ProgressSheetExport;
use App\Models\ProgressSheet;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportProgressSheetJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $search;
    protected $date;
    protected $name;
    protected $format;
    protected $userId;
    protected $jobId;
    protected $companyId;

    /**
     * Create a new job instance.
     */
    public function __construct(
        $search,
        $date,
        $name,
        $format,
        $userId,
        $jobId,
        $companyId
    ) {
        $this->search = $search;
        $this->date = $date;
        $this->name = $name;
        $this->format = $format;
        $this->userId = $userId;
        $this->jobId = $jobId;
        $this->companyId = $companyId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Update progress: Starting
            $this->updateProgress(10, 'Starting export...');

            $fileName = 'progress_sheet_export_' . now()->format('Y_m_d_His') . '.' . $this->format;
            $filePath = 'exports/' . $fileName;

            // Update progress: Processing data
            $this->updateProgress(30, 'Processing data...');

            if ($this->format === 'xlsx' || $this->format === 'csv') {
                // Export to Excel/CSV
                Excel::store(
                    new ProgressSheetExport($this->search, $this->date, $this->name),
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
            \Log::error('ExportProgressSheetJob Error: ' . $th->getMessage());
            // Update progress: Failed
            $this->updateProgress(-1, 'Export failed: ' . $th->getMessage());
            throw $th;
        }
    }

    protected function exportToPDF($filePath)
    {
        $query = ProgressSheet::latest();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'LIKE', "%{$this->search}%")
                    ->orWhere('working', 'LIKE', "%{$this->search}%")
                    ->orWhere('remarks', 'LIKE', "%{$this->search}%");
            });
        }
        if ($this->date) {
            $query->whereDate('date', $this->date);
        }
        if ($this->name) {
            $query->where('name', $this->name);
        }

        $sheets = $query->get();
        $filters = ['search' => $this->search, 'date' => $this->date];

        $this->updateProgress(70, 'Rendering PDF');

        $pdf = \PDF::loadView('pdf.progress-sheet-export', compact('sheets', 'filters'))
            ->setPaper('a4', 'landscape');
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
