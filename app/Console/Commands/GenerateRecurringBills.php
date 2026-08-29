<?php

namespace App\Console\Commands;

use App\Models\AccountingPeriod;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\ChartOfAccount;
use App\Models\RecurringBill;
use App\Models\Transaction;
use App\Services\BillService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateRecurringBills extends Command
{
    /**
     * Usage:
     *   php artisan bills:generate-recurring
     *   php artisan bills:generate-recurring --dry-run
     */
    protected $signature = 'bills:generate-recurring
                            {--dry-run : Show which bills would be generated without creating them}';

    protected $description = 'Generate Bill records from active recurring bill schedules that are due today';

    public function handle(BillService $billService): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('[DRY RUN] No bills will be created.');
        }

        $due = RecurringBill::with(['vendor', 'items', 'items.taxRate', 'company'])
            ->dueToday()
            ->get();

        if ($due->isEmpty()) {
            $this->info('No recurring bills due today.');
            return self::SUCCESS;
        }

        $this->info("Found {$due->count()} recurring bill(s) due.");

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
                $this->info("  [DRY] Would generate bill for: {$recurring->title} — Vendor: {$recurring->vendor->name} — Total: {$recurring->total}");
                $generated++;
                continue;
            }

            DB::beginTransaction();
            try {
                $recurring = RecurringBill::with(['vendor', 'items.taxRate', 'company'])->lockForUpdate()->findOrFail($recurring->id);
                if ($recurring->status !== 'active' || $recurring->next_run_date->isAfter($today)) {
                    DB::rollBack(); $skipped++; continue;
                }
                // ── Find accounting period ───────────────────────────────────
                $period = AccountingPeriod::findPeriodForDate($today->toDateString(), $recurring->company_id);
                if (! $period) {
                    $this->warn("  Skipped [{$recurring->title}]: No unlocked accounting period for {$today->toDateString()}");
                    DB::rollBack();
                    $skipped++;
                    continue;
                }

                // ── Build bill number ────────────────────────────────────────
                $uniqueNo  = $billService->nextBillUniqueNo($recurring->company_id, $today->toDateString());
                $invoiceNo = 'RECB-' . $recurring->id . '-' . $today->format('Ymd');
                $totals = $this->calculateTotalsFromItems($recurring->items);
                $billService->assertAccountingConfiguration($recurring->company_id, $totals['discount'], $totals['tax']);
                $dueDate = $today->copy()->addDays((int) ($recurring->vendor->payment_terms_days ?? 30));

                // ── Create Bill record ───────────────────────────────────────
                $bill = Bill::create([
                    'company_id'           => $recurring->company_id,
                    'accounting_period_id' => $period->id,
                    'vendor_id'            => $recurring->vendor_id,
                    'account_id'           => $recurring->account_id,
                    'currency_id'          => $recurring->company->currency_id,
                    'exchange_rate'        => 1,
                    'unique_no'            => $uniqueNo,
                    'invoice_no'           => $invoiceNo,
                    'ref_no'               => "Recurring: {$recurring->title}",
                    'date'                 => $today->toDateString(),
                    'vendor_invoice_date' => $today->toDateString(),
                    'posting_date' => $today->toDateString(),
                    'due_date' => $dueDate->toDateString(),
                    'note'                 => $recurring->note,
                    'sub_total'            => $totals['sub_total'],
                    'discount_total'       => $totals['discount'],
                    'net_before_tax'       => $totals['total_before_tax'],
                    'tax'                  => $totals['tax'],
                    'grand_total'          => $totals['total'],
                    'base_currency_total' => $totals['total'],
                    'total'                => $totals['total'],
                    'total_after_discount' => $totals['total'],
                    'paid_amount'          => 0,
                    'remaining_amount'     => $totals['total'],
                    'approval_status'      => 'draft',
                    'payment_status'       => 'unpaid',
                    'status'               => 1,
                    'created_by'           => $recurring->created_by,
                    'updated_by'           => $recurring->created_by,
                ]);

                // ── Create Bill Items ────────────────────────────────────────
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

                    BillItem::create([
                        'company_id'           => $recurring->company_id,
                        'accounting_period_id' => $period->id,
                        'bill_id'              => $bill->id,
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
                        'vendor_id'            => $recurring->vendor_id,
                        'payable_type'         => 'vendor',
                        'payable_id'           => $recurring->vendor_id,
                        'reference_id'         => $bill->id,
                        'invoice_no'           => $invoiceNo,
                        'unique_no'            => $uniqueNo,
                        'date'                 => $today->toDateString(),
                        'type'                 => 'Bill',
                        'description'          => $item->description ?: "Recurring Bill: {$recurring->title}",
                        'debit'                => $rowSubTotal,
                        'credit'               => 0,
                        'status'               => 0,
                        'created_by'           => $recurring->created_by,
                        'updated_by'           => $recurring->created_by,
                        'created_at'           => now(),
                        'updated_at'           => now(),
                    ];
                }

                // ── Create double-entry transactions ─────────────────────────
                // DR: Expense, DR: Purchase Tax, CR: Purchase Discount, CR: Accounts Payable
                $apAccountId = ChartOfAccount::getAccountsPayableId($recurring->company_id);

                if (! $apAccountId) {
                    throw new \Exception('Accounts Payable account not found.');
                }

                if ($totalDiscountAmount > 0) {
                    $discountAccountId = ChartOfAccount::getPurchaseDiscountAccountId($recurring->company_id);
                    if (! $discountAccountId) {
                        throw new \Exception('Purchase Discount account not found.');
                    }

                    $transactions[] = [
                        'company_id'           => $recurring->company_id,
                        'accounting_period_id' => $period->id,
                        'account_id'           => $discountAccountId,
                        'vendor_id'            => $recurring->vendor_id,
                        'payable_type'         => 'vendor',
                        'payable_id'           => $recurring->vendor_id,
                        'reference_id'         => $bill->id,
                        'invoice_no'           => $invoiceNo,
                        'unique_no'            => $uniqueNo,
                        'date'                 => $today->toDateString(),
                        'type'                 => 'Bill',
                        'description'          => "Purchase Discount for Recurring Bill: {$recurring->title}",
                        'debit'                => 0,
                        'credit'               => round($totalDiscountAmount, 2),
                        'status'               => 0,
                        'created_by'           => $recurring->created_by,
                        'updated_by'           => $recurring->created_by,
                        'created_at'           => now(),
                        'updated_at'           => now(),
                    ];
                }

                if ($totalTaxAmount > 0) {
                    $taxAccountId = ChartOfAccount::getPurchaseTaxAccountId($recurring->company_id);
                    if (! $taxAccountId) {
                        throw new \Exception('Purchase Tax account not found.');
                    }

                    $transactions[] = [
                        'company_id'           => $recurring->company_id,
                        'accounting_period_id' => $period->id,
                        'account_id'           => $taxAccountId,
                        'vendor_id'            => $recurring->vendor_id,
                        'payable_type'         => 'vendor',
                        'payable_id'           => $recurring->vendor_id,
                        'reference_id'         => $bill->id,
                        'invoice_no'           => $invoiceNo,
                        'unique_no'            => $uniqueNo,
                        'date'                 => $today->toDateString(),
                        'type'                 => 'Bill',
                        'description'          => "Purchase Tax for Recurring Bill: {$recurring->title}",
                        'debit'                => round($totalTaxAmount, 2),
                        'credit'               => 0,
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
                    'account_id'           => $apAccountId,
                    'vendor_id'            => $recurring->vendor_id,
                    'payable_type'         => 'vendor',
                    'payable_id'           => $recurring->vendor_id,
                    'reference_id'         => $bill->id,
                    'invoice_no'           => $invoiceNo,
                    'unique_no'            => $uniqueNo,
                    'date'                 => $today->toDateString(),
                    'type'                 => 'Bill',
                    'description'          => "Accounts Payable for Recurring Bill: {$recurring->title}",
                    'debit'                => 0,
                    'credit'               => $totals['total'],
                    'status'               => 0,
                    'created_by'           => $recurring->created_by,
                    'updated_by'           => $recurring->created_by,
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ];

                Transaction::insert($transactions);
                $billService->captureVersion($bill->fresh('billItems'), 'recurring-generated');

                // ── Advance next run date ────────────────────────────────────
                $nextRun = $recurring->calculateNextRunDate($today);

                $newStatus = 'active';
                if ($recurring->end_date && $nextRun->gt($recurring->end_date)) {
                    $newStatus = 'completed';
                }
                if ($recurring->max_occurrences && ($recurring->bills_generated + 1) >= $recurring->max_occurrences) {
                    $newStatus = 'completed';
                }

                $recurring->update([
                    'last_run_date'      => $today->toDateString(),
                    'next_run_date'      => $nextRun->toDateString(),
                    'bills_generated'    => $recurring->bills_generated + 1,
                    'status'             => $newStatus,
                ]);

                DB::commit();

                $this->info("  Generated: {$invoiceNo} for {$recurring->vendor->name} (next run: {$nextRun->toDateString()})");
                $generated++;

                Log::channel('daily')->info('Recurring bill generated', [
                    'recurring_id' => $recurring->id,
                    'title'        => $recurring->title,
                    'invoice_no'   => $invoiceNo,
                    'bill_id'      => $bill->id,
                    'vendor'       => $recurring->vendor->name,
                    'total'        => $totals['total'],
                    'next_run'     => $nextRun->toDateString(),
                ]);
            } catch (\Throwable $e) {
                DB::rollBack();
                $this->error("  Failed [{$recurring->title}]: {$e->getMessage()}");
                Log::error('Recurring bill generation failed', [
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
