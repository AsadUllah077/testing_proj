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
        Schema::table('employee_attendances', function (Blueprint $table) {
            // Drop old unique index
            $table->dropUnique(['employee_id', 'attendance_date']);

            // Create new unique index including deleted_at
            $table->unique(['employee_id', 'attendance_date', 'deleted_at'], 'employee_attendance_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_attendances', function (Blueprint $table) {
            // Remove new index
            $table->dropUnique('employee_attendance_unique');

            // Restore old unique index
            $table->unique(['employee_id', 'attendance_date']);
        });
    }
};
