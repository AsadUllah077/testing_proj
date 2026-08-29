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
        Schema::create('employee_attendances', function (Blueprint $table) {
            $table->id();
            // Foreign key
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();

            // Attendance
            $table->date('attendance_date');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();

            // Total work hours (in decimal, e.g. 8.50)
            $table->decimal('total_work_hours', 5, 2)->nullable();
            // Prevent duplicate attendance for same day
            $table->unique(['employee_id', 'attendance_date']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_attendances');
    }
};
