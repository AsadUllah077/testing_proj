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
        Schema::create('logistics_booking_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('logistics_booking_id')->constrained('logistics_bookings')->cascadeOnDelete();
            $table->string('event_type'); // pickup_approved, pickup_rejected, delivery_approved, delivery_rejected
            $table->date('scheduled_date')->nullable();
            $table->date('actual_date')->nullable();
            $table->text('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logistics_booking_events');
    }
};
