<?php

namespace App\Console\Commands;

use App\Models\AccountingPeriod;
use App\Models\FixedAsset;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PostAssetDepreciation extends Command
{
    /**
     * Post monthly depreciation journal entries for all active fixed assets.
     *
     *   --month=YYYY-MM  : Target month (defaults to current month)
     *   --dry-run        : Preview without posting
     */
    protected $signature = 'assets:post-depreciation
                            {--month= : Target month YYYY-MM (default: current month)}
                            {--dry-run : Preview without posting entries}';

    protected $description = 'Post monthly depreciation journal entries for active fixed assets';

    public function handle(): int
    {
        $isDryRun  = (bool) $this->option('dry-run');
        $monthStr  = $this->option('month') ?? now()->format('Y-m');

        try {
            $targetMonth = Carbon::createFromFormat('Y-m', $monthStr)->startOfMonth();
        } catch (\Throwable) {
            $this->error("Invalid month format. Use YYYY-MM.");
            return self::FAILURE;
        }

        $this->info($isDryRun
            ? "[DRY RUN] Asset Depreciation for {$targetMonth->format('F Y')}"
            : "Posting Asset Depreciation for {$targetMonth->format('F Y')}");

        $assets = FixedAsset::where('status', 'active')
            ->whereDate('in_service_date', '<=', $targetMonth->copy()->endOfMonth())
            ->get();

        if ($assets->isEmpty()) {
            $this->info('No active assets to depreciate.');
            return self::SUCCESS;
        }

        $posted  = 0;
        $skipped = 0;

        foreach ($assets as $asset) {
            // Skip if already depreciated this month
            if ($asset->last_depreciation_date &&
                $asset->last_depreciation_date->format('Y-m') === $targetMonth->format('Y-m')) {
                $this->line("  SKIP  {$asset->asset_number} {$asset->name} — already depreciated this month");
                $skipped++;
                continue;
            }

            // Skip if fully depreciated
            if ($asset->isFullyDepreciated()) {
                $this->line("  SKIP  {$asset->asset_number} {$asset->name} — fully depreciated");
                $asset->update(['status' => 'fully_depreciated']);
                $skipped++;
                continue;
            }

            $depAmount = $asset->monthlyDepreciation();

            // Don't depreciate below residual value
            $depAmount = min($depAmount, max(0, (float) $asset->current_book_value - (float) $asset->residual_value));

            if ($depAmount <= 0) {
                $skipped++;
                continue;
            }

            $period   = AccountingPeriod::findPeriodForDate($targetMonth->format('Y-m-d'), $asset->company_id);
            if (! $period) {
                $this->line("  SKIP  {$asset->asset_number} {$asset->name} — no unlocked accounting period found");
                $skipped++;
                continue;
            }
            $uniqueNo = "DEP-{$asset->asset_number}-{$targetMonth->format('Ym')}";

            $this->line(sprintf(
                "  POST  %s  %-30s  %.2f  method=%s",
                $asset->asset_number,
                $asset->name,
                $depAmount,
                $asset->depreciation_method
            ));

            if (!$isDryRun) {
                DB::transaction(function () use ($asset, $depAmount, $period, $targetMonth, $uniqueNo) {
                    $base = [
                        'company_id'           => $asset->company_id,
                        'accounting_period_id' => $period?->id,
                        'reference_id'         => $asset->id,
                        'unique_no'            => $uniqueNo,
                        'invoice_no'           => $uniqueNo,
                        'date'                 => $targetMonth->endOfMonth()->format('Y-m-d'),
                        'type'                 => 'Depreciation',
                        'created_by'           => 1,
                        'updated_by'           => 1,
                    ];

                    // DR Depreciation Expense
                    Transaction::create(array_merge($base, [
                        'account_id'  => $asset->depreciation_expense_account_id,
                        'debit'       => $depAmount,
                        'credit'      => 0,
                        'description' => "Depreciation — {$asset->name} ({$targetMonth->format('M Y')})",
                    ]));

                    // CR Accumulated Depreciation
                    Transaction::create(array_merge($base, [
                        'account_id'  => $asset->accumulated_depreciation_account_id,
                        'debit'       => 0,
                        'credit'      => $depAmount,
                        'description' => "Acc. Depreciation — {$asset->name} ({$targetMonth->format('M Y')})",
                    ]));

                    $newBookValue = round((float) $asset->current_book_value - $depAmount, 2);

                    $asset->update([
                        'current_book_value'       => $newBookValue,
                        'accumulated_depreciation' => round((float) $asset->accumulated_depreciation + $depAmount, 2),
                        'last_depreciation_date'   => $targetMonth->endOfMonth(),
                        'status' => $newBookValue <= (float) $asset->residual_value ? 'fully_depreciated' : 'active',
                    ]);
                });
            }

            $posted++;
        }

        $tag = $isDryRun ? '[DRY RUN] Would post' : 'Posted';
        $this->info("{$tag} {$posted} depreciation entries. Skipped: {$skipped}.");

        Log::channel('daily')->info("Asset Depreciation — month={$targetMonth->format('Y-m')} posted={$posted} skipped={$skipped} dry={$isDryRun}");

        return self::SUCCESS;
    }
}
