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
        if (! Schema::hasTable('logistics_bookings')) {
            Schema::create('logistics_bookings', function (Blueprint $table) {
                $table->id();
                $table->string('booking_number')->nullable();
                $table->string('booking_type')->nullable();
                $table->string('freight_mode')->nullable();
                $table->string('freight_sub_mode')->nullable();
                $table->string('origin_port')->nullable();
                $table->string('destination_port')->nullable();
                $table->string('place_of_receipt')->nullable();
                $table->string('final_delivery_place')->nullable();
                $table->string('exporter')->nullable();
                $table->string('importer')->nullable();
                $table->string('notify_party')->nullable();
                $table->string('agent')->nullable();
                $table->string('estimated_time_departure')->nullable();
                $table->string('estimated_time_delivery')->nullable();
                $table->string('service_level')->nullable();
                $table->foreignId('hs_code_id')->constrained('hs_codes')->cascadeOnDelete();
                $table->foreignId('carrier_id')->constrained('logistics_v2_carriers')->cascadeOnDelete();
                $table->string('gross_weight')->nullable();
                $table->string('volume_cbm')->nullable();
                $table->string('declared_cargo_value')->nullable();
                $table->string('dangerous_good')->nullable();
                $table->string('remarks')->nullable();
                $table->string('incoterms')->nullable();
                $table->string('freight_terms')->nullable();
                $table->string('quoted_freight_rate')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logistics_booking');
    }
};
