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
        Schema::create('logistics_lc_payments', function (Blueprint $table) {
            $table->id();
            $table->date('date')->nullable();
            $table->text('details')->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->string('status')->nullable();
            $table->string('receipt_no')->nullable();
            $table->string('upload_doc')->nullable();
            $table->string('original_filename')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->foreignId('booking_id')->nullable()->constrained('logistics_bookings')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logistics_lc_payments');
    }
};
