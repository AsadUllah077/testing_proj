<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('salary_benchmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('designation');
            $table->string('experience_level')->nullable(); // entry, mid, senior, etc.
            $table->decimal('min_salary', 15, 2)->default(0);
            $table->decimal('median_salary', 15, 2)->default(0);
            $table->decimal('max_salary', 15, 2)->default(0);
            $table->string('currency')->default('PKR');
            $table->string('source')->nullable(); // e.g., "Payscale", "Internal Survey"
            $table->timestamps();
            $table->softDeletes();

            $table->index(['designation', 'experience_level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_benchmarks');
    }
};
