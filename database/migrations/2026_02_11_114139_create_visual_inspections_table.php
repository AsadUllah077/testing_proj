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
        Schema::create('visual_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->json('checklist_items');
            $table->text('inspection_notes')->nullable();
            $table->string('grade')->nullable();
            $table->json('custom_checklist')->nullable();
            $table->foreignId('inspected_by')->constrained('users');
            $table->timestamp('inspected_at')->nullable();
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamps();

            // Indexes
            $table->index('product_id');
            $table->index('company_id');
            $table->index('inspected_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visual_inspections');
    }
};
