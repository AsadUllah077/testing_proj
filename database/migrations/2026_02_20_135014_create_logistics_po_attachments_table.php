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
        Schema::create('logistics_po_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('logistics_purchase_order_id')->constrained('logistics_purchase_orders')->onDelete('cascade');
            $table->string('file_name');
            $table->string('file_path');
            $table->unsignedBigInteger('file_size'); // in bytes
            $table->string('file_type'); // e.g., 'image/jpeg', 'application/pdf'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logistics_po_attachments');
    }
};
