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
        Schema::create('parts', function (Blueprint $table) {
            $table->id();
            $table->string('part_number')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->string('location');
            $table->string('rack_position')->nullable();
            $table->string('shelf')->nullable();
            $table->integer('quantity')->default(0);
            $table->integer('min_stock_level')->default(5);
            $table->integer('reorder_level')->default(10);
            $table->enum('status', ['IN_STOCK', 'LOW_STOCK', 'OUT_OF_STOCK', 'REQUESTED', 'RESERVED'])->default('IN_STOCK');
            $table->json('specifications')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'location']);
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parts');
    }
};
