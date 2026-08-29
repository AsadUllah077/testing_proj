<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->decimal('discount_total', 18, 4)->default(0)->after('sub_total');
            $table->decimal('net_before_tax', 18, 4)->default(0)->after('discount_total');
            $table->decimal('grand_total', 18, 4)->default(0)->after('tax');
            $table->decimal('withholding_tax', 18, 4)->default(0)->after('grand_total');
            $table->foreignId('withholding_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->string('payment_status')->default('unpaid')->after('approval_status');
            $table->boolean('email_on_approval')->default(false)->after('payment_status');
            $table->date('vendor_invoice_date')->nullable()->after('date');
            $table->date('posting_date')->nullable()->after('vendor_invoice_date');
            $table->date('due_date')->nullable()->after('posting_date');
            $table->decimal('exchange_rate', 18, 8)->default(1)->after('currency_id');
            $table->decimal('base_currency_total', 18, 4)->default(0)->after('withholding_tax');
            $table->string('purchase_order_no')->nullable()->after('ref_no');
            $table->string('goods_receipt_no')->nullable()->after('purchase_order_no');
            $table->string('project')->nullable();
            $table->string('cost_centre')->nullable();
            $table->string('department')->nullable();
            $table->string('warehouse')->nullable();
            $table->string('vendor_tax_invoice_reference')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('void_reason')->nullable();

            $table->index(['company_id', 'payment_status'], 'bills_company_payment_status_idx');
            $table->unique(['company_id', 'vendor_id', 'invoice_no'], 'bills_company_vendor_invoice_unique');
            $table->unique(['company_id', 'unique_no'], 'bills_company_unique_no_unique');
        });

        Schema::table('bill_items', function (Blueprint $table) {
            $table->decimal('quantity', 18, 4)->nullable()->change();
            $table->decimal('kg', 18, 4)->nullable()->change();
            $table->string('unit_of_measure', 30)->default('unit')->after('kg');
            $table->string('tax_name_snapshot')->nullable()->after('tax_id');
            $table->decimal('tax_rate_snapshot', 9, 4)->default(0)->after('tax_name_snapshot');
            $table->decimal('line_subtotal', 18, 4)->default(0)->after('tax_rate_snapshot');
            $table->decimal('discount_amount', 18, 4)->default(0)->after('line_subtotal');
            $table->decimal('tax_amount', 18, 4)->default(0)->after('discount_amount');
        });

        Schema::table('bill_attachments', function (Blueprint $table) {
            $table->string('original_name')->nullable()->after('attachment');
            $table->string('mime_type')->nullable()->after('original_name');
            $table->unsignedBigInteger('file_size')->nullable()->after('mime_type');
            $table->string('storage_disk')->default('public')->after('file_size');
            $table->string('checksum', 64)->nullable()->after('storage_disk');
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->unsignedSmallInteger('payment_terms_days')->default(30);
        });

        Schema::create('bill_invoice_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedBigInteger('next_number')->default(1);
            $table->timestamps();
            $table->unique(['company_id', 'year']);
        });

        Schema::create('bill_invoice_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bill_id')->constrained('bills')->cascadeOnDelete();
            $table->string('channel')->default('email');
            $table->string('recipient');
            $table->string('status')->default('queued');
            $table->text('failure_reason')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['bill_id', 'status']);
        });

        Schema::create('bill_invoice_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bill_id')->constrained('bills')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('event');
            $table->json('snapshot');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['bill_id', 'version']);
        });

        $sequenceSeeds = [];
        DB::table('bills')->whereNull('deleted_at')->select(['company_id', 'unique_no'])->orderBy('id')->each(function ($bill) use (&$sequenceSeeds) {
            if (preg_match('/^BILL-(\d{4})-(\d+)$/', (string) $bill->unique_no, $matches)) {
                $key = $bill->company_id . ':' . $matches[1];
                $sequenceSeeds[$key] = max($sequenceSeeds[$key] ?? 1, ((int) $matches[2]) + 1);
            }
        });
        foreach ($sequenceSeeds as $key => $nextNumber) {
            [$companyId, $year] = array_map('intval', explode(':', $key));
            DB::table('bill_invoice_sequences')->updateOrInsert(
                ['company_id' => $companyId, 'year' => $year],
                ['next_number' => $nextNumber, 'created_at' => now(), 'updated_at' => now()]
            );
        }

        DB::table('bill_items')->whereNull('deleted_at')->orderBy('id')->chunkById(250, function ($items) {
            $taxes = DB::table('tax_rates')->whereIn('id', $items->pluck('tax_id')->filter())->get()->keyBy('id');
            foreach ($items as $item) {
                $quantity = (float) ($item->quantity ?? 0);
                $kg = (float) ($item->kg ?? 0);
                $gross = ($kg > 0 ? $kg : $quantity) * (float) ($item->price ?? 0);
                $discount = $gross * ((float) ($item->discount ?? 0) / 100);
                $tax = $taxes->get($item->tax_id);
                $taxAmount = ($gross - $discount) * ((float) ($tax->tax_rate ?? 0) / 100);
                DB::table('bill_items')->where('id', $item->id)->update([
                    'unit_of_measure' => $kg > 0 ? 'kg' : 'unit',
                    'tax_name_snapshot' => $tax->name ?? null,
                    'tax_rate_snapshot' => $tax->tax_rate ?? 0,
                    'line_subtotal' => round($gross, 4),
                    'discount_amount' => round($discount, 4),
                    'tax_amount' => round($taxAmount, 4),
                    'total' => round($gross - $discount + $taxAmount, 4),
                ]);
            }
        });

        DB::table('bills')->whereNull('deleted_at')->orderBy('id')->chunkById(250, function ($bills) {
            foreach ($bills as $bill) {
                $payable = (float) ($bill->total_after_discount ?? $bill->total ?? 0);
                $tax = (float) ($bill->tax ?? 0);
                $net = $payable - $tax;
                $discount = max(0, (float) ($bill->sub_total ?? 0) - $net);
                $paid = (float) ($bill->paid_amount ?? 0);
                $remaining = (float) ($bill->remaining_amount ?? max(0, $payable - $paid));
                $posted = DB::table('transactions')
                    ->where('company_id', $bill->company_id)
                    ->where('reference_id', (string) $bill->id)
                    ->whereIn('type', ['Bill', 'BillPayment'])
                    ->where('status', 1)
                    ->whereNull('deleted_at')
                    ->exists();

                DB::table('bills')->where('id', $bill->id)->update([
                    'discount_total' => round($discount, 4),
                    'net_before_tax' => round($net, 4),
                    'grand_total' => round($payable, 4),
                    'base_currency_total' => round($payable, 4),
                    'vendor_invoice_date' => $bill->date,
                    'posting_date' => $bill->date,
                    'due_date' => $bill->date ? date('Y-m-d', strtotime($bill->date . ' +30 days')) : null,
                    'payment_status' => $remaining <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'),
                    'approval_status' => ($bill->approval_status === 'paid' || ($bill->approval_status === 'draft' && $posted))
                        ? 'approved'
                        : $bill->approval_status,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_invoice_versions');
        Schema::dropIfExists('bill_invoice_deliveries');
        Schema::dropIfExists('bill_invoice_sequences');

        Schema::table('vendors', fn (Blueprint $table) => $table->dropColumn('payment_terms_days'));
        Schema::table('bill_attachments', fn (Blueprint $table) => $table->dropColumn(['original_name', 'mime_type', 'file_size', 'storage_disk', 'checksum']));
        Schema::table('bill_items', function (Blueprint $table) {
            $table->integer('quantity')->nullable()->change();
            $table->integer('kg')->nullable()->change();
            $table->dropColumn(['unit_of_measure', 'tax_name_snapshot', 'tax_rate_snapshot', 'line_subtotal', 'discount_amount', 'tax_amount']);
        });
        Schema::table('bills', function (Blueprint $table) {
            $table->dropUnique('bills_company_vendor_invoice_unique');
            $table->dropUnique('bills_company_unique_no_unique');
            $table->dropIndex('bills_company_payment_status_idx');
            $table->dropConstrainedForeignId('withholding_account_id');
            $table->dropConstrainedForeignId('voided_by');
            $table->dropColumn([
                'discount_total', 'net_before_tax', 'grand_total', 'withholding_tax', 'base_currency_total',
                'payment_status', 'email_on_approval', 'vendor_invoice_date', 'posting_date', 'due_date',
                'exchange_rate', 'purchase_order_no', 'goods_receipt_no', 'project', 'cost_centre',
                'department', 'warehouse', 'vendor_tax_invoice_reference', 'voided_at', 'void_reason',
            ]);
        });
    }
};
