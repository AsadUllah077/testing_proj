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
        // CRITICAL: Add indexes to transactions table (300k+ records)
        // These indexes significantly improve report performance
        Schema::table('transactions', function (Blueprint $table) {
            // Primary index for date range queries (used in all reports)
            $table->index('date', 'idx_transactions_date');
            // Index for account-based filtering (Balance Sheet, P&L, Journal)
            $table->index('account_id', 'idx_transactions_account_id');
            // Composite index for company + status filtering (most common query pattern)
            $table->index(['company_id', 'status'], 'idx_transactions_company_status');
            // Composite index for account + date queries (Balance Sheet, P&L aggregation)
            $table->index(['account_id', 'date'], 'idx_transactions_account_date');
            // Composite index for company + status + date (optimized for date range reports)
            $table->index(['company_id', 'status', 'date'], 'idx_transactions_company_status_date');
            // Index for customer-based queries (Aged Receivables)
            $table->index('customer_id', 'idx_transactions_customer_id');
            // Composite index for payable queries (Aged Payables)
        });

        // Add indexes to sales table for better query performance
        Schema::table('sales', function (Blueprint $table) {
            // Index for customer filter
            $table->index('customer_id', 'idx_sales_customer_id');
            // Index for date range queries
            $table->index('date', 'idx_sales_date');
            // Index for invoice search
            $table->index('invoice_no', 'idx_sales_invoice_no');
            // Composite index for common filter combinations
            $table->index(['customer_id', 'date'], 'idx_sales_customer_date');
        });

        // Add indexes to bills table for better query performance
        Schema::table('bills', function (Blueprint $table) {
            // Index for vendor filter
            $table->index('vendor_id', 'idx_bills_vendor_id');
            // Index for date range queries
            $table->index('date', 'idx_bills_date');
            // Composite index for common filter combinations
            $table->index(['vendor_id', 'date'], 'idx_bills_vendor_date');
        });

        // Add indexes to customers table
        Schema::table('customers', function (Blueprint $table) {
            // Index for search by name
            $table->index('name', 'idx_customers_name');
            // Index for company filter
            $table->index('company_id', 'idx_customers_company_id');
        });

        // Add indexes to chart_of_accounts table
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            // Index for category filtering (Balance Sheet, P&L)
            $table->index('category', 'idx_coa_category');
            // Composite index for category + type (report filtering)
            $table->index(['category', 'type'], 'idx_coa_category_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('idx_transactions_date');
            $table->dropIndex('idx_transactions_account_id');
            $table->dropIndex('idx_transactions_company_status');
            $table->dropIndex('idx_transactions_account_date');
            $table->dropIndex('idx_transactions_company_status_date');
            $table->dropIndex('idx_transactions_customer_id');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('idx_sales_customer_id');
            $table->dropIndex('idx_sales_date');
            $table->dropIndex('idx_sales_invoice_no');
            $table->dropIndex('idx_sales_customer_date');
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->dropIndex('idx_bills_vendor_id');
            $table->dropIndex('idx_bills_date');
            $table->dropIndex('idx_bills_vendor_date');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('idx_customers_name');
            $table->dropIndex('idx_customers_company_id');
        });

        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->dropIndex('idx_coa_category');
            $table->dropIndex('idx_coa_category_type');
        });
    }
};
