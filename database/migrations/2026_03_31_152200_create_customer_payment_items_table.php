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
        Schema::create('customer_payment_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_payment_id');
            $table->unsignedBigInteger('sale_id');
            $table->decimal('amount_to_apply', 18, 2);
            $table->decimal('invoice_total', 18, 2);
            $table->decimal('invoice_remaining', 18, 2);
            $table->timestamps();

            $table->foreign('customer_payment_id', 'cp_items_payment_id_foreign')->references('id')->on('customer_payments')->onDelete('cascade');
            $table->foreign('sale_id')->references('id')->on('sales');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_payment_items');
    }
};
