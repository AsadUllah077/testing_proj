<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tax_periods', function (Blueprint $table) {
            $table->string('approval_status')->default('draft')->after('status');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_note')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('settlement_reference')->nullable();
            $table->string('tax_authority')->nullable();
            $table->string('jurisdiction')->nullable();
            $table->string('registration_number')->nullable();
            $table->string('filing_type')->nullable();
            $table->string('filing_reference')->nullable();
            $table->date('filed_at')->nullable();
            $table->string('filing_status')->default('not_filed');
            $table->string('filing_attachment')->nullable();
            $table->index(['company_id', 'approval_status']);
            $table->index(['company_id', 'filing_status']);
        });

        Schema::create('tax_settlement_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tax_period_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->string('status')->default('posted');
            $table->date('settlement_date');
            $table->decimal('output_tax', 18, 4)->default(0);
            $table->decimal('input_tax', 18, 4)->default(0);
            $table->decimal('carried_forward_amount', 18, 4)->default(0);
            $table->decimal('net_amount', 18, 4)->default(0);
            $table->json('snapshot');
            $table->text('reversal_reason')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['tax_period_id', 'status']);
        });

        Schema::create('tax_payment_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tax_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_account_id')->constrained('chart_of_accounts')->restrictOnDelete();
            $table->string('reference')->unique();
            $table->enum('direction', ['payment', 'refund']);
            $table->date('payment_date');
            $table->decimal('amount', 18, 4);
            $table->string('external_reference')->nullable();
            $table->string('attachment')->nullable();
            $table->string('status')->default('posted');
            $table->text('reversal_reason')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['tax_period_id', 'status']);
        });

        Schema::create('liability_payment_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_account_id')->constrained('chart_of_accounts')->restrictOnDelete();
            $table->string('reference')->unique();
            $table->date('payment_date');
            $table->decimal('total_amount', 18, 4)->default(0);
            $table->string('payee')->nullable();
            $table->string('external_reference')->nullable();
            $table->string('attachment')->nullable();
            $table->string('status')->default('posted');
            $table->text('reversal_reason')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('liability_payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('liability_payment_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('chart_of_accounts')->restrictOnDelete();
            $table->string('source_reference')->nullable();
            $table->string('source_type')->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('amount', 18, 4);
            $table->timestamps();
            $table->index(['company_id', 'account_id', 'source_reference']);
        });

        DB::table('tax_periods')->whereNull('deleted_at')->orderBy('id')->each(function ($period) {
            DB::table('tax_periods')->where('id', $period->id)->update([
                'approval_status' => in_array($period->status, ['settled', 'paid', 'partial_paid'], true) ? 'approved' : 'draft',
                'settlement_reference' => $period->status === 'open' ? null : $period->unique_no,
                'locked_at' => in_array($period->status, ['settled', 'paid', 'partial_paid'], true) ? now() : null,
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liability_payment_allocations');
        Schema::dropIfExists('liability_payment_batches');
        Schema::dropIfExists('tax_payment_batches');
        Schema::dropIfExists('tax_settlement_batches');
        Schema::table('tax_periods', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'approval_status']);
            $table->dropIndex(['company_id', 'filing_status']);
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropConstrainedForeignId('locked_by');
            $table->dropColumn(['approval_status', 'reviewed_at', 'approval_note', 'locked_at', 'settlement_reference', 'tax_authority', 'jurisdiction', 'registration_number', 'filing_type', 'filing_reference', 'filed_at', 'filing_status', 'filing_attachment']);
        });
    }
};
