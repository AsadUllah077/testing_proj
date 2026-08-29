<?php

namespace App\Console\Commands;

use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\RecurringInvoice;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Transaction;
use App\Services\SaleService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateRecurringInvoices extends Command
{
    /**
     * Usage:
     *   php artisan invoices:generate-recurring
     *   php artisan invoices:generate-recurring --dry-run
     */
    protected $signature = 'invoices:generate-recurring
                            {--dry-run : Show which invoices would be generated without creating them}';

    protected $description = 'Generate Sale invoices from active recurring invoice schedules that are due today';

    public function handle(SaleService $saleService): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('[DRY RUN] No invoices will be created.');
        }

        $due = RecurringInvoice::with(['customer', 'items', 'items.taxRate', 'company'])
            ->dueToday()
            ->get();

        if ($due->isEmpty()) {
            $this->info('No recurring invoices due today.');
            return self::SUCCESS;
        }

        $this->info("Found {$due->count()} recurring invoice(s) due.");

        $generated = 0;
        $skipped   = 0;

        foreach ($due as $recurring) {
            // ── Expiry check ────────────────────────────────────────────────
            if ($recurring->isExpired()) {
                if (!$dryRun) {
                    $recurring->update(['status' => 'completed']);
                }
                $this->warn("  Completed (expired): {$recurring->title}");
                $skipped++;
                continue;
            }

            $today = Carbon::today();

            if ($dryRun) {
                $this->info("  [DRY] Would generate invoice for: {$recurring->title} — Customer: {$recurring->customer->name} — Total: {$recurring->total}");
                $generated++;
                continue;
            }

            DB::beginTransaction();
            try {
                $recurring = RecurringInvoice::with(['customer', 'items.taxRate', 'company'])
                    ->lockForUpdate()
                    ->findOrFail($recurring->id);

                if ($recurring->status !== 'active' || $recurring->next_run_date->isAfter($today)) {
                    DB::rollBack();
                    $skipped++;
                    continue;
                }

                // ── Find accounting period ───────────────────────────────────
                $period = AccountingPeriod::findPeriodForDate($today->toDateString(), $recurring->company_id);
                if (! $period) {
                    $this->warn("  Skipped [{$recurring->title}]: No unlocked accounting period for {$today->toDateString()}");
                    DB::rollBack();
                    $skipped++;
                    continue;
                }

                // ── Build invoice number ─────────────────────────────────────
                $uniqueNo  = $saleService->nextInvoiceUniqueNo($recurring->company_id, $today->toDateString());
                $invoiceNo = 'REC-' . $recurring->id . '-' . $today->format('Ymd');
                $totals = $this->calculateTotalsFromItems($recurring->items);
                $saleService->assertAccountingConfiguration(
                    $recurring->company_id,
                    $totals['discount'],
                    $totals['tax']
                );
                $dueDate = $today->copy()->addDays((int) ($recurring->customer->payment_terms_days ?? 30));
                $currentExposure = (float) Sale::withoutGlobalScopes()
                    ->where('company_id', $recurring->company_id)
                    ->where('customer_id', $recurring->customer_id)
                    ->where('approval_status', 'approved')
                    ->sum('remaining_amount');
                $creditLimit = (float) ($recurring->customer->credit_limit ?? 0);

                // ── Create Sale record ───────────────────────────────────────
                $sale = Sale::create([
                    'company_id'           => $recurring->company_id,
                    'accounting_period_id' => $period->id,
                    'customer_id'          => $recurring->customer_id,
                    'account_id'           => $recurring->account_id,
                    'currency_id'          => $recurring->company->currency_id,
                    'exchange_rate'        => 1,
                    'unique_no'            => $uniqueNo,
                    'invoice_no'           => $invoiceNo,
                    'ref_no'               => "Recurring: {$recurring->title}",
                    'date'                 => $today->toDateString(),
                    'due_date'             => $dueDate->toDateString(),
                    'note'                 => $recurring->note,
                    'sub_total'            => $totals['sub_total'],
                    'discount_total'       => $totals['discount'],
                    'net_before_tax'       => $totals['total_before_tax'],
                    'tax'                  => $totals['tax'],
                    'total'                => $totals['total'],
                    'grand_total'          => $totals['total'],
                    'base_currency_total' => $totals['total'],
                    'total_after_discount' => $totals['total'],
                    'paid_amount'          => 0,
                    'remaining_amount'     => $totals['total'],
                    'payment_status'       => 'unpaid',
                    'approval_status'      => 'draft',
                    'credit_limit_exceeded' => $creditLimit > 0
                        && ($currentExposure + $totals['total']) > $creditLimit,
                    'status'               => 1,
                    'created_by'           => $recurring->created_by,
                    'updated_by'           => $recurring->created_by,
                ]);

                // ── Create Sale Items ────────────────────────────────────────
                $transactions = [];
                $totalDiscountAmount = 0.0;
                $totalTaxAmount = 0.0;

                foreach ($recurring->items as $item) {
                    $rowSubTotal = round((float) ($item->sub_total ?? ((float) $item->quantity * (float) $item->price)), 2);
                    $rowTax = round((float) ($item->tax_amount ?? 0), 2);
                    $rowTotal = round((float) ($item->total ?? 0), 2);
                    $rowDiscount = round(max(0, $rowSubTotal + $rowTax - $rowTotal), 2);

                    $totalDiscountAmount += $rowDiscount;
                    $totalTaxAmount += $rowTax;

                    SaleItem::create([
                        'company_id'           => $recurring->company_id,
                        'accounting_period_id' => $period->id,
                        'sale_id'              => $sale->id,
                        'account_id'           => $item->account_id,
                        'tax_id'               => $item->tax_id,
                        'description'          => $item->description,
                        'quantity'             => (int)$item->quantity,
                        'kg'                   => 0,
                        'unit_of_measure'      => 'unit',
                        'price'                => $item->price,
                        'discount'             => $item->discount_percentage,
                        'tax_name_snapshot'    => $item->taxRate?->name,
                        'tax_rate_snapshot'    => $item->taxRate?->tax_rate ?? 0,
                        'line_subtotal'        => $rowSubTotal,
                        'discount_amount'      => $rowDiscount,
                        'tax_amount'           => $rowTax,
                        'total'                => $item->total,
                        'created_by'           => $recurring->created_by,
                        'updated_by'           => $recurring->created_by,
                    ]);

                    $transactions[] = [
                        'company_id'           => $recurring->company_id,
                        'accounting_period_id' => $period->id,
                        'account_id'           => $item->account_id,
                        'customer_id'          => $recurring->customer_id,
                        'reference_id'         => $sale->id,
                        'invoice_no'           => $invoiceNo,
                        'unique_no'            => $uniqueNo,
                        'date'                 => $today->toDateString(),
                        'type'                 => 'Sale',
                        'description'          => $item->description ?: "Recurring Invoice: {$recurring->title}",
                        'debit'                => 0,
                        'credit'               => $rowSubTotal,
                        'status'               => 0,
                        'created_by'           => $recurring->created_by,
                        'updated_by'           => $recurring->created_by,
                        'created_at'           => now(),
                        'updated_at'           => now(),
                    ];
                }

                // ── Create double-entry transactions ─────────────────────────
                // DR: Accounts Receivable, DR: Sales Discount, CR: Tax, CR: Revenue
                $arAccountId = ChartOfAccount::getAccountsReceivableId($recurring->company_id);

                if (! $arAccountId) {
                    throw new \Exception('Accounts Receivable account not found.');
                }

                if ($totalDiscountAmount > 0) {
                    $discountAccountId = ChartOfAccount::getSalesDiscountAccountId($recurring->company_id);
                    if (! $discountAccountId) {
                        throw new \Exception('Sales Discount account not found.');
                    }

                    $transactions[] = [
                        'company_id'           => $recurring->company_id,
                        'accounting_period_id' => $period->id,
                        'account_id'           => $discountAccountId,
                        'customer_id'          => $recurring->customer_id,
                        'reference_id'         => $sale->id,
                        'invoice_no'           => $invoiceNo,
                        'unique_no'            => $uniqueNo,
                        'date'                 => $today->toDateString(),
                        'type'                 => 'Sale',
                        'description'          => "Sales Discount for Recurring Invoice: {$recurring->title}",
                        'debit'                => round($totalDiscountAmount, 2),
                        'credit'               => 0,
                        'status'               => 0,
                        'created_by'           => $recurring->created_by,
                        'updated_by'           => $recurring->created_by,
                        'created_at'           => now(),
                        'updated_at'           => now(),
                    ];
                }

                if ($totalTaxAmount > 0) {
                    $taxAccountId = ChartOfAccount::getSalesTaxPayableId($recurring->company_id);
                    if (! $taxAccountId) {
                        throw new \Exception('Sales Tax Payable account not found.');
                    }

                    $transactions[] = [
                        'company_id'           => $recurring->company_id,
                        'accounting_period_id' => $period->id,
                        'account_id'           => $taxAccountId,
                        'customer_id'          => $recurring->customer_id,
                        'reference_id'         => $sale->id,
                        'invoice_no'           => $invoiceNo,
                        'unique_no'            => $uniqueNo,
                        'date'                 => $today->toDateString(),
                        'type'                 => 'Sale',
                        'description'          => "Sales Tax for Recurring Invoice: {$recurring->title}",
                        'debit'                => 0,
                        'credit'               => round($totalTaxAmount, 2),
                        'status'               => 0,
                        'created_by'           => $recurring->created_by,
                        'updated_by'           => $recurring->created_by,
                        'created_at'           => now(),
                        'updated_at'           => now(),
                    ];
                }

                $transactions[] = [
                    'company_id'           => $recurring->company_id,
                    'accounting_period_id' => $period->id,
                    'account_id'           => $arAccountId,
                    'customer_id'          => $recurring->customer_id,
                    'reference_id'         => $sale->id,
                    'invoice_no'           => $invoiceNo,
                    'unique_no'            => $uniqueNo,
                    'date'                 => $today->toDateString(),
                    'type'                 => 'Sale',
                    'description'          => "Accounts Receivable for Recurring Invoice: {$recurring->title}",
                    'debit'                => $totals['total'],
                    'credit'               => 0,
                    'status'               => 0,
                    'created_by'           => $recurring->created_by,
                    'updated_by'           => $recurring->created_by,
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ];

                Transaction::insert($transactions);
                $saleService->captureVersion($sale->fresh('saleItems'), 'recurring-generated');

                // ── Advance next run date ────────────────────────────────────
                $nextRun = $recurring->calculateNextRunDate($today);

                $newStatus = 'active';
                if ($recurring->end_date && $nextRun->gt($recurring->end_date)) {
                    $newStatus = 'completed';
                }
                if ($recurring->max_occurrences && ($recurring->invoices_generated + 1) >= $recurring->max_occurrences) {
                    $newStatus = 'completed';
                }

                $recurring->update([
                    'last_run_date'       => $today->toDateString(),
                    'next_run_date'       => $nextRun->toDateString(),
                    'invoices_generated'  => $recurring->invoices_generated + 1,
                    'status'              => $newStatus,
                ]);

                DB::commit();

                $this->info("  Generated: {$invoiceNo} for {$recurring->customer->name} (next run: {$nextRun->toDateString()})");
                $generated++;

                Log::channel('daily')->info('Recurring invoice generated', [
                    'recurring_id' => $recurring->id,
                    'title'        => $recurring->title,
                    'invoice_no'   => $invoiceNo,
                    'sale_id'      => $sale->id,
                    'customer'     => $recurring->customer->name,
                    'total'        => $totals['total'],
                    'next_run'     => $nextRun->toDateString(),
                ]);
            } catch (\Throwable $e) {
                DB::rollBack();
                $this->error("  Failed [{$recurring->title}]: {$e->getMessage()}");
                Log::error('Recurring invoice generation failed', [
                    'recurring_id' => $recurring->id,
                    'error'        => $e->getMessage(),
                ]);
                $skipped++;
            }
        }

        $this->newLine();
        $this->info("Done. Generated: {$generated} | Skipped/Errored: {$skipped}");

        return self::SUCCESS;
    }

    private function calculateTotalsFromItems($items): array
    {
        $subTotal = 0.0;
        $discount = 0.0;
        $tax = 0.0;
        $total = 0.0;

        foreach ($items as $item) {
            $rowSubTotal = round((float) ($item->sub_total ?? ((float) $item->quantity * (float) $item->price)), 2);
            $rowTax = round((float) ($item->tax_amount ?? 0), 2);
            $rowTotal = round((float) ($item->total ?? 0), 2);
            $rowDiscount = round(max(0, $rowSubTotal + $rowTax - $rowTotal), 2);

            $subTotal += $rowSubTotal;
            $discount += $rowDiscount;
            $tax += $rowTax;
            $total += $rowTotal;
        }

        return [
            'sub_total' => round($subTotal, 2),
            'discount' => round($discount, 2),
            'tax' => round($tax, 2),
            'total_before_tax' => round($subTotal - $discount, 2),
            'total' => round($total, 2),
        ];
    }
}
