<?php

namespace App\Jobs;

use App\Exports\BillExport;
use App\Exports\PurchaseOrderExport;
use App\Models\Bill;
use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportPurchaseOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $format;

    protected $userId;

    protected $jobId;

    /**
     * Create a new job instance.
     */
    public function __construct($userId, $jobId, $format)
    {
        $this->userId = $userId;
        $this->jobId = $jobId;
        $this->format = $format;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Update progress: Starting
            $this->updateProgress(10, 'Starting export...');

            $fileName = 'purchase_order_export_' . now()->format('Y_m_d_His') . '.' . $this->format;
            $filePath = 'exports/' . $fileName;

            // Update progress: Processing data
            $this->updateProgress(30, 'Processing customer data...');

            if ($this->format === 'xlsx') {
                // Export to Excel
                Excel::store(
                    new PurchaseOrderExport,
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

    protected function exportToPDF($filePath)
    {
        $purchaseOrders = PurchaseOrder::withoutGlobalScopes()
            ->with([
                'vendor' => fn($q) => $q->withoutGlobalScopes(),
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        $pdf = \PDF::loadView('pdf.purchase-order-pdf', compact('purchaseOrders'));

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
