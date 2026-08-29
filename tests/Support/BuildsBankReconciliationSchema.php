<?php

namespace Tests\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

trait BuildsBankReconciliationSchema
{
    protected function buildBankReconciliationSchema(): void
    {
        foreach (['activity_logs', 'bank_reconciliation_events', 'bank_reconciliation_matches', 'journal_entries', 'transactions', 'bank_statement_lines', 'bank_statement_imports', 'accounting_periods', 'chart_of_accounts', 'companies'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('companies', function (Blueprint $t) {
            $t->id(); $t->string('name')->nullable();
        });
        Schema::create('chart_of_accounts', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('company_id')->nullable(); $t->integer('account_number'); $t->string('name');
            $t->string('category'); $t->string('type')->default('Account'); $t->string('sub_type')->nullable();
            $t->boolean('is_system')->default(false); $t->decimal('initial_balance', 18, 4)->default(0); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('accounting_periods', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('company_id'); $t->string('name'); $t->date('start_date'); $t->date('end_date');
            $t->boolean('is_active')->default(true); $t->boolean('is_locked')->default(false); $t->timestamps();
        });
        Schema::create('bank_statement_imports', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('company_id'); $t->unsignedBigInteger('bank_account_id'); $t->string('statement_number')->nullable();
            $t->date('start_date'); $t->date('end_date'); $t->decimal('opening_balance', 18, 4)->default(0); $t->decimal('closing_balance', 18, 4);
            $t->string('currency', 3)->nullable(); $t->string('source_filename')->nullable(); $t->string('file_hash', 64)->nullable();
            $t->unsignedInteger('line_count')->default(0); $t->string('status')->default('draft'); $t->text('review_note')->nullable();
            $t->unsignedBigInteger('imported_by')->nullable(); $t->timestamp('imported_at')->nullable(); $t->unsignedBigInteger('reviewed_by')->nullable();
            $t->timestamp('reviewed_at')->nullable(); $t->unsignedBigInteger('finalized_by')->nullable(); $t->timestamp('finalized_at')->nullable();
            $t->unsignedBigInteger('reopened_by')->nullable(); $t->timestamp('reopened_at')->nullable(); $t->text('reopen_reason')->nullable();
            $t->timestamps(); $t->softDeletes(); $t->unique(['company_id', 'bank_account_id', 'statement_number']);
        });
        Schema::create('bank_statement_lines', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('company_id'); $t->unsignedBigInteger('accounting_period_id')->nullable();
            $t->unsignedBigInteger('bank_account_id'); $t->unsignedBigInteger('bank_statement_import_id')->nullable(); $t->date('date');
            $t->decimal('amount', 18, 4); $t->decimal('remaining_amount', 18, 4)->nullable(); $t->string('payee')->nullable();
            $t->text('description')->nullable(); $t->string('reference')->nullable(); $t->unsignedBigInteger('transaction_id')->nullable();
            $t->string('status')->default('Unreconciled'); $t->text('note')->nullable(); $t->string('unique_no')->nullable(); $t->string('row_hash', 64)->nullable();
            $t->unsignedBigInteger('reconciled_by')->nullable(); $t->timestamp('reconciled_at')->nullable(); $t->unsignedBigInteger('unreconciled_by')->nullable();
            $t->timestamp('unreconciled_at')->nullable(); $t->text('unreconcile_reason')->nullable(); $t->unsignedTinyInteger('match_confidence')->nullable();
            $t->boolean('is_locked')->default(false); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('transactions', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('company_id'); $t->unsignedBigInteger('accounting_period_id')->nullable(); $t->unsignedBigInteger('account_id');
            $t->unsignedBigInteger('vendor_id')->nullable(); $t->unsignedBigInteger('customer_id')->nullable(); $t->unsignedBigInteger('reference_id')->nullable();
            $t->string('unique_no')->nullable(); $t->string('account_label')->nullable(); $t->string('invoice_no')->nullable(); $t->date('date'); $t->string('type')->nullable();
            $t->string('source_system')->nullable(); $t->string('source_reference')->nullable(); $t->string('posting_idempotency_key')->nullable();
            $t->boolean('is_opening_balance')->default(false); $t->unsignedBigInteger('reversal_of_transaction_id')->nullable(); $t->string('reversal_of_unique_no')->nullable();
            $t->string('correction_type')->nullable(); $t->unsignedBigInteger('accounting_repair_run_id')->nullable(); $t->text('description')->nullable();
            $t->decimal('debit', 18, 4)->default(0); $t->decimal('credit', 18, 4)->default(0); $t->unsignedBigInteger('created_by')->nullable();
            $t->unsignedBigInteger('updated_by')->nullable(); $t->unsignedBigInteger('posted_by')->nullable(); $t->timestamp('posted_at')->nullable();
            $t->integer('status')->default(1); $t->integer('credit_note_status')->nullable(); $t->unsignedBigInteger('bank_statement_line_id')->nullable();
            $t->unsignedBigInteger('opening_balance_batch_id')->nullable(); $t->unsignedBigInteger('payable_id')->nullable(); $t->string('payable_type')->nullable();
            $t->timestamps(); $t->softDeletes();
        });
        Schema::create('journal_entries', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('company_id'); $t->unsignedBigInteger('accounting_period_id')->nullable(); $t->unsignedBigInteger('account_id');
            $t->date('date'); $t->string('unique_no')->nullable(); $t->text('narration')->nullable(); $t->text('description')->nullable();
            $t->decimal('debit', 18, 4)->default(0); $t->decimal('credit', 18, 4)->default(0); $t->string('posting_status')->nullable();
            $t->unsignedBigInteger('created_by')->nullable(); $t->unsignedBigInteger('updated_by')->nullable(); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('bank_reconciliation_matches', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('company_id'); $t->unsignedBigInteger('bank_statement_line_id'); $t->unsignedBigInteger('transaction_id')->nullable();
            $t->string('matchable_type')->nullable(); $t->unsignedBigInteger('matchable_id')->nullable(); $t->decimal('amount', 18, 4); $t->string('origin');
            $t->string('posting_unique_no')->nullable(); $t->unsignedTinyInteger('confidence')->nullable(); $t->unsignedBigInteger('matched_by')->nullable();
            $t->timestamp('matched_at')->nullable(); $t->unsignedBigInteger('reversed_by')->nullable(); $t->timestamp('reversed_at')->nullable();
            $t->text('reversal_reason')->nullable(); $t->string('reversal_unique_no')->nullable(); $t->timestamps();
        });
        Schema::create('bank_reconciliation_events', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('company_id'); $t->unsignedBigInteger('bank_statement_line_id')->nullable();
            $t->unsignedBigInteger('bank_statement_import_id')->nullable(); $t->string('event'); $t->json('metadata')->nullable();
            $t->unsignedBigInteger('user_id')->nullable(); $t->timestamps();
        });
        Schema::create('activity_logs', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('company_id')->nullable(); $t->string('loggable_type'); $t->unsignedBigInteger('loggable_id');
            $t->unsignedBigInteger('user_id')->nullable(); $t->unsignedBigInteger('employee_id')->nullable(); $t->string('action'); $t->json('changes')->nullable(); $t->timestamps();
        });
    }
}
