<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Xero-style system account tagging.
     *
     * system_account_type — Identifies the ROLE of system-critical accounts
     *   (e.g. ACCOUNTS_RECEIVABLE, WAGES_PAYABLE). Only ~10 accounts get tagged.
     *
     * sub_type — Classifies ALL accounts for reporting purposes
     *   (e.g. BANK, FIXED_ASSET, CURRENT_LIABILITY, INVENTORY, DEPRECIATION).
     *   Replaces all hardcoded account number ranges in reports.
     */
    public function up(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->string('system_account_type')->nullable()->after('category')
                  ->comment('Xero-style role tag: ACCOUNTS_RECEIVABLE, ACCOUNTS_PAYABLE, SALES_TAX, etc.');
            $table->string('sub_type')->nullable()->after('system_account_type')
                  ->comment('Classification for reports: BANK, FIXED_ASSET, CURRENT_LIABILITY, INVENTORY, etc.');

            $table->index('system_account_type');
            $table->index('sub_type');
        });
    }

    public function down(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->dropIndex(['system_account_type']);
            $table->dropIndex(['sub_type']);
            $table->dropColumn(['system_account_type', 'sub_type']);
        });
    }
};
