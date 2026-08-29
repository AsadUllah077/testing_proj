<?php

namespace App\Jobs;

use App\Models\Company;
use App\Services\StatutoryComplianceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CheckComplianceJob implements ShouldQueue
{
    use Queueable;

    protected $companyId;

    /**
     * Create a new job instance.
     */
    public function __construct($companyId = null)
    {
        $this->companyId = $companyId;
    }

    /**
     * Execute the job.
     */
    public function handle(StatutoryComplianceService $complianceService): void
    {
        $companies = $this->companyId
            ? Company::where('id', $this->companyId)->get()
            : Company::all();

        Log::info("Starting compliance check job for {$companies->count()} companies");

        foreach ($companies as $company) {
            try {
                Log::info("Checking compliance for company: {$company->name} (ID: {$company->id})");

                // Generate compliance events
                $events = array_merge(
                    $complianceService->generatePayrollComplianceEvents($company->id),
                    $complianceService->generateTaxComplianceEvents($company->id)
                );

                // Check for regulatory changes and create alerts
                $alerts = $complianceService->checkRegulatoryChanges($company->id);

                Log::info("Company {$company->id}: Generated " . count($events) . " events, created " . count($alerts) . " alerts");

            } catch (\Exception $e) {
                Log::error("Error checking compliance for company {$company->id}: " . $e->getMessage());
            }
        }

        Log::info("Compliance check job completed");
    }
}
