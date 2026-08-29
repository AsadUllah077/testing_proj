<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logistics_lc_payments', function (Blueprint $table) {
            $table->string('payment_type')->default('product_payment');
            $table->decimal('currency_value', 15, 6)->nullable();
            $table->string('entry_source')->default('lc_payment');
            $table->index(['booking_id', 'entry_source'], 'logistics_lc_payments_booking_source_index');
            $table->index(['company_id', 'payment_type'], 'logistics_lc_payments_company_type_index');
        });

        DB::table('logistics_lc_payments')
            ->where('details', 'like', 'Booking No:%')
            ->where('remarks', 'like', 'Freight Terms:%')
            ->update([
                'payment_type' => 'freight_payment',
                'entry_source' => 'manage_booking',
                'status' => 'pending',
            ]);
    }

    public function down(): void
    {
        Schema::table('logistics_lc_payments', function (Blueprint $table) {
            $table->dropIndex('logistics_lc_payments_booking_source_index');
            $table->dropIndex('logistics_lc_payments_company_type_index');
            $table->dropColumn(['payment_type', 'currency_value', 'entry_source']);
        });
    }
};
