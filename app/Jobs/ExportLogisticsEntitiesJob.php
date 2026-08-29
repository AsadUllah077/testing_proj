<?php

namespace App\Jobs;

use App\Exports\LogisticsEntitiesExport;
use App\Models\LogisticsEntity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportLogisticsEntitiesJob implements ShouldQueue
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

            $fileName = 'entities_export_' . now()->format('Y_m_d_His') . '.' . $this->format;
            $filePath = 'exports/' . $fileName;

            $this->updateProgress(30, 'Processing entity data...');

            if ($this->format === 'xlsx') {
                Excel::store(
                    new LogisticsEntitiesExport($this->search),
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
        $query = LogisticsEntity::query();

        if ($this->search) {
            $search = strtolower($this->search);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(business_legal_name) LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('LOWER(entity_type) LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('LOWER(contact_full_name) LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('LOWER(contact_email) LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('LOWER(tax_id_vat_number) LIKE ?', ['%' . $search . '%']);
            });
        }

        $entities = $query->latest()->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pages.products.logistics.entities.pdf.export', compact('entities'));

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
