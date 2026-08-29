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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            // Personal Information
            $table->string('full_name');
            $table->string('gender')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('national_id')->nullable();

            // Contact Information
            $table->string('phone_number')->nullable();
            $table->string('email')->nullable();
            $table->text('residential_address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('emergency_contact_relationship')->nullable();

            // Job Information
            $table->foreignId('employment_type_id')->nullable()->constrained('employee_types')->onDelete('set null');
            $table->string('reporting_to')->nullable();
            $table->foreignId('department_id')->nullable()->constrained()->onDelete('set null');
            // $table->foreignId('company_id')->nullable()->constrained()->onDelete('set null');
            $table->string('designation')->nullable();
            $table->date('date_of_joining')->nullable();
            $table->date('date_of_confirmation')->nullable();
            $table->string('work_location')->nullable();
            $table->text('job_description')->nullable();
            $table->foreignId('linked_erp_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');

            // Status & System
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('status')->default('active');
            $table->string('profile_picture')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Indexes
            $table->index(['full_name', 'email', 'phone_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
