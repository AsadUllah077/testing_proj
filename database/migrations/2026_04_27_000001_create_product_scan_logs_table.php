<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_scan_logs', function (Blueprint $table) {
            $table->id();
            $table->string('barcode');                                  // scanned barcode value
            $table->enum('location', ['A', 'B']);                       // auto-set by scan page
            $table->enum('color', ['Yellow', 'Green']);                 // Yellow=A, Green=B
            $table->timestamp('scanned_at')->useCurrent();
            $table->unsignedBigInteger('product_id')->nullable();       // resolved product if found
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->unsignedBigInteger('scanned_by')->nullable();
            $table->foreign('scanned_by')->references('id')->on('users')->onDelete('set null');
            $table->timestamps();

            // Indexes
            $table->index('barcode');
            $table->index('location');
            $table->index('company_id');
            $table->index('scanned_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_scan_logs');
    }
};
