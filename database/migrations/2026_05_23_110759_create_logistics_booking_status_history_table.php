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
        Schema::create('logistics_booking_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('logistics_bookings')->cascadeOnDelete();
            $table->string('event_type')->nullable();
            $table->string('original_date')->nullable();
            $table->string('new_date')->nullable();
            $table->string('status_changed_to')->nullable();
            $table->string('reason')->nullable();
            $table->foreignId('action_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logistics_booking_status_history');
    }
};
