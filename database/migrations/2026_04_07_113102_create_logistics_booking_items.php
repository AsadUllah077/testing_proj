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
         if (!Schema::hasTable('logistics_booking_items')) {
        Schema::create('logistics_booking_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('logistics_bookings')->cascadeOnDelete();
            $table->foreignId('carrier_id')->constrained('logistics_v2_carriers')->cascadeOnDelete();
            $table->string('freight_mode')->nullable();
            $table->string('freight_sub_mode')->nullable();

            // container FCL Sea
            $table->string('container_type')->nullable();
            $table->string('number_of_pallets')->nullable();
            $table->text('details_of_commodities')->nullable();

            // LCL Sea
            $table->text('item_description')->nullable();
            $table->string('package_type')->nullable();
            $table->string('package')->nullable();
            $table->integer('quantity')->nullable();
            $table->decimal('length', 10, 2)->nullable();
            $table->decimal('width', 10, 2)->nullable();
            $table->decimal('height', 10, 2)->nullable();
            $table->string('dimension_unit')->nullable();
            $table->decimal('weight', 10, 2)->nullable();
            $table->string('image_path')->nullable();
            $table->timestamps();
        });
         }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logistics_booking_items');
    }
};
