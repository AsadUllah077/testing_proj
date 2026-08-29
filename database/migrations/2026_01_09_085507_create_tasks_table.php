<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_tasks_table.php

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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('deadline');
            $table->foreignId('added_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('company_id')->constrained()->onDelete('cascade');

            // Status options: draft, in_progress, completed, overdue
            $table->string('status')->default('draft');

            // Additional fields from the UI
            $table->string('category')->nullable(); // daily, weekly
            $table->string('priority')->nullable(); // low, medium, high
            $table->text('manager_remarks')->nullable();
            $table->foreignId('department_id')->nullable()->constrained()->onDelete('set null');

            // Timestamps
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('task_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['task_id', 'employee_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_assignments');
        Schema::dropIfExists('tasks');
    }
};
