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
        Schema::create('barcodes', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->unique();
            $table->string('container_id');
            $table->foreignId('category_id')->constrained('product_categories')->onDelete('cascade');
            $table->string('sequence_number');
            $table->string('barcode_image_path')->nullable();
            $table->string('barcode_data');
            $table->string('status')->default('active');
            $table->foreignId('generated_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('generated_at')->useCurrent();
            $table->timestamp('used_at')->nullable();
            $table->string('used_for_type')->nullable(); // product, container, etc.
            $table->unsignedBigInteger('used_for_id')->nullable();
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('container_id');
            $table->index('category_id');
            $table->index('unique_id');
            $table->index('status');
            $table->index('company_id');
            $table->index(['used_for_type', 'used_for_id']);
            $table->index(['container_id', 'category_id', 'sequence_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barcodes');
    }
};
