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
        Schema::create('product_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('technician_id')->constrained('users');
            $table->string('task_type')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('workflow_stage_id')->nullable()->constrained('workflow_stages');
            $table->foreignId('disposition_id')->nullable()->constrained('dispositions');
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('assigned_by')->constrained('users');
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamps();

            // Indexes
            $table->index('product_id');
            $table->index('technician_id');
            $table->index('company_id');
            $table->index(['assigned_at', 'completed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_assignments');
    }
};
