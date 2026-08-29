<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('logistics_purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('logistics_purchase_order_id')->constrained('logistics_purchase_orders')->onDelete('cascade');
            $table->string('sku');
            $table->text('description')->nullable();
            $table->string('hs_code')->nullable();
            $table->integer('quantity');
            $table->decimal('weight_kg', 12, 4)->default(0);
            $table->decimal('price', 18, 4);
            $table->decimal('tax_percentage', 5, 2)->default(0);
            $table->decimal('total', 18, 4);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logistics_purchase_order_items');
    }
};
