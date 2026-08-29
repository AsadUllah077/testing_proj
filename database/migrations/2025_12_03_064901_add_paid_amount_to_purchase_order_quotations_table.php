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
        Schema::table('purchase_order_quotations', function (Blueprint $table) {
             $table->decimal('paid_amount', 15, 2)
                  ->default(0.00)
                  ->comment('paid_amount amount in currency')
                  ->after('sub_total');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_order_quotations', function (Blueprint $table) {
            $table->dropColumn('paid_amount');
        });
    }
};
