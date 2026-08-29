<?php

namespace App\Console\Commands;

use App\Services\Account\OpeningBalanceRepairService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class AuditOpeningBalances extends Command
{
    protected $signature = 'accounting:audit-opening-balances
        {--company= : Company ID}
        {--fiscal-year= : Optional configured fiscal period ID}
        {--cutover-date= : Opening posting date in YYYY-MM-DD}
        {--batch= : Optional opening batch ID}
        {--dry-run : Explicitly select the default non-destructive mode}
        {--apply : Not accepted by this audit command}
        {--output= : Output file path}
        {--format=text : json, csv, or text}
        {--approved-by= : Reserved for repair mode}
        {--reason= : Audit reason}
        {--force : Suppress confirmation only; never bypass validation}';

    protected $description = 'Audit opening balances and save a non-destructive repair plan.';

    public function handle(OpeningBalanceRepairService $service): int
    {
        if (! Schema::hasTable('accounting_repair_runs') || ! Schema::hasTable('accounting_repair_items')) {
            $this->error('Opening-balance repair schema is not installed. No audit or accounting records were changed.');
            $this->line('After verifying a database backup, apply only this migration:');
            $this->line('php artisan migrate --path=database/migrations/2026_08_21_000001_harden_opening_balance_accounting.php --force');

            return self::FAILURE;
        }

        if ($this->option('apply')) {
            $this->error('This command is always non-destructive. Use accounting:repair-opening-balances with an approved plan.');
            return self::INVALID;
        }
        $companyId = filter_var($this->option('company'), FILTER_VALIDATE_INT);
        $date = (string) $this->option('cutover-date');
        if (! $companyId || $date === '') {
            $this->error('--company and --cutover-date are required.');
            return self::INVALID;
        }
        $format = strtolower((string) $this->option('format'));
        if (! in_array($format, ['text', 'json', 'csv'], true)) {
            $this->error('--format must be text, json, or csv.');
            return self::INVALID;
        }

        $run = $service->audit((int) $companyId, $date, array_filter([
            'fiscal_year_id' => $this->option('fiscal-year'),
            'batch_id' => $this->option('batch'),
            'reason' => $this->option('reason'),
        ], fn ($value) => $value !== null && $value !== ''), auth('web')->id());
        $payload = $this->renderPayload($run, $format);
        if ($output = $this->option('output')) {
            $output = (string) $output;
            File::ensureDirectoryExists(dirname($output));
            File::put($output, $payload);
            $this->info('Audit report written to ' . $output);
        } else {
            $this->line($payload);
        }
        $this->info('Dry-run plan ID: ' . $run->id . '. Accounting rows changed: 0.');

        return $run->items->contains('severity', 'blocked') ? self::FAILURE : self::SUCCESS;
    }

    private function renderPayload($run, string $format): string
    {
        $rows = $run->items->map(fn ($item) => [
            'id' => $item->id, 'issue_type' => $item->issue_type, 'severity' => $item->severity,
            'entity_type' => $item->entity_type, 'entity_id' => $item->entity_id,
            'proposed_action' => $item->proposed_action,
        ])->all();
        if ($format === 'json') {
            return json_encode(['run' => $run->only(['id', 'company_id', 'status', 'parameters', 'summary']), 'items' => $rows], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        if ($format === 'csv') {
            $stream = fopen('php://temp', 'w+');
            fputcsv($stream, array_keys($rows[0] ?? ['id' => null, 'issue_type' => null, 'severity' => null, 'entity_type' => null, 'entity_id' => null, 'proposed_action' => null]));
            foreach ($rows as $row) fputcsv($stream, $row);
            rewind($stream);
            return stream_get_contents($stream);
        }
        return collect($rows)->map(fn ($row) => implode(' | ', $row))->prepend('ID | ISSUE | SEVERITY | ENTITY | ENTITY ID | ACTION')->implode(PHP_EOL);
    }
}
