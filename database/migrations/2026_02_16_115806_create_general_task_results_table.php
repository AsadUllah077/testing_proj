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
        Schema::create('general_task_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('product_assignments')->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('technician_id')->constrained('users')->onDelete('cascade');

            // Task results
            $table->enum('status', ['pending', 'in_progress', 'completed', 'failed'])->default('pending');
            $table->enum('result', ['pass', 'fail'])->nullable();
            $table->text('remarks')->nullable();
            $table->text('technician_notes')->nullable();
            $table->json('additional_data')->nullable(); // For any extra data

            // Time tracking
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

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
        Schema::dropIfExists('general_task_results');
    }
};
