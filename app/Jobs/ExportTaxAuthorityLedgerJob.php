<?php

namespace App\Jobs;

use App\Exports\TaxAuthorityLedgerExport;
use App\Models\TaxPeriod;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportTaxAuthorityLedgerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;
    protected $jobId;
    protected $format;
    protected $companyId;
    protected $filters;

    /**
     * Create a new job instance.
     */
    public function __construct($userId, $jobId, $format, $companyId, $filters = [])
    {
        $this->userId = $userId;
        $this->jobId = $jobId;
        $this->format = $format;
        $this->companyId = $companyId;
        $this->filters = $filters;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Update progress: Starting
            $this->updateProgress(10, 'Starting tax authority ledger export...');

            $fileName = 'tax_authority_ledger_export_' . now()->format('Y_m_d_His') . '.' . $this->format;
            $filePath = 'exports/' . $fileName;

            // Get data
            $this->updateProgress(30, 'Collecting tax data...');

            // Get all tax periods with filters
            $query = TaxPeriod::where('company_id', $this->companyId);

            if (!empty($this->filters['name'])) {
                $query->where('name', 'like', '%' . $this->filters['name'] . '%');
            }

            if (!empty($this->filters['type'])) {
                $query->where('type', $this->filters['type']);
            }

            if (!empty($this->filters['status'])) {
                $query->where('status', $this->filters['status']);
            }

            if (!empty($this->filters['start_date'])) {
                $query->whereDate('start_date', '>=', $this->filters['start_date']);
            }

            if (!empty($this->filters['end_date'])) {
                $query->whereDate('end_date', '<=', $this->filters['end_date']);
            }

            $taxPeriods = $query->orderBy('start_date', 'desc')->get();

            // Calculate cumulative totals
            $totalSalesTax = 0;
            $totalPurchaseTax = 0;
            $totalSettlements = 0;
            $totalPayments = 0;
            $totalRefunds = 0;

            foreach ($taxPeriods as $period) {
                if ($period->status !== 'open') {
                    $totalSalesTax += $period->total_payable;
                    $totalPurchaseTax += $period->total_receivable;
                    $totalSettlements += abs($period->net_amount);

                    if ($period->net_amount > 0) {
                        $totalPayments += $period->net_amount;
                    } else {
                        $totalRefunds += abs($period->net_amount);
                    }
                }
            }

            $remainingBalance = $totalPayments - $totalRefunds;

            $data = [
                'totalSalesTax' => $totalSalesTax,
                'totalPurchaseTax' => $totalPurchaseTax,
                'totalSettlements' => $totalSettlements,
                'totalPayments' => $totalPayments,
                'totalRefunds' => $totalRefunds,
                'remainingBalance' => $remainingBalance,
                'taxPeriods' => $taxPeriods
            ];

            if ($this->format === 'xlsx') {
                // Export to Excel
                Excel::store(
                    new TaxAuthorityLedgerExport($data),
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
            $this->updateProgress(100, 'Tax authority ledger export completed!');
        } catch (\Exception $e) {
            // Update progress: Failed
            $this->updateProgress(-1, 'Export failed: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function exportToPDF($filePath, $data)
    {
        $pdf = Pdf::loadView('pdf.tax-authority-ledger-pdf', compact('data'));
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
