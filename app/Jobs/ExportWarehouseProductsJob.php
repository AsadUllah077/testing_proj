<?php

namespace App\Jobs;

use App\Exports\WarehouseProductExport;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportWarehouseProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filters;
    protected $format;
    protected $userId;
    protected $jobId;
    protected $companyId;

    /**
     * Create a new job instance.
     */
    public function __construct($filters, $format, $userId, $jobId, $companyId)
    {
        $this->filters = $filters;
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
            $this->updateProgress(10, 'Starting export...');

            $fileName = 'warehouse_products_' . now()->format('Y_m_d_His') . '.' . ($this->format === 'xlsx' ? 'xlsx' : 'pdf');
            $filePath = 'exports/' . $fileName;

            $this->updateProgress(30, 'Processing data...');

            if ($this->format === 'xlsx') {
                Excel::store(new WarehouseProductExport($this->filters, $this->companyId), $filePath, 'public');
            } else {
                $this->exportToPdf($filePath);
            }

            $this->updateProgress(90, 'Finalizing export...');

            Cache::put("export_result_{$this->jobId}", [
                'url' => Storage::url($filePath),
                'filename' => $fileName,
            ], now()->addHours(24));

            $this->updateProgress(100, 'Export completed!');
        } catch (\Throwable $th) {
            Log::error('ExportWarehouseProductsJob Error: ' . $th->getMessage());
            $this->updateProgress(-1, 'Export failed: ' . $th->getMessage());
            throw $th;
        }
    }

    protected function exportToPdf($filePath)
    {
        $exporter = new WarehouseProductExport($this->filters, $this->companyId);
        $products = $exporter->collection();
        $filters = $this->filters;
        $columns = $exporter->columns();
        $headings = $exporter->headings();

        $this->updateProgress(60, 'Generating PDF...');

        $pdf = Pdf::loadView('pdf.products-export', compact('products', 'filters', 'exporter', 'columns', 'headings'))
            ->setPaper('a4', 'landscape');

        Storage::disk('public')->put($filePath, $pdf->output());
    }

    protected function updateProgress($percentage, $message)
    {
        Cache::put("export_progress_{$this->jobId}", [
            'percentage' => $percentage,
            'message' => $message,
        ], now()->addHours(1));
    }

    public function failed(\Throwable $exception)
    {
        $this->updateProgress(-1, 'Export failed: ' . $exception->getMessage());
    }
}
