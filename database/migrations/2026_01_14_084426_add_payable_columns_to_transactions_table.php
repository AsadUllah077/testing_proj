<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['payable_type']);
            // Polymorphic payable columns
            $table->string('payable_type')->nullable()->after('vendor_id');
            $table->unsignedBigInteger('payable_id')->nullable()->after('payable_type');

            // Optional index for performance
            $table->index(['payable_type', 'payable_id'], 'transactions_payable_index');
        });

        /**
         * ------------------------------------------------------------
         * BACKFILL EXISTING VENDOR DATA (VERY IMPORTANT)
         * ------------------------------------------------------------
         * Convert old vendor_id records to polymorphic payables
         */
        DB::statement("
            UPDATE transactions
            SET payable_type = 'vendor',
                payable_id   = vendor_id
            WHERE vendor_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_payable_index');
            $table->dropColumn(['payable_type', 'payable_id']);
        });
    }
};
