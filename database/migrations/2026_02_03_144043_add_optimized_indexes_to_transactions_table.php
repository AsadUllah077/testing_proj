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
        Schema::table('transactions', function (Blueprint $table) {
            // Composite index for ledger queries (account_id + date range)
            // This optimizes: WHERE account_id IN (...) AND date >= ? AND date <= ?
            $table->index(['account_id', 'date', 'id'], 'idx_transactions_ledger_query');

            // Composite index for aged payable queries
            // This optimizes: WHERE payable_type = ? AND payable_id = ? AND date <= ?
            $table->index(['payable_type', 'payable_id', 'date'], 'idx_transactions_aged_payable');

            // Composite index for aged receivable queries
            // This optimizes: WHERE customer_id = ? AND date <= ?
            $table->index(['customer_id', 'date'], 'idx_transactions_aged_receivable');

            // Composite index for date-based account queries
            // This optimizes: WHERE date >= ? AND date <= ? AND account_id IN (...)
            $table->index(['date', 'account_id'], 'idx_transactions_date_account');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('idx_transactions_ledger_query');
            $table->dropIndex('idx_transactions_aged_payable');
            $table->dropIndex('idx_transactions_aged_receivable');
            $table->dropIndex('idx_transactions_date_account');
        });
    }
};
