<?php

namespace App\Jobs;

use App\Exports\LogisticsCarriersExport;
use App\Models\LogisticsCarrier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportLogisticsCarriersJob implements ShouldQueue
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
            $this->updateProgress(10, 'Starting export...');

            $fileName = 'carriers_export_' . now()->format('Y_m_d_His') . '.' . $this->format;
            $filePath = 'exports/' . $fileName;

            $this->updateProgress(30, 'Processing carrier data...');

            if ($this->format === 'xlsx') {
                Excel::store(
                    new LogisticsCarriersExport($this->search),
                    $filePath,
                    'public'
                );
            } elseif ($this->format === 'pdf') {
                $this->exportToPDF($filePath);
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
        } catch (\Exception $e) {
            $this->updateProgress(-1, 'Export failed: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function exportToPDF($filePath)
    {
        $query = LogisticsCarrier::query();

        if ($this->search) {
            $search = strtolower($this->search);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(legal_carrier_name) LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('LOWER(logistics_mode) LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('LOWER(scac_iata_code) LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('LOWER(dot_number) LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('LOWER(mc_authority) LIKE ?', ['%' . $search . '%']);
            });
        }

        $carriers = $query->latest()->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.carrier-pdf', compact('carriers'));

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
}
