<?php

namespace App\Console\Commands;

use App\Models\AccountingRepairRun;
use App\Models\User;
use App\Services\Account\OpeningBalanceRepairService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class RepairOpeningBalances extends Command
{
    protected $signature = 'accounting:repair-opening-balances
        {--company= : Company ID}
        {--fiscal-year= : Optional configured fiscal period ID}
        {--cutover-date= : Must match the plan cutover date}
        {--batch= : Optional batch scope}
        {--plan= : Required dry-run plan ID}
        {--dry-run : Do not apply; directs the operator to the audit command}
        {--apply : Required to change accounting data}
        {--output= : Output JSON report path}
        {--format=json : json or text}
        {--approved-by= : Required approving user ID}
        {--executed-by= : Required repair operator user ID}
        {--reason= : Required correction reason}
        {--force : Suppress confirmation only; never bypass validation}';

    protected $description = 'Apply a reviewed, production-safe opening balance repair plan.';

    public function handle(OpeningBalanceRepairService $service): int
    {
        if (! Schema::hasTable('accounting_repair_runs') || ! Schema::hasTable('accounting_repair_items')) {
            $this->error('Opening-balance repair schema is not installed. No accounting records were changed.');
            $this->line('After verifying a database backup, apply only this migration:');
            $this->line('php artisan migrate --path=database/migrations/2026_08_21_000001_harden_opening_balance_accounting.php --force');

            return self::FAILURE;
        }

        if ($this->option('dry-run') || ! $this->option('apply')) {
            $this->error('No changes made. Run accounting:audit-opening-balances for a dry-run; pass --apply here only after review.');
            return self::INVALID;
        }
        foreach (['company', 'cutover-date', 'plan', 'approved-by', 'executed-by', 'reason'] as $required) {
            if (! $this->option($required)) {
                $this->error("--{$required} is required.");
                return self::INVALID;
            }
        }
        $planId = filter_var($this->option('plan'), FILTER_VALIDATE_INT);
        $plan = $planId
            ? AccountingRepairRun::withoutGlobalScopes()->with('items')->find((int) $planId)
            : null;
        if (! $plan) {
            $this->error('Dry-run plan ID ' . ($this->option('plan') ?: '(missing)') . ' does not exist. No records changed.');
            $recentPlans = AccountingRepairRun::withoutGlobalScopes()
                ->where('company_id', (int) $this->option('company'))
                ->where('dry_run', true)
                ->latest('id')
                ->limit(5)
                ->get(['id', 'status', 'created_at']);
            if ($recentPlans->isEmpty()) {
                $this->line('No dry-run plans exist for this company. Run accounting:audit-opening-balances first.');
            } else {
                $this->line('Recent dry-run plans for this company:');
                foreach ($recentPlans as $recentPlan) {
                    $this->line("  {$recentPlan->id} | {$recentPlan->status} | {$recentPlan->created_at}");
                }
            }

            return self::INVALID;
        }
        if ((int) $plan->company_id !== (int) $this->option('company') || ($plan->parameters['cutover_date'] ?? null) !== $this->option('cutover-date')) {
            $this->error('The company and cutover date must exactly match the saved plan.');
            return self::INVALID;
        }
        if (! $plan->dry_run || ! in_array($plan->status, ['ready', 'partially_repaired'], true)) {
            $blockedCount = $plan->items->where('severity', 'blocked')->count();
            $this->error("Plan {$plan->id} has status '{$plan->status}' and is not eligible for automatic plan repair. No records changed.");
            $this->line("Blocked findings: {$blockedCount}. Only ready or partially_repaired dry-run plans can be applied.");
            if ($plan->items->contains(fn ($item) => in_array($item->issue_type, [
                'REVERSAL_BEFORE_ORIGINAL',
                'OPENING_ENTRIES_SPLIT_ACROSS_CUTOVER',
            ], true))) {
                $this->line('This plan contains the legacy date-edit chain. Use accounting:repair-legacy-opening-date-chain after confirming the exact dates and account ID.');
            }

            return self::FAILURE;
        }
        $approver = User::query()->find((int) $this->option('approved-by'));
        if (! $approver || (int) $approver->company_id !== (int) $plan->company_id) {
            $this->error('The approving user does not exist.');
            return self::INVALID;
        }
        $operator = User::query()->find((int) $this->option('executed-by'));
        if (! $operator || (int) $operator->company_id !== (int) $plan->company_id || $operator->id === $approver->id) {
            $this->error('The repair operator must exist and be different from the approver.');
            return self::INVALID;
        }
        if (! $this->option('force') && ! $this->confirm('Apply the reviewed repair plan during the approved maintenance window?')) {
            $this->warn('No changes made.');
            return self::SUCCESS;
        }

        try {
            $run = $service->apply($plan, $approver->id, $operator->id, (string) $this->option('reason'));
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->error($message);
                }
            }
            $this->line('No repair was applied.');

            return self::FAILURE;
        }
        $payload = json_encode(['run_id' => $run->id, 'status' => $run->status, 'summary' => $run->summary, 'items' => $run->items->toArray()], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($output = $this->option('output')) {
            $output = (string) $output;
            File::ensureDirectoryExists(dirname($output));
            File::put($output, $payload);
        } else {
            $this->line($payload);
        }

        return $run->status === 'completed' ? self::SUCCESS : self::FAILURE;
    }
}
