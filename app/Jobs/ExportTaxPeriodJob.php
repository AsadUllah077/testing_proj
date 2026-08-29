<?php

namespace App\Jobs;

use App\Exports\TaxPeriodExport;
use App\Models\TaxPeriod;
use App\Models\Transaction;
use App\Services\TaxService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportTaxPeriodJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;
    protected $jobId;
    protected $format;
    protected $taxPeriodId;

    /**
     * Create a new job instance.
     */
    public function __construct($userId, $jobId, $format, $taxPeriodId)
    {
        $this->userId = $userId;
        $this->jobId = $jobId;
        $this->format = $format;
        $this->taxPeriodId = $taxPeriodId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Update progress: Starting
            $this->updateProgress(10, 'Starting tax period export...');

            $fileName = 'tax_period_export_' . now()->format('Y_m_d_His') . '.' . $this->format;
            $filePath = 'exports/' . $fileName;

            // Get data
            $this->updateProgress(30, 'Collecting tax period data...');

            $taxPeriod = TaxPeriod::findOrFail($this->taxPeriodId);
            $entries = Transaction::with('account')->where('company_id', $taxPeriod->company_id)
                ->where('reference_id', $this->taxPeriodId)
                ->whereIn('type', ['Tax Settlement', 'Tax Payment'])
                ->orderBy('date')
                ->orderBy('created_at')
                ->get();
            $taxService = app(TaxService::class);
            $balances = $taxService->calculateBalances($taxPeriod->company_id, $taxPeriod->start_date, $taxPeriod->end_date);

            $data = [
                'taxPeriod' => $taxPeriod,
                'balances' => $balances,
                'entries' => $entries ?? collect(),
            ];

            if ($this->format === 'xlsx') {
                // Export to Excel
                Excel::store(
                    new TaxPeriodExport($data),
                    $filePath,
                    'public'
                );
            } elseif ($this->format === 'pdf') {
                // Export to PDF
                $this->exportToPDF($filePath, $data);
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
            $this->updateProgress(100, 'Tax period export completed!');
        } catch (\Exception $e) {
            // Update progress: Failed
            $this->updateProgress(-1, 'Export failed: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function exportToPDF($filePath, $data)
    {
        $pdf = Pdf::loadView('pdf.tax-period-pdf', compact('data'));
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
