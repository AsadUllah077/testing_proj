<?php

namespace App\Console\Commands;

use App\Services\Account\AnomalyDetectionService;
use Illuminate\Console\Command;

class AuditScanAnomalies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:scan-anomalies {--days=90 : Days to scan} {--json : Output as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan transactions for financial anomalies and risk patterns';

    /**
     * Execute the console command.
     */
    public function handle(AnomalyDetectionService $service)
    {
        $days = (int) $this->option('days');
        $fromDate = now()->subDays($days)->format('Y-m-d');
        
        $this->info("Scanning transactions since {$fromDate}...");
        
        $result = $service->scan($fromDate);
        $anomalies = collect($result['anomalies']);

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT));
            return 0;
        }

        $this->table(
            ['Severity', 'Rule', 'Title', 'Amount', 'Date', 'User', 'Audit Action'],
            $anomalies->take(20)->map(fn($a) => [
                strtoupper($a['severity']),
                $a['rule'],
                $a['title'],
                number_format($a['amount'], 2),
                $a['date'],
                $a['user'] ?? 'N/A',
                strtoupper($a['audit']['action'] ?? 'NONE')
            ])
        );

        $this->info("Summary:");
        $this->line("- Total: {$result['summary']['total']}");
        $this->error("- Critical: {$result['summary']['critical']}");
        $this->warn("- Warning: {$result['summary']['warning']}");
        $this->info("- Info: {$result['summary']['info']}");

        if ($anomalies->count() > 20) {
            $this->comment("... and " . ($anomalies->count() - 20) . " more anomalies.");
        }

        return 0;
    }
}
