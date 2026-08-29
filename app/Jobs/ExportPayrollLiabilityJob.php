<?php

namespace App\Jobs;

use App\Exports\PayrollLiabilityExport;
use App\Models\PayslipItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class ExportPayrollLiabilityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $companyId;
    protected $userId;
    protected $jobId;
    protected $format;
    protected $filters;

    /**
     * Create a new job instance.
     */
    public function __construct($companyId, $userId, $jobId, $format, $filters = [])
    {
        $this->companyId = $companyId;
        $this->userId = $userId;
        $this->jobId = $jobId;
        $this->format = $format;
        $this->filters = $filters;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Update progress: Starting
            $this->updateProgress(10, 'Starting payroll liability export...');

            $fileName = 'payroll_liabilities_export_' . now()->format('Y_m_d_His') . '.' . $this->format;
            $filePath = 'exports/' . $fileName;

            // Get data
            $this->updateProgress(30, 'Collecting payroll liability data...');

            if (!$this->companyId) {
                throw new \Exception('Company ID is missing');
            }

            $query = PayslipItem::with(['payItem', 'payslip.payRun', 'payItem.account', 'payslip.employee'])
                ->whereHas('payslip', function ($q) {
                    $q->where('company_id', $this->companyId);
                })
                ->whereHas('payItem', function ($q) {
                    $q->where('type', 'deduction')
                        ->whereRaw('LOWER(name) NOT LIKE ?', ['%advance%'])
                        ->where(function ($accountScope) {
                            $accountScope->whereDoesntHave('account')->orWhereHas('account', fn ($account) => $account->whereNull('system_account_type')->orWhere('system_account_type', '!=', 'EMPLOYEE_ADVANCES'));
                        });
                });

            // Filter by pay run status
            $payRunStatuses = ['draft', 'posted', 'paid'];
            if (isset($this->filters['status']) && $this->filters['status'] && in_array($this->filters['status'], $payRunStatuses)) {
                $query->whereHas('payslip.payRun', function ($q) {
                    $q->where('status', $this->filters['status']);
                });
            } else {
                // Default to posted/paid if no status filter
                $query->whereHas('payslip.payRun', function ($q) {
                    $q->whereIn('status', ['posted', 'paid']);
                });
            }

            // Filter by payment status
            if (isset($this->filters['payment_status']) && $this->filters['payment_status'] === 'paid') {
                $query->whereNotNull('paid_at');
            } elseif (isset($this->filters['payment_status']) && $this->filters['payment_status'] === 'unpaid') {
                $query->whereNull('paid_at');
            }

            if (isset($this->filters['pay_item_id']) && $this->filters['pay_item_id']) {
                $query->where('pay_item_id', $this->filters['pay_item_id']);
            }

            if (isset($this->filters['pay_run_id']) && $this->filters['pay_run_id']) {
                $query->whereHas('payslip', function ($q) {
                    $q->where('pay_run_id', $this->filters['pay_run_id']);
                });
            }

            if (isset($this->filters['date_from']) && $this->filters['date_from']) {
                $query->whereHas('payslip.payRun', function ($q) {
                    $q->where('period_start', '>=', $this->filters['date_from']);
                });
            }

            if (isset($this->filters['date_to']) && $this->filters['date_to']) {
                $query->whereHas('payslip.payRun', function ($q) {
                    $q->where('period_end', '<=', $this->filters['date_to']);
                });
            }

            if (isset($this->filters['employee_id']) && $this->filters['employee_id']) {
                $query->whereHas('payslip', function ($q) {
                    $q->where('employee_id', $this->filters['employee_id']);
                });
            }

            $liabilities = $query->get();

            if ($this->format === 'xlsx') {
                // Export to Excel
                Excel::store(
                    new PayrollLiabilityExport($liabilities),
                    $filePath,
                    'public'
                );
            } elseif ($this->format === 'pdf') {
                // Export to PDF
                $this->exportToPDF($filePath, $liabilities);
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
            $this->updateProgress(100, 'Payroll liability export completed!');
        } catch (\Exception $e) {
            // Update progress: Failed
            $this->updateProgress(-1, 'Export failed: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function exportToPDF($filePath, $liabilities)
    {
        $groupedLiabilities = $liabilities->groupBy('pay_item_id');
        $pdf = Pdf::loadView('pdf.payroll-liability-report-pdf', compact('groupedLiabilities', 'liabilities'));
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
