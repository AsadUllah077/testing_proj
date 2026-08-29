<?php

namespace App\Jobs;

use App\Exports\ProductIssueExport;
use App\Models\ProductIssue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportProductIssueJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $search;
    protected $format;
    protected $userId;
    protected $jobId;
    protected $companyId;

    /**
     * Create a new job instance.
     */
    public function __construct(
        $search,
        $format,
        $userId,
        $jobId,
        $companyId
    ) {
        $this->search = $search;
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

            $fileName = 'product_issue_export_' . now()->format('Y_m_d_His') . '.' . $this->format;
            $filePath = 'exports/' . $fileName;

            // Update progress: Processing data
            $this->updateProgress(30, 'Processing data...');

            if ($this->format === 'xlsx') {
                // Export to Excel
                Excel::store(
                    new ProductIssueExport($this->search),
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
            \Log::error('ExportProductIssueJob Error: ' . $th->getMessage());
            // Update progress: Failed
            $this->updateProgress(-1, 'Export failed: ' . $th->getMessage());
            throw $th;
        }
    }

    protected function exportToPDF($filePath)
    {
        $query = ProductIssue::with(['product', 'employee', 'issuedBy']);

        if ($this->search) {
            $search = strtolower($this->search);
            $query->whereHas('product', function ($q) use ($search) {
                $q->whereRaw('LOWER(title) LIKE ?', ['%' . $search . '%'])
                  ->orWhereRaw('LOWER(unique_id) LIKE ?', ['%' . $search . '%']);
            })->orWhereHas('employee', function ($q) use ($search) {
                $q->whereRaw('LOWER(full_name) LIKE ?', ['%' . $search . '%']);
            });
        }

        $issues = $query->latest()->get();

        $this->updateProgress(70, 'Rendering PDF');

        $pdf = \PDF::loadView('pdf.product-issue', compact('issues'))
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
