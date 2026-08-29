<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PayrollAutoPostOld extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payroll:auto-post';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically generate and post payroll on the first of each month';

    protected $payrollService;

    public function __construct(PayrollService $payrollService)
    {
        parent::__construct();
        $this->payrollService = $payrollService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Payroll Auto-Post process...');

        $companies = Company::all();
        $startOfMonth = now()->subMonth()->startOfMonth();
        $endOfMonth = now()->subMonth()->endOfMonth();
        $paymentDate = now()->startOfMonth(); // Post on the 1st

        foreach ($companies as $company) {
            $this->info("Processing company: {$company->name} (ID: {$company->id})");

            try {
                // 1. Generate Pay Run
                $payRun = $this->payrollService->generatePayRun(
                    $company->id,
                    $startOfMonth->format('Y-m-d'),
                    $endOfMonth->format('Y-m-d'),
                    $paymentDate->format('Y-m-d'),
                    [], // All employees
                    'monthly'
                );

                $this->info("Generated Pay Run ID: {$payRun->id}");

                // 2. Post to Accounting
                $this->payrollService->postToAccounting($payRun->id);
                $this->info("Posted Pay Run ID: {$payRun->id} to accounting.");
            } catch (\Exception $e) {
                $this->error("Failed to process company {$company->id}: " . $e->getMessage());
                Log::error("Payroll Auto-Post Error [Company: {$company->id}]: " . $e->getMessage());
            }
        }

        $this->info('Payroll Auto-Post process completed.');
    }
}
