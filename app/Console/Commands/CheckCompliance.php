<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\StatutoryComplianceService;
use Illuminate\Console\Command;

class CheckCompliance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'compliance:check {--company_id= : Specific company ID to check}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check statutory compliance and generate alerts for all companies';

    protected $complianceService;

    public function __construct(StatutoryComplianceService $complianceService)
    {
        parent::__construct();
        $this->complianceService = $complianceService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $companyId = $this->option('company_id');

        if ($companyId) {
            $companies = Company::where('id', $companyId)->get();
        } else {
            $companies = Company::all();
        }

        $this->info("Checking compliance for {$companies->count()} companies...");

        $totalEvents = 0;
        $totalAlerts = 0;

        foreach ($companies as $company) {
            $this->line("Processing company: {$company->name} (ID: {$company->id})");

            try {
                // Generate compliance events
                $events = $this->complianceService->generatePayrollComplianceEvents($company->id);
                $events = array_merge($events, $this->complianceService->generateTaxComplianceEvents($company->id));

                // Check for regulatory changes and create alerts
                $alerts = $this->complianceService->checkRegulatoryChanges($company->id);

                $totalEvents += count($events);
                $totalAlerts += count($alerts);

                $this->info("  - Generated " . count($events) . " compliance events");
                $this->info("  - Created " . count($alerts) . " alerts");

            } catch (\Exception $e) {
                $this->error("Error processing company {$company->id}: " . $e->getMessage());
            }
        }

        $this->info("Compliance check completed!");
        $this->info("Total events generated: {$totalEvents}");
        $this->info("Total alerts created: {$totalAlerts}");

        return Command::SUCCESS;
    }
}
