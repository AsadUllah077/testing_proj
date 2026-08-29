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
        Schema::create('logistics_booking_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_v2_id')->constrained('logistics_bookings')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('event_type'); // pickup_approved, pickup_rejected, delivery_approved, delivery_rejected
            $table->date('original_date')->nullable();
            $table->date('new_date')->nullable();
            $table->string('status_changed_to')->nullable();
            $table->text('reason')->nullable();
            $table->foreignId('actioned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logistics_booking_status_histories');
    }
};
