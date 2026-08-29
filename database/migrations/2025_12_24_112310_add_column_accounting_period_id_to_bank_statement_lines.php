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
        Schema::table('bank_statement_lines', function (Blueprint $table) {
            $table->foreignId('accounting_period_id')->nullable()->constrained('accounting_periods')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_statement_lines', function (Blueprint $table) {
            $table->dropForeign(['accounting_period_id']);
            $table->dropIndex(['accounting_period_id']);
            $table->dropColumn('accounting_period_id');
        });
    }
};
