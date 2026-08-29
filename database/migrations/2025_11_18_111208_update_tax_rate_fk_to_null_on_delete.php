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
        Schema::table('accounting_periods', function (Blueprint $table) {
            $table->dropForeign(['tax_rate_id']);

            $table->foreign('tax_rate_id')
                ->references('id')
                ->on('tax_rates')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounting_periods', function (Blueprint $table) {
            $table->dropForeign(['tax_rate_id']);

            $table->foreign('tax_rate_id')
                ->references('id')
                ->on('tax_rates')
                ->cascadeOnDelete();
        });
    }
};
