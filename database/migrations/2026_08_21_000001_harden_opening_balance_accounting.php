<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('opening_balance_batches', function (Blueprint $table) {
            $table->string('idempotency_key', 100)->nullable()->after('reference');
            $table->string('journal_unique_no')->nullable()->after('idempotency_key');
            $table->string('source_file_reference')->nullable()->after('source_system');
            $table->foreignId('reviewed_by')->nullable()->after('posted_by')->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->after('reviewed_by')->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at')->nullable()->after('posted_at');
            $table->unique(['company_id', 'idempotency_key'], 'opening_batch_company_idempotency_unique');
            $table->unique(['company_id', 'journal_unique_no'], 'opening_batch_company_journal_unique');
            $table->index(['company_id', 'conversion_date', 'status'], 'opening_batch_scope_lookup');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->string('posting_idempotency_key', 160)->nullable()->after('source_reference');
            $table->string('correction_type', 30)->nullable()->after('reversal_of_unique_no');
            $table->foreignId('accounting_repair_run_id')->nullable()->after('correction_type');
            $table->foreignId('posted_by')->nullable()->after('updated_by')->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable()->after('posted_by');
            $table->unique(['company_id', 'posting_idempotency_key'], 'transaction_company_posting_idempotency_unique');
            $table->index(['company_id', 'is_opening_balance', 'date'], 'transaction_opening_audit_lookup');
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->string('posting_status', 20)->default('posted')->after('credit');
            $table->string('reversal_of_unique_no')->nullable()->after('posting_status')->index();
            $table->string('correction_type', 30)->nullable()->after('reversal_of_unique_no');
            $table->foreignId('posted_by')->nullable()->after('updated_by')->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable()->after('posted_by');
        });

        // A non-unique audit index is deploy-safe when production may already
        // contain duplicates. The repair command blocks duplicate system roles;
        // a unique constraint can be added after the dry-run is clean.
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->index(['company_id', 'system_account_type'], 'chart_company_system_account_lookup');
        });

        Schema::create('accounting_repair_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('repair_type', 60)->default('opening_balances');
            $table->json('parameters');
            $table->boolean('dry_run')->default(true);
            $table->string('status', 30)->index();
            $table->string('backup_reference')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('summary')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'repair_type', 'status'], 'repair_run_active_scope');
        });

        Schema::create('accounting_repair_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_run_id')->constrained('accounting_repair_runs')->cascadeOnDelete();
            $table->string('issue_type', 80)->index();
            $table->string('severity', 30)->index();
            $table->string('entity_type', 80)->nullable();
            $table->string('entity_id')->nullable();
            $table->json('related_entity_ids')->nullable();
            $table->json('before_snapshot')->nullable();
            $table->json('authoritative_target')->nullable();
            $table->string('proposed_action', 80);
            $table->json('after_snapshot')->nullable();
            $table->string('result', 40)->nullable();
            $table->text('error_message')->nullable();
            $table->string('idempotency_key', 160);
            $table->timestamps();
            $table->unique(['repair_run_id', 'idempotency_key'], 'repair_item_run_idempotency_unique');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreign('accounting_repair_run_id')->references('id')->on('accounting_repair_runs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['accounting_repair_run_id']);
            $table->dropUnique('transaction_company_posting_idempotency_unique');
            $table->dropIndex('transaction_opening_audit_lookup');
            $table->dropColumn(['posting_idempotency_key', 'correction_type', 'accounting_repair_run_id', 'posted_by', 'posted_at']);
        });
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropIndex(['reversal_of_unique_no']);
            $table->dropConstrainedForeignId('posted_by');
            $table->dropColumn(['posting_status', 'reversal_of_unique_no', 'correction_type', 'posted_at']);
        });
        Schema::dropIfExists('accounting_repair_items');
        Schema::dropIfExists('accounting_repair_runs');
        Schema::table('chart_of_accounts', fn (Blueprint $table) => $table->dropIndex('chart_company_system_account_lookup'));
        Schema::table('opening_balance_batches', function (Blueprint $table) {
            $table->dropUnique('opening_batch_company_idempotency_unique');
            $table->dropUnique('opening_batch_company_journal_unique');
            $table->dropIndex('opening_batch_scope_lookup');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['idempotency_key', 'journal_unique_no', 'source_file_reference', 'locked_at']);
        });
    }
};
