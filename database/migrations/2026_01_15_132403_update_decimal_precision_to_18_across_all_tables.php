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
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->decimal('initial_balance', 18, 4)->nullable()->change();
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->decimal('debit', 18, 4)->nullable()->change();
            $table->decimal('credit', 18, 4)->nullable()->change();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('debit', 18, 4)->nullable()->change();
            $table->decimal('credit', 18, 4)->nullable()->change();
        });

        Schema::table('bank_statement_lines', function (Blueprint $table) {
            $table->decimal('amount', 18, 4)->nullable()->change();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('sub_total', 18, 4)->nullable()->change();
            $table->decimal('tax', 18, 4)->nullable()->change();
            $table->decimal('total', 18, 4)->nullable()->change();
            $table->decimal('paid_amount', 18, 4)->nullable()->change();
            $table->decimal('remaining_amount', 18, 4)->nullable()->change();
            $table->decimal('total_after_discount', 18, 4)->nullable()->change();
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('price', 18, 4)->nullable()->change();
            $table->decimal('total', 18, 4)->nullable()->change();
            $table->decimal('discount', 18, 4)->nullable()->change();
        });

        Schema::table('credit_notes', function (Blueprint $table) {
            $table->decimal('sub_total', 18, 4)->nullable()->change();
            $table->decimal('tax', 18, 4)->nullable()->change();
            $table->decimal('total', 18, 4)->nullable()->change();
            $table->decimal('paid_amount', 18, 4)->nullable()->change();
            $table->decimal('remaining_amount', 18, 4)->nullable()->change();
            $table->decimal('total_after_discount', 18, 4)->nullable()->change();
            $table->decimal('discount', 18, 4)->nullable()->change();
        });

        Schema::table('credit_note_items', function (Blueprint $table) {
            $table->decimal('price', 18, 4)->nullable()->change();
            $table->decimal('total', 18, 4)->nullable()->change();
            $table->decimal('discount', 18, 4)->nullable()->change();
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->decimal('sub_total', 18, 4)->nullable()->change();
            $table->decimal('tax', 18, 4)->nullable()->change();
            $table->decimal('total', 18, 4)->nullable()->change();
            $table->decimal('paid_amount', 18, 4)->nullable()->change();
            $table->decimal('remaining_amount', 18, 4)->nullable()->change();
            $table->decimal('total_after_discount', 18, 4)->nullable()->change();
        });

        Schema::table('bill_items', function (Blueprint $table) {
            $table->decimal('price', 18, 4)->nullable()->change();
            $table->decimal('total', 18, 4)->nullable()->change();
            $table->decimal('discount', 18, 4)->nullable()->change();
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->decimal('sub_total', 18, 4)->nullable()->change();
            $table->decimal('total_discount', 18, 4)->nullable()->change();
            $table->decimal('tax', 18, 4)->nullable()->change();
            $table->decimal('total', 18, 4)->nullable()->change();
            $table->decimal('remaining_amount', 18, 4)->nullable()->change();
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->decimal('price', 18, 4)->nullable()->change();
            $table->decimal('total', 18, 4)->nullable()->change();
        });

        Schema::table('purchase_order_quotations', function (Blueprint $table) {
            $table->decimal('sub_total', 18, 4)->nullable()->change();
            $table->decimal('total_discount', 18, 4)->nullable()->change();
            $table->decimal('tax', 18, 4)->nullable()->change();
            $table->decimal('total', 18, 4)->nullable()->change();
            $table->decimal('remaining_amount', 18, 4)->nullable()->change();
        });

        Schema::table('purchase_order_quotation_items', function (Blueprint $table) {
            $table->decimal('price', 18, 4)->nullable()->change();
            $table->decimal('total', 18, 4)->nullable()->change();
            $table->decimal('discount', 18, 4)->nullable()->change();
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->decimal('salary', 18, 4)->nullable()->change();
        });

        Schema::table('employee_pay_structures', function (Blueprint $table) {
            $table->decimal('amount', 18, 4)->nullable()->change();
        });

        Schema::table('payslips', function (Blueprint $table) {
            $table->decimal('gross_earnings', 18, 4)->nullable()->change();
            $table->decimal('total_deductions', 18, 4)->nullable()->change();
            $table->decimal('net_pay', 18, 4)->nullable()->change();
        });

        Schema::table('payslip_items', function (Blueprint $table) {
            $table->decimal('amount', 18, 4)->nullable()->change();
        });

        Schema::table('tax_rates', function (Blueprint $table) {
            $table->decimal('tax_rate', 18, 4)->nullable()->change();
        });

        Schema::table('invoice_templates', function (Blueprint $table) {
            $table->decimal('top_margin', 18, 4)->nullable()->change();
            $table->decimal('bottom_margin', 18, 4)->nullable()->change();
            $table->decimal('address_padding', 18, 4)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverting to some common previous values if needed, 
        // but often it's not strictly necessary to go back to specific old precisions 
        // unless there's a specific reason. 18,4 is generally safe.
    }
};
