<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_statement_lines', function (Blueprint $table) {
            $table->dropForeign(['bank_account_id']);
            $table->foreign('bank_account_id')->references('id')->on('chart_of_accounts')->restrictOnDelete();
        });

        Schema::create('bank_statement_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_account_id')->constrained('chart_of_accounts')->restrictOnDelete();
            $table->string('statement_number')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('opening_balance', 18, 4)->default(0);
            $table->decimal('closing_balance', 18, 4);
            $table->string('currency', 3)->nullable();
            $table->string('source_filename')->nullable();
            $table->string('file_hash', 64)->nullable();
            $table->unsignedInteger('line_count')->default(0);
            $table->enum('status', ['draft', 'in_progress', 'reviewed', 'finalized', 'reopened'])->default('draft');
            $table->text('review_note')->nullable();
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('imported_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->foreignId('reopened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reopened_at')->nullable();
            $table->text('reopen_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'bank_account_id', 'statement_number'], 'bank_statement_identity_unique');
            $table->unique(['company_id', 'bank_account_id', 'file_hash'], 'bank_statement_file_unique');
            $table->index(['company_id', 'bank_account_id', 'status'], 'bank_statement_workflow_idx');
        });

        Schema::table('bank_statement_lines', function (Blueprint $table) {
            $table->foreignId('bank_statement_import_id')->nullable()->after('bank_account_id')
                ->constrained('bank_statement_imports')->nullOnDelete();
            $table->string('row_hash', 64)->nullable()->after('unique_no');
            $table->foreignId('reconciled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reconciled_at')->nullable();
            $table->foreignId('unreconciled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('unreconciled_at')->nullable();
            $table->text('unreconcile_reason')->nullable();
            $table->unsignedTinyInteger('match_confidence')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->unique(['company_id', 'unique_no'], 'bank_statement_company_unique_no_unique');
            $table->unique(['bank_statement_import_id', 'row_hash'], 'bank_statement_import_row_unique');
        });

        Schema::table('bank_rules', function (Blueprint $table) {
            $table->unsignedInteger('accepted_count')->default(0);
            $table->unsignedInteger('rejected_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('last_rejected_at')->nullable();
            $table->string('tax_code')->nullable();
            $table->string('tracking_category')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
        });

        Schema::create('bank_reconciliation_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_statement_line_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->nullableMorphs('matchable');
            $table->decimal('amount', 18, 4);
            $table->enum('origin', ['existing', 'generated', 'adjustment', 'payroll'])->default('existing');
            $table->string('posting_unique_no')->nullable();
            $table->unsignedTinyInteger('confidence')->nullable();
            $table->foreignId('matched_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('matched_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->string('reversal_unique_no')->nullable();
            $table->timestamps();
            $table->index(['bank_statement_line_id', 'reversed_at'], 'bank_reconciliation_active_match_idx');
            $table->index(['company_id', 'posting_unique_no'], 'bank_reconciliation_posting_idx');
        });

        Schema::create('bank_reconciliation_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_statement_line_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('bank_statement_import_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event');
            $table->json('metadata')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['company_id', 'event', 'created_at'], 'bank_reconciliation_event_idx');
        });

        DB::table('bank_statement_lines')->whereNull('remaining_amount')->update([
            'remaining_amount' => DB::raw('amount'),
        ]);

        DB::table('bank_statement_lines')->whereNull('accounting_period_id')->orderBy('id')->chunkById(200, function ($lines) {
            foreach ($lines as $line) {
                $periodId = DB::table('accounting_periods')
                    ->where('company_id', $line->company_id)
                    ->whereDate('start_date', '<=', $line->date)
                    ->whereDate('end_date', '>=', $line->date)
                    ->value('id');
                if ($periodId) DB::table('bank_statement_lines')->where('id', $line->id)->update(['accounting_period_id' => $periodId]);
            }
        });

        $orphaned = DB::table('bank_statement_lines as bsl')
            ->where('bsl.status', 'Reconciled')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')->from('transactions as txn')
                    ->whereColumn('txn.bank_statement_line_id', 'bsl.id')
                    ->where('txn.status', 1)->whereNull('txn.deleted_at');
            })->select('bsl.id', 'bsl.company_id', 'bsl.bank_statement_import_id')->get();

        foreach ($orphaned as $line) {
            DB::table('bank_statement_lines')->where('id', $line->id)->update([
                'status' => 'Unreconciled', 'remaining_amount' => DB::raw('amount'),
                'unreconciled_at' => now(), 'unreconcile_reason' => 'Integrity repair: reconciled line had no active linked transaction.',
            ]);
            DB::table('bank_reconciliation_events')->insert([
                'company_id' => $line->company_id, 'bank_statement_line_id' => $line->id,
                'bank_statement_import_id' => $line->bank_statement_import_id, 'event' => 'integrity_repair',
                'metadata' => json_encode(['reason' => 'orphaned_reconciliation']), 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_reconciliation_events');
        Schema::dropIfExists('bank_reconciliation_matches');
        Schema::table('bank_rules', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['accepted_count', 'rejected_count', 'last_used_at', 'last_rejected_at', 'tax_code', 'tracking_category', 'approved_by', 'approved_at']);
        });
        Schema::table('bank_statement_lines', function (Blueprint $table) {
            $table->dropUnique('bank_statement_company_unique_no_unique');
            $table->dropUnique('bank_statement_import_row_unique');
            $table->dropForeign(['bank_statement_import_id']);
            $table->dropForeign(['reconciled_by']);
            $table->dropForeign(['unreconciled_by']);
            $table->dropColumn([
                'bank_statement_import_id', 'row_hash', 'reconciled_by', 'reconciled_at',
                'unreconciled_by', 'unreconciled_at', 'unreconcile_reason', 'match_confidence', 'is_locked',
            ]);
        });
        Schema::dropIfExists('bank_statement_imports');
        Schema::table('bank_statement_lines', function (Blueprint $table) {
            $table->dropForeign(['bank_account_id']);
            $table->foreign('bank_account_id')->references('id')->on('chart_of_accounts')->cascadeOnDelete();
        });
    }
};
