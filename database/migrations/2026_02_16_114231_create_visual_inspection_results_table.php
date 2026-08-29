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
        Schema::create('visual_inspection_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('product_assignments')->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('technician_id')->constrained('users')->onDelete('cascade');

            // Inspection results
            $table->json('checklist_items')->nullable(); // Store checklist items with status
            $table->text('inspection_notes')->nullable();
            $table->string('grade')->nullable(); // A, B, C, F
            $table->json('photos')->nullable(); // Store photo paths if any

            // Status
            $table->enum('result', ['pass', 'fail'])->default('pass');
            $table->text('remarks')->nullable();

            $table->foreignId('submitted_by')->constrained('users');
            $table->timestamp('submitted_at');
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->index(['assignment_id', 'product_id']);
            $table->index('technician_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visual_inspection_results');
    }
};
