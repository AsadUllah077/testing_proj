<?php

namespace App\Jobs;

use App\Exports\LogisticsBookingsExport;
use App\Models\LogisticsBooking;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportLogisticsBookingsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $search;
    protected $mode;
    protected $format;
    protected $userId;
    protected $jobId;

    public function __construct($search, $mode, $format, $userId, $jobId)
    {
        $this->search = $search;
        $this->mode   = $mode ?: 'Inland';
        $this->format = $format;
        $this->userId = $userId;
        $this->jobId  = $jobId;
    }

    public function handle(): void
    {
        try {
            $this->updateProgress(10, 'Starting export...');

            $fileName = 'bookings_export_' . now()->format('Y_m_d_His') . '.' . $this->format;
            $filePath = 'exports/' . $fileName;

            $this->updateProgress(30, 'Processing booking data...');

            if ($this->format === 'xlsx') {
                Excel::store(
                    new LogisticsBookingsExport($this->mode),
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
                    'url'      => Storage::url($filePath),
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

    protected function exportToPDF(string $filePath): void
    {
        $query = LogisticsBooking::with('carrier')->orderBy('created_at', 'desc');

        if ($this->mode === 'Inland') {
            $query->whereIn('service_type', ['Inland', 'LTL', 'FTL']);
        } else {
            $query->where('service_type', $this->mode);
        }

        $bookings = $query->get();
        $mode     = $this->mode;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.booking-pdf', compact('bookings', 'mode'))
            ->setPaper('a4', 'landscape');

        Storage::disk('public')->put($filePath, $pdf->output());
    }

    protected function updateProgress(int $percentage, string $message): void
    {
        Cache::put(
            "export_progress_{$this->jobId}",
            [
                'percentage' => $percentage,
                'message'    => $message,
            ],
            now()->addHours(1)
        );
    }
}
