<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('hrm_candidates', function (Blueprint $table) {
            $table->id();

            // Personal Information
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            // Application Details
            $table->foreignId('hrm_job_id')->nullable()->constrained('hrm_jobs')->onDelete('set null');
            $table->string('current_role')->nullable();
            $table->enum('source', ['linkedin', 'website', 'referral', 'direct', 'other'])->nullable();
            $table->enum('stage', ['new', 'screening', 'interviewing', 'offer', 'rejected', 'hired'])->default('new');
            $table->date('applied_date')->nullable();

            // Resume/CV
            $table->string('resume_path')->nullable();
            $table->string('resume_original_name')->nullable();

            // Rating & Notes
            $table->decimal('rating', 2, 1)->nullable()->default(0.0);
            $table->text('notes')->nullable();

            // Job Application Fields
            $table->text('cover_letter')->nullable();
            $table->json('answers')->nullable(); // For job-specific questions

            // System Fields
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('added_by')->constrained('users');
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['email', 'company_id']);
            $table->index(['stage', 'company_id']);
            $table->index(['hrm_job_id', 'stage']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('hrm_candidates');
    }
};
