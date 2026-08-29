<?php

namespace App\Jobs;

use App\Models\Company;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PayrollAutoPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 600;

    public function handle(PayrollService $payrollService): void
    {
        Log::info('PayrollAutoPostJob: starting.');

        $startOfMonth = now()->subMonth()->startOfMonth();
        $endOfMonth   = now()->subMonth()->endOfMonth();
        $paymentDate  = now()->startOfMonth();

        foreach (Company::all() as $company) {
            Log::info("PayrollAutoPostJob: processing company {$company->id} ({$company->name})");

            try {
                $payRun = $payrollService->generatePayRun(
                    $company->id,
                    $startOfMonth->format('Y-m-d'),
                    $endOfMonth->format('Y-m-d'),
                    $paymentDate->format('Y-m-d'),
                    [],
                    'monthly'
                );

                $payRun->update([
                    'approval_status' => 'approved',
                    'approved_by' => 1,
                    'approved_at' => now(),
                    'approval_notes' => 'Automatically approved by scheduled payroll auto-post job.',
                ]);

                $payrollService->postToAccounting($payRun->id, $company->id);

                Log::info("PayrollAutoPostJob: posted pay run {$payRun->id} for company {$company->id}");
            } catch (\Exception $e) {
                Log::error("PayrollAutoPostJob: failed for company {$company->id}: {$e->getMessage()}");
            }
        }

        Log::info('PayrollAutoPostJob: done.');
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("PayrollAutoPostJob failed: {$exception->getMessage()}");
    }
}
