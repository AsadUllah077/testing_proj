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
        Schema::create('tax_brackets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('fiscal_year', 9); // e.g. 2024-2025
            $table->decimal('min_income', 15, 2)->default(0);
            $table->decimal('max_income', 15, 2)->nullable(); // null = unlimited
            $table->decimal('base_tax', 15, 2)->default(0);
            $table->decimal('rate', 5, 2)->default(0); // percentage over min_income
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['company_id', 'fiscal_year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_brackets');
    }
};
