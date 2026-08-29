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
        Schema::create('logistics_trade_documents', function (Blueprint $table) {
            $table->id();
            $table->string('document_type');        // Bill of Lading, Commercial Invoice, etc.
            $table->text('description')->nullable();
            $table->string('required_for')->nullable();
            $table->string('status')->default('Pending'); // Received/Pending/Missing/Active/In Progress/Filed/Not Required
            $table->string('file_path')->nullable();
            $table->string('original_filename')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->foreignId('booking_id')->constrained('logistics_bookings')->cascadeOnDelete();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logistics_trade_documents');
    }
};
