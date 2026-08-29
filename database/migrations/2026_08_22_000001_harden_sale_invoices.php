<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('discount_total', 18, 4)->default(0)->after('sub_total');
            $table->decimal('net_before_tax', 18, 4)->default(0)->after('discount_total');
            $table->decimal('grand_total', 18, 4)->default(0)->after('tax');
            $table->string('payment_status')->default('unpaid')->after('approval_status');
            $table->boolean('email_on_approval')->default(false)->after('payment_status');
            $table->date('due_date')->nullable()->after('date');
            $table->decimal('exchange_rate', 18, 8)->default(1)->after('currency_id');
            $table->decimal('base_currency_total', 18, 4)->default(0)->after('grand_total');
            $table->string('customer_po_no')->nullable()->after('ref_no');
            $table->text('delivery_address')->nullable()->after('customer_po_no');
            $table->foreignId('salesperson_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cost_centre')->nullable();
            $table->string('project')->nullable();
            $table->string('warehouse')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('void_reason')->nullable();

            $table->index(['company_id', 'payment_status'], 'sales_company_payment_status_idx');
            $table->unique(['company_id', 'invoice_no'], 'sales_company_invoice_unique');
            $table->unique(['company_id', 'unique_no'], 'sales_company_unique_no_unique');
        });

        DB::table('sales')->orderBy('id')->chunkById(250, function ($sales) {
            foreach ($sales as $sale) {
                $grandTotal = (float) ($sale->total_after_discount ?? $sale->total ?? 0);
                $tax = (float) ($sale->tax ?? 0);
                $netBeforeTax = $grandTotal - $tax;
                $discount = max(0, (float) ($sale->sub_total ?? 0) - $netBeforeTax);
                $paid = (float) ($sale->paid_amount ?? 0);
                $remaining = (float) ($sale->remaining_amount ?? max(0, $grandTotal - $paid));
                $hasPostedJournal = DB::table('transactions')
                    ->where('company_id', $sale->company_id)
                    ->where('reference_id', $sale->id)
                    ->where('type', 'Sale')
                    ->where('status', 1)
                    ->whereNull('deleted_at')
                    ->exists();

                DB::table('sales')->where('id', $sale->id)->update([
                    'discount_total' => $discount,
                    'net_before_tax' => $netBeforeTax,
                    'grand_total' => $grandTotal,
                    'base_currency_total' => $grandTotal,
                    'payment_status' => $remaining <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'),
                    'approval_status' => $sale->approval_status === 'draft' && $hasPostedJournal
                        ? 'approved'
                        : $sale->approval_status,
                ]);
            }
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('kg', 18, 4)->nullable()->change();
            $table->string('unit_of_measure', 30)->default('unit')->after('kg');
            $table->string('tax_name_snapshot')->nullable()->after('tax_id');
            $table->decimal('tax_rate_snapshot', 9, 4)->default(0)->after('tax_name_snapshot');
            $table->decimal('line_subtotal', 18, 4)->default(0)->after('tax_rate_snapshot');
            $table->decimal('discount_amount', 18, 4)->default(0)->after('line_subtotal');
            $table->decimal('tax_amount', 18, 4)->default(0)->after('discount_amount');
        });

        Schema::table('sale_attachments', function (Blueprint $table) {
            $table->string('original_name')->nullable()->after('attachment');
            $table->string('storage_disk')->default('public')->after('original_name');
            $table->string('mime_type')->nullable()->after('original_name');
            $table->unsignedBigInteger('file_size')->nullable()->after('mime_type');
        });

        Schema::create('sale_invoice_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedBigInteger('next_number')->default(1);
            $table->timestamps();
            $table->unique(['company_id', 'year']);
        });

        Schema::create('sale_invoice_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->string('channel')->default('email');
            $table->string('recipient');
            $table->string('status')->default('queued');
            $table->text('failure_reason')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['sale_id', 'status']);
        });

        Schema::create('sale_invoice_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('event');
            $table->json('snapshot');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['sale_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_invoice_versions');
        Schema::dropIfExists('sale_invoice_deliveries');
        Schema::dropIfExists('sale_invoice_sequences');

        Schema::table('sale_attachments', function (Blueprint $table) {
            $table->dropColumn(['original_name', 'storage_disk', 'mime_type', 'file_size']);
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->integer('kg')->nullable()->change();
            $table->dropColumn([
                'unit_of_measure', 'tax_name_snapshot', 'tax_rate_snapshot',
                'line_subtotal', 'discount_amount', 'tax_amount',
            ]);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique('sales_company_invoice_unique');
            $table->dropUnique('sales_company_unique_no_unique');
            $table->dropIndex('sales_company_payment_status_idx');
            $table->dropConstrainedForeignId('salesperson_id');
            $table->dropConstrainedForeignId('voided_by');
            $table->dropColumn([
                'discount_total', 'net_before_tax', 'grand_total', 'payment_status', 'email_on_approval',
                'due_date', 'exchange_rate', 'base_currency_total', 'customer_po_no',
                'delivery_address', 'cost_centre', 'project', 'warehouse',
                'voided_at', 'void_reason',
            ]);
        });
    }
};
