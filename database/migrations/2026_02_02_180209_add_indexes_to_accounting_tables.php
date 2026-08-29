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
        Schema::table('bills', function (Blueprint $table) {
            $table->index('company_id');
            $table->index('accounting_period_id');
            $table->index('vendor_id');
            $table->index('account_id');
            $table->index('currency_id');
            $table->index('unique_no');
            $table->index('invoice_no');
            $table->index('date');
            $table->index('status');
            $table->index('ref_no');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->index('company_id');
            $table->index('accounting_period_id');
            $table->index('account_id');
            $table->index('vendor_id');
            $table->index('customer_id');
            $table->index('reference_id');
            $table->index('unique_no');
            $table->index('invoice_no');
            $table->index('date');
            $table->index('type');
            $table->index('status');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->index('ref_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
            $table->dropIndex(['accounting_period_id']);
            $table->dropIndex(['vendor_id']);
            $table->dropIndex(['account_id']);
            $table->dropIndex(['currency_id']);
            $table->dropIndex(['unique_no']);
            $table->dropIndex(['invoice_no']);
            $table->dropIndex(['date']);
            $table->dropIndex(['status']);
            $table->dropIndex(['ref_no']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
            $table->dropIndex(['accounting_period_id']);
            $table->dropIndex(['account_id']);
            $table->dropIndex(['vendor_id']);
            $table->dropIndex(['customer_id']);
            $table->dropIndex(['reference_id']);
            $table->dropIndex(['unique_no']);
            $table->dropIndex(['invoice_no']);
            $table->dropIndex(['date']);
            $table->dropIndex(['type']);
            $table->dropIndex(['status']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['ref_no']);
        });
    }
};
