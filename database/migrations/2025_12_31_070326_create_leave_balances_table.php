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
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('leave_type_id')->constrained('leave_types')->onDelete('cascade');

            $table->integer('total_leaves');        // e.g. 20
            $table->decimal('used_leaves', 5, 2)->default(0); // supports half-day (0.5)
            $table->decimal('remaining_leaves', 5, 2);

            $table->year('year'); // important (2025, 2026, etc.)

            $table->timestamps();
            $table->softDeletes();

            // Prevent duplicate rows
            $table->unique(['employee_id', 'leave_type_id', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_balances');
    }
};
