<?php

namespace App\Console\Commands;

use App\Models\AccountingPeriod;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoLockAccountingPeriods extends Command
{
    /**
     * Artisan command signature.
     *
     * Usage:
     *   php artisan accounting:auto-lock-periods
     *   php artisan accounting:auto-lock-periods --grace-days=5
     *   php artisan accounting:auto-lock-periods --dry-run
     */
    protected $signature = 'accounting:auto-lock-periods
                            {--grace-days=0 : Number of grace days after period end before locking}
                            {--dry-run : Show which periods would be locked without actually locking}';

    protected $description = 'Automatically lock accounting periods whose end date has passed (plus optional grace days)';

    public function handle(): int
    {
        $graceDays = (int) $this->option('grace-days');
        $dryRun    = (bool) $this->option('dry-run');
        $cutoff    = Carbon::today()->subDays($graceDays);

        if ($dryRun) {
            $this->warn('[DRY RUN] No periods will be locked.');
        }

        $this->info("Locking periods that ended on or before: {$cutoff->toDateString()} (grace: {$graceDays} day(s))");

        $periods = AccountingPeriod::where('is_locked', false)
            ->where('end_date', '<=', $cutoff->toDateString())
            ->get();

        if ($periods->isEmpty()) {
            $this->info('No periods to lock.');
            return self::SUCCESS;
        }

        $locked  = 0;
        $failed  = 0;

        foreach ($periods as $period) {
            if ($dryRun) {
                $this->info("  [DRY] Would lock: {$period->name} (ended {$period->end_date})");
                $locked++;
                continue;
            }

            try {
                $period->update(['is_locked' => true]);
                $this->info("  Locked: {$period->name} (ended {$period->end_date})");
                $locked++;

                Log::channel('daily')->info('Accounting period auto-locked', [
                    'period_id'   => $period->id,
                    'period_name' => $period->name,
                    'end_date'    => $period->end_date,
                ]);
            } catch (\Throwable $e) {
                $this->error("  Failed to lock period {$period->name}: {$e->getMessage()}");
                $failed++;
                Log::error('Accounting period auto-lock failed', [
                    'period_id' => $period->id,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info("Done. Locked: {$locked} | Failed: {$failed}");

        return self::SUCCESS;
    }
}
