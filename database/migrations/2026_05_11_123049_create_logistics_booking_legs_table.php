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
        Schema::create('logistics_booking_legs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_v2_id')->constrained('logistics_bookings')->onDelete('cascade');
            $table->string('origin');
            $table->string('destination');
            $table->date('etd')->nullable();
            $table->date('eta')->nullable();
            $table->date('estimated_delivery_date')->nullable();
            $table->date('original_etd')->nullable();
            $table->date('original_eta')->nullable();
            $table->string('timing_status')->default('pending'); // pending, approved, rescheduled
            $table->text('timing_reason')->nullable();
            $table->string('mode')->nullable(); // sea, air, road, rail
            $table->string('carrier')->nullable();
            $table->string('status')->default('Pending'); // Pending, In-Transit, Arrived, Delivered
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logistics_booking_legs');
    }
};
