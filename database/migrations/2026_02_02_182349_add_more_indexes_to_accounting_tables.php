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
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->index('company_id');
            $table->index('accounting_period_id');
            $table->index('account_id');
            $table->index('unique_no');
            $table->index('date');
        });

        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->index('account_number');
            $table->index('name');
            $table->index('category');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->index('name');
            // company_id index might already be created by foreign key, but being explicit is safer if not already present.
            // However, to avoid "already exists", let's check or just use name for now.
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
            $table->dropIndex(['accounting_period_id']);
            $table->dropIndex(['account_id']);
            $table->dropIndex(['unique_no']);
            $table->dropIndex(['date']);
        });

        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->dropIndex(['account_number']);
            $table->dropIndex(['name']);
            $table->dropIndex(['category']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['name']);
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->dropIndex(['name']);
        });
    }
};
