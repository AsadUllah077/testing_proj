<?php

namespace App\Jobs;

use App\Exports\VendorsExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Cache;

class ExportVendorsJob implements ShouldQueue
{
    use Queueable;

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $search;
    protected $format;
    protected $userId;
    protected $jobId;
    protected $companyId;
    protected $companyName;

    /**
     * Create a new job instance.
     */
    public function __construct($search, $format, $userId, $jobId, $companyId, $companyName)
    {
        $this->search = $search;
        $this->format = $format;
        $this->userId = $userId;
        $this->jobId = $jobId;
        $this->companyId = $companyId;
        $this->companyName = $companyName;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            // Update progress: Starting
            $this->updateProgress(10, 'Starting export...');

            $fileName = 'vendors_export_' . now()->format('Y_m_d_His') . '.' . $this->format;
            $filePath = 'exports/' . $fileName;

            // Update progress: Processing data
            $this->updateProgress(30, 'Processing customer data...');

            if ($this->format === 'xlsx') {
                // Export to Excel
                Excel::store(
                    new VendorsExport($this->search, $this->companyId),
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
        $vendors = \App\Models\Vendor::query()
            ->withoutGlobalScope('company')
            ->where('company_id', $this->companyId)
            ->when($this->search, function ($query) {
                $searchFilter = strtolower(trim($this->search));
                return $query->where(function ($q) use ($searchFilter) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchFilter}%"])
                        ->orWhereRaw('LOWER(email) LIKE ?', ["%{$searchFilter}%"])
                        ->orWhereRaw('LOWER(phone) LIKE ?', ["%{$searchFilter}%"])
                        ->orWhereRaw('LOWER(address) LIKE ?', ["%{$searchFilter}%"]);
                });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $companyName = $this->companyName;
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pages.account.bill.vendor.pdf.export', compact('vendors', 'companyName'));

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

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception)
    {
        $this->updateProgress(-1, 'Export failed: ' . $exception->getMessage());
    }
}
