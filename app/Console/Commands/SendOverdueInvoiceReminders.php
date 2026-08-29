<?php

namespace App\Console\Commands;

use App\Mail\OverdueInvoiceReminderMail;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOverdueInvoiceReminders extends Command
{
    /**
     * Artisan command signature.
     *
     * Usage:
     *   php artisan invoices:send-overdue-reminders
     *   php artisan invoices:send-overdue-reminders --days=7,14,30
     *   php artisan invoices:send-overdue-reminders --dry-run
     */
    protected $signature = 'invoices:send-overdue-reminders
                            {--days=7,14,30 : Comma-separated days-overdue thresholds to send reminders on}
                            {--dry-run : Log what would be sent without actually sending emails}';

    protected $description = 'Send payment reminder emails for unpaid invoices that are overdue by the specified day thresholds';

    public function handle(): int
    {
        $thresholds = collect(explode(',', $this->option('days')))
            ->map(fn ($d) => (int) trim($d))
            ->filter(fn ($d) => $d > 0)
            ->values();

        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('[DRY RUN] No emails will be sent.');
        }

        $this->info("Checking overdue thresholds: {$thresholds->implode(', ')} day(s)");

        $today   = Carbon::today();
        $sent    = 0;
        $skipped = 0;

        foreach ($thresholds as $days) {
            // Net-30 terms: due date = invoice date + 30 days
            // Overdue by $days means invoice date = today - 30 - $days
            $targetDueDate = $today->copy()->subDays($days)->toDateString();

            $invoices = Sale::with(['customer', 'company'])
                ->where('approval_status', 'approved')
                ->where('remaining_amount', '>', 0)
                ->whereDate('due_date', $targetDueDate)
                ->get();

            if ($invoices->isEmpty()) {
                $this->line("  [{$days}d] No invoices found.");
                continue;
            }

            foreach ($invoices as $invoice) {
                $customerEmail = $invoice->customer->email ?? null;
                $companyName   = optional($invoice->company)->name ?? config('app.name');

                if (!$customerEmail) {
                    $this->warn("  [{$days}d] Invoice #{$invoice->invoice_no} — customer has no email, skipped.");
                    $skipped++;
                    continue;
                }

                if ($dryRun) {
                    $this->info("  [DRY] Would send to {$customerEmail} — Invoice #{$invoice->invoice_no}, overdue {$days}d, balance {$invoice->remaining_amount}");
                    $sent++;
                    continue;
                }

                try {
                    Mail::to($customerEmail)
                        ->queue(new OverdueInvoiceReminderMail($invoice, $days, $companyName));

                    $this->info("  [{$days}d] Reminder queued for {$customerEmail} — Invoice #{$invoice->invoice_no}");
                    $sent++;

                    Log::channel('daily')->info('Overdue invoice reminder sent', [
                        'invoice_no'   => $invoice->invoice_no,
                        'customer'     => $invoice->customer->name,
                        'email'        => $customerEmail,
                        'days_overdue' => $days,
                        'balance'      => $invoice->remaining_amount,
                    ]);
                } catch (\Throwable $e) {
                    $this->error("  [{$days}d] Failed for Invoice #{$invoice->invoice_no}: {$e->getMessage()}");
                    Log::error('Overdue invoice reminder failed', [
                        'invoice_no' => $invoice->invoice_no,
                        'error'      => $e->getMessage(),
                    ]);
                    $skipped++;
                }
            }
        }

        $this->newLine();
        $this->info("Done. Sent: {$sent} | Skipped: {$skipped}");

        return self::SUCCESS;
    }
}
