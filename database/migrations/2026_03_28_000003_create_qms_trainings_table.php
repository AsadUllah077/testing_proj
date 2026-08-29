<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qms_trainings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('linked_sop_id')->nullable()->constrained('qms_sops')->onDelete('set null');
            $table->foreignId('linked_policy_id')->nullable()->constrained('qms_policies')->onDelete('set null');
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->boolean('certification_enabled')->default(false);
            $table->text('certification_criteria')->nullable();
            $table->boolean('has_assessment')->default(false);
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('qms_training_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_id')->constrained('qms_trainings')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->enum('completion_status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->boolean('certified')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qms_training_employees');
        Schema::dropIfExists('qms_trainings');
    }
};
