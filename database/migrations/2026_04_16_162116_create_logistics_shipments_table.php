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
        Schema::dropIfExists('logistics_shipments');
        Schema::create('logistics_shipments', function (Blueprint $table) {
            $table->id();
            $table->string('shipment_number')->unique();
            $table->unsignedBigInteger('booking_v2_id')->nullable();
            $table->string('shipment_type')->nullable();      // import / export
            $table->string('freight_mode')->nullable();       // fcl_sea / lcl_sea / air / road_truck
            $table->string('carrier')->nullable();
            $table->string('vessel_flight')->nullable();
            $table->string('voyage_number')->nullable();
            $table->string('origin_port')->nullable();
            $table->string('dest_port')->nullable();
            $table->date('etd')->nullable();
            $table->date('eta')->nullable();
            $table->date('atd')->nullable();                  // Actual departure
            $table->date('ata')->nullable();                  // Actual arrival
            $table->string('container_number')->nullable();
            $table->string('seal_number')->nullable();
            $table->string('hs_code')->nullable();
            $table->string('cargo_description')->nullable();
            $table->string('shipper')->nullable();
            $table->string('consignee')->nullable();
            $table->decimal('cargo_value', 15, 2)->nullable();
            $table->string('currency')->default('USD');
            $table->integer('delay_days')->nullable();
            $table->text('delay_reason')->nullable();
            $table->string('status')->default('Booked');     // Booked/In-Transit/Arrived/Cleared/Delivered/Delayed
            $table->unsignedBigInteger('company_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('booking_v2_id')
                  ->references('id')->on('logistics_bookings')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logistics_shipments');
    }
};
