<?php

namespace App\Jobs;

use App\Exports\LogisticsSuppliersExport;
use App\Models\LogisticsSupplier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportLogisticsSuppliersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $search;
    protected $format;
    protected $userId;
    protected $jobId;

    /**
     * Create a new job instance.
     */
    public function __construct($search, $format, $userId, $jobId)
    {
        $this->search = $search;
        $this->format = $format;
        $this->userId = $userId;
        $this->jobId = $jobId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Update progress: Starting
            $this->updateProgress(10, 'Starting export...');

            $fileName = 'suppliers_export_' . now()->format('Y_m_d_His') . '.' . $this->format;
            $filePath = 'exports/' . $fileName;

            // Update progress: Processing data
            $this->updateProgress(30, 'Processing supplier data...');

            if ($this->format === 'xlsx') {
                // Export to Excel
                Excel::store(
                    new LogisticsSuppliersExport($this->search),
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
        } catch (\Exception $e) {
            // Update progress: Failed
            $this->updateProgress(-1, 'Export failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Export to PDF
     */
    protected function exportToPDF($filePath)
    {
        $query = LogisticsSupplier::query();

        if ($this->search) {
            $search = strtolower($this->search);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(legal_company_name) LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('LOWER(primary_contact_name) LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('LOWER(official_email) LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('LOWER(country_of_registration) LIKE ?', ['%' . $search . '%']);
            });
        }

        $suppliers = $query->latest()->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.supplier-pdf', compact('suppliers'));

        Storage::disk('public')->put($filePath, $pdf->output());
    }

    /**
     * Update progress in cache
     */
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
}
