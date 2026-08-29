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
        Schema::create('functional_testing_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('product_assignments')->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('technician_id')->constrained('users')->onDelete('cascade');

            // Test results
            $table->json('test_results')->nullable(); // Store all test results
            $table->text('overall_summary')->nullable();
            $table->string('grade')->nullable(); // A, B, C, F
            $table->json('test_data')->nullable(); // Additional test data

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
        Schema::dropIfExists('functional_testing_results');
    }
};
