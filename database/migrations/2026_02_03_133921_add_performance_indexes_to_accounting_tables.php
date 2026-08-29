<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $blueprint) {
            // Speed up ledger and financial reports
            $blueprint->index(['account_id', 'date', 'status'], 'idx_transactions_acc_date_status');

            // Speed up Aged Payables/Receivables
            $blueprint->index(['payable_type', 'payable_id'], 'idx_transactions_payable');
            $blueprint->index(['customer_id'], 'idx_transactions_customer');

            // Speed up lookups by unique_no
            $blueprint->index(['unique_no'], 'idx_transactions_unique_no');
        });

        Schema::table('bank_statement_lines', function (Blueprint $blueprint) {
            // Speed up bank reconciliation index and matching
            $blueprint->index(['bank_account_id', 'date', 'status'], 'idx_bank_stmt_lines_acc_date_status');
            $blueprint->index(['unique_no'], 'idx_bank_stmt_lines_unique_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $blueprint) {
            $blueprint->dropIndex('idx_transactions_acc_date_status');
            $blueprint->dropIndex('idx_transactions_payable');
            $blueprint->dropIndex('idx_transactions_customer');
            $blueprint->dropIndex('idx_transactions_unique_no');
        });

        Schema::table('bank_statement_lines', function (Blueprint $blueprint) {
            $blueprint->dropIndex('idx_bank_stmt_lines_acc_date_status');
            $blueprint->dropIndex('idx_bank_stmt_lines_unique_no');
        });
    }
};
