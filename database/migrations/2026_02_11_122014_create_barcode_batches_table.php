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
        Schema::create('barcode_batches', function (Blueprint $table) {
          $table->id();
            $table->string('batch_number')->unique();
            $table->string('container_id');
            $table->foreignId('category_id')->constrained('product_categories')->onDelete('cascade');
            $table->string('start_sequence');
            $table->string('end_sequence');
            $table->integer('quantity');
            $table->json('generated_ids'); // Array of all generated IDs
            $table->foreignId('generated_by')->constrained('users');
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamps();

            // Indexes
            $table->index('batch_number');
            $table->index('container_id');
            $table->index('category_id');
            $table->index('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barcode_batches');
    }
};
