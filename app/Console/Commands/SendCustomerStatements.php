<?php

namespace App\Console\Commands;

use App\Mail\CustomerStatementMail;
use App\Models\Customer;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendCustomerStatements extends Command
{
    /**
     * Send account statement emails to customers with outstanding balances.
     *
     * Options:
     *   --customer=ID    Only send to a specific customer (by ID)
     *   --min-balance=1  Skip customers whose outstanding is below this amount (default 0.01)
     *   --dry-run        Preview without sending
     */
    protected $signature = 'account:send-customer-statements
                            {--customer= : Specific customer ID (omit for all)}
                            {--min-balance=0.01 : Minimum outstanding balance to trigger email}
                            {--dry-run : Preview output without sending}';

    protected $description = 'Email account statements to customers with outstanding invoice balances';

    public function handle(): int
    {
        $isDryRun    = (bool) $this->option('dry-run');
        $customerId  = $this->option('customer');
        $minBalance  = (float) $this->option('min-balance');
        $companyName = config('app.name', 'Our Company');
        $statementDate = Carbon::today()->format('d M Y');

        $this->info($isDryRun ? '[DRY RUN] Customer Statements' : 'Sending Customer Statements');
        $this->line("Statement date: {$statementDate}");

        // ── Build query ──────────────────────────────────────────────────────
        $customerQuery = Customer::whereHas('sales', function ($q) use ($minBalance) {
            $q->where('remaining_amount', '>', $minBalance);
        });

        if ($customerId) {
            $customerQuery->where('id', $customerId);
        }

        $customers = $customerQuery->with(['sales' => function ($q) {
            $q->where('remaining_amount', '>', 0)
              ->orderBy('date');
        }])->get();

        if ($customers->isEmpty()) {
            $this->info('No customers with outstanding balances found.');
            return self::SUCCESS;
        }

        $sent    = 0;
        $skipped = 0;

        foreach ($customers as $customer) {
            $invoices = $customer->sales;

            if ($invoices->isEmpty()) {
                $skipped++;
                continue;
            }

            if (empty($customer->email)) {
                $this->warn("  ⚠  {$customer->name} — no email address, skipping.");
                $skipped++;
                continue;
            }

            $totalInvoiced    = (float) $invoices->sum('total');
            $totalPaid        = (float) $invoices->sum('paid_amount');
            $totalOutstanding = (float) $invoices->sum('remaining_amount');

            if ($totalOutstanding < $minBalance) {
                $skipped++;
                continue;
            }

            $this->line(sprintf(
                "  → %s <%s>  invoices: %d  outstanding: %.2f",
                $customer->name,
                $customer->email,
                $invoices->count(),
                $totalOutstanding
            ));

            if (!$isDryRun) {
                Mail::to($customer->email)->queue(
                    new CustomerStatementMail(
                        customer:         $customer,
                        invoices:         $invoices,
                        totalInvoiced:    $totalInvoiced,
                        totalPaid:        $totalPaid,
                        totalOutstanding: $totalOutstanding,
                        companyName:      $companyName,
                        statementDate:    $statementDate,
                    )
                );
            }

            $sent++;
        }

        $tag = $isDryRun ? '[DRY RUN] Would send' : 'Sent';
        $this->info("{$tag} {$sent} statement(s). Skipped: {$skipped}.");

        return self::SUCCESS;
    }
}
