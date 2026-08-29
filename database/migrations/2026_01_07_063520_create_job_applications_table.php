<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hrm_job_id')->constrained('hrm_jobs')->onDelete('cascade');
            $table->foreignId('candidate_id')->nullable()->constrained('users');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('resume_path')->nullable();
            $table->text('cover_letter')->nullable();
            $table->json('answers')->nullable(); // For custom application questions
            $table->enum('status', ['applied', 'reviewed', 'shortlisted', 'interviewed', 'rejected', 'hired'])->default('applied');
            $table->text('notes')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->timestamp('applied_at')->useCurrent();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('job_applications');
    }
};
